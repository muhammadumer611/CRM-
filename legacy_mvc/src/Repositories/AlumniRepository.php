<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class AlumniRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data, $pdo = null) {
        $db = $pdo ?? $this->db;
        $sql = "INSERT INTO alumni (
                    original_student_id, name, cnic, phone, guardian_info, 
                    previous_room, previous_bed, joining_date, leaving_date, 
                    leaving_reason, final_fee_status, remarks
                ) VALUES (
                    :original_student_id, :name, :cnic, :phone, :guardian_info, 
                    :previous_room, :previous_bed, :joining_date, :leaving_date, 
                    :leaving_reason, :final_fee_status, :remarks
                )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'original_student_id' => $data['original_student_id'],
            'name' => $data['name'],
            'cnic' => $data['cnic'],
            'phone' => $data['phone'],
            'guardian_info' => $data['guardian_info'],
            'previous_room' => $data['previous_room'],
            'previous_bed' => $data['previous_bed'],
            'joining_date' => $data['joining_date'],
            'leaving_date' => $data['leaving_date'],
            'leaving_reason' => $data['leaving_reason'],
            'final_fee_status' => $data['final_fee_status'],
            'remarks' => $data['remarks'] ?? null
        ]);
        return $db->lastInsertId();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM alumni WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findByOriginalStudentId($studentIdStr) {
        $stmt = $this->db->prepare("SELECT * FROM alumni WHERE original_student_id = ?");
        $stmt->execute([$studentIdStr]);
        return $stmt->fetch();
    }

    public function findAll($filters = [], $limit = 50, $offset = 0) {
        $query = "SELECT SQL_CALC_FOUND_ROWS * FROM alumni WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $query .= " AND (name LIKE :search OR cnic LIKE :search OR original_student_id LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $query .= " ORDER BY created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        $totalStmt = $this->db->query("SELECT FOUND_ROWS()");
        $total = $totalStmt->fetchColumn();

        return ['data' => $data, 'total' => $total];
    }
}
