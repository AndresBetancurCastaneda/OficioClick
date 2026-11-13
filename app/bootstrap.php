<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

session_start();

if (!ensureDatabase()) {
    $_SESSION['db_status'] = 'warning';
}
