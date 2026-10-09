<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class AllocationRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAllActive() {
        $query = "
            SELECT a.*, s.full_name, s.student_id_str, r.room_number, r.block
            FROM room_allocations a
            JOIN students s ON a.student_id = s.id
            JOIN rooms r ON a.room_id = r.id
            WHERE a.status = 'Active'
            ORDER BY a.created_at DESC
        ";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }

    public function getActiveAllocationByStudent($studentId, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("SELECT * FROM room_allocations WHERE student_id = ? AND status = 'Active'");
        $stmt->execute([$studentId]);
        return $stmt->fetch();
    }
    
    public function getActiveAllocationByRoomAndBed($roomId, $bedNumber, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("SELECT * FROM room_allocations WHERE room_id = ? AND bed_number = ? AND status = 'Active'");
        $stmt->execute([$roomId, $bedNumber]);
        return $stmt->fetch();
    }

    public function getActiveAllocationById($allocationId, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("SELECT * FROM room_allocations WHERE id = ? AND status = 'Active' FOR UPDATE");
        $stmt->execute([$allocationId]);
        return $stmt->fetch();
    }

    public function create($studentId, $roomId, $bedNumber, $date, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("INSERT INTO room_allocations (student_id, room_id, bed_number, joining_date, status) VALUES (?, ?, ?, ?, 'Active')");
        $stmt->execute([$studentId, $roomId, $bedNumber, $date]);
        return $db->lastInsertId();
    }

    public function closeAllocation($allocationId, $date, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("UPDATE room_allocations SET status = 'Closed', leaving_date = ? WHERE id = ?");
        return $stmt->execute([$date, $allocationId]);
    }
}
