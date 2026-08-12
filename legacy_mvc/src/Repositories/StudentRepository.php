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
        $query = "SELECT * FROM students WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $query .= " AND (full_name LIKE ? OR cnic LIKE ? OR student_id_str LIKE ? OR phone LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
        }

        $query .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        
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
        $query = "SELECT COUNT(*) FROM students WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $query .= " AND (full_name LIKE ? OR cnic LIKE ? OR student_id_str LIKE ? OR phone LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM students WHERE id = ?");
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
    
    public function generateStudentId() {
        $stmt = $this->db->query("SELECT id FROM students ORDER BY id DESC LIMIT 1");
        $lastId = $stmt->fetchColumn();
        $nextId = $lastId ? $lastId + 1 : 1;
        return 'STU-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    public function create($data) {
        $sql = "INSERT INTO students (
            student_id_str, full_name, cnic, phone, email, blood_group, address,
            guardian_name, guardian_phone, guardian_cnic, relation, status
        ) VALUES (
            :student_id_str, :full_name, :cnic, :phone, :email, :blood_group, :address,
            :guardian_name, :guardian_phone, :guardian_cnic, :relation, :status
        )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = :$key";
        }
        $fieldsStr = implode(', ', $fields);
        
        $data['id'] = $id;
        
        $sql = "UPDATE students SET $fieldsStr WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
}
