<?php
namespace Helpers;

class Response {
    public static function json($success, $message, $data = null, $errors = null, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        echo json_encode([
            'success' => (bool)$success,
            'message' => $message,
            'data' => $data,
            'errors' => $errors
        ]);
        exit;
    }

    public static function success($message, $data = []) {
        self::json(true, $message, $data, null, 200);
    }

    public static function error($message, $statusCode = 400, $errors = []) {
        $errors = empty($errors) ? null : $errors;
        self::json(false, $message, null, $errors, $statusCode);
    }
}
