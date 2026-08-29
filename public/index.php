<?php
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (strpos($uri, '/api/') === 0 || $uri === '/api' || $uri === '/health') {
    // API backend entry point
    require_once __DIR__ . '/../config/config.php';

    spl_autoload_register(function ($class) {
        $prefix = '';
        $base_dir = __DIR__ . '/../';
        $file = $base_dir . str_replace('\\', '/', $class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });

    set_exception_handler(function (\Throwable $e) {
        \Core\Logger::exception($e);
        $config = require __DIR__ . '/../config/config.php';
        $message = $config['debug'] ? $e->getMessage() : 'An internal server error occurred.';
        \Core\Response::error($message, 500);
    });

    session_start();

    $requestBody = [];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $json = file_get_contents('php://input');
        $requestBody = json_decode($json, true) ?? [];
    } else {
        $requestBody = $_POST;
    }

    if (file_exists(__DIR__ . '/../routes/api.php')) {
        require_once __DIR__ . '/../routes/api.php';
    } else {
        \Core\Response::error('API Routes not configured.', 500);
    }
    exit;
}

// Browser requests should load the legacy MVC admin UI
$_GET['url'] = trim($uri, '/');
require_once __DIR__ . '/../legacy_mvc/public/index.php';
