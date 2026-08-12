<?php
// Display errors in development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// Initialize Session
\App\Core\Session::init();

// Initialize Router
$router = new \App\Core\Router();

// Define Routes
require __DIR__ . '/../src/routes.php';

// Dispatch
$url = $_GET['url'] ?? '';
$router->dispatch($url);
