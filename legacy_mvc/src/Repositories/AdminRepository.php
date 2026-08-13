<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class AdminRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM admins WHERE username = :username");
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }

    public function logAction($adminId, $action, $description, $ip) {
        $stmt = $this->db->prepare("INSERT INTO system_logs (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$adminId, $action, $description, $ip]);
    }

    public function getFailedLoginAttempts($ip, $username) {
        $key = 'login_attempt_' . md5($ip . ':' . strtolower(trim($username)));
        $data = $_SESSION[$key] ?? ['count' => 0, 'timestamp' => time()];
        $window = (int)(require APP_ROOT . '/config/app.php')['login_attempt_window'];

        if ((time() - (int)$data['timestamp']) > $window) {
            $data = ['count' => 0, 'timestamp' => time()];
            $_SESSION[$key] = $data;
        }

        return ['key' => $key, 'data' => $data];
    }

    public function recordFailedLogin($ip, $username) {
        $attemptState = $this->getFailedLoginAttempts($ip, $username);
        $key = $attemptState['key'];
        $attemptState['data']['count'] = (int)($attemptState['data']['count'] ?? 0) + 1;
        $attemptState['data']['timestamp'] = time();
        $_SESSION[$key] = $attemptState['data'];
        return $attemptState['data']['count'];
    }

    public function clearFailedLoginAttempts($ip, $username) {
        $key = 'login_attempt_' . md5($ip . ':' . strtolower(trim($username)));
        unset($_SESSION[$key]);
    }
}
