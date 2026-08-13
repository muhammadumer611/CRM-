<?php
// Display errors in development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('APP_ROOT', dirname(__DIR__));

// Simple autoloader for PSR-4 style
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Secure default headers before any output
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Initialize Session
\App\Core\Session::init();

// Initialize Router
$router = new \App\Core\Router();

// Define Routes
require __DIR__ . '/../src/routes.php';

// Dispatch
$url = $_GET['url'] ?? '';
$router->dispatch($url);
