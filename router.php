<?php
// Dev server helper:  php -S localhost:8000 router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file(__DIR__ . $path) && !preg_match('/\.php$/', $path)) { return false; }
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
