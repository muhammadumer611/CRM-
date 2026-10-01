<?php
$requestPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

if (preg_match('#^/(?:config|core|controllers|db|helpers|repositories|routes|services|utils|logs|legacy_mvc|screenshort)(?:/|$)#i', $requestPath)
    || preg_match('#(?:^|/)\.#', $requestPath)
    || preg_match('#\.(?:sql(?:\.err)?|log|err|bak|backup|pem|key|md|txt|out|html?)$#i', $requestPath)) {
    http_response_code(404);
    exit;
}

$publicRoot = realpath(__DIR__ . '/public');
$requestedPublicFile = realpath($publicRoot . DIRECTORY_SEPARATOR . ltrim($requestPath, '/\\'));
if ($publicRoot && $requestedPublicFile && is_file($requestedPublicFile)
    && strpos($requestedPublicFile, $publicRoot . DIRECTORY_SEPARATOR) === 0) {
    return false;
}

require __DIR__ . '/public/index.php';
