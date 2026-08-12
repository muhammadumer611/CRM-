<?php
namespace Repositories;

use Core\Database;
use PDO;

class FeeRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO fee_records (student_id, billing_month, billing_year, amount, due_date, status, remarks)
            VALUES (:student_id, :billing_month, :billing_year, :amount, :due_date, :status, :remarks)
        ");
        $stmt->execute([
            'student_id' => $data['student_id'],
            'billing_month' => $data['billing_month'],
            'billing_year' => $data['billing_year'],
            'amount' => $data['amount'],
            'due_date' => $data['due_date'],
            'status' => $data['status'],
            'remarks' => $data['remarks'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM fee_records WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function findByStudentAndBillingPeriod($studentId, $month, $year) {
        $stmt = $this->db->prepare("
            SELECT * FROM fee_records 
            WHERE student_id = :student_id AND billing_month = :month AND billing_year = :year
        ");
        $stmt->execute(['student_id' => $studentId, 'month' => $month, 'year' => $year]);
        return $stmt->fetch();
    }

    public function search($filters) {
        $query = "SELECT f.*, s.full_name, s.student_id_str 
                  FROM fee_records f 
                  JOIN students s ON f.student_id = s.id 
                  WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $query .= " AND f.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['student_id'])) {
            $query .= " AND f.student_id = :student_id";
            $params['student_id'] = $filters['student_id'];
        }
        if (!empty($filters['month'])) {
            $query .= " AND f.billing_month = :month";
            $params['month'] = $filters['month'];
        }
        if (!empty($filters['year'])) {
            $query .= " AND f.billing_year = :year";
            $params['year'] = $filters['year'];
        }

        $query .= " ORDER BY f.billing_year DESC, f.billing_month DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getStatistics() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_fee_records,
                COALESCE(SUM(amount), 0) as total_amount,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM(amount - paid_amount), 0) as total_outstanding,
                SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) as paid_records,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_records,
                SUM(CASE WHEN status = 'Partial' THEN 1 ELSE 0 END) as partial_records,
                SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) as overdue_records
            FROM fee_records
        ");
        return $stmt->fetch();
    }

    public function getStudentFeeSummary($studentId) {
        $stmt = $this->db->prepare("
            SELECT 
                s.id as student_id,
                s.full_name as student_name,
                COALESCE(SUM(f.amount), 0) as total_fee_amount,
                COALESCE(SUM(f.paid_amount), 0) as total_paid,
                COALESCE(SUM(f.amount - f.paid_amount), 0) as total_outstanding,
                SUM(CASE WHEN f.status = 'Paid' THEN 1 ELSE 0 END) as paid_records,
                SUM(CASE WHEN f.status = 'Pending' THEN 1 ELSE 0 END) as pending_records,
                SUM(CASE WHEN f.status = 'Partial' THEN 1 ELSE 0 END) as partial_records,
                SUM(CASE WHEN f.status = 'Overdue' THEN 1 ELSE 0 END) as overdue_records,
                MAX(f.payment_date) as latest_payment_date
            FROM students s
            LEFT JOIN fee_records f ON s.id = f.student_id
            WHERE s.id = :id
            GROUP BY s.id, s.full_name
        ");
        $stmt->execute(['id' => $studentId]);
        return $stmt->fetch();
    }
}
