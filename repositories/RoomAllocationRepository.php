<?php
namespace Repositories;

use Core\Database;
use PDO;

class RoomAllocationRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("
            INSERT INTO room_allocations (student_id, room_id, bed_number, joining_date, remarks)
            VALUES (:student_id, :room_id, :bed_number, :joining_date, :remarks)
        ");
        $stmt->execute([
            'student_id' => $data['student_id'],
            'room_id' => $data['room_id'],
            'bed_number' => $data['bed_number'],
            'joining_date' => $data['joining_date'],
            'remarks' => $data['remarks'] ?? null
        ]);
        return $db->lastInsertId();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM room_allocations WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function findActiveByStudent($studentId) {
        $stmt = $this->db->prepare("SELECT * FROM room_allocations WHERE student_id = :student_id AND status = 'Active'");
        $stmt->execute(['student_id' => $studentId]);
        return $stmt->fetch();
    }

    public function isBedOccupied($roomId, $bedNumber, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("
            SELECT id FROM room_allocations 
            WHERE room_id = :room_id AND bed_number = :bed_number AND status = 'Active'
        ");
        $stmt->execute(['room_id' => $roomId, 'bed_number' => $bedNumber]);
        return $stmt->fetch() !== false;
    }

    public function getAllActive() {
        $stmt = $this->db->query("
            SELECT a.*, s.full_name, r.room_number, r.block 
            FROM room_allocations a
            JOIN students s ON a.student_id = s.id
            JOIN rooms r ON a.room_id = r.id
            WHERE a.status = 'Active'
        ");
        return $stmt->fetchAll();
    }

    public function getActiveByRoom($roomId) {
        $stmt = $this->db->prepare("
            SELECT a.*, s.full_name, s.student_id_str 
            FROM room_allocations a
            JOIN students s ON a.student_id = s.id
            WHERE a.room_id = :room_id AND a.status = 'Active'
        ");
        $stmt->execute(['room_id' => $roomId]);
        return $stmt->fetchAll();
    }

    public function getHistoryByStudent($studentId) {
        $stmt = $this->db->prepare("
            SELECT a.*, r.room_number, r.block 
            FROM room_allocations a
            JOIN rooms r ON a.room_id = r.id
            WHERE a.student_id = :student_id
            ORDER BY a.joining_date DESC
        ");
        $stmt->execute(['student_id' => $studentId]);
        return $stmt->fetchAll();
    }

    public function getHistoryByRoom($roomId) {
        $stmt = $this->db->prepare("
            SELECT a.*, s.full_name, s.student_id_str 
            FROM room_allocations a
            JOIN students s ON a.student_id = s.id
            WHERE a.room_id = :room_id
            ORDER BY a.joining_date DESC
        ");
        $stmt->execute(['room_id' => $roomId]);
        return $stmt->fetchAll();
    }

    public function getStudentsWithoutRoom() {
        $stmt = $this->db->query("
            SELECT s.* FROM students s
            LEFT JOIN room_allocations a ON s.id = a.student_id AND a.status = 'Active'
            WHERE s.status = 'Active' AND a.id IS NULL
        ");
        return $stmt->fetchAll();
    }

    public function closeAllocation($id, $leavingDate, $remarks, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("
            UPDATE room_allocations 
            SET status = 'Closed', leaving_date = :leaving_date, remarks = :remarks
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'leaving_date' => $leavingDate,
            'remarks' => $remarks
        ]);
    }

    public function changeBed($id, $newBedNumber, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("UPDATE room_allocations SET bed_number = :bed_number WHERE id = :id");
        return $stmt->execute(['id' => $id, 'bed_number' => $newBedNumber]);
    }

    public function getStatistics() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_allocations,
                SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_allocations,
                SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed_allocations
            FROM room_allocations
        ");
        return $stmt->fetch();
    }
}
