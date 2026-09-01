<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class StudentRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll($filters = [], $limit = 50, $offset = 0) {
        $query = "SELECT s.*, ra.room_id, ra.bed_number, r.room_number, r.block
                  FROM students s
                  LEFT JOIN room_allocations ra ON s.id = ra.student_id AND ra.status = 'Active'
                  LEFT JOIN rooms r ON ra.room_id = r.id
                  WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $rawTerm = trim((string)$filters['search']);
            $searchTerm = '%' . $rawTerm . '%';
            $cleanDigits = preg_replace('/[^0-9]/', '', $rawTerm);
            
            if (strlen($cleanDigits) >= 3) {
                $cleanTerm = '%' . $cleanDigits . '%';
                $query .= " AND (s.full_name LIKE ? OR s.cnic LIKE ? OR s.cnic LIKE ? OR s.student_id_str LIKE ? OR s.phone LIKE ?)";
                $params = array_merge($params, [$searchTerm, $searchTerm, $cleanTerm, $searchTerm, $searchTerm]);
            } else {
                $query .= " AND (s.full_name LIKE ? OR s.cnic LIKE ? OR s.student_id_str LIKE ? OR s.phone LIKE ?)";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND s.status = ?";
            $params[] = $filters['status'];
        }

        $query .= " ORDER BY s.id DESC LIMIT ? OFFSET ?";
        
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
        $query = "SELECT COUNT(*) FROM students s WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $rawTerm = trim((string)$filters['search']);
            $searchTerm = '%' . $rawTerm . '%';
            $cleanDigits = preg_replace('/[^0-9]/', '', $rawTerm);
            
            if (strlen($cleanDigits) >= 3) {
                $cleanTerm = '%' . $cleanDigits . '%';
                $query .= " AND (s.full_name LIKE ? OR s.cnic LIKE ? OR s.cnic LIKE ? OR s.student_id_str LIKE ? OR s.phone LIKE ?)";
                $params = array_merge($params, [$searchTerm, $searchTerm, $cleanTerm, $searchTerm, $searchTerm]);
            } else {
                $query .= " AND (s.full_name LIKE ? OR s.cnic LIKE ? OR s.student_id_str LIKE ? OR s.phone LIKE ?)";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND s.status = ?";
            $params[] = $filters['status'];
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT s.*, 
                   ra.id AS allocation_id, ra.room_id, ra.bed_number, ra.joining_date AS allocation_date,
                   r.room_number, r.block, r.floor, r.room_type, r.monthly_fee AS room_monthly_fee
            FROM students s
            LEFT JOIN room_allocations ra ON s.id = ra.student_id AND ra.status = 'Active'
            LEFT JOIN rooms r ON ra.room_id = r.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function findByCnic($cnic, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT * FROM students WHERE cnic = ? AND id != ?");
            $stmt->execute([$cnic, $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM students WHERE cnic = ?");
            $stmt->execute([$cnic]);
        }
        return $stmt->fetch();
    }
    
    public function generateStudentId($pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->query("SELECT id FROM students ORDER BY id DESC LIMIT 1");
        $lastId = $stmt->fetchColumn();
        $nextId = $lastId ? $lastId + 1 : 1;
        return 'STU-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    public function create($data, $pdo = null) {
        $db = $pdo ?? $this->db;
        $fields = array_keys($data);
        $placeholders = array_map(fn($f) => ":$f", $fields);
        $sql = "INSERT INTO students (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        $stmt->execute($data);
        return $db->lastInsertId();
    }

    public function update($id, $data, $pdo = null) {
        $db = $pdo ?? $this->db;
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
        }
        $fieldsStr = implode(', ', $fields);
        $data['id'] = $id;
        $sql = "UPDATE students SET $fieldsStr WHERE id = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute($data);
    }

    public function getFeeHistory($studentId) {
        $stmt = $this->db->prepare("
            SELECT f.*, 
                   (SELECT COUNT(*) FROM fee_payments fp WHERE fp.invoice_id = f.id AND fp.status = 'Completed') AS payment_count
            FROM fee_records f
            WHERE f.student_id = ?
            ORDER BY f.billing_year DESC, f.billing_month DESC
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }
}
