<?php
/**
 * Application Configuration
 * 
 * Update these settings based on your environment (Local XAMPP vs Production cPanel)
 */

// Dynamically determine the base URL to work on both XAMPP and cPanel
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script = $_SERVER['SCRIPT_NAME'] ?? '';

// If running from public/index.php, get the directory path up to public
$base_dir = str_replace('/index.php', '', $script);

return [
    'app_name' => 'Hostel Management System',
    
    // For XAMPP: http://localhost/legacy_mvc/public
    // For cPanel: https://yourdomain.com
    'base_url' => $protocol . '://' . $host . $base_dir,
    
    'session_name' => 'HMS_SECURE_SESSION',
    
    'environment' => 'development', // 'development' or 'production'
];
