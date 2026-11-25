<?php

function dbConnection(): ?mysqli
{
    static $connection = null;

    if (is_object($connection) && $connection instanceof mysqli) {
        return $connection;
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    try {
        $connection = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    } catch (Throwable $e) {
        $connection = null;
        return null;
    }

    if (!is_object($connection) || !($connection instanceof mysqli) || mysqli_connect_errno() !== 0) {
        $connection = null;
        return null;
    }

    mysqli_set_charset($connection, 'utf8mb4');
    return $connection;
}

function ensureDatabase(): bool
{
    $connection = dbConnection();
    if ($connection === null) {
        return false;
    }

    $result = mysqli_query($connection, 'SELECT 1 FROM usuario LIMIT 0');
    if ($result === false) {
        error_log('OficioClick database check failed: ' . mysqli_error($connection));
        return false;
    }

    mysqli_free_result($result);
    return true;
}

function e(string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $page): void
{
    header('Location: index.php?page=' . rawurlencode($page));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user']['id']);
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function rotateCsrfToken(): void
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrfToken(mixed $submittedToken): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? null;

    return is_string($sessionToken)
        && is_string($submittedToken)
        && hash_equals($sessionToken, $submittedToken);
}

function isValidAccountPassword(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[A-Za-z]/', $password) === 1
        && preg_match('/\d/', $password) === 1;
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    return $_SESSION['user'];
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        flash('error', 'Debes iniciar sesión para continuar.');
        redirect('login');
    }
}

function requireRole(array $allowedRoles): void
{
    requireLogin();

    $user = currentUser();
    $databaseUser = $user === null ? null : fetchUserById((int) $user['id']);
    if ($databaseUser === null) {
        if (dbConnection() === null) {
            flash('error', 'No se pudo verificar tu cuenta porque MySQL no está disponible. Inténtalo más tarde.');
            redirect('login');
        }

        invalidateCurrentUserSession('Tu cuenta ya no está disponible. Inicia sesión nuevamente.');
    }

    if (!sessionMatchesActiveUser($user, $databaseUser)) {
        invalidateCurrentUserSession('Tu cuenta está inactiva o sus permisos cambiaron. Inicia sesión nuevamente.');
    }

    if (!in_array($databaseUser['role_name'], $allowedRoles, true)) {
        flash('error', 'No tienes permisos para acceder a esta vista.');
        redirect('home');
    }
}

function sessionMatchesActiveUser(array $sessionUser, array $databaseUser): bool
{
    return isset(
        $sessionUser['id'],
        $sessionUser['role'],
        $databaseUser['id'],
        $databaseUser['role_name'],
        $databaseUser['estado']
    )
        && (int) $sessionUser['id'] === (int) $databaseUser['id']
        && $databaseUser['estado'] === 'activo'
        && $sessionUser['role'] === $databaseUser['role_name'];
}

function invalidateCurrentUserSession(string $message): void
{
    unset($_SESSION['user']);
    session_regenerate_id(true);
    flash('error', $message);
    redirect('login');
}

function userRoleName(int $roleId): string
{
    $db = dbConnection();
    if ($db === null) {
        return 'Usuario';
    }

    $stmt = mysqli_prepare($db, 'SELECT nombre FROM rol WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $roleId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $row['nombre'] ?? 'Usuario';
}

function categoryOptions(): array
{
    $db = dbConnection();
    if ($db === null) {
        return [];
    }

    $result = mysqli_query(
        $db,
        "SELECT id, nombre FROM categoria
         WHERE nombre IN ('Plomería', 'Electricidad', 'Obra blanca', 'Aseo general')
         ORDER BY FIELD(nombre, 'Plomería', 'Electricidad', 'Obra blanca', 'Aseo general')"
    );
    if ($result === false) {
        error_log('OficioClick category query failed: ' . mysqli_error($db));
        return [];
    }

    $categories = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    return $categories;
}

function cityOptions(): array
{
    $db = dbConnection();
    if ($db === null) {
        return [];
    }

    $result = mysqli_query($db, 'SELECT id, nombre FROM ciudad ORDER BY nombre');
    if ($result === false) {
        error_log('OficioClick city query failed: ' . mysqli_error($db));
        return [];
    }

    $cities = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $cities[] = $row;
    }
    mysqli_free_result($result);

    return $cities;
}

