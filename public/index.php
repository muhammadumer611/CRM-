<?php
// Central Entry Point for all API Requests
require_once __DIR__ . '/../config/config.php';

// Very basic autoloader for our namespaces (Core, Services, Models, etc.)
spl_autoload_register(function ($class) {
    $prefix = '';
    $base_dir = __DIR__ . '/../';
    
    // Replace namespace separators with directory separators in the relative class name
    $file = $base_dir . str_replace('\\', '/', $class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Set global exception handler to prevent leaking stack traces
set_exception_handler(function (\Throwable $e) {
    \Core\Logger::exception($e);
    
    $config = require __DIR__ . '/../config/config.php';
    $message = $config['debug'] ? $e->getMessage() : 'An internal server error occurred.';
    
    \Core\Response::error($message, 500);
});

session_start();

// Parse Request Body for JSON payloads
$requestBody = [];
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $json = file_get_contents('php://input');
    $requestBody = json_decode($json, true) ?? [];
} else {
    $requestBody = $_POST;
}

// Load API Routes
if (file_exists(__DIR__ . '/../routes/api.php')) {
    require_once __DIR__ . '/../routes/api.php';
} else {
    \Core\Response::error('API Routes not configured.', 500);
}
