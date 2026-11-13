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

        if (!in_array($role, ['Cliente', 'Trabajador'], true)) {
            flash('error', 'El rol seleccionado no está permitido para el registro público.');
            redirect('register');
        }

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

        if ($documento !== '') {
            $documentQuery = mysqli_prepare($db, 'SELECT id FROM usuario WHERE documento_identidad = ? LIMIT 1');
            mysqli_stmt_bind_param($documentQuery, 's', $documento);
            mysqli_stmt_execute($documentQuery);
            $documentResult = mysqli_stmt_get_result($documentQuery);
            $documentExists = mysqli_fetch_assoc($documentResult);
            mysqli_stmt_close($documentQuery);

            if ($documentExists) {
                flash('error', 'El documento de identidad ya se encuentra registrado.');
                redirect('register');
            }
        }

        $roleMap = ['Trabajador' => 2, 'Cliente' => 3];
        $roleId = $roleMap[$role];
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($db, 'INSERT INTO usuario (rol_id, nombre, apellido, email, telefono, documento_identidad, direccion, password_hash, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "activo")');
        mysqli_stmt_bind_param($stmt, 'isssssss', $roleId, $nombre, $apellido, $email, $telefono, $documento, $direccion, $passwordHash);
        if (!mysqli_stmt_execute($stmt)) {
            flash('error', 'No se pudo registrar el usuario.');
            redirect('register');
        }
        $userId = mysqli_insert_id($db);
        mysqli_stmt_close($stmt);

        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $userId, 'name' => $nombre, 'role' => $role, 'email' => $email];
        flash('success', 'Registro exitoso. Bienvenido a OficioClick.');

        if ($role === 'Trabajador') {
            redirect('worker-dashboard');
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
        if (!$user || $user['estado'] !== 'activo' || !password_verify($password, $user['password_hash'])) {
            flash('error', 'Credenciales incorrectas.');
            redirect('login');
        }

        session_regenerate_id(true);
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

        if ($titulo === '' || $descripcion === '' || $precio <= 0) {
            flash('error', 'Completa los datos del servicio y asigna un precio válido.');
            redirect('worker-dashboard');
        }

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

        $scheduledAt = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time);
        $dateErrors = DateTimeImmutable::getLastErrors();
        $hasDateErrors = $dateErrors !== false
            && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0);

        if (
            !$scheduledAt
            || $hasDateErrors
            || $scheduledAt->format('Y-m-d H:i') !== $date . ' ' . $time
            || $scheduledAt <= new DateTimeImmutable()
        ) {
            flash('error', 'Selecciona una fecha y hora futura válida.');
            redirect('catalog');
        }

        $service = fetchServiceById($serviceId);
        if (
            !$service
            || ($service['estado'] ?? '') !== 'activo'
            || (int) ($service['disponible'] ?? 0) !== 1
        ) {
            flash('error', 'El servicio ya no está disponible.');
            redirect('catalog');
        }

        $slotStart = $scheduledAt->format('Y-m-d H:i:s');
        $availability = mysqli_prepare(
            $db,
            'SELECT r.id
             FROM reserva r
             INNER JOIN servicio s ON s.id = r.servicio_id
               WHERE TIMESTAMP(r.fecha, r.hora) < DATE_ADD(?, INTERVAL 1 HOUR)
                 AND DATE_ADD(TIMESTAMP(r.fecha, r.hora), INTERVAL 1 HOUR) > ?
               AND r.estado IN ("pendiente", "confirmada")
               AND (r.cliente_id = ? OR s.trabajador_id = ?)
             LIMIT 1'
        );
        mysqli_stmt_bind_param(
            $availability,
            'ssii',
            $slotStart,
            $slotStart,
            $_SESSION['user']['id'],
            $service['trabajador_id']
        );
        mysqli_stmt_execute($availability);
        $availabilityResult = mysqli_stmt_get_result($availability);
        $conflictingReservation = mysqli_fetch_assoc($availabilityResult);
        mysqli_stmt_close($availability);

        if ($conflictingReservation) {
            flash('error', 'Ese horario ya no está disponible para el cliente o el trabajador.');
            redirect('catalog');
        }

        $stmt = mysqli_prepare($db, 'INSERT INTO reserva (cliente_id, servicio_id, fecha, hora, estado) VALUES (?, ?, ?, ?, "pendiente")');
        mysqli_stmt_bind_param($stmt, 'iiss', $_SESSION['user']['id'], $serviceId, $date, $time);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            flash('error', 'No se pudo registrar la reserva. Inténtalo nuevamente.');
            redirect('catalog');
        }
        mysqli_stmt_close($stmt);

        flash('success', 'La reserva se registró correctamente.');
        redirect('client-dashboard');
    }

    if ($action === 'update-reservation-status') {
        requireRole(['Cliente', 'Trabajador', 'Administrador']);
        $reservationId = (int) ($_POST['reservation_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');

        if ($reservationId <= 0 || !in_array($status, ['confirmada', 'finalizada', 'cancelada'], true)) {
            flash('error', 'La solicitud de cambio de estado no es válida.');
            redirect('home');
        }

        $reservationQuery = mysqli_prepare(
            $db,
            'SELECT r.cliente_id, r.estado, s.trabajador_id
             FROM reserva r
             INNER JOIN servicio s ON s.id = r.servicio_id
             WHERE r.id = ?
             LIMIT 1'
        );
        mysqli_stmt_bind_param($reservationQuery, 'i', $reservationId);
        mysqli_stmt_execute($reservationQuery);
        $reservationResult = mysqli_stmt_get_result($reservationQuery);
        $reservation = mysqli_fetch_assoc($reservationResult);
        mysqli_stmt_close($reservationQuery);

        $role = $_SESSION['user']['role'];
        $userId = (int) $_SESSION['user']['id'];
        $currentStatus = $reservation['estado'] ?? '';
        $allowedTransition = false;
        $stmt = null;

        if ($reservation && !in_array($currentStatus, ['finalizada', 'cancelada'], true)) {
            if (
                $role === 'Trabajador'
                && (int) $reservation['trabajador_id'] === $userId
                && (
                    ($currentStatus === 'pendiente' && $status === 'confirmada')
                    || ($currentStatus === 'confirmada' && $status === 'finalizada')
                )
            ) {
                $allowedTransition = true;
                $stmt = mysqli_prepare(
                    $db,
                    'UPDATE reserva r
                     INNER JOIN servicio s ON s.id = r.servicio_id
                     SET r.estado = ?
                     WHERE r.id = ? AND r.estado = ? AND s.trabajador_id = ?'
                );
                mysqli_stmt_bind_param($stmt, 'sisi', $status, $reservationId, $currentStatus, $userId);
            } elseif (
                $role === 'Cliente'
                && (int) $reservation['cliente_id'] === $userId
                && in_array($currentStatus, ['pendiente', 'confirmada'], true)
                && $status === 'cancelada'
            ) {
                $allowedTransition = true;
                $stmt = mysqli_prepare(
                    $db,
                    'UPDATE reserva SET estado = ? WHERE id = ? AND cliente_id = ? AND estado = ?'
                );
                mysqli_stmt_bind_param($stmt, 'siis', $status, $reservationId, $userId, $currentStatus);
            } elseif (
                $role === 'Administrador'
                && in_array($currentStatus, ['pendiente', 'confirmada'], true)
                && $status === 'cancelada'
            ) {
                $allowedTransition = true;
                $stmt = mysqli_prepare($db, 'UPDATE reserva SET estado = ? WHERE id = ? AND estado = ?');
                mysqli_stmt_bind_param($stmt, 'sis', $status, $reservationId, $currentStatus);
            }
        }

        if (!$allowedTransition || $stmt === null) {
            flash('error', 'No tienes permiso para realizar ese cambio o la reserva ya está cerrada.');
            if ($role === 'Cliente') {
                redirect('client-dashboard');
            }
            redirect($role === 'Administrador' ? 'admin-dashboard' : 'worker-dashboard');
        }

        $updated = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) === 1;
        mysqli_stmt_close($stmt);

        if (!$updated) {
            flash('error', 'No se pudo actualizar la reserva. Puede que haya cambiado en otra sesión.');
        } else {
            flash('success', 'Estado de la reserva actualizado.');
        }

        if ($role === 'Cliente') {
            redirect('client-dashboard');
        }
        redirect($role === 'Administrador' ? 'admin-dashboard' : 'worker-dashboard');
    }

    if ($action === 'update-user') {
        requireRole(['Administrador']);
        $userId = (int) ($_POST['user_id'] ?? 0);
        $role = (string) ($_POST['role'] ?? '');
        $status = (string) ($_POST['status'] ?? '');
        $allowedRoles = ['Cliente', 'Trabajador', 'Administrador'];
        $allowedStatuses = ['activo', 'inactivo'];

        if (
            $userId <= 0
            || !in_array($role, $allowedRoles, true)
            || !in_array($status, $allowedStatuses, true)
        ) {
            flash('error', 'Los datos de actualización de usuario no son válidos.');
            redirect('admin-dashboard');
        }

        if (
            $userId === (int) $_SESSION['user']['id']
            && ($role !== 'Administrador' || $status !== 'activo')
        ) {
            flash('error', 'No puedes cambiar tu propio rol ni desactivar tu cuenta de administrador.');
            redirect('admin-dashboard');
        }

        $targetQuery = mysqli_prepare($db, 'SELECT id FROM usuario WHERE id = ? LIMIT 1');
        mysqli_stmt_bind_param($targetQuery, 'i', $userId);
        mysqli_stmt_execute($targetQuery);
        $targetResult = mysqli_stmt_get_result($targetQuery);
        $targetUser = mysqli_fetch_assoc($targetResult);
        mysqli_stmt_close($targetQuery);

        if (!$targetUser) {
            flash('error', 'El usuario seleccionado no existe.');
            redirect('admin-dashboard');
        }

        $stmt = mysqli_prepare(
            $db,
            'UPDATE usuario u
             INNER JOIN rol r ON r.nombre = ?
             SET u.rol_id = r.id, u.estado = ?
             WHERE u.id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'ssi', $role, $status, $userId);
        $updated = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if (!$updated) {
            flash('error', 'No se pudo actualizar el usuario.');
            redirect('admin-dashboard');
        }

        flash('success', 'Usuario actualizado correctamente.');
        redirect('admin-dashboard');
    }

    if ($action === 'submit-review') {
        requireRole(['Cliente']);
        $reservationId = (int) ($_POST['reservation_id'] ?? 0);
        $stars = (int) ($_POST['stars'] ?? 0);
        $comment = trim((string) ($_POST['comment'] ?? ''));

        if ($stars < 1 || $stars > 5) {
            flash('error', 'La calificación debe estar entre 1 y 5 estrellas.');
            redirect('client-dashboard');
        }

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
        if (!$service) {
            flash('error', 'No se encontró el servicio asociado a la reserva.');
            redirect('client-dashboard');
        }

        $reviewInsert = mysqli_prepare($db, 'INSERT INTO calificacion (reserva_id, cliente_id, trabajador_id, estrellas, comentario) VALUES (?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($reviewInsert, 'iiiis', $reservationId, $_SESSION['user']['id'], $service['trabajador_id'], $stars, $comment);
        if (!mysqli_stmt_execute($reviewInsert)) {
            mysqli_stmt_close($reviewInsert);
            flash('error', 'No se pudo guardar la calificación. Inténtalo nuevamente.');
            redirect('client-dashboard');
        }
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
