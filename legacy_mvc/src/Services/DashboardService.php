<?php
namespace App\Services;

use App\Core\Database;

class DashboardService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getStats() {
        $stats = [
            'total_students' => 0,
            'active_students' => 0,
            'total_rooms' => 0,
            'available_beds' => 0,
            'pending_fees' => 0,
            'open_complaints' => 0
        ];

        $stmt = $this->db->query("SELECT COUNT(*) FROM students WHERE status = 'Active'");
        $stats['active_students'] = $stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COUNT(*) FROM students");
        $stats['total_students'] = $stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COUNT(*) FROM rooms WHERE status != 'Disabled'");
        $stats['total_rooms'] = $stmt->fetchColumn();

        $stmt = $this->db->query("SELECT SUM(total_beds - occupied_beds) FROM rooms WHERE status != 'Disabled'");
        $stats['available_beds'] = $stmt->fetchColumn() ?: 0;

        $stmt = $this->db->query("SELECT COUNT(*) FROM fee_records WHERE status IN ('Pending', 'Partial', 'Overdue')");
        $stats['pending_fees'] = $stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COUNT(*) FROM complaints WHERE status IN ('Open', 'In Progress')");
        $stats['open_complaints'] = $stmt->fetchColumn();

        return $stats;
    }
    
    public function getRecentActivity() {
        $stmt = $this->db->query("
            SELECT l.*, a.username 
            FROM system_logs l 
            JOIN admins a ON l.admin_id = a.id 
            ORDER BY l.created_at DESC LIMIT 5
        ");
        return $stmt->fetchAll();
    }
}
