<?php
// Minimal PHP-FPM test — no Laravel, no extensions
header('Content-Type: application/json');
echo json_encode([
    'pong' => true,
    'php'  => PHP_VERSION,
    'time' => microtime(true),
]);
