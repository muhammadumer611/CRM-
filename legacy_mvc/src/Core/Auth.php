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

    public static function user() {
        Session::init();
        return Session::get('admin_user');
    }

    public static function id() {
        Session::init();
        return Session::get('admin_id');
    }
}
