<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Session;
use App\Core\View;
use App\Services\AdminSettingsService;

class AdminSettingsController {
    private $adminSettingsService;

    public function __construct() {
        Auth::check();
        $this->adminSettingsService = new AdminSettingsService();
    }

    public function index() {
        $admin = $this->adminSettingsService->getCurrentAdmin();
        if (!$admin) {
            Session::set('error', 'Admin account not found.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/login');
            exit;
        }

        View::render('admin/settings/index', [
            'title' => 'Account Settings',
            'admin' => $admin,
            'csrf_token' => CSRF::generateToken()
        ], 'admin');
    }

    public function updateUsername() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        CSRF::verifyToken($_POST['csrf_token'] ?? '');

        $newUsername = trim((string)($_POST['new_username'] ?? ''));
        $confirmUsername = trim((string)($_POST['confirm_username'] ?? ''));

        if ($newUsername === '') {
            Session::set('error', 'New username cannot be empty.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/account-settings');
            exit;
        }

        if ($newUsername !== $confirmUsername) {
            Session::set('error', 'Username confirmation does not match.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/account-settings');
            exit;
        }

        $result = $this->adminSettingsService->updateUsername($newUsername);
        if ($result['success']) {
            Session::set('success', 'Username updated successfully.');
        } else {
            Session::set('error', $result['error']);
        }

        header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/account-settings');
        exit;
    }

    public function updatePassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        CSRF::verifyToken($_POST['csrf_token'] ?? '');

        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_new_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            Session::set('error', 'Current password, new password, and confirmation are required.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/account-settings');
            exit;
        }

        $result = $this->adminSettingsService->updatePassword($currentPassword, $newPassword, $confirmPassword);
        if ($result['success']) {
            Session::set('success', 'Password updated successfully.');
        } else {
            Session::set('error', $result['error']);
        }

        header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/account-settings');
        exit;
    }
}
