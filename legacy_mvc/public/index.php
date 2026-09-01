<?php
define('APP_ROOT', dirname(__DIR__));

$appConfig = file_exists(APP_ROOT . '/config/app.php') ? require APP_ROOT . '/config/app.php' : ['environment' => 'development'];
$isProduction = ($appConfig['environment'] ?? 'development') === 'production';

// Ensure logs directory exists
$logsDir = APP_ROOT . '/logs';
if (!is_dir($logsDir)) {
    @mkdir($logsDir, 0755, true);
}
$errorLogFile = $logsDir . '/error.log';

if ($isProduction) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    ini_set('error_log', $errorLogFile);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    ini_set('error_log', $errorLogFile);
}

// Global exception handler
set_exception_handler(function (\Throwable $e) use ($isProduction, $errorLogFile) {
    $logMessage = sprintf(
        "[%s] Uncaught Exception %s: \"%s\" in %s on line %d\nStack trace:\n%s\n",
        date('Y-m-d H:i:s'),
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    @error_log($logMessage, 3, $errorLogFile);

    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $isProduction ? 'An unexpected internal error occurred.' : $e->getMessage()
        ]);
        exit;
    }

    http_response_code(500);
    if ($isProduction) {
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>System Error</title><style>body{background:#0f172a;color:#f8fafc;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}div{text-align:center;padding:2rem;border:1px solid #334155;border-radius:12px;background:#1e293b;}h1{color:#ef4444;}p{color:#94a3b8;}</style></head><body><div><h1>System Notice</h1><p>An unexpected error occurred. Our team has been notified.</p><a href="/" style="color:#38bdf8;text-decoration:none;">Return to Dashboard</a></div></body></html>';
    } else {
        echo '<!DOCTYPE html><html><head><title>Exception</title><style>body{font-family:sans-serif;padding:2rem;background:#1e293b;color:#f8fafc;}pre{background:#0f172a;padding:1rem;border-radius:8px;overflow:auto;border:1px solid #334155;color:#38bdf8;}</style></head><body>';
        echo '<h1 style="color:#ef4444;">Uncaught Exception: ' . htmlspecialchars($e->getMessage()) . '</h1>';
        echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ' on line ' . $e->getLine() . '</p>';
        echo '<h3>Stack Trace:</h3><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</body></html>';
    }
    exit;
});

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
if ($url === '' && isset($_SERVER['REQUEST_URI'])) {
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
    $basePath = parse_url($appConfig['base_url'] ?? '', PHP_URL_PATH) ?: '';
    if ($basePath !== '' && strpos($requestPath, $basePath) === 0) {
        $url = substr($requestPath, strlen($basePath));
    } else {
        $url = $requestPath;
    }
}
$url = trim($url, '/');
$router->dispatch($url);

