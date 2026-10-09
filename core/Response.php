<?php
namespace Core;

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

    public static function success($message = 'Operation successful.', $data = []) {
        self::json(true, $message, empty($data) ? new \stdClass() : $data, null, 200);
    }

    public static function error($message = 'Something went wrong.', $statusCode = 400, $errors = null) {
        $errors = empty($errors) ? new \stdClass() : $errors;
        self::json(false, $message, null, $errors, $statusCode);
    }
}
