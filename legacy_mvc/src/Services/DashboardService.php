<?php
namespace App\Services;

use App\Core\Database;

class DashboardService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getStats() {
<<<<<<< HEAD
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
=======
        // Reconcile room occupancy for accuracy
        $roomRepo = new \App\Repositories\RoomRepository();
        $roomRepo->reconcileOccupancy();
>>>>>>> 962ef01 (Update HMS)

        $stats = [];

        // Students
        $stmt = $this->db->query("SELECT COUNT(*) FROM students WHERE status = 'Active'");
        $stats['active_students'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COUNT(*) FROM students WHERE status = 'Inactive'");
        $stats['alumni_count'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COUNT(*) FROM students");
        $stats['total_students'] = (int)$stmt->fetchColumn();

        // Rooms & Beds
        $stmt = $this->db->query("SELECT COUNT(*) FROM rooms WHERE status != 'Disabled'");
        $stats['total_rooms'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COALESCE(SUM(total_beds), 0) FROM rooms WHERE status != 'Disabled'");
        $stats['total_beds'] = (int)$stmt->fetchColumn();

<<<<<<< HEAD
        $pendingStmt = $this->db->query("SELECT COALESCE(SUM((amount + additional_charges - discount) - paid_amount), 0) AS total_pending_amount, COUNT(DISTINCT student_id) AS pending_student_count FROM fee_records WHERE (amount + additional_charges - discount) > paid_amount");
        $pendingSummary = $pendingStmt->fetch();
        $stats['pending_fees'] = (float)($pendingSummary['total_pending_amount'] ?? 0);
        $stats['pending_fee_students'] = (int)($pendingSummary['pending_student_count'] ?? 0);

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
=======
        $stmt = $this->db->query("SELECT COALESCE(SUM(occupied_beds), 0) FROM rooms WHERE status != 'Disabled'");
        $stats['occupied_beds'] = (int)$stmt->fetchColumn();

        $stats['available_beds'] = $stats['total_beds'] - $stats['occupied_beds'];

        // Fees — Monthly only, NOT security deposits
        $stmt = $this->db->query("SELECT COALESCE(SUM(amount), 0) FROM fee_records WHERE charge_type = 'MONTHLY_FEE' AND YEAR(invoice_date) = YEAR(CURDATE()) AND MONTH(invoice_date) = MONTH(CURDATE())");
        $stats['this_month_expected'] = (float)$stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COALESCE(SUM(paid_amount), 0) FROM fee_records WHERE charge_type = 'MONTHLY_FEE' AND YEAR(invoice_date) = YEAR(CURDATE()) AND MONTH(invoice_date) = MONTH(CURDATE())");
        $stats['this_month_collected'] = (float)$stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COUNT(*) FROM fee_records WHERE charge_type = 'MONTHLY_FEE' AND status IN ('Pending','Partial','Overdue')");
        $stats['pending_fees'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COUNT(*) FROM fee_records WHERE charge_type = 'MONTHLY_FEE' AND status = 'Overdue'");
        $stats['overdue_fees'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COALESCE(SUM(amount - paid_amount), 0) FROM fee_records WHERE charge_type = 'MONTHLY_FEE' AND status IN ('Pending','Partial','Overdue')");
        $stats['total_outstanding'] = (float)$stmt->fetchColumn();

        // Security Deposits (completely separate)
        $stmt = $this->db->query("SELECT COALESCE(SUM(original_amount), 0) FROM security_deposits WHERE status = 'HELD'");
        $stats['security_held'] = (float)$stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COALESCE(SUM(original_amount), 0) FROM security_deposits WHERE status IN ('REFUNDED','PARTIALLY_REFUNDED')");
        $stats['security_returned'] = (float)$stmt->fetchColumn();
>>>>>>> 962ef01 (Update HMS)

        return $stats;
    }
    
    public function getRecentActivity() {
        $stmt = $this->db->query("
            SELECT l.*, a.username 
            FROM system_logs l 
            LEFT JOIN admins a ON l.admin_id = a.id 
            ORDER BY l.created_at DESC LIMIT 10
        ");
        return $stmt->fetchAll();
    }
}
