<?php
require_once __DIR__ . '/../app/bootstrap.php';

$db = dbConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'register') {
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $apellido = trim((string) ($_POST['apellido'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $telefono = trim((string) ($_POST['telefono'] ?? ''));
        $documento = trim((string) ($_POST['documento_identidad'] ?? ''));
        $direccion = trim((string) ($_POST['direccion'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['rol'] ?? 'Cliente');

        if ($nombre === '' || $apellido === '' || $email === '' || $password === '') {
            flash('error', 'Completa los campos obligatorios para continuar.');
            redirect('register');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'El correo electrónico no es válido.');
            redirect('register');
        }

        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            flash('error', 'La contraseña debe tener al menos 8 caracteres, una letra y un número.');
            redirect('register');
        }

        if ($db === null) {
            flash('error', 'La base de datos no está disponible. Revisa la configuración de MySQL.');
            redirect('register');
        }

        $existing = fetchUserByEmail($email);
        if ($existing) {
            flash('error', 'El correo ya se encuentra registrado.');
            redirect('register');
        }

        $roleMap = ['Administrador' => 1, 'Trabajador' => 2, 'Cliente' => 3];
        $roleId = $roleMap[$role] ?? 3;
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($db, 'INSERT INTO usuario (rol_id, nombre, apellido, email, telefono, documento_identidad, direccion, password_hash, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "activo")');
        mysqli_stmt_bind_param($stmt, 'isssssss', $roleId, $nombre, $apellido, $email, $telefono, $documento, $direccion, $passwordHash);
        if (!mysqli_stmt_execute($stmt)) {
            flash('error', 'No se pudo registrar el usuario.');
            redirect('register');
        }
        $userId = mysqli_insert_id($db);
        mysqli_stmt_close($stmt);

        $_SESSION['user'] = ['id' => $userId, 'name' => $nombre, 'role' => $role, 'email' => $email];
        flash('success', 'Registro exitoso. Bienvenido a OficioClick.');

        if ($role === 'Trabajador') {
            redirect('worker-dashboard');
        }
        if ($role === 'Administrador') {
            redirect('admin-dashboard');
        }
        redirect('client-dashboard');
    }

    if ($action === 'login') {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            flash('error', 'Ingresa tu correo y contraseña.');
            redirect('login');
        }

        $user = fetchUserByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            flash('error', 'Credenciales incorrectas.');
            redirect('login');
        }

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['nombre'],
            'role' => $user['role_name'],
            'email' => $user['email'],
        ];

        flash('success', 'Has iniciado sesión correctamente.');
        if ($user['role_name'] === 'Trabajador') {
            redirect('worker-dashboard');
        }
        if ($user['role_name'] === 'Administrador') {
            redirect('admin-dashboard');
        }
        redirect('client-dashboard');
    }

    if ($action === 'logout') {
        session_destroy();
        redirect('home');
    }

    if ($action === 'create-service') {
        requireRole(['Trabajador']);
        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
        $categoria = trim((string) ($_POST['categoria'] ?? ''));
        $precio = (float) ($_POST['precio'] ?? 0);

        if ($titulo === '' || $descripcion === '' || $categoria === '' || $precio <= 0) {
            flash('error', 'Completa los datos del servicio y asigna un precio válido.');
            redirect('worker-dashboard');
        }

        $db = dbConnection();
        $stmt = mysqli_prepare($db, 'SELECT id FROM categoria WHERE nombre = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $categoria);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$row) {
            flash('error', 'La categoría seleccionada no existe.');
            redirect('worker-dashboard');
        }

        $insert = mysqli_prepare($db, 'INSERT INTO servicio (trabajador_id, categoria_id, titulo, descripcion, precio, estado, disponible) VALUES (?, ?, ?, ?, ?, "activo", 1)');
        mysqli_stmt_bind_param($insert, 'iisd', $_SESSION['user']['id'], $row['id'], $titulo, $descripcion, $precio);
        if (!mysqli_stmt_execute($insert)) {
            flash('error', 'No se pudo guardar el servicio.');
            redirect('worker-dashboard');
        }
        mysqli_stmt_close($insert);

        flash('success', 'Servicio publicado correctamente.');
        redirect('worker-dashboard');
    }

    if ($action === 'update-service') {
        requireRole(['Trabajador']);
        $id = (int) ($_POST['service_id'] ?? 0);
        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
        $precio = (float) ($_POST['precio'] ?? 0);

        $service = fetchServiceById($id);
        if (!$service || (int) $service['trabajador_id'] !== (int) $_SESSION['user']['id']) {
            flash('error', 'No puedes editar este servicio.');
            redirect('worker-dashboard');
        }

        $stmt = mysqli_prepare($db, 'UPDATE servicio SET titulo = ?, descripcion = ?, precio = ? WHERE id = ? AND trabajador_id = ?');
        mysqli_stmt_bind_param($stmt, 'ssdi', $titulo, $descripcion, $precio, $id, $_SESSION['user']['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        flash('success', 'Servicio actualizado.');
        redirect('worker-dashboard');
    }

    if ($action === 'delete-service') {
        requireRole(['Trabajador']);
        $id = (int) ($_POST['service_id'] ?? 0);
        $stmt = mysqli_prepare($db, 'DELETE FROM servicio WHERE id = ? AND trabajador_id = ?');
        mysqli_stmt_bind_param($stmt, 'ii', $id, $_SESSION['user']['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        flash('success', 'Servicio eliminado.');
        redirect('worker-dashboard');
    }

    if ($action === 'reserve-service') {
        requireRole(['Cliente']);
        $serviceId = (int) ($_POST['service_id'] ?? 0);
        $date = trim((string) ($_POST['reservation_date'] ?? ''));
        $time = trim((string) ($_POST['reservation_time'] ?? ''));

        if ($serviceId <= 0 || $date === '' || $time === '') {
            flash('error', 'Selecciona fecha y hora para reservar el servicio.');
            redirect('catalog');
        }

        $service = fetchServiceById($serviceId);
        if (!$service || ($service['estado'] ?? '') !== 'activo') {
            flash('error', 'El servicio ya no está disponible.');
            redirect('catalog');
        }

        $stmt = mysqli_prepare($db, 'INSERT INTO reserva (cliente_id, servicio_id, fecha, hora, estado) VALUES (?, ?, ?, ?, "pendiente")');
        mysqli_stmt_bind_param($stmt, 'iiss', $_SESSION['user']['id'], $serviceId, $date, $time);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        flash('success', 'La reserva se registró correctamente.');
        redirect('client-dashboard');
    }

    if ($action === 'update-reservation-status') {
        requireRole(['Trabajador', 'Administrador']);
        $reservationId = (int) ($_POST['reservation_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? 'pendiente');

        if ($status === 'finalizada') {
            $stmt = mysqli_prepare($db, 'UPDATE reserva SET estado = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'si', $status, $reservationId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            flash('success', 'Estado actualizado.');
            redirect('worker-dashboard');
        }

        $stmt = mysqli_prepare($db, 'UPDATE reserva SET estado = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'si', $status, $reservationId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        flash('success', 'Estado de la reserva actualizado.');

        if ($_SESSION['user']['role'] === 'Administrador') {
            redirect('admin-dashboard');
        }
        redirect('worker-dashboard');
    }

    if ($action === 'submit-review') {
        requireRole(['Cliente']);
        $reservationId = (int) ($_POST['reservation_id'] ?? 0);
        $stars = (int) ($_POST['stars'] ?? 0);
        $comment = trim((string) ($_POST['comment'] ?? ''));

        $stmt = mysqli_prepare($db, 'SELECT * FROM reserva WHERE id = ? AND cliente_id = ? AND estado = "finalizada" LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'ii', $reservationId, $_SESSION['user']['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $reservation = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$reservation) {
            flash('error', 'Solo puedes calificar reservas finalizadas.');
            redirect('client-dashboard');
        }

        $existing = mysqli_prepare($db, 'SELECT id FROM calificacion WHERE reserva_id = ?');
        mysqli_stmt_bind_param($existing, 'i', $reservationId);
        mysqli_stmt_execute($existing);
        $reviewResult = mysqli_stmt_get_result($existing);
        if (mysqli_num_rows($reviewResult) > 0) {
            flash('error', 'Esta reserva ya fue calificada.');
            redirect('client-dashboard');
        }
        mysqli_stmt_close($existing);

        $service = fetchServiceById((int) $reservation['servicio_id']);
        $reviewInsert = mysqli_prepare($db, 'INSERT INTO calificacion (reserva_id, cliente_id, trabajador_id, estrellas, comentario) VALUES (?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($reviewInsert, 'iiiss', $reservationId, $_SESSION['user']['id'], $service['trabajador_id'], $stars, $comment);
        mysqli_stmt_execute($reviewInsert);
        mysqli_stmt_close($reviewInsert);

        flash('success', 'Gracias por calificar el servicio.');
        redirect('client-dashboard');
    }
}

$page = $_GET['page'] ?? 'home';
$view = 'home';
$viewData = [];

switch ($page) {
    case 'home':
        $view = 'home';
        $viewData['services'] = fetchServices(['available_only' => true]);
        break;
    case 'register':
        $view = 'register';
        break;
    case 'login':
        $view = 'login';
        break;
    case 'catalog':
        $view = 'catalog';
        $filters = [
            'category' => (string) ($_GET['category'] ?? ''),
            'search' => (string) ($_GET['search'] ?? ''),
            'available_only' => ($_GET['available_only'] ?? '1') === '1',
        ];
        $viewData['services'] = fetchServices($filters);
        $viewData['categories'] = categoryOptions();
        $viewData['filters'] = $filters;
        break;
    case 'service':
        $serviceId = (int) ($_GET['id'] ?? 0);
        $service = fetchServiceById($serviceId);
        if (!$service) {
            flash('error', 'El servicio solicitado no existe.');
            redirect('catalog');
        }
        $view = 'service-detail';
        $viewData['service'] = $service;
        $viewData['reviews'] = fetchReviewsForService($serviceId);
        $viewData['rating'] = getAverageRating($serviceId);
        break;
    case 'client-dashboard':
        requireRole(['Cliente']);
        $view = 'client-dashboard';
        $viewData['reservations'] = fetchReservationsForUser((int) $_SESSION['user']['id'], 'Cliente');
        break;
    case 'worker-dashboard':
        requireRole(['Trabajador']);
        $view = 'worker-dashboard';
        $viewData['services'] = fetchUserServices((int) $_SESSION['user']['id']);
        $viewData['reservations'] = fetchReservationsForUser((int) $_SESSION['user']['id'], 'Trabajador');
        $viewData['categories'] = categoryOptions();
        break;
    case 'admin-dashboard':
        requireRole(['Administrador']);
        $view = 'admin-dashboard';
        $viewData['users'] = fetchUsersTable();
        $viewData['reservations'] = fetchReservationsForUser((int) $_SESSION['user']['id'], 'Administrador');
        break;
    case 'logout':
        session_destroy();
        redirect('home');
        break;
    default:
        $view = 'home';
        $viewData['services'] = fetchServices(['available_only' => true]);
        break;
}

$viewFile = __DIR__ . '/../views/' . $view . '.php';
if (!is_file($viewFile)) {
    $viewFile = __DIR__ . '/../views/home.php';
}

include __DIR__ . '/../views/layouts/base.php';