function findCityById(int $cityId): bool
{
    $db = dbConnection();
    if ($db === null || $cityId <= 0) {
        return false;
    }

    $stmt = mysqli_prepare($db, 'SELECT id FROM ciudad WHERE id = ? LIMIT 1');
    if ($stmt === false) {
        error_log('OficioClick city validation preparation failed: ' . mysqli_error($db));
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'i', $cityId);
    if (!mysqli_stmt_execute($stmt)) {
        error_log('OficioClick city validation failed: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    $result = mysqli_stmt_get_result($stmt);
    $exists = $result !== false && mysqli_num_rows($result) === 1;
    mysqli_stmt_close($stmt);

    return $exists;
}

function fetchUserProfile(int $userId): ?array
{
    $db = dbConnection();
    if ($db === null) {
        return null;
    }

    $stmt = mysqli_prepare(
        $db,
        'SELECT id, ciudad_id, nombre, apellido, email, telefono, documento_identidad, direccion
         FROM usuario
         WHERE id = ?
         LIMIT 1'
    );
    if ($stmt === false) {
        error_log('OficioClick profile query preparation failed: ' . mysqli_error($db));
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $userId);
    if (!mysqli_stmt_execute($stmt)) {
        error_log('OficioClick profile query failed: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return null;
    }

    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        error_log('OficioClick profile result retrieval failed: ' . mysqli_error($db));
        mysqli_stmt_close($stmt);
        return null;
    }

    $profile = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $profile ?: null;
}

function insertUserAccount(
    mysqli $db,
    int $roleId,
    int $cityId,
    string $name,
    string $lastName,
    string $email,
    string $phone,
    string $document,
    string $address,
    string $password,
    ?int &$errorCode = null
): int|false {
    $errorCode = 0;
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare(
        $db,
        'INSERT INTO usuario
            (rol_id, ciudad_id, nombre, apellido, email, telefono, documento_identidad, direccion, password_hash, estado)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "activo")'
    );
    if ($stmt === false) {
        $errorCode = mysqli_errno($db);
        error_log('OficioClick user insert preparation failed: ' . mysqli_error($db));
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'iisssssss',
        $roleId,
        $cityId,
        $name,
        $lastName,
        $email,
        $phone,
        $document,
        $address,
        $passwordHash
    );
    if (!mysqli_stmt_execute($stmt)) {
        $errorCode = mysqli_stmt_errno($stmt);
        error_log('OficioClick user insert failed: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    $userId = mysqli_insert_id($db);
    mysqli_stmt_close($stmt);

    return $userId;
}

function normalizeCategoryName(string $value): string
{
    $map = [
        'plomeria' => 'Plomería',
        'electricidad' => 'Electricidad',
        'obra blanca' => 'Obra blanca',
        'obra_blanca' => 'Obra blanca',
        'aseo general' => 'Aseo general',
        'aseo_general' => 'Aseo general',
    ];

    $key = strtolower(trim($value));
    return $map[$key] ?? trim($value);
}

function isAllowedServiceCategory(string $value): bool
{
    return in_array(
        normalizeCategoryName($value),
        ['Plomería', 'Electricidad', 'Obra blanca', 'Aseo general'],
        true
    );
}

function fetchUserByEmail(string $email): ?array
{
    $db = dbConnection();
    if ($db === null) {
        return null;
    }

    $stmt = mysqli_prepare($db, 'SELECT u.*, r.nombre AS role_name FROM usuario u INNER JOIN rol r ON r.id = u.rol_id WHERE u.email = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $user ?: null;
}

function fetchUserById(int $id): ?array
{
    $db = dbConnection();
    if ($db === null) {
        return null;
    }

    $stmt = mysqli_prepare($db, 'SELECT u.*, r.nombre AS role_name FROM usuario u INNER JOIN rol r ON r.id = u.rol_id WHERE u.id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $user ?: null;
}

function serviceFilterConditions(array $filters): array
{
    $conditions = [
        's.estado = "activo"',
        "c.nombre IN ('Plomería', 'Electricidad', 'Obra blanca', 'Aseo general')",
    ];
    $params = [];
    $types = '';

    if (!empty($filters['category'])) {
        $conditions[] = 'c.nombre = ?';
        $params[] = normalizeCategoryName((string) $filters['category']);
        $types .= 's';
    }

    if (!empty($filters['search'])) {
        $conditions[] = '(s.titulo LIKE ? OR s.descripcion LIKE ? OR c.nombre LIKE ?)';
        $term = '%' . trim((string) $filters['search']) . '%';
        array_push($params, $term, $term, $term);
        $types .= 'sss';
    }

    if (!empty($filters['available_only'])) {
        $conditions[] = 's.disponible = 1';
    }

    if (isset($filters['min_price']) && $filters['min_price'] !== '') {
        $conditions[] = 's.precio >= ?';
        $params[] = (float) $filters['min_price'];
        $types .= 'd';
    }

    if (isset($filters['max_price']) && $filters['max_price'] !== '') {
        $conditions[] = 's.precio <= ?';
        $params[] = (float) $filters['max_price'];
        $types .= 'd';
    }

    if (isset($filters['min_rating']) && $filters['min_rating'] !== '') {
        $conditions[] = 'COALESCE((
            SELECT AVG(cal.estrellas)
            FROM calificacion cal
            INNER JOIN reserva res ON res.id = cal.reserva_id
            WHERE res.servicio_id = s.id
        ), 0) >= ?';
        $params[] = (float) $filters['min_rating'];
        $types .= 'd';
    }

    return [implode(' AND ', $conditions), $params, $types];
}

function fetchServices(array $filters = []): array
{
    $db = dbConnection();
    if ($db === null) {
        return [];
    }

    [$where, $params, $types] = serviceFilterConditions($filters);
    $query = 'SELECT s.*, c.nombre AS category_name, u.nombre AS worker_name, u.apellido AS worker_lastname
              FROM servicio s
              INNER JOIN categoria c ON c.id = s.categoria_id
              INNER JOIN usuario u ON u.id = s.trabajador_id
              WHERE ' . $where . ' ORDER BY s.created_at DESC';

    if (isset($filters['per_page'])) {
        $perPage = max(1, min(50, (int) $filters['per_page']));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $query .= ' LIMIT ? OFFSET ?';
        $params[] = $perPage;
        $params[] = ($page - 1) * $perPage;
        $types .= 'ii';
    }

    $stmt = $db->prepare($query);
    if ($stmt && $params) {
        $stmt->bind_param($types, ...$params);
    }

    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        $services = [];
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
        $stmt->close();
        return $services;
    }

    return [];
}

function countServices(array $filters = []): ?int
{
    $db = dbConnection();
    if ($db === null) {
        return null;
    }

    [$where, $params, $types] = serviceFilterConditions($filters);
    $stmt = $db->prepare(
        'SELECT COUNT(*) AS total
         FROM servicio s
         INNER JOIN categoria c ON c.id = s.categoria_id
         WHERE ' . $where
    );
    if ($stmt === false) {
        error_log('OficioClick service count preparation failed: ' . mysqli_error($db));
        return null;
    }

    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        error_log('OficioClick service count failed: ' . $stmt->error);
        $stmt->close();
        return null;
    }

    $result = $stmt->get_result();
    $row = $result === false ? null : $result->fetch_assoc();
    if ($result === false) {
        error_log('OficioClick service count result retrieval failed: ' . $stmt->error);
    }
    $stmt->close();

    return $row === null ? null : (int) $row['total'];
}

function fetchServiceById(int $id): ?array
{
    $db = dbConnection();
    if ($db === null) {
        return null;
    }

    $stmt = mysqli_prepare($db, "SELECT s.*, c.nombre AS category_name, u.nombre AS worker_name, u.apellido AS worker_lastname, u.email AS worker_email, u.telefono AS worker_phone
        FROM servicio s
        INNER JOIN categoria c ON c.id = s.categoria_id
        INNER JOIN usuario u ON u.id = s.trabajador_id
        WHERE s.id = ?
          AND c.nombre IN ('Plomería', 'Electricidad', 'Obra blanca', 'Aseo general')
        LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $service = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $service ?: null;
}

function fetchReservationsForUser(int $userId, string $role, array $filters = []): array
{
    $db = dbConnection();
    if ($db === null) {
        return [];
    }

    if ($role === 'Cliente') {
        $sql = 'SELECT r.*, cal.id AS calificacion_id, s.titulo, s.precio, c.nombre AS category_name, u.nombre AS worker_name, u.apellido AS worker_lastname
                FROM reserva r
                INNER JOIN servicio s ON s.id = r.servicio_id
                INNER JOIN categoria c ON c.id = s.categoria_id
                INNER JOIN usuario u ON u.id = s.trabajador_id
                LEFT JOIN calificacion cal ON cal.reserva_id = r.id
                WHERE r.cliente_id = ? ORDER BY r.fecha DESC, r.hora DESC';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
    } elseif ($role === 'Trabajador') {
        $sql = 'SELECT r.*, s.titulo, s.precio, c.nombre AS category_name, u.nombre AS client_name, u.apellido AS client_lastname
                FROM reserva r
                INNER JOIN servicio s ON s.id = r.servicio_id
                INNER JOIN categoria c ON c.id = s.categoria_id
                INNER JOIN usuario u ON u.id = r.cliente_id
                WHERE s.trabajador_id = ? ORDER BY r.fecha DESC, r.hora DESC';
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
    } else {
        $sql = 'SELECT r.*, s.titulo, s.precio, c.nombre AS category_name, client.nombre AS client_name, client.apellido AS client_lastname
                FROM reserva r
                INNER JOIN servicio s ON s.id = r.servicio_id
                INNER JOIN categoria c ON c.id = s.categoria_id
                INNER JOIN usuario client ON client.id = r.cliente_id
                WHERE 1 = 1';
        $params = [];
        $types = '';

        if (!empty($filters['status']) && in_array($filters['status'], ['pendiente', 'confirmada', 'finalizada', 'cancelada'], true)) {
            $sql .= ' AND r.estado = ?';
            $params[] = $filters['status'];
            $types .= 's';
        }
        if (!empty($filters['date_from'])) {
            $sql .= ' AND r.fecha >= ?';
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        if (!empty($filters['date_to'])) {
            $sql .= ' AND r.fecha <= ?';
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        if (!empty($filters['category_id'])) {
            $sql .= ' AND s.categoria_id = ?';
            $params[] = (int) $filters['category_id'];
            $types .= 'i';
        }
        $sql .= ' ORDER BY r.fecha DESC, r.hora DESC';

        $stmt = mysqli_prepare($db, $sql);
        if ($stmt === false) {
            error_log('OficioClick admin reservation query preparation failed: ' . mysqli_error($db));
            return [];
        }
        if ($params) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
    }

    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);

    return $rows;
}

function fetchUserServices(int $userId): array
{
    $db = dbConnection();
    if ($db === null) {
        return [];
    }

    $stmt = mysqli_prepare($db, 'SELECT s.*, c.nombre AS category_name FROM servicio s INNER JOIN categoria c ON c.id = s.categoria_id WHERE s.trabajador_id = ? ORDER BY s.created_at DESC');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);

    return $rows;
}

function fetchUsersTable(): array
{
    $db = dbConnection();
    if ($db === null) {
        return [];
    }

    $result = mysqli_query(
        $db,
        'SELECT u.*, r.nombre AS role_name, c.nombre AS city_name
         FROM usuario u
         INNER JOIN rol r ON r.id = u.rol_id
         LEFT JOIN ciudad c ON c.id = u.ciudad_id
         ORDER BY u.created_at DESC'
    );
    if ($result === false) {
        error_log('OficioClick users query failed: ' . mysqli_error($db));
        return [];
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function fetchAdminServices(): array
{
    $db = dbConnection();
    if ($db === null) {
        return [];
    }

    $result = mysqli_query(
        $db,
        'SELECT s.*, c.nombre AS category_name, u.nombre AS worker_name, u.apellido AS worker_lastname
         FROM servicio s
         INNER JOIN categoria c ON c.id = s.categoria_id
         INNER JOIN usuario u ON u.id = s.trabajador_id
         ORDER BY s.created_at DESC'
    );
    if ($result === false) {
        error_log('OficioClick admin services query failed: ' . mysqli_error($db));
        return [];
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_free_result($result);

    return $rows;
}

function fetchAdminReviews(): array
{
    $db = dbConnection();
    if ($db === null) {
        return [];
    }

    $result = mysqli_query(
        $db,
        'SELECT cal.id, cal.estrellas, cal.comentario, cal.created_at,
                r.id AS reservation_id, s.titulo,
                client.nombre AS client_name, client.apellido AS client_lastname,
                worker.nombre AS worker_name, worker.apellido AS worker_lastname
         FROM calificacion cal
         INNER JOIN reserva r ON r.id = cal.reserva_id
         INNER JOIN servicio s ON s.id = r.servicio_id
         INNER JOIN usuario client ON client.id = r.cliente_id
         INNER JOIN usuario worker ON worker.id = s.trabajador_id
         ORDER BY cal.created_at DESC'
    );
    if ($result === false) {
        error_log('OficioClick admin reviews query failed: ' . mysqli_error($db));
        return [];
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_free_result($result);

    return $rows;
}

function fetchAdminActions(int $limit = 50): array
{
    $db = dbConnection();
    if ($db === null) {
        return [];
    }

    $stmt = mysqli_prepare(
        $db,
        'SELECT a.*, u.nombre AS admin_name, u.apellido AS admin_lastname
         FROM admin_action a
         INNER JOIN usuario u ON u.id = a.admin_id
         ORDER BY a.created_at DESC, a.id DESC
         LIMIT ?'
    );
    if ($stmt === false) {
        error_log('OficioClick admin audit query preparation failed: ' . mysqli_error($db));
        return [];
    }

    mysqli_stmt_bind_param($stmt, 'i', $limit);
    if (!mysqli_stmt_execute($stmt)) {
        error_log('OficioClick admin audit query failed: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($result !== false && ($row = mysqli_fetch_assoc($result))) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);

    return $rows;
}

function logAdminAction(int $adminId, string $action, string $entity, ?int $entityId, array $details = []): bool
{
    $db = dbConnection();
    if ($db === null) {
        error_log('OficioClick could not record an administrative action because the database is unavailable.');
        return false;
    }

    $encodedDetails = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encodedDetails === false) {
        error_log('OficioClick could not encode administrative audit details.');
        return false;
    }

    $stmt = mysqli_prepare(
        $db,
        'INSERT INTO admin_action (admin_id, action, entity, entity_id, details) VALUES (?, ?, ?, ?, ?)'
    );
    if ($stmt === false) {
        error_log('OficioClick admin audit insert preparation failed: ' . mysqli_error($db));
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'issis', $adminId, $action, $entity, $entityId, $encodedDetails);
    $success = mysqli_stmt_execute($stmt);
    if (!$success) {
        error_log('OficioClick admin audit insert failed: ' . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);

    return $success;
}

function getAverageRating(int $serviceId): ?float
{
    $db = dbConnection();
    if ($db === null) {
        return null;
    }

    $stmt = mysqli_prepare($db, 'SELECT AVG(c.estrellas) AS rating FROM calificacion c INNER JOIN reserva r ON r.id = c.reserva_id WHERE r.servicio_id = ?');
    if ($stmt === false) {
        error_log('OficioClick rating query preparation failed: ' . mysqli_error($db));
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $serviceId);
    if (!mysqli_stmt_execute($stmt)) {
        error_log('OficioClick rating query failed: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return null;
    }

    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        error_log('OficioClick rating result retrieval failed: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return null;
    }

    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return ($row['rating'] ?? null) === null ? null : (float) $row['rating'];
}

function fetchDashboardMetrics(): ?array
{
    $db = dbConnection();
    if ($db === null) {
        return null;
    }

    $query = "SELECT
                  (SELECT COUNT(*) FROM usuario) AS users_total,
                  (SELECT COUNT(*) FROM servicio WHERE estado = 'activo') AS active_services,
                  (SELECT COUNT(*) FROM servicio WHERE estado = 'activo' AND disponible = 1) AS available_services,
                  (SELECT COUNT(*) FROM reserva) AS reservations_total,
                  (SELECT COUNT(*) FROM reserva WHERE estado = 'pendiente') AS reservations_pending,
                  (SELECT COUNT(*) FROM reserva WHERE estado = 'confirmada') AS reservations_confirmed,
                  (SELECT COUNT(*) FROM reserva WHERE estado = 'finalizada') AS reservations_finished,
                  (SELECT COUNT(*) FROM reserva WHERE estado = 'cancelada') AS reservations_cancelled,
                  (SELECT COUNT(*) FROM calificacion c
                   INNER JOIN reserva r ON r.id = c.reserva_id
                   INNER JOIN servicio s ON s.id = r.servicio_id
                   WHERE s.estado = 'activo' AND s.disponible = 1) AS available_review_count,
                  (SELECT AVG(c.estrellas) FROM calificacion c
                   INNER JOIN reserva r ON r.id = c.reserva_id
                   INNER JOIN servicio s ON s.id = r.servicio_id
                   WHERE s.estado = 'activo' AND s.disponible = 1) AS available_average_rating";
    $result = mysqli_query($db, $query);
    if ($result === false) {
        error_log('OficioClick dashboard metrics query failed: ' . mysqli_error($db));
        return null;
    }

    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    if ($row === null || $row === false) {
        error_log('OficioClick dashboard metrics query returned no result.');
        return null;
    }

    return [
        'users_total' => (int) $row['users_total'],
        'active_services' => (int) $row['active_services'],
        'available_services' => (int) $row['available_services'],
        'reservations_total' => (int) $row['reservations_total'],
        'reservations_pending' => (int) $row['reservations_pending'],
        'reservations_confirmed' => (int) $row['reservations_confirmed'],
        'reservations_finished' => (int) $row['reservations_finished'],
        'reservations_cancelled' => (int) $row['reservations_cancelled'],
        'available_review_count' => (int) $row['available_review_count'],
        'available_average_rating' => $row['available_average_rating'] === null
            ? null
            : (float) $row['available_average_rating'],
    ];
}

function fetchAdminCsvData(string $dataset): ?array
{
    $reports = [
        'reservations' => [
            'filename' => 'oficioclick_reservas.csv',
            'headers' => ['ID reserva', 'Servicio', 'Cliente', 'Fecha', 'Hora', 'Estado', 'Registrada el'],
            'query' => "SELECT r.id, s.titulo, CONCAT(client.nombre, ' ', client.apellido), r.fecha, r.hora, r.estado, r.created_at
                        FROM reserva r
                        INNER JOIN servicio s ON s.id = r.servicio_id
                        INNER JOIN usuario client ON client.id = r.cliente_id
                        ORDER BY r.created_at DESC, r.id DESC",
        ],
        'services' => [
            'filename' => 'oficioclick_servicios.csv',
            'headers' => ['ID servicio', 'Título', 'Categoría', 'Trabajador', 'Precio por hora', 'Estado', 'Disponible', 'Publicado el'],
            'query' => "SELECT s.id, s.titulo, c.nombre, CONCAT(worker.nombre, ' ', worker.apellido), s.precio, s.estado,
                               IF(s.disponible = 1, 'Sí', 'No'), s.created_at
                        FROM servicio s
                        INNER JOIN categoria c ON c.id = s.categoria_id
                        INNER JOIN usuario worker ON worker.id = s.trabajador_id
                        ORDER BY s.created_at DESC, s.id DESC",
        ],
        'users' => [
            'filename' => 'oficioclick_usuarios.csv',
            'headers' => ['ID usuario', 'Nombre', 'Apellido', 'Correo', 'Rol', 'Estado', 'Registrado el'],
            'query' => "SELECT u.id, u.nombre, u.apellido, u.email, r.nombre, u.estado, u.created_at
                        FROM usuario u
                        INNER JOIN rol r ON r.id = u.rol_id
                        ORDER BY u.created_at DESC, u.id DESC",
        ],
    ];

    if ($dataset === 'summary') {
        $metrics = fetchDashboardMetrics();
        if ($metrics === null) {
            return null;
        }

        return [
            'filename' => 'oficioclick_resumen.csv',
            'headers' => ['Indicador', 'Valor'],
            'rows' => [
                ['Usuarios registrados', $metrics['users_total']],
                ['Servicios activos', $metrics['active_services']],
                ['Servicios disponibles', $metrics['available_services']],
                ['Reservas registradas', $metrics['reservations_total']],
                ['Reservas pendientes', $metrics['reservations_pending']],
                ['Reservas confirmadas', $metrics['reservations_confirmed']],
                ['Reservas finalizadas', $metrics['reservations_finished']],
                ['Reservas canceladas', $metrics['reservations_cancelled']],
                [
                    'Promedio de reseñas de servicios disponibles (sobre 5)',
                    $metrics['available_average_rating'] === null ? '' : number_format($metrics['available_average_rating'], 1, '.', ''),
                ],
                ['Número de reseñas incluidas en el promedio', $metrics['available_review_count']],
            ],
        ];
    }

    if (!isset($reports[$dataset])) {
        return null;
    }

    $db = dbConnection();
    if ($db === null) {
        return null;
    }

    $result = mysqli_query($db, $reports[$dataset]['query']);
    if ($result === false) {
        error_log('OficioClick admin CSV query failed (' . $dataset . '): ' . mysqli_error($db));
        return null;
    }

    $rows = [];
    while ($row = mysqli_fetch_row($result)) {
        $rows[] = $row;
    }
    mysqli_free_result($result);

    return [
        'filename' => $reports[$dataset]['filename'],
        'headers' => $reports[$dataset]['headers'],
        'rows' => $rows,
    ];
}

function csvSafeCell($value): string
{
    if ($value === null) {
        return '';
    }

    $value = (string) $value;
    if (preg_match('/^[\x00-\x20]*[=+\-@]/', $value) === 1) {
        return "'" . $value;
    }

    return $value;
}

function sendCsvDownload(array $csvData): bool
{
    $stream = fopen('php://temp/maxmemory:2097152', 'w+b');
    if ($stream === false) {
        error_log('OficioClick could not create a temporary stream for CSV output.');
        return false;
    }

    if (fwrite($stream, "\xEF\xBB\xBF") === false) {
        error_log('OficioClick could not write the UTF-8 BOM for CSV output.');
        fclose($stream);
        return false;
    }

    $headers = array_map('csvSafeCell', $csvData['headers']);
    if (fputcsv($stream, $headers, ',', '"', '') === false) {
        error_log('OficioClick could not write CSV headers.');
        fclose($stream);
        return false;
    }

    foreach ($csvData['rows'] as $row) {
        $safeRow = array_map('csvSafeCell', $row);
        if (fputcsv($stream, $safeRow, ',', '"', '') === false) {
            error_log('OficioClick could not write a CSV row.');
            fclose($stream);
            return false;
        }
    }

    rewind($stream);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $csvData['filename'] . '"');
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    if (fpassthru($stream) === false) {
        error_log('OficioClick could not stream the completed CSV download.');
    }
    fclose($stream);

    return true;
}

function fetchReviewsForService(int $serviceId): array
{
    $db = dbConnection();
    if ($db === null) {
        return [];
    }

    $stmt = mysqli_prepare(
        $db,
        'SELECT c.*, u.nombre, u.apellido
         FROM calificacion c
         INNER JOIN reserva r ON r.id = c.reserva_id
         INNER JOIN usuario u ON u.id = r.cliente_id
         WHERE r.servicio_id = ?
         ORDER BY c.created_at DESC'
    );
    mysqli_stmt_bind_param($stmt, 'i', $serviceId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);

    return $rows;
}

function formatMoney(float $amount): string
{
    return '$' . number_format($amount, 0, ',', '.');
}
