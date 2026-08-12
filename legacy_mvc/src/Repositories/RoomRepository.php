<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class RoomRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll($filters = [], $limit = 50, $offset = 0) {
        $query = "SELECT * FROM rooms WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $query .= " AND (room_number LIKE ? OR block LIKE ? OR room_type LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
        }

        $query .= " ORDER BY block ASC, room_number ASC LIMIT ? OFFSET ?";
        
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
        $query = "SELECT COUNT(*) FROM rooms WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $query .= " AND (room_number LIKE ? OR block LIKE ? OR room_type LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
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
        $stmt = $this->db->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function findByRoomNumberAndBlock($roomNumber, $block, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT * FROM rooms WHERE room_number = ? AND block = ? AND id != ?");
            $stmt->execute([$roomNumber, $block, $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM rooms WHERE room_number = ? AND block = ?");
            $stmt->execute([$roomNumber, $block]);
        }
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO rooms (
            room_number, block, floor, room_type, total_beds, 
            monthly_fee, security_deposit, status
        ) VALUES (
            :room_number, :block, :floor, :room_type, :total_beds,
            :monthly_fee, :security_deposit, :status
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
        
        $sql = "UPDATE rooms SET $fieldsStr WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
}
