<?php
namespace App\Services;

use App\Repositories\AdminRepository;
use App\Core\Session;

class AuthService {
    private $adminRepo;

    public function __construct() {
        $this->adminRepo = new AdminRepository();
    }

    public function login($username, $password, $ip) {
        $admin = $this->adminRepo->findByUsername($username);

        if ($admin && password_verify($password, $admin['password_hash'])) {
            Session::regenerate();
            Session::init();
            Session::set('admin_id', (int)$admin['id']);
            Session::set('admin_user', $admin['username']);
            Session::set('last_activity', time());

            $this->adminRepo->logAction((int)$admin['id'], 'Login', 'Admin logged in successfully', $ip);
            return true;
        }

        if ($admin) {
            $this->adminRepo->logAction((int)$admin['id'], 'Failed Login', 'Failed login attempt', $ip);
        }
        return false;
    }

    public function logout($ip) {
        $adminId = Session::get('admin_id');
        if ($adminId) {
            $this->adminRepo->logAction((int)$adminId, 'Logout', 'Admin logged out', $ip);
        }
        Session::destroy();
    }

    public function getFailedLoginAttempts($ip, $username) {
        return (int)($this->adminRepo->getFailedLoginAttempts($ip, $username)['data']['count'] ?? 0);
    }

    public function recordFailedLogin($ip, $username) {
        return $this->adminRepo->recordFailedLogin($ip, $username);
    }

    public function clearFailedLoginAttempts($ip, $username) {
        $this->adminRepo->clearFailedLoginAttempts($ip, $username);
    }
}
