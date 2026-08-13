<?php
namespace App\Core;

class Auth {
    public static function check() {
        Session::init();

        if (!Session::get('admin_id')) {
            Session::destroy();
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/');
            exit;
        }

        Session::touchLifetime();
    }

    public static function checkAPI() {
        Session::init();

        if (!Session::get('admin_id')) {
            Session::destroy();
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized. Please log in.',
                'data' => null,
                'errors' => ['Session expired or not authenticated.']
            ]);
            exit;
        }

        Session::touchLifetime();
        return true;
    }

    public static function user() {
        Session::init();
        return Session::get('admin_user');
    }

    public static function id() {
        Session::init();
        return Session::get('admin_id');
    }
}
