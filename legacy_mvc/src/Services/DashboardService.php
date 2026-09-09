<?php
namespace App\Services;

use App\Core\Database;

class DashboardService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getStats() {
        // Reconcile room occupancy for accuracy
        $roomRepo = new \App\Repositories\RoomRepository();
        $roomRepo->reconcileOccupancy();

        $stats = [
            'total_students' => 0,
            'active_students' => 0,
            'alumni_count' => 0,
            'total_rooms' => 0,
            'total_beds' => 0,
            'occupied_beds' => 0,
            'available_beds' => 0,
            'pending_fees' => 0,
            'pending_fee_students' => 0,
            'overdue_fees' => 0,
            'total_collection' => 0,
            'total_outstanding' => 0,
            'this_month_expected' => 0,
            'this_month_collected' => 0,
            'security_held' => 0,
            'security_returned' => 0,
            'security_total_held' => 0,
            'security_total_refunded' => 0,
            'security_total_deducted' => 0,
            'security_held_students' => 0
        ];

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

        $stmt = $this->db->query("SELECT COALESCE(SUM(occupied_beds), 0) FROM rooms WHERE status != 'Disabled'");
        $stats['occupied_beds'] = (int)$stmt->fetchColumn();

        $stats['available_beds'] = max(0, $stats['total_beds'] - $stats['occupied_beds']);

        // Fees — Monthly invoices
        $stmt = $this->db->query("SELECT COALESCE(SUM(amount), 0) FROM fee_records WHERE charge_type = 'MONTHLY_FEE' AND YEAR(invoice_date) = YEAR(CURDATE()) AND MONTH(invoice_date) = MONTH(CURDATE())");
        $stats['this_month_expected'] = (float)$stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COALESCE(SUM(paid_amount), 0) FROM fee_records WHERE charge_type = 'MONTHLY_FEE' AND YEAR(invoice_date) = YEAR(CURDATE()) AND MONTH(invoice_date) = MONTH(CURDATE())");
        $stats['this_month_collected'] = (float)$stmt->fetchColumn();

        $stmt = $this->db->query("
            SELECT COUNT(DISTINCT fr.student_id)
            FROM fee_records fr
            JOIN students s ON s.id = fr.student_id
            WHERE s.status = 'Active'
              AND (fr.amount + fr.additional_charges - fr.discount) > fr.paid_amount
        ");
        $stats['pending_fees'] = (int)$stmt->fetchColumn();
        $stats['pending_fee_students'] = $stats['pending_fees'];

        $stmt = $this->db->query("
            SELECT COUNT(DISTINCT fr.student_id)
            FROM fee_records fr
            JOIN students s ON s.id = fr.student_id
            WHERE s.status = 'Active'
              AND (fr.amount + fr.additional_charges - fr.discount) > fr.paid_amount
              AND fr.due_date < CURDATE()
        ");
        $stats['overdue_fees'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->query("
            SELECT COALESCE(SUM((fr.amount + fr.additional_charges - fr.discount) - fr.paid_amount), 0)
            FROM fee_records fr
            JOIN students s ON s.id = fr.student_id
            WHERE s.status = 'Active'
              AND (fr.amount + fr.additional_charges - fr.discount) > fr.paid_amount
        ");
        $stats['total_outstanding'] = (float)$stmt->fetchColumn();

        $collectionStmt = $this->db->query("SELECT COALESCE(SUM(amount), 0) FROM fee_payments WHERE status <> 'Reversed' AND amount > 0");
        $stats['total_collection'] = (float)($collectionStmt->fetchColumn() ?: 0);

        // Security Deposits (completely separate)
        $stmt = $this->db->query("SELECT COALESCE(SUM(remaining_amount), 0) FROM security_deposits WHERE status IN ('HELD', 'ADJUSTED', 'PARTIALLY_REFUNDED')");
        $stats['security_held'] = (float)$stmt->fetchColumn();
        $stats['security_total_held'] = $stats['security_held'];

        $stmt = $this->db->query("SELECT COUNT(DISTINCT student_id) FROM security_deposits WHERE status IN ('HELD', 'ADJUSTED', 'PARTIALLY_REFUNDED')");
        $stats['security_held_students'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->query("SELECT COALESCE(SUM(original_amount - remaining_amount), 0) FROM security_deposits WHERE status IN ('REFUNDED','PARTIALLY_REFUNDED','DEDUCTED')");
        $stats['security_returned'] = (float)$stmt->fetchColumn();
        $stats['security_total_refunded'] = $stats['security_returned'];

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
