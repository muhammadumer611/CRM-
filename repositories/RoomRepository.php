<?php
namespace Repositories;

use Core\Database;
use PDO;

class RoomRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO rooms (room_number, block, floor, room_type, total_beds, monthly_fee, security_deposit)
            VALUES (:room_number, :block, :floor, :room_type, :total_beds, :monthly_fee, :security_deposit)
        ");
        $stmt->execute([
            'room_number' => $data['room_number'],
            'block' => $data['block'],
            'floor' => $data['floor'],
            'room_type' => $data['room_type'],
            'total_beds' => $data['total_beds'],
            'monthly_fee' => $data['monthly_fee'],
            'security_deposit' => $data['security_deposit']
        ]);
        return $this->db->lastInsertId();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM rooms WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function findByRoomNumberAndBlock($roomNumber, $block) {
        $stmt = $this->db->prepare("SELECT * FROM rooms WHERE room_number = :room_number AND block = :block");
        $stmt->execute(['room_number' => $roomNumber, 'block' => $block]);
        return $stmt->fetch();
    }

    public function search($filters) {
        $query = "SELECT * FROM rooms WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $query .= " AND status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['room_number'])) {
            $query .= " AND room_number LIKE :room_number";
            $params['room_number'] = '%' . $filters['room_number'] . '%';
        }
        if (!empty($filters['block'])) {
            $query .= " AND block = :block";
            $params['block'] = $filters['block'];
        }

        $query .= " ORDER BY block ASC, room_number ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE rooms 
            SET room_number = :room_number, block = :block, floor = :floor, 
                room_type = :room_type, total_beds = :total_beds, 
                monthly_fee = :monthly_fee, security_deposit = :security_deposit,
                status = :status
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'room_number' => $data['room_number'],
            'block' => $data['block'],
            'floor' => $data['floor'],
            'room_type' => $data['room_type'],
            'total_beds' => $data['total_beds'],
            'monthly_fee' => $data['monthly_fee'],
            'security_deposit' => $data['security_deposit'],
            'status' => $data['status']
        ]);
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE rooms SET status = :status WHERE id = :id");
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public function getStatistics() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_rooms,
                SUM(CASE WHEN status != 'Disabled' THEN 1 ELSE 0 END) as active_rooms,
                SUM(CASE WHEN status = 'Disabled' THEN 1 ELSE 0 END) as disabled_rooms,
                SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available_rooms,
                SUM(CASE WHEN status = 'Partially Occupied' THEN 1 ELSE 0 END) as partially_occupied_rooms,
                SUM(CASE WHEN status = 'Occupied' THEN 1 ELSE 0 END) as occupied_rooms,
                COALESCE(SUM(total_beds), 0) as total_beds,
                COALESCE(SUM(occupied_beds), 0) as occupied_beds,
                COALESCE(SUM(total_beds - occupied_beds), 0) as available_beds
            FROM rooms
        ");
        return $stmt->fetch();
    }
}
