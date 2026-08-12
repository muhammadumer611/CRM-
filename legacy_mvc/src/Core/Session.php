<?php
namespace App\Core;

class Session {
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../config/app.php';
            session_name($config['session_name'] ?? 'PHPSESSID');
            
            // Security settings
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            // ini_set('session.cookie_secure', 1); // Uncomment for HTTPS in production
            
            session_start();
        }
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    public static function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    public static function destroy() {
        session_unset();
        session_destroy();
    }
    
    public static function regenerate() {
        session_regenerate_id(true);
    }
}
