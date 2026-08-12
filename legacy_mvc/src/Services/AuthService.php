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
            Session::set('admin_id', $admin['id']);
            Session::set('admin_user', $admin['username']);
            
            $this->adminRepo->logAction($admin['id'], 'Login', 'Admin logged in successfully', $ip);
            return true;
        }
        
        if ($admin) {
             $this->adminRepo->logAction($admin['id'], 'Failed Login', 'Failed login attempt', $ip);
        }
        return false;
    }

    public function logout($ip) {
        if (Session::get('admin_id')) {
            $this->adminRepo->logAction(Session::get('admin_id'), 'Logout', 'Admin logged out', $ip);
        }
        Session::destroy();
    }
}
