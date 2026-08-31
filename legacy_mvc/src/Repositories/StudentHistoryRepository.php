<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class StudentHistoryRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("
            INSERT INTO student_history (student_id, event_type, description, old_value, new_value, performed_by_admin)
            VALUES (:student_id, :event_type, :description, :old_value, :new_value, :admin_id)
        ");
        $stmt->execute([
            'student_id' => $data['student_id'],
            'event_type' => $data['event_type'],
            'description' => $data['description'],
            'old_value' => $data['old_value'] ? json_encode($data['old_value']) : null,
            'new_value' => $data['new_value'] ? json_encode($data['new_value']) : null,
            'admin_id' => $data['admin_id'] ?? null
        ]);
        return $db->lastInsertId();
    }

    public function search($filters, $limit, $offset) {
        $query = "SELECT SQL_CALC_FOUND_ROWS h.*, s.full_name as student_name, s.student_id_str, a.username as admin_username
                  FROM student_history h
                  LEFT JOIN students s ON h.student_id = s.id
                  LEFT JOIN admins a ON h.performed_by_admin = a.id
                  WHERE 1=1";
        $params = [];

        if (!empty($filters['student_id'])) {
            $val = trim((string)$filters['student_id']);
            if (is_numeric($val)) {
                $query .= " AND (h.student_id = :student_id OR s.student_id_str LIKE :student_id_str)";
                $params['student_id'] = (int)$val;
                $params['student_id_str'] = '%' . $val . '%';
            } else {
                $query .= " AND s.student_id_str = :student_id_str";
                $params['student_id_str'] = $val;
            }
        }
        if (!empty($filters['event_type'])) {
            $query .= " AND h.event_type = :event_type";
            $params['event_type'] = $filters['event_type'];
        }
        if (!empty($filters['admin_id'])) {
            $query .= " AND h.performed_by_admin = :admin_id";
            $params['admin_id'] = $filters['admin_id'];
        }
        if (!empty($filters['date_from'])) {
            $query .= " AND DATE(h.created_at) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $query .= " AND DATE(h.created_at) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        if (!empty($filters['keyword'])) {
            $kw = '%' . trim((string)$filters['keyword']) . '%';
            $query .= " AND (h.description LIKE :kw1 OR h.event_type LIKE :kw2 OR s.full_name LIKE :kw3 OR s.student_id_str LIKE :kw4 OR CAST(h.student_id AS CHAR) LIKE :kw5)";
            $params['kw1'] = $kw;
            $params['kw2'] = $kw;
            $params['kw3'] = $kw;
            $params['kw4'] = $kw;
            $params['kw5'] = $kw;
        }

        $query .= " ORDER BY h.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        $totalStmt = $this->db->query("SELECT FOUND_ROWS()");
        $total = $totalStmt->fetchColumn();

        return ['data' => $data, 'total' => $total];
    }

    public function count($filters) {
        return $this->search($filters, 1, 0)['total'];
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT h.*, s.full_name as student_name, s.student_id_str, a.username as admin_username
            FROM student_history h
            LEFT JOIN students s ON h.student_id = s.id
            LEFT JOIN admins a ON h.performed_by_admin = a.id
            WHERE h.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
}
