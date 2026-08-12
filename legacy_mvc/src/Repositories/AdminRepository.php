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
        $stmt = $this->db->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    public function logAction($adminId, $action, $description, $ip) {
        $stmt = $this->db->prepare("INSERT INTO system_logs (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$adminId, $action, $description, $ip]);
    }
}
