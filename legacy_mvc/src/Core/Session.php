<?php
namespace App\Core;

class Session {
    public static function init() {
        if (session_status() !== PHP_SESSION_NONE) {
            self::touchLifetime();
            return;
        }

        $config = require APP_ROOT . '/config/app.php';
        $sessionConfig = $config['session'] ?? [];
        $sessionTimeout = (int)($sessionConfig['lifetime'] ?? $config['session_timeout'] ?? 7200);
        $secure = !empty($sessionConfig['secure']) || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $httponly = !empty($sessionConfig['httponly']) ? true : false;
        $samesite = $sessionConfig['samesite'] ?? 'Lax';

        session_name($sessionConfig['name'] ?? $config['session_name'] ?? 'HMS_SECURE_SESSION');
        session_set_cookie_params([
            'lifetime' => $sessionTimeout,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite,
        ]);

        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', $httponly ? '1' : '0');
        ini_set('session.cookie_secure', $secure ? '1' : '0');
        ini_set('session.cookie_samesite', $samesite);

        session_start();
        self::touchLifetime();
    }

    public static function set($key, $value) {
        self::init();
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null) {
        self::init();
        return $_SESSION[$key] ?? $default;
    }

    public static function remove($key) {
        self::init();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    public static function destroy() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];

            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 3600,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );

            session_unset();
            session_destroy();
        }
    }

    public static function regenerate() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function touchLifetime() {
        $config = require APP_ROOT . '/config/app.php';
        $timeout = (int)($config['session']['lifetime'] ?? $config['session_timeout'] ?? 7200);
        $now = time();

        if (isset($_SESSION['last_activity']) && ($now - (int)$_SESSION['last_activity']) > $timeout) {
            self::destroy();
            return;
        }

        $_SESSION['last_activity'] = $now;
    }
}
