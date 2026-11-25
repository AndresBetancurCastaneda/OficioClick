<?php

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/helpers.php';

function verify(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$cities = cityOptions();
$categories = categoryOptions();
verify(count($cities) >= 10, 'Expected the Medellin metropolitan-area city list.');
verify(count($categories) === 4, 'Only the four project service categories should be offered.');
verify(findCityById((int) $cities[0]['id']), 'A listed city must be valid for registration.');
verify(isAllowedServiceCategory('Plomería'), 'Plomería must be an allowed category.');
verify(isAllowedServiceCategory('Obra blanca'), 'Obra blanca must be an allowed category.');
verify(!isAllowedServiceCategory('Jardinería'), 'Categories outside the scope must be rejected.');

[$where, $params, $types] = serviceFilterConditions([
    'category' => 'Electricidad',
    'search' => 'instalación',
    'available_only' => true,
    'min_price' => '1000',
    'max_price' => '50000',
    'min_rating' => '3',
]);
verify(strpos($where, 's.disponible = 1') !== false, 'Availability must be included in the catalog filters.');
verify(strpos($where, 's.precio >= ?') !== false, 'Minimum price must be included in the catalog filters.');
verify(strpos($where, 's.precio <= ?') !== false, 'Maximum price must be included in the catalog filters.');
verify(strpos($where, 'AVG(cal.estrellas)') !== false, 'Minimum rating must use recorded reviews.');
verify(strlen($types) === count($params), 'Every dynamic catalog filter must have a bound parameter.');

$catalogFilters = ['available_only' => true];
$serviceCount = countServices($catalogFilters);
verify($serviceCount !== null && $serviceCount >= 0, 'Catalog count must be available.');
$services = fetchServices($catalogFilters + ['per_page' => 12, 'page' => 1]);
verify(count($services) <= 12, 'Catalog pagination must respect its page size.');
foreach ($services as $service) {
    verify(isAllowedServiceCategory((string) $service['category_name']), 'Catalog results must stay within the four categories.');
    verify((float) $service['precio'] > 0, 'Published service prices must be positive.');
}

$reservations = fetchReservationsForUser(0, 'Administrador', [
    'status' => 'finalizada',
    'date_from' => '2000-01-01',
]);
foreach ($reservations as $reservation) {
    verify($reservation['estado'] === 'finalizada', 'Admin reservation status filter must be applied.');
    verify($reservation['fecha'] >= '2000-01-01', 'Admin reservation date filter must be applied.');
}

$metrics = fetchDashboardMetrics();
verify($metrics !== null, 'Dashboard metrics must be available.');
verify(isset($metrics['users_total'], $metrics['active_services'], $metrics['reservations_total']), 'Admin summary metrics must include users, active services, and reservations.');
verify(is_array(fetchAdminServices()), 'Admin service management query must return a list.');
verify(is_array(fetchAdminReviews()), 'Admin review moderation query must return a list.');
verify(is_array(fetchAdminActions()), 'Admin audit query must return a list.');
$users = fetchUsersTable();
verify(count($users) > 0, 'At least one account must be available to administer.');
$profile = fetchUserProfile((int) $users[0]['id']);
verify(
    $profile !== null
        && array_key_exists('email', $profile)
        && array_key_exists('ciudad_id', $profile),
    'Profile details must be retrievable for an existing account, including an optional city.'
);

fwrite(STDOUT, "Requirement smoke checks passed." . PHP_EOL);
