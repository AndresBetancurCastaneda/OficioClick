<?php

require_once __DIR__ . '/../app/helpers.php';

session_start();

$token = csrfToken();
if (strlen($token) !== 64 || !ctype_xdigit($token)) {
    fwrite(STDERR, "CSRF token must be 32 random bytes encoded as hexadecimal." . PHP_EOL);
    exit(1);
}
if (!verifyCsrfToken($token)) {
    fwrite(STDERR, "The current CSRF token must validate." . PHP_EOL);
    exit(1);
}
if (strpos(csrfField(), 'name="csrf_token"') === false) {
    fwrite(STDERR, "CSRF forms must include the hidden token field." . PHP_EOL);
    exit(1);
}
if (verifyCsrfToken($token . '0') || verifyCsrfToken(null) || verifyCsrfToken(['invalid'])) {
    fwrite(STDERR, "Invalid CSRF token values must be rejected." . PHP_EOL);
    exit(1);
}
if (!isValidAccountPassword('Clave2026')) {
    fwrite(STDERR, "A password with a letter, number, and sufficient length must be accepted." . PHP_EOL);
    exit(1);
}
if (
    isValidAccountPassword('Clave26')
    || isValidAccountPassword('contraseña-larga')
    || isValidAccountPassword('123456789')
) {
    fwrite(STDERR, "Weak passwords must be rejected." . PHP_EOL);
    exit(1);
}
rotateCsrfToken();
if (csrfToken() === $token) {
    fwrite(STDERR, "The CSRF token must rotate after authentication." . PHP_EOL);
    exit(1);
}

$sessionUser = ['id' => 42, 'role' => 'Trabajador'];
$activeWorker = ['id' => 42, 'role_name' => 'Trabajador', 'estado' => 'activo'];
$inactiveWorker = ['id' => 42, 'role_name' => 'Trabajador', 'estado' => 'inactivo'];
$changedRole = ['id' => 42, 'role_name' => 'Cliente', 'estado' => 'activo'];

if (!sessionMatchesActiveUser($sessionUser, $activeWorker)) {
    fwrite(STDERR, "An active account with the same role must retain its session." . PHP_EOL);
    exit(1);
}
if (
    sessionMatchesActiveUser($sessionUser, $inactiveWorker)
    || sessionMatchesActiveUser($sessionUser, $changedRole)
) {
    fwrite(STDERR, "Inactive or role-changed accounts must not retain their session." . PHP_EOL);
    exit(1);
}

session_destroy();
fwrite(STDOUT, "Security helper smoke checks passed." . PHP_EOL);
