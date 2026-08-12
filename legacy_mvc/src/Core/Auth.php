<?php
namespace App\Core;

class Auth {
    public static function check() {
        if (!Session::get('admin_id')) {
            header('Location: ' . (require __DIR__ . '/../../config/app.php')['base_url'] . '/');
            exit;
        }
    }

    public static function user() {
        return Session::get('admin_user');
    }

    public static function id() {
        return Session::get('admin_id');
    }
}
