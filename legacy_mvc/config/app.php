<?php
/**
 * Application Configuration
 * 
 * Update these settings based on your environment (Local XAMPP vs Production cPanel)
 */

// Dynamically determine the base URL to work on both Apache rewrite URLs and direct front-controller URLs.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$redirectUrl = $_SERVER['REDIRECT_URL'] ?? '';
$combinedPath = $requestUri . ' ' . $redirectUrl;

if (stripos($combinedPath, '/legacy_mvc/public') !== false) {
    $base_dir = '/legacy_mvc/public';
} elseif (stripos($combinedPath, '/CRM') !== false) {
    $base_dir = '/CRM';
} elseif (stripos($combinedPath, '/legacy_mvc') !== false) {
    $base_dir = '/legacy_mvc';
} else {
    $base_dir = '';
}

return [
    'app_name' => 'Hostel Management System',
    'base_url' => rtrim($protocol . '://' . $host . $base_dir, '/'),
    
    'session_name' => 'HMS_SECURE_SESSION',
    'session_timeout' => 7200,
    'login_attempt_limit' => 5,
    'login_attempt_window' => 300,
    'login_lockout_duration' => 300,
    'environment' => getenv('HMS_ENV') ?: 'production', // Set HMS_ENV=development for local diagnostics.
    'session' => [
        'name' => 'HMS_SECURE_SESSION',
        'lifetime' => 7200,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ],
    'max_room_beds' => 10
];
