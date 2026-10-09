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
                    leaving_reason, final_fee_status, remarks, checked_out_by_name, checked_out_at, processed_by_name, processed_at
                ) VALUES (
                    :original_student_id, :name, :cnic, :phone, :guardian_info, 
                    :previous_room, :previous_bed, :joining_date, :leaving_date, 
                    :leaving_reason, :final_fee_status, :remarks, :checked_out_by_name, NOW(), :processed_by_name, NOW()
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
            'remarks' => $data['remarks'] ?? null,
            'checked_out_by_name' => $data['checked_out_by_name'] ?? null,
            'processed_by_name' => $data['processed_by_name'] ?? null,
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
        $query = "SELECT SQL_CALC_FOUND_ROWS a.*,
                         (SELECT s.status FROM students s WHERE s.student_id_str = a.original_student_id LIMIT 1) AS current_student_status,
                         (SELECT CONCAT(r.block, '-', r.room_number)
                          FROM room_allocations ra
                          JOIN students s ON s.id = ra.student_id
                          JOIN rooms r ON r.id = ra.room_id
                          WHERE s.student_id_str = a.original_student_id AND ra.status = 'Active'
                          ORDER BY ra.id DESC LIMIT 1) AS current_room,
                         (SELECT ra.bed_number
                          FROM room_allocations ra
                          JOIN students s ON s.id = ra.student_id
                          WHERE s.student_id_str = a.original_student_id AND ra.status = 'Active'
                          ORDER BY ra.id DESC LIMIT 1) AS current_bed
                  FROM alumni a WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $query .= " AND (a.name LIKE :search_name OR a.cnic LIKE :search_cnic OR a.original_student_id LIKE :search_student_id)";
            $search = '%' . trim((string)$filters['search']) . '%';
            $params['search_name'] = $search;
            $params['search_cnic'] = $search;
            $params['search_student_id'] = $search;
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
