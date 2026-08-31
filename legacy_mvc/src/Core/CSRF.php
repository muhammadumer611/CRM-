<?php
namespace App\Core;

class CSRF {
    public static function generateToken() {
        Session::init();
        if (!Session::get('csrf_token')) {
            Session::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return Session::get('csrf_token');
    }

    public static function verifyToken($token) {
        Session::init();
        $expected = Session::get('csrf_token');
        $provided = is_string($token) ? $token : '';

        if (empty($expected) || empty($provided) || !hash_equals($expected, $provided)) {
            Session::set('error', 'Invalid request token. Please try again.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/');
            exit;
        }

        return true;
    }

    public static function verifyTokenJson($token) {
        Session::init();
        $expected = Session::get('csrf_token');
        $provided = is_string($token) ? $token : '';

        if (empty($expected) || empty($provided) || !hash_equals($expected, $provided)) {
            throw new \Exception('CSRF token validation failed.');
        }

        return true;
    }
}

