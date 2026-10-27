<?php
// Vercel entry point (vercel-php runtime). Every request is routed here by vercel.json
// and handed to the regular front controller.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF']    = '/index.php';

$uri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($uri, '/api/index.php') === 0) {
    $uri = substr($uri, strlen('/api/index.php'));
    $_SERVER['REQUEST_URI'] = ($uri === '' || $uri[0] !== '/') ? '/' . $uri : $uri;
}

require __DIR__ . '/../index.php';
