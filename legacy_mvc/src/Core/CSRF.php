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
        if (!Session::get('csrf_token') || !hash_equals(Session::get('csrf_token'), $token)) {
            die("CSRF token validation failed.");
        }
        return true;
    }

    public static function verifyTokenJson($token) {
        if (!Session::get('csrf_token') || !hash_equals(Session::get('csrf_token'), $token)) {
            throw new \Exception("CSRF token validation failed.");
        }
        return true;
    }
}
