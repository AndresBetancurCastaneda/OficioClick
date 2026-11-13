<?php

date_default_timezone_set('America/Bogota');

define('APP_ROOT', dirname(__DIR__));
define('APP_NAME', 'OficioClick');
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', (int) (getenv('DB_PORT') ?: 3306));
define('DB_USER', getenv('DB_USER') ?: 'root');
$databasePassword = getenv('DB_PASS');
define('DB_PASS', $databasePassword === false ? '' : $databasePassword);
define('DB_NAME', getenv('DB_NAME') ?: 'oficioclick');
