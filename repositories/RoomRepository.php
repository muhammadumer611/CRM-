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
        $stmt = $this->db->prepare("SELECT r.*, COALESCE(occupancy.occupied_beds, 0) AS allocation_occupied_beds, COALESCE(reservations.reserved_beds, 0) AS active_reserved_beds FROM rooms r {$this->availabilityJoins()} WHERE r.id = :id");
        $stmt->execute(['id' => $id]);
        $room = $stmt->fetch();
        return $room ? $this->normalizeAvailability($room) : false;
    }

    public function findByRoomNumberAndBlock($roomNumber, $block) {
        $stmt = $this->db->prepare("SELECT * FROM rooms WHERE room_number = :room_number AND block = :block");
        $stmt->execute(['room_number' => $roomNumber, 'block' => $block]);
        return $stmt->fetch();
    }

    public function search($filters) {
        $query = "SELECT r.*, COALESCE(occupancy.occupied_beds, 0) AS allocation_occupied_beds, COALESCE(reservations.reserved_beds, 0) AS active_reserved_beds FROM rooms r {$this->availabilityJoins()} WHERE 1=1";
        $params = [];

        if (!empty($filters['room_number'])) {
            $query .= " AND r.room_number LIKE :room_number";
            $params['room_number'] = '%' . $filters['room_number'] . '%';
        }
        if (!empty($filters['block'])) {
            $query .= " AND r.block = :block";
            $params['block'] = $filters['block'];
        }

        $query .= " ORDER BY r.block ASC, r.room_number ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $rooms = array_map([$this, 'normalizeAvailability'], $stmt->fetchAll());
        if (!empty($filters['status'])) {
            $rooms = array_values(array_filter($rooms, static function ($room) use ($filters) {
                return $room['status'] === $filters['status'];
            }));
        }
        return $rooms;
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
                SUM(CASE WHEN r.status != 'Disabled' THEN 1 ELSE 0 END) as active_rooms,
                SUM(CASE WHEN r.status = 'Disabled' THEN 1 ELSE 0 END) as disabled_rooms,
                SUM(CASE WHEN r.status != 'Disabled' AND COALESCE(occupancy.occupied_beds, 0) + COALESCE(reservations.reserved_beds, 0) = 0 THEN 1 ELSE 0 END) as available_rooms,
                SUM(CASE WHEN r.status != 'Disabled' AND COALESCE(occupancy.occupied_beds, 0) + COALESCE(reservations.reserved_beds, 0) > 0 AND COALESCE(occupancy.occupied_beds, 0) + COALESCE(reservations.reserved_beds, 0) < r.total_beds THEN 1 ELSE 0 END) as partially_occupied_rooms,
                SUM(CASE WHEN r.status != 'Disabled' AND COALESCE(occupancy.occupied_beds, 0) + COALESCE(reservations.reserved_beds, 0) >= r.total_beds THEN 1 ELSE 0 END) as occupied_rooms,
                COALESCE(SUM(r.total_beds), 0) as total_beds,
                COALESCE(SUM(COALESCE(occupancy.occupied_beds, 0)), 0) as occupied_beds,
                COALESCE(SUM(CASE WHEN r.status = 'Disabled' THEN 0 ELSE GREATEST(0, r.total_beds - COALESCE(occupancy.occupied_beds, 0) - COALESCE(reservations.reserved_beds, 0)) END), 0) as available_beds
            FROM rooms r
            {$this->availabilityJoins()}
        ");
        return $stmt->fetch();
    }

    private function availabilityJoins() {
        return "
            LEFT JOIN (
                SELECT ra.room_id, SUM(CASE WHEN ra.bed_number = 0 THEN r.total_beds ELSE 1 END) AS occupied_beds
                FROM room_allocations ra
                JOIN rooms r ON r.id = ra.room_id
                WHERE ra.status = 'Active'
                GROUP BY ra.room_id
            ) occupancy ON occupancy.room_id = r.id
            LEFT JOIN (
                SELECT room_id, COUNT(DISTINCT bed_number) AS reserved_beds
                FROM reservations
                WHERE status IN ('PENDING', 'CONFIRMED')
                GROUP BY room_id
            ) reservations ON reservations.room_id = r.id
        ";
    }

    private function normalizeAvailability(array $room) {
        $occupiedBeds = (int)($room['allocation_occupied_beds'] ?? 0);
        $reservedBeds = (int)($room['active_reserved_beds'] ?? 0);
        $totalBeds = (int)$room['total_beds'];
        $room['occupied_beds'] = $occupiedBeds;
        $room['active_reserved_beds'] = $reservedBeds;
        $room['available_beds'] = max(0, $totalBeds - $occupiedBeds - $reservedBeds);

        if ($room['status'] !== 'Disabled') {
            $heldBeds = $occupiedBeds + $reservedBeds;
            $room['status'] = $heldBeds === 0 ? 'Available' : ($heldBeds >= $totalBeds ? 'Occupied' : 'Partially Occupied');
        }

        unset($room['allocation_occupied_beds']);
        return $room;
    }
}
