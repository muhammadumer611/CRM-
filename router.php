<?php
$base = '/legacy_mvc/public';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

if (file_exists(__DIR__ . $requestPath) && !is_dir(__DIR__ . $requestPath)) {
    return false;
}

if (strpos($requestPath, $base) === 0) {
    $requestPath = substr($requestPath, strlen($base));
}

$_GET['url'] = trim($requestPath, '/');
require __DIR__ . '/legacy_mvc/public/index.php';
