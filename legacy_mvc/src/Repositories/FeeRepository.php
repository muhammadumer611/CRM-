<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class FeeRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll($filters = [], $limit = 50, $offset = 0) {
        $query = "
            SELECT f.*, s.full_name, s.student_id_str 
            FROM fee_records f 
            JOIN students s ON f.student_id = s.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $query .= " AND (s.full_name LIKE ? OR s.student_id_str LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND f.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['month'])) {
            $query .= " AND f.billing_month = ?";
            $params[] = $filters['month'];
        }
        
        if (!empty($filters['year'])) {
            $query .= " AND f.billing_year = ?";
            $params[] = $filters['year'];
        }

        $query .= " ORDER BY f.billing_year DESC, f.billing_month DESC, f.id DESC LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($query);
        
        $i = 1;
        foreach ($params as $param) {
            $stmt->bindValue($i++, $param);
        }
        $stmt->bindValue($i++, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue($i, (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function count($filters = []) {
        $query = "
            SELECT COUNT(*) 
            FROM fee_records f 
            JOIN students s ON f.student_id = s.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $query .= " AND (s.full_name LIKE ? OR s.student_id_str LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND f.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['month'])) {
            $query .= " AND f.billing_month = ?";
            $params[] = $filters['month'];
        }
        
        if (!empty($filters['year'])) {
            $query .= " AND f.billing_year = ?";
            $params[] = $filters['year'];
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT f.*, s.full_name, s.student_id_str 
            FROM fee_records f 
            JOIN students s ON f.student_id = s.id 
            WHERE f.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function findByStudentAndMonthYear($studentId, $month, $year) {
        $stmt = $this->db->prepare("SELECT * FROM fee_records WHERE student_id = ? AND billing_month = ? AND billing_year = ?");
        $stmt->execute([$studentId, $month, $year]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO fee_records (
            student_id, billing_month, billing_year, amount, due_date, status, remarks
        ) VALUES (
            :student_id, :billing_month, :billing_year, :amount, :due_date, :status, :remarks
        )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function updatePayment($id, $paidAmount, $paymentMethod, $transactionRef, $status, $paymentDate) {
        $sql = "UPDATE fee_records SET paid_amount = paid_amount + ?, payment_method = ?, transaction_ref = ?, status = ?, payment_date = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$paidAmount, $paymentMethod, $transactionRef, $status, $paymentDate, $id]);
    }

    public function getOutstandingBalance($studentId, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("SELECT SUM(amount - paid_amount) as outstanding_balance FROM fee_records WHERE student_id = ? AND status IN ('Pending', 'Partial', 'Overdue')");
        $stmt->execute([$studentId]);
        $result = $stmt->fetchColumn();
        return $result ? (float)$result : 0.0;
    }
}
