<?php

$required = [
    'DB_HOST',
    'DB_PORT',
    'DB_USER',
    'DB_PASS',
    'DB_NAME',
];

foreach ($required as $variable) {
    if (getenv($variable) === false || getenv($variable) === '') {
        fwrite(STDERR, "Missing environment variable: {$variable}\n");
        exit(1);
    }
}

echo "Backup/restore environment variables are available.\n";
