<?php
namespace Repositories;

use Core\Database;
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
            $query .= " AND h.student_id = :student_id";
            $params['student_id'] = $filters['student_id'];
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
            $query .= " AND (h.description LIKE :keyword OR h.event_type LIKE :keyword)";
            $params['keyword'] = '%' . $filters['keyword'] . '%';
        }

        $query .= " ORDER BY h.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        $totalStmt = $this->db->query("SELECT FOUND_ROWS()");
        $total = $totalStmt->fetchColumn();

        return ['data' => $data, 'total' => $total];
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

    public function getByStudentId($studentId, $limit = 50, $offset = 0) {
        return $this->search(['student_id' => $studentId], $limit, $offset);
    }

    public function getAll($limit = 50, $offset = 0) {
        return $this->search([], $limit, $offset);
    }

    public function getByEventType($eventType, $limit = 50, $offset = 0) {
        return $this->search(['event_type' => $eventType], $limit, $offset);
    }

    public function getByAdmin($adminId, $limit = 50, $offset = 0) {
        return $this->search(['admin_id' => $adminId], $limit, $offset);
    }

    public function getByDateRange($dateFrom, $dateTo, $limit = 50, $offset = 0) {
        return $this->search(['date_from' => $dateFrom, 'date_to' => $dateTo], $limit, $offset);
    }

    public function count() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM student_history");
        return $stmt->fetchColumn();
    }

    public function getRecent($limit = 10) {
        return $this->search([], $limit, 0);
    }

    public function getStatistics() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_history_records,
                SUM(CASE WHEN DATE(created_at) = CURRENT_DATE THEN 1 ELSE 0 END) as today_events,
                SUM(CASE WHEN MONTH(created_at) = MONTH(CURRENT_DATE) AND YEAR(created_at) = YEAR(CURRENT_DATE) THEN 1 ELSE 0 END) as this_month_events
            FROM student_history
        ");
        $stats = $stmt->fetch();
        
        $typeStmt = $this->db->query("SELECT event_type, COUNT(*) as count FROM student_history GROUP BY event_type");
        $stats['events_by_type'] = $typeStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        return $stats;
    }
}
