<?php
require_once __DIR__ . '/../app/bootstrap.php';

if (
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
    && !verifyCsrfToken($_POST['csrf_token'] ?? null)
) {
    http_response_code(403);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Solicitud no válida | OficioClick</title></head><body><main><h1>Solicitud no válida</h1><p role="alert">No pudimos verificar esta solicitud. Recarga la página e inténtalo nuevamente.</p><a href="index.php?page=home">Volver al inicio</a></main></body></html>';
    exit;
}

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
        $cityId = (int) ($_POST['ciudad_id'] ?? 0);
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['rol'] ?? 'Cliente');

        if (!in_array($role, ['Cliente', 'Trabajador'], true)) {
            flash('error', 'El rol seleccionado no está permitido para el registro público.');
            redirect('register');
        }

        if (
            $nombre === ''
            || $apellido === ''
            || $email === ''
            || $telefono === ''
            || $documento === ''
            || $direccion === ''
            || $password === ''
            || $cityId <= 0
        ) {
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

        if (!findCityById($cityId)) {
            flash('error', 'Selecciona una ciudad válida.');
            redirect('register');
        }

        $existing = fetchUserByEmail($email);
        if ($existing) {
            flash('error', 'El correo ya se encuentra registrado.');
            redirect('register');
        }

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

        $roleQuery = mysqli_prepare($db, 'SELECT id FROM rol WHERE nombre = ? LIMIT 1');
        mysqli_stmt_bind_param($roleQuery, 's', $role);
        mysqli_stmt_execute($roleQuery);
        $roleResult = mysqli_stmt_get_result($roleQuery);
        $roleRow = mysqli_fetch_assoc($roleResult);
        mysqli_stmt_close($roleQuery);
        if (!$roleRow) {
            flash('error', 'No se encontró el rol solicitado. Contacta al administrador.');
            redirect('register');
        }

        $roleId = (int) $roleRow['id'];
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($db, 'INSERT INTO usuario (rol_id, ciudad_id, nombre, apellido, email, telefono, documento_identidad, direccion, password_hash, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "activo")');
        mysqli_stmt_bind_param($stmt, 'iisssssss', $roleId, $cityId, $nombre, $apellido, $email, $telefono, $documento, $direccion, $passwordHash);
        if (!mysqli_stmt_execute($stmt)) {
            flash('error', 'No se pudo registrar el usuario.');
            redirect('register');
        }
        $userId = mysqli_insert_id($db);
        mysqli_stmt_close($stmt);

        session_regenerate_id(true);
        rotateCsrfToken();
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

        if ($db === null) {
            flash('error', 'No se pudo conectar con MySQL. Verifica que el servicio esté iniciado e inténtalo de nuevo.');
            redirect('login');
        }

        $user = fetchUserByEmail($email);
        if (!$user || $user['estado'] !== 'activo' || !password_verify($password, $user['password_hash'])) {
            flash('error', 'Credenciales incorrectas.');
            redirect('login');
        }

        session_regenerate_id(true);
        rotateCsrfToken();
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
        $categoria = normalizeCategoryName(trim((string) ($_POST['categoria'] ?? '')));
        $precio = (float) ($_POST['precio'] ?? 0);

        if ($titulo === '' || $descripcion === '' || $categoria === '' || $precio <= 0) {
            flash('error', 'Completa los datos del servicio y asigna un precio válido.');
            redirect('worker-dashboard');
        }
        if (!isAllowedServiceCategory($categoria)) {
            flash('error', 'Selecciona una de las cuatro categorías permitidas.');
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
        mysqli_stmt_bind_param($insert, 'iissd', $_SESSION['user']['id'], $row['id'], $titulo, $descripcion, $precio);
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
        $categoria = normalizeCategoryName(trim((string) ($_POST['categoria'] ?? '')));
        $precio = (float) ($_POST['precio'] ?? 0);

        if ($titulo === '' || $descripcion === '' || $categoria === '' || $precio <= 0) {
            flash('error', 'Completa los datos del servicio y asigna un precio válido.');
            redirect('worker-dashboard');
        }
        if (!isAllowedServiceCategory($categoria)) {
            flash('error', 'Selecciona una de las cuatro categorías permitidas.');
            redirect('worker-dashboard');
        }

        $service = fetchServiceById($id);
        if (!$service || (int) $service['trabajador_id'] !== (int) $_SESSION['user']['id']) {
            flash('error', 'No puedes editar este servicio.');
            redirect('worker-dashboard');
        }

        $categoryQuery = mysqli_prepare($db, 'SELECT id FROM categoria WHERE nombre = ? LIMIT 1');
        mysqli_stmt_bind_param($categoryQuery, 's', $categoria);
        mysqli_stmt_execute($categoryQuery);
        $categoryResult = mysqli_stmt_get_result($categoryQuery);
        $categoryRow = mysqli_fetch_assoc($categoryResult);
        mysqli_stmt_close($categoryQuery);
        if (!$categoryRow) {
            flash('error', 'La categoría seleccionada no existe.');
            redirect('worker-dashboard');
        }

        $stmt = mysqli_prepare($db, 'UPDATE servicio SET titulo = ?, descripcion = ?, categoria_id = ?, precio = ? WHERE id = ? AND trabajador_id = ?');
        mysqli_stmt_bind_param($stmt, 'ssidii', $titulo, $descripcion, $categoryRow['id'], $precio, $id, $_SESSION['user']['id']);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            flash('error', 'No se pudo actualizar el servicio.');
            redirect('worker-dashboard');
        }
        mysqli_stmt_close($stmt);
        flash('success', 'Servicio actualizado.');
        redirect('worker-dashboard');
    }

    if ($action === 'toggle-service-status') {
        requireRole(['Trabajador']);
        $id = (int) ($_POST['service_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        if ($id <= 0 || !in_array($status, ['activo', 'inactivo'], true)) {
            flash('error', 'La solicitud para cambiar el estado del servicio no es válida.');
            redirect('worker-dashboard');
        }

        $stmt = mysqli_prepare(
            $db,
            'UPDATE servicio SET estado = ? WHERE id = ? AND trabajador_id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'sii', $status, $id, $_SESSION['user']['id']);
        $updated = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) === 1;
        mysqli_stmt_close($stmt);
        if (!$updated) {
            flash('error', 'No se pudo cambiar el estado del servicio.');
            redirect('worker-dashboard');
        }

        flash('success', 'Estado del servicio actualizado.');
        redirect('worker-dashboard');
    }

    if ($action === 'delete-service') {
        requireRole(['Trabajador']);
        $id = (int) ($_POST['service_id'] ?? 0);
        $stmt = mysqli_prepare($db, 'DELETE FROM servicio WHERE id = ? AND trabajador_id = ?');
        mysqli_stmt_bind_param($stmt, 'ii', $id, $_SESSION['user']['id']);
        $deleted = mysqli_stmt_execute($stmt);
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        if (!$deleted) {
            error_log('OficioClick service deletion failed: ' . $error);
            flash('error', 'No se pudo eliminar el servicio. Si tiene reservas asociadas, desactívalo en lugar de eliminarlo.');
            redirect('worker-dashboard');
        }
        flash('success', 'Servicio eliminado.');
        redirect('worker-dashboard');
    }

    if ($action === 'reserve-service') {
        requireRole(['Cliente']);
        $serviceId = (int) ($_POST['service_id'] ?? 0);
        $date = trim((string) ($_POST['reservation_date'] ?? ''));
        $time = trim((string) ($_POST['reservation_time'] ?? ''));
        $address = trim((string) ($_POST['service_address'] ?? ''));

        if ($serviceId <= 0 || $date === '' || $time === '' || $address === '') {
            flash('error', 'Selecciona fecha, hora y dirección para reservar el servicio.');
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

        if (!mysqli_begin_transaction($db)) {
            flash('error', 'No se pudo iniciar la validación del horario. Inténtalo nuevamente.');
            redirect('catalog');
        }

        $accountIds = [(int) $_SESSION['user']['id'], (int) $service['trabajador_id']];
        sort($accountIds, SORT_NUMERIC);
        $accountLock = mysqli_prepare(
            $db,
            'SELECT u.id, u.estado, r.nombre AS role_name
             FROM usuario u
             INNER JOIN rol r ON r.id = u.rol_id
             WHERE u.id = ?
             FOR UPDATE'
        );
        $accountsLocked = $accountLock !== false;
        if ($accountsLocked) {
            $expectedRoles = [
                (int) $_SESSION['user']['id'] => 'Cliente',
                (int) $service['trabajador_id'] => 'Trabajador',
            ];
            foreach (array_unique($accountIds) as $accountId) {
                mysqli_stmt_bind_param($accountLock, 'i', $accountId);
                if (!mysqli_stmt_execute($accountLock)) {
                    $accountsLocked = false;
                    break;
                }
                $accountResult = mysqli_stmt_get_result($accountLock);
                $lockedAccount = $accountResult === false ? null : mysqli_fetch_assoc($accountResult);
                if (
                    !$lockedAccount
                    || $lockedAccount['estado'] !== 'activo'
                    || $lockedAccount['role_name'] !== $expectedRoles[$accountId]
                ) {
                    $accountsLocked = false;
                    break;
                }
            }
            mysqli_stmt_close($accountLock);
        }

        if (!$accountsLocked) {
            mysqli_rollback($db);
            flash('error', 'No fue posible validar las cuentas relacionadas con esta reserva.');
            redirect('catalog');
        }

        $serviceLock = mysqli_prepare(
            $db,
            'SELECT trabajador_id, estado, disponible FROM servicio WHERE id = ? LIMIT 1 FOR UPDATE'
        );
        if ($serviceLock === false) {
            mysqli_rollback($db);
            flash('error', 'No fue posible verificar la disponibilidad del servicio.');
            redirect('catalog');
        }
        mysqli_stmt_bind_param($serviceLock, 'i', $serviceId);
        $serviceLocked = mysqli_stmt_execute($serviceLock);
        $lockedServiceResult = $serviceLocked ? mysqli_stmt_get_result($serviceLock) : false;
        $lockedService = $lockedServiceResult === false ? null : mysqli_fetch_assoc($lockedServiceResult);
        mysqli_stmt_close($serviceLock);
        if (
            !$lockedService
            || (int) $lockedService['trabajador_id'] !== (int) $service['trabajador_id']
            || $lockedService['estado'] !== 'activo'
            || (int) $lockedService['disponible'] !== 1
        ) {
            mysqli_rollback($db);
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
             LIMIT 1
             FOR UPDATE'
        );
        if ($availability === false) {
            mysqli_rollback($db);
            flash('error', 'No fue posible comprobar el horario solicitado.');
            redirect('catalog');
        }
        mysqli_stmt_bind_param(
            $availability,
            'ssii',
            $slotStart,
            $slotStart,
            $_SESSION['user']['id'],
            $service['trabajador_id']
        );
        if (!mysqli_stmt_execute($availability)) {
            error_log('OficioClick reservation availability query failed: ' . mysqli_stmt_error($availability));
            mysqli_stmt_close($availability);
            mysqli_rollback($db);
            flash('error', 'No fue posible comprobar el horario solicitado.');
            redirect('catalog');
        }
        $availabilityResult = mysqli_stmt_get_result($availability);
        if ($availabilityResult === false) {
            error_log('OficioClick reservation availability result failed: ' . mysqli_stmt_error($availability));
            mysqli_stmt_close($availability);
            mysqli_rollback($db);
            flash('error', 'No fue posible comprobar el horario solicitado.');
            redirect('catalog');
        }
        $conflictingReservation = mysqli_fetch_assoc($availabilityResult);
        mysqli_stmt_close($availability);

        if ($conflictingReservation) {
            mysqli_rollback($db);
            flash('error', 'Ese horario ya no está disponible para el cliente o el trabajador.');
            redirect('catalog');
        }

        $stmt = mysqli_prepare($db, 'INSERT INTO reserva (cliente_id, servicio_id, fecha, hora, direccion_atencion, estado) VALUES (?, ?, ?, ?, ?, "pendiente")');
        if ($stmt === false) {
            mysqli_rollback($db);
            flash('error', 'No fue posible preparar la solicitud de reserva.');
            redirect('catalog');
        }
        mysqli_stmt_bind_param($stmt, 'iisss', $_SESSION['user']['id'], $serviceId, $date, $time, $address);
        if (!mysqli_stmt_execute($stmt)) {
            error_log('OficioClick reservation insert failed: ' . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            mysqli_rollback($db);
            flash('error', 'No se pudo registrar la reserva. Inténtalo nuevamente.');
            redirect('catalog');
        }
        mysqli_stmt_close($stmt);
        if (!mysqli_commit($db)) {
            error_log('OficioClick reservation transaction commit failed: ' . mysqli_error($db));
            mysqli_rollback($db);
            flash('error', 'No se pudo confirmar la solicitud de reserva. Inténtalo nuevamente.');
            redirect('catalog');
        }

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

        $adminTransaction = $role === 'Administrador';
        if ($adminTransaction && !mysqli_begin_transaction($db)) {
            mysqli_stmt_close($stmt);
            flash('error', 'No se pudo iniciar el registro de auditoría de la reserva.');
            redirect('admin-dashboard');
        }

        $updated = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) === 1;
        mysqli_stmt_close($stmt);
        if ($adminTransaction) {
            if (
                $updated
                && logAdminAction($userId, 'cancel_reservation', 'reservation', $reservationId, ['status' => $status])
            ) {
                $updated = mysqli_commit($db);
            } else {
                mysqli_rollback($db);
                $updated = false;
            }
        }

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
        if (!mysqli_begin_transaction($db)) {
            mysqli_stmt_close($stmt);
            flash('error', 'No se pudo iniciar la actualización administrativa.');
            redirect('admin-dashboard');
        }
        $updated = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if ($updated && logAdminAction(
            (int) $_SESSION['user']['id'],
            'update_user',
            'user',
            $userId,
            ['role' => $role, 'status' => $status]
        )) {
            $updated = mysqli_commit($db);
        } else {
            mysqli_rollback($db);
            $updated = false;
        }

        if (!$updated) {
            flash('error', 'No se pudo actualizar el usuario.');
            redirect('admin-dashboard');
        }

        flash('success', 'Usuario actualizado correctamente.');
        redirect('admin-dashboard');
    }

    if ($action === 'admin-update-service') {
        requireRole(['Administrador']);
        $serviceId = (int) ($_POST['service_id'] ?? 0);
        $title = trim((string) ($_POST['titulo'] ?? ''));
        $description = trim((string) ($_POST['descripcion'] ?? ''));
        $category = normalizeCategoryName(trim((string) ($_POST['categoria'] ?? '')));
        $price = (float) ($_POST['precio'] ?? 0);
        if (
            $serviceId <= 0
            || $title === ''
            || $description === ''
            || $price <= 0
            || !isAllowedServiceCategory($category)
        ) {
            flash('error', 'Revisa los datos del servicio antes de guardar.');
            redirect('admin-dashboard');
        }

        $categoryQuery = mysqli_prepare($db, 'SELECT id FROM categoria WHERE nombre = ? LIMIT 1');
        mysqli_stmt_bind_param($categoryQuery, 's', $category);
        mysqli_stmt_execute($categoryQuery);
        $categoryResult = mysqli_stmt_get_result($categoryQuery);
        $categoryRow = mysqli_fetch_assoc($categoryResult);
        mysqli_stmt_close($categoryQuery);
        if (!$categoryRow) {
            flash('error', 'La categoría seleccionada no existe.');
            redirect('admin-dashboard');
        }

        $stmt = mysqli_prepare(
            $db,
            'UPDATE servicio SET titulo = ?, descripcion = ?, categoria_id = ?, precio = ? WHERE id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'ssidi', $title, $description, $categoryRow['id'], $price, $serviceId);
        if (!mysqli_begin_transaction($db)) {
            mysqli_stmt_close($stmt);
            flash('error', 'No se pudo iniciar la actualización administrativa.');
            redirect('admin-dashboard');
        }
        $updated = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if ($updated && logAdminAction(
            (int) $_SESSION['user']['id'],
            'update_service',
            'service',
            $serviceId,
            ['title' => $title, 'category' => $category, 'price' => $price]
        )) {
            $updated = mysqli_commit($db);
        } else {
            mysqli_rollback($db);
            $updated = false;
        }
        if (!$updated) {
            flash('error', 'No se pudo actualizar el servicio.');
            redirect('admin-dashboard');
        }

        flash('success', 'Servicio actualizado correctamente.');
        redirect('admin-dashboard');
    }

    if ($action === 'admin-delete-service') {
        requireRole(['Administrador']);
        $serviceId = (int) ($_POST['service_id'] ?? 0);
        $reservationQuery = mysqli_prepare($db, 'SELECT id, titulo FROM servicio WHERE id = ? LIMIT 1');
        mysqli_stmt_bind_param($reservationQuery, 'i', $serviceId);
        mysqli_stmt_execute($reservationQuery);
        $serviceResult = mysqli_stmt_get_result($reservationQuery);
        $serviceRow = mysqli_fetch_assoc($serviceResult);
        mysqli_stmt_close($reservationQuery);
        if (!$serviceRow) {
            flash('error', 'El servicio seleccionado no existe.');
            redirect('admin-dashboard');
        }

        $reservationQuery = mysqli_prepare($db, 'SELECT COUNT(*) AS total FROM reserva WHERE servicio_id = ?');
        mysqli_stmt_bind_param($reservationQuery, 'i', $serviceId);
        mysqli_stmt_execute($reservationQuery);
        $reservationResult = mysqli_stmt_get_result($reservationQuery);
        $reservationRow = mysqli_fetch_assoc($reservationResult);
        mysqli_stmt_close($reservationQuery);
        if ((int) ($reservationRow['total'] ?? 0) > 0) {
            flash('error', 'No se puede eliminar un servicio con reservas asociadas. Desactívalo para conservar el historial.');
            redirect('admin-dashboard');
        }

        $stmt = mysqli_prepare($db, 'DELETE FROM servicio WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $serviceId);
        if (!mysqli_begin_transaction($db)) {
            mysqli_stmt_close($stmt);
            flash('error', 'No se pudo iniciar la eliminación administrativa.');
            redirect('admin-dashboard');
        }
        $deleted = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) === 1;
        mysqli_stmt_close($stmt);
        if ($deleted && logAdminAction(
            (int) $_SESSION['user']['id'],
            'delete_service',
            'service',
            $serviceId,
            ['title' => $serviceRow['titulo']]
        )) {
            $deleted = mysqli_commit($db);
        } else {
            mysqli_rollback($db);
            $deleted = false;
        }
        if (!$deleted) {
            flash('error', 'No se pudo eliminar el servicio.');
            redirect('admin-dashboard');
        }

        flash('success', 'Servicio eliminado correctamente.');
        redirect('admin-dashboard');
    }

    if ($action === 'set-admin-service-status') {
        requireRole(['Administrador']);
        $serviceId = (int) ($_POST['service_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        if ($serviceId <= 0 || !in_array($status, ['activo', 'inactivo'], true)) {
            flash('error', 'La solicitud para cambiar el estado del servicio no es válida.');
            redirect('admin-dashboard');
        }

        $stmt = mysqli_prepare($db, 'UPDATE servicio SET estado = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'si', $status, $serviceId);
        if (!mysqli_begin_transaction($db)) {
            mysqli_stmt_close($stmt);
            flash('error', 'No se pudo iniciar la actualización administrativa.');
            redirect('admin-dashboard');
        }
        $updated = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if ($updated && logAdminAction(
            (int) $_SESSION['user']['id'],
            'set_service_status',
            'service',
            $serviceId,
            ['status' => $status]
        )) {
            $updated = mysqli_commit($db);
        } else {
            mysqli_rollback($db);
            $updated = false;
        }
        if (!$updated) {
            flash('error', 'No se pudo actualizar el estado del servicio.');
            redirect('admin-dashboard');
        }

        flash('success', 'Estado del servicio actualizado.');
        redirect('admin-dashboard');
    }

    if ($action === 'delete-review') {
        requireRole(['Administrador']);
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        if ($reviewId <= 0) {
            flash('error', 'La reseña seleccionada no es válida.');
            redirect('admin-dashboard');
        }

        $stmt = mysqli_prepare($db, 'DELETE FROM calificacion WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $reviewId);
        if (!mysqli_begin_transaction($db)) {
            mysqli_stmt_close($stmt);
            flash('error', 'No se pudo iniciar la moderación de la reseña.');
            redirect('admin-dashboard');
        }
        $deleted = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) === 1;
        mysqli_stmt_close($stmt);
        if ($deleted && logAdminAction(
            (int) $_SESSION['user']['id'],
            'delete_review',
            'review',
            $reviewId
        )) {
            $deleted = mysqli_commit($db);
        } else {
            mysqli_rollback($db);
            $deleted = false;
        }
        if (!$deleted) {
            flash('error', 'No se pudo eliminar la reseña seleccionada.');
            redirect('admin-dashboard');
        }

        flash('success', 'Reseña moderada correctamente.');
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

        $reviewInsert = mysqli_prepare($db, 'INSERT INTO calificacion (reserva_id, estrellas, comentario) VALUES (?, ?, ?)');
        mysqli_stmt_bind_param($reviewInsert, 'iis', $reservationId, $stars, $comment);
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
        $viewData['categories'] = categoryOptions();
        $viewData['metrics'] = fetchDashboardMetrics();
        break;
    case 'register':
        $view = 'register';
        $viewData['cities'] = cityOptions();
        break;
    case 'login':
        $view = 'login';
        break;
    case 'catalog':
        $view = 'catalog';
        $minPrice = trim((string) ($_GET['min_price'] ?? ''));
        $maxPrice = trim((string) ($_GET['max_price'] ?? ''));
        $minRating = trim((string) ($_GET['min_rating'] ?? ''));
        if (
            ($minPrice !== '' && (!is_numeric($minPrice) || (float) $minPrice < 0))
            || ($maxPrice !== '' && (!is_numeric($maxPrice) || (float) $maxPrice < 0))
            || ($minPrice !== '' && $maxPrice !== '' && (float) $minPrice > (float) $maxPrice)
            || ($minRating !== '' && !in_array($minRating, ['1', '2', '3', '4', '5'], true))
        ) {
            flash('error', 'Revisa los valores de precio y calificación de los filtros.');
            redirect('catalog');
        }

        $filters = [
            'category' => (string) ($_GET['category'] ?? ''),
            'search' => (string) ($_GET['search'] ?? ''),
            'available_only' => !isset($_GET['available_only']) || $_GET['available_only'] === '1',
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'min_rating' => $minRating,
        ];
        $perPage = 12;
        $totalServices = countServices($filters);
        $pageNumber = max(1, (int) ($_GET['page_number'] ?? 1));
        if ($totalServices !== null) {
            $totalPages = max(1, (int) ceil($totalServices / $perPage));
            $pageNumber = min($pageNumber, $totalPages);
        } else {
            $totalPages = 1;
        }
        $paginatedFilters = $filters;
        $paginatedFilters['per_page'] = $perPage;
        $paginatedFilters['page'] = $pageNumber;
        $viewData['services'] = fetchServices($paginatedFilters);
        $viewData['categories'] = categoryOptions();
        $viewData['filters'] = $filters;
        $viewData['totalServices'] = $totalServices;
        $viewData['currentPage'] = $pageNumber;
        $viewData['totalPages'] = $totalPages;
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
        $dateFrom = trim((string) ($_GET['date_from'] ?? ''));
        $dateTo = trim((string) ($_GET['date_to'] ?? ''));
        $reservationStatus = (string) ($_GET['status'] ?? '');
        $categoryId = (int) ($_GET['category_id'] ?? 0);
        foreach ([$dateFrom, $dateTo] as $filterDate) {
            if ($filterDate !== '') {
                $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $filterDate);
                $dateErrors = DateTimeImmutable::getLastErrors();
                if (
                    !$parsedDate
                    || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
                    || $parsedDate->format('Y-m-d') !== $filterDate
                ) {
                    flash('error', 'La fecha indicada para filtrar reservas no es válida.');
                    redirect('admin-dashboard');
                }
            }
        }
        if (
            ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo)
            || ($reservationStatus !== '' && !in_array($reservationStatus, ['pendiente', 'confirmada', 'finalizada', 'cancelada'], true))
        ) {
            flash('error', 'Revisa el rango de fechas y el estado de las reservas.');
            redirect('admin-dashboard');
        }
        $adminFilters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status' => $reservationStatus,
            'category_id' => $categoryId > 0 ? $categoryId : '',
        ];
        $viewData['users'] = fetchUsersTable();
        $viewData['reservations'] = fetchReservationsForUser((int) $_SESSION['user']['id'], 'Administrador', $adminFilters);
        $viewData['metrics'] = fetchDashboardMetrics();
        $viewData['reservationFilters'] = $adminFilters;
        $viewData['categories'] = categoryOptions();
        $viewData['services'] = fetchAdminServices();
        $viewData['reviews'] = fetchAdminReviews();
        $viewData['adminActions'] = fetchAdminActions();
        break;
    case 'admin-export':
        requireRole(['Administrador']);
        $dataset = (string) ($_GET['dataset'] ?? '');
        if (!in_array($dataset, ['summary', 'reservations', 'services', 'users'], true)) {
            flash('error', 'El tipo de CSV solicitado no es válido.');
            redirect('admin-dashboard');
        }

        $csvData = fetchAdminCsvData($dataset);
        if ($csvData === null) {
            flash('error', 'No se pudo generar el CSV. Revisa la conexión y los registros de la aplicación.');
            redirect('admin-dashboard');
        }
        if (!logAdminAction((int) $_SESSION['user']['id'], 'export_csv', 'report', null, ['dataset' => $dataset])) {
            flash('error', 'No se pudo registrar la descarga en la auditoría.');
            redirect('admin-dashboard');
        }
        if (!sendCsvDownload($csvData)) {
            flash('error', 'No se pudo preparar el archivo CSV. Inténtalo nuevamente.');
            redirect('admin-dashboard');
        }
        exit;
    case 'logout':
        redirect('home');
    default:
        $view = 'home';
        $viewData['services'] = fetchServices(['available_only' => true]);
        $viewData['categories'] = categoryOptions();
        $viewData['metrics'] = fetchDashboardMetrics();
        break;
}

$viewFile = __DIR__ . '/../views/' . $view . '.php';
if (!is_file($viewFile)) {
    $viewFile = __DIR__ . '/../views/home.php';
}

include __DIR__ . '/../views/layouts/base.php';
