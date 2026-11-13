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
    if (!in_array($user['role'], $allowedRoles, true)) {
        flash('error', 'No tienes permisos para acceder a esta vista.');
        redirect('home');
    }
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

    $result = mysqli_query($db, 'SELECT id, nombre FROM categoria ORDER BY nombre');
    $categories = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    return $categories;
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

function fetchServices(array $filters = []): array
{
    $db = dbConnection();
    if ($db === null) {
        return [];
    }

    $query = 'SELECT s.*, c.nombre AS category_name, u.nombre AS worker_name, u.apellido AS worker_lastname
              FROM servicio s
              INNER JOIN categoria c ON c.id = s.categoria_id
              INNER JOIN usuario u ON u.id = s.trabajador_id
              WHERE s.estado = "activo"';

    $params = [];
    $types = '';

    if (!empty($filters['category'])) {
        $query .= ' AND c.nombre = ?';
        $params[] = normalizeCategoryName($filters['category']);
        $types .= 's';
    }

    if (!empty($filters['search'])) {
        $query .= ' AND (s.titulo LIKE ? OR s.descripcion LIKE ? OR c.nombre LIKE ?)';
        $term = '%' . trim($filters['search']) . '%';
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $types .= 'sss';
    }

    if (!empty($filters['available_only'])) {
        $query .= ' AND s.disponible = 1';
    }

    $query .= ' ORDER BY s.created_at DESC';

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

function fetchServiceById(int $id): ?array
{
    $db = dbConnection();
    if ($db === null) {
        return null;
    }

    $stmt = mysqli_prepare($db, 'SELECT s.*, c.nombre AS category_name, u.nombre AS worker_name, u.apellido AS worker_lastname, u.email AS worker_email, u.telefono AS worker_phone
        FROM servicio s
        INNER JOIN categoria c ON c.id = s.categoria_id
        INNER JOIN usuario u ON u.id = s.trabajador_id
        WHERE s.id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $service = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $service ?: null;
}

function fetchReservationsForUser(int $userId, string $role): array
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
                ORDER BY r.fecha DESC';
        $stmt = mysqli_prepare($db, $sql);
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

    $result = mysqli_query($db, 'SELECT u.*, r.nombre AS role_name FROM usuario u INNER JOIN rol r ON r.id = u.rol_id ORDER BY u.created_at DESC');
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function getAverageRating(int $serviceId): float
{
    $db = dbConnection();
    if ($db === null) {
        return 0;
    }

    $stmt = mysqli_prepare($db, 'SELECT AVG(c.estrellas) AS rating FROM calificacion c INNER JOIN reserva r ON r.id = c.reserva_id WHERE r.servicio_id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $serviceId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return (float) ($row['rating'] ?? 0);
}

function fetchReviewsForService(int $serviceId): array
{
    $db = dbConnection();
    if ($db === null) {
        return [];
    }

    $stmt = mysqli_prepare($db, 'SELECT c.*, u.nombre, u.apellido FROM calificacion c INNER JOIN usuario u ON u.id = c.cliente_id INNER JOIN reserva r ON r.id = c.reserva_id WHERE r.servicio_id = ? ORDER BY c.created_at DESC');
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
