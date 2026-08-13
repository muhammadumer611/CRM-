<?php
namespace App\Core;

class CSRF {
    public static function generateToken() {
        if (!Session::get('csrf_token')) {
            Session::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return Session::get('csrf_token');
    }

    public static function verifyToken($token) {
        $expected = Session::get('csrf_token');
        $provided = is_string($token) ? $token : '';

        if (empty($expected) || !hash_equals($expected, $provided)) {
            Session::remove('csrf_token');
            Session::set('error', 'Invalid request token.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/');
            exit;
        }

        Session::remove('csrf_token');
        return true;
    }

    public static function verifyTokenJson($token) {
        $expected = Session::get('csrf_token');
        $provided = is_string($token) ? $token : '';

        if (empty($expected) || !hash_equals($expected, $provided)) {
            Session::remove('csrf_token');
            throw new \Exception('CSRF token validation failed.');
        }

        Session::remove('csrf_token');
        return true;
    }
}
