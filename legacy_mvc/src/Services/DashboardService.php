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
            'total_collection' => 0,
            'security_total_held' => 0,
            'security_total_refunded' => 0,
            'security_total_deducted' => 0,
            'security_held_students' => 0
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

        $collectionStmt = $this->db->query("SELECT COALESCE(SUM(amount), 0) AS total_collection FROM fee_payments WHERE status <> 'Reversed' AND amount > 0");
        $stats['total_collection'] = (float)($collectionStmt->fetchColumn() ?: 0);

        $securitySummaryStmt = $this->db->query("SELECT
            COALESCE(SUM(CASE WHEN sd.status IN ('HELD', 'ADJUSTED', 'PARTIALLY_REFUNDED') THEN sd.remaining_amount ELSE 0 END), 0) AS total_held,
            COALESCE(COUNT(DISTINCT CASE WHEN sd.status IN ('HELD', 'ADJUSTED', 'PARTIALLY_REFUNDED') THEN sd.student_id END), 0) AS held_students,
            COALESCE(SUM(CASE WHEN sdt.transaction_type = 'REFUND' THEN sdt.amount ELSE 0 END), 0) AS total_refunded,
            COALESCE(SUM(CASE WHEN sdt.transaction_type IN ('ADJUSTMENT', 'FORFEIT') THEN sdt.amount ELSE 0 END), 0) AS total_deducted
            FROM security_deposits sd
            LEFT JOIN security_deposit_transactions sdt ON sdt.security_deposit_id = sd.id");
        $securitySummary = $securitySummaryStmt->fetch();
        $stats['security_total_held'] = (float)($securitySummary['total_held'] ?? 0);
        $stats['security_held_students'] = (int)($securitySummary['held_students'] ?? 0);
        $stats['security_total_refunded'] = (float)($securitySummary['total_refunded'] ?? 0);
        $stats['security_total_deducted'] = (float)($securitySummary['total_deducted'] ?? 0);

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
