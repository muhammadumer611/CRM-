<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class ComplaintRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll($filters = [], $limit = 50, $offset = 0) {
        $query = "
            SELECT c.*, s.full_name, s.student_id_str 
            FROM complaints c 
            JOIN students s ON c.student_id = s.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $query .= " AND (s.full_name LIKE ? OR s.student_id_str LIKE ? OR c.subject LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND c.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $query .= " AND c.priority = ?";
            $params[] = $filters['priority'];
        }

        $query .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
        
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
            FROM complaints c 
            JOIN students s ON c.student_id = s.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $query .= " AND (s.full_name LIKE ? OR s.student_id_str LIKE ? OR c.subject LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND c.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $query .= " AND c.priority = ?";
            $params[] = $filters['priority'];
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT c.*, s.full_name, s.student_id_str 
            FROM complaints c 
            JOIN students s ON c.student_id = s.id 
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function update($id, $status, $adminResponse) {
        $sql = "UPDATE complaints SET status = ?, admin_response = ?, resolved_at = CASE WHEN ? IN ('Resolved', 'Closed') AND resolved_at IS NULL THEN CURRENT_TIMESTAMP ELSE resolved_at END WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $adminResponse, $status, $id]);
    }
}
