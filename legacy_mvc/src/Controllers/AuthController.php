<?php
namespace App\Controllers;

use App\Core\View;
use App\Core\CSRF;
use App\Core\Session;
use App\Services\AuthService;

class AuthController {
    private $authService;

    public function __construct() {
        $this->authService = new AuthService();
    }

    public function loginForm() {
        if (Session::get('admin_id')) {
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/dashboard');
            exit;
        }

        $error = Session::get('error');
        Session::remove('error');

        View::render('auth/login', [
            'csrf_token' => CSRF::generateToken(),
            'error' => $error
        ]);
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        CSRF::verifyToken($_POST['csrf_token'] ?? '');

        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $config = require APP_ROOT . '/config/app.php';
        $attempts = $this->authService->getFailedLoginAttempts($ip, $username);

        if ($attempts >= (int)($config['login_attempt_limit'] ?? 5)) {
            Session::set('error', 'Too many login attempts. Please try again later.');
            header('Location: ' . $config['base_url'] . '/');
            exit;
        }

        if (empty($username) || empty($password)) {
            $this->authService->recordFailedLogin($ip, $username);
            Session::set('error', 'Invalid username or password.');
            header('Location: ' . $config['base_url'] . '/');
            exit;
        }

        if ($this->authService->login($username, $password, $ip)) {
            $this->authService->clearFailedLoginAttempts($ip, $username);
            header('Location: ' . $config['base_url'] . '/dashboard');
            exit;
        }

        $this->authService->recordFailedLogin($ip, $username);
        Session::set('error', 'Invalid username or password.');
        header('Location: ' . $config['base_url'] . '/');
        exit;
    }

    public function logout() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $this->authService->logout($ip);
        header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/');
        exit;
    }
}
