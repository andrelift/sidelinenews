<?php
// Usage: php cron/refresh.php   (cron example: */15 * * * * php /path/to/cron/refresh.php)
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__ . '/../lib/helpers.php';
require __DIR__ . '/../lib/Aggregator.php';
$agg = new Aggregator(config());
$stats = $agg->refresh(true, true);
echo json_encode($stats, JSON_PRETTY_PRINT), PHP_EOL;
