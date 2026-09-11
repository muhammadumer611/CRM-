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
            'paid_fees' => 0,
            'paid_fee_students' => 0,
            'pending_fees' => 0,
            'pending_fee_students' => 0,
            'overdue_fees' => 0,
            'total_outstanding' => 0,
            'this_month_collected' => 0
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

        $occupiedSql = "
            SELECT COALESCE(SUM(CASE WHEN ra.bed_number = 0 THEN r.total_beds ELSE 1 END), 0)
            FROM room_allocations ra JOIN rooms r ON r.id = ra.room_id JOIN students s ON s.id = ra.student_id AND s.status = 'Active'
            WHERE ra.status = 'Active' AND r.status != 'Disabled'
        ";
        $stmt = $this->db->query($occupiedSql);
        $stats['occupied_beds'] = (int)$stmt->fetchColumn();
        $reservedSql = "SELECT COUNT(DISTINCT CONCAT(res.room_id, ':', res.bed_number)) FROM reservations res JOIN rooms r ON r.id = res.room_id WHERE res.status IN ('PENDING', 'CONFIRMED') AND r.status != 'Disabled'";
        $stats['occupied_beds'] += (int)$this->db->query($reservedSql)->fetchColumn();

        $stats['available_beds'] = max(0, $stats['total_beds'] - $stats['occupied_beds']);

        // Fees — Monthly invoices
        $stmt = $this->db->query("SELECT COALESCE(SUM(fr.paid_amount), 0) FROM fee_records fr JOIN students s ON s.id = fr.student_id WHERE fr.charge_type = 'MONTHLY_FEE' AND s.status = 'Active' AND YEAR(fr.invoice_date) = YEAR(CURDATE()) AND MONTH(fr.invoice_date) = MONTH(CURDATE())");
        $stats['this_month_collected'] = (float)$stmt->fetchColumn();

        $stmt = $this->db->query("
            SELECT COUNT(DISTINCT s.id)
            FROM students s
            JOIN fee_records fr ON fr.student_id = s.id
            WHERE s.status = 'Active'
              AND (fr.amount + fr.additional_charges - fr.discount) <= fr.paid_amount
              AND fr.paid_amount > 0
              AND NOT EXISTS (
                  SELECT 1 FROM fee_records fr2
                  WHERE fr2.student_id = s.id
                    AND (fr2.amount + fr2.additional_charges - fr2.discount) > fr2.paid_amount
              )
        ");
        $stats['paid_fees'] = (int)$stmt->fetchColumn();
        $stats['paid_fee_students'] = $stats['paid_fees'];

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

        return $stats;
    }

    public function searchStudents(string $term, int $limit = 12): array {
        if ($term === '') {
            return [];
        }

        $like = '%' . $term . '%';
        $cleanDigits = preg_replace('/[^0-9]/', '', $term);
        $digitLike = '%' . $cleanDigits . '%';
        $sql = "
            SELECT s.id, s.full_name, s.student_id_str, s.cnic, s.phone, s.address, s.status,
                   ra.bed_number, r.room_number,
                   latest.status AS fee_status,
                   latest.pending_amount
            FROM students s
            LEFT JOIN room_allocations ra ON ra.student_id = s.id AND ra.status = 'Active'
            LEFT JOIN rooms r ON r.id = ra.room_id
            LEFT JOIN (
                SELECT fr.student_id, fr.status,
                       GREATEST(0, fr.amount + fr.additional_charges - fr.discount - fr.paid_amount) AS pending_amount
                FROM fee_records fr
                INNER JOIN (
                    SELECT student_id, MAX(id) AS latest_id
                    FROM fee_records
                    WHERE charge_type = 'MONTHLY_FEE'
                    GROUP BY student_id
                ) current_fee ON current_fee.latest_id = fr.id
            ) latest ON latest.student_id = s.id
                        WHERE s.status = 'Active'
                            AND (s.full_name LIKE ?
               OR s.cnic LIKE ?
               OR s.cnic LIKE ?
               OR s.phone LIKE ?
               OR s.student_id_str LIKE ?
               OR s.address LIKE ?
               OR r.room_number LIKE ?
               OR CAST(ra.bed_number AS CHAR) LIKE ?)
            ORDER BY CASE WHEN s.status = 'Active' THEN 0 ELSE 1 END, s.full_name ASC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $like);
        $stmt->bindValue(2, $like);
        $stmt->bindValue(3, $digitLike);
        $stmt->bindValue(4, $like);
        $stmt->bindValue(5, $like);
        $stmt->bindValue(6, $like);
        $stmt->bindValue(7, $like);
        $stmt->bindValue(8, $like);
        $stmt->bindValue(9, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
