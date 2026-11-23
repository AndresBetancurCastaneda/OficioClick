<?php

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

check(isAllowedServiceCategory('Plomería'), 'Plomería must be allowed.');
check(isAllowedServiceCategory('Electricidad'), 'Electricidad must be allowed.');
check(isAllowedServiceCategory('Obra blanca'), 'Obra blanca must be allowed.');
check(isAllowedServiceCategory('Aseo general'), 'Aseo general must be allowed.');
check(!isAllowedServiceCategory('Jardinería'), 'Outside categories must be rejected.');

$password = 'Clave2026';
check(strlen($password) >= 8, 'Password length must be validated.');
check(preg_match('/[A-Za-z]/', $password) === 1, 'Password must contain a letter.');
check(preg_match('/\d/', $password) === 1, 'Password must contain a number.');
check(password_verify($password, password_hash($password, PASSWORD_DEFAULT)), 'Password hash must verify.');

$db = dbConnection();
check($db !== null, 'Database must be available.');

$checks = [
    'SELECT COUNT(*) AS n FROM usuario' => 1,
    'SELECT COUNT(*) AS n FROM rol' => 3,
    'SELECT COUNT(*) AS n FROM categoria' => 4,
];
foreach ($checks as $query => $minimum) {
    $result = mysqli_query($db, $query);
    check($result !== false, 'Required query must execute: ' . $query);
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    check((int) ($row['n'] ?? 0) >= $minimum, 'Minimum row count must be met: ' . $query);
}

[$where, $params, $types] = serviceFilterConditions([
    'available_only' => true,
    'category' => 'Electricidad',
    'min_rating' => '1',
]);
check(str_contains($where, 's.disponible = 1'), 'Availability filter must be present.');
check(str_contains($where, 'c.nombre = ?'), 'Category filter must be present.');
check(str_contains($where, 'AVG(cal.estrellas)'), 'Rating filter must be present.');
check(strlen($types) === count($params), 'Filter parameter types must match parameters.');

$metrics = fetchDashboardMetrics();
check(is_array($metrics), 'Dashboard metrics must be available.');
check(array_key_exists('users_total', $metrics), 'User metric must exist.');
check(array_key_exists('reservations_total', $metrics), 'Reservation metric must exist.');

fwrite(STDOUT, "P0/P1 smoke checks passed.\n");
