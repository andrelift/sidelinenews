<?php
// Second entry point: serves the same site under /v2 (e.g. /v2/, /v2/sport/football).
// Links and assets are generated with the /v2 prefix because SCRIPT_NAME lives there.
$_SERVER['SCRIPT_NAME'] = '/v2/index.php';
$_SERVER['PHP_SELF']    = '/v2/index.php';

$uri = $_SERVER['REQUEST_URI'] ?? '/v2/';
if (strpos($uri, '/api/index2.php') === 0) {
    $uri = '/v2' . substr($uri, strlen('/api/index2.php'));
    $_SERVER['REQUEST_URI'] = $uri;
}

require __DIR__ . '/../index.php';
