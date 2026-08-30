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

    public function findAllWithAvailability() {
        $query = "
            SELECT r.*,
                   COUNT(ra.id) AS active_allocations,
                   COALESCE(SUM(CASE
                       WHEN ra.status = 'Active' AND ra.bed_number = 0 THEN r.total_beds
                       WHEN ra.status = 'Active' AND ra.bed_number > 0 THEN 1
                       ELSE 0
                   END), 0) AS active_occupied_beds,
                   COALESCE(SUM(CASE
                       WHEN ra.status = 'Active' AND ra.bed_number = 0 THEN 1
                       ELSE 0
                   END), 0) AS full_room_allocations,
                   (r.total_beds - COALESCE(SUM(CASE
                       WHEN ra.status = 'Active' AND ra.bed_number = 0 THEN r.total_beds
                       WHEN ra.status = 'Active' AND ra.bed_number > 0 THEN 1
                       ELSE 0
                   END), 0)) AS available_beds
            FROM rooms r
            LEFT JOIN room_allocations ra ON ra.room_id = r.id AND ra.status = 'Active'
            WHERE r.status != 'Disabled'
            GROUP BY r.id
            ORDER BY r.block ASC, r.room_number ASC
        ";

        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }

    public function findAvailableForAdmission($limit = 1000, $offset = 0) {
        return $this->findAvailableRoomsForAdmission('BED', $limit, $offset);
    }

    public function findAvailableRoomsForAdmission($allocationType = 'BED', $limit = 1000, $offset = 0) {
        $rooms = $this->findAllWithAvailability();
        $type = strtoupper(trim((string)$allocationType));
        $filtered = [];

        foreach ($rooms as $room) {
            $totalBeds = (int)($room['total_beds'] ?? 0);
            $activeOccupied = (int)($room['active_occupied_beds'] ?? 0);
            $cachedOccupied = (int)($room['occupied_beds'] ?? 0);
            $effectiveOccupied = max($activeOccupied, $cachedOccupied);
            $availableBeds = max(0, $totalBeds - $effectiveOccupied);
            $activeAllocations = (int)($room['active_allocations'] ?? 0);
            $fullRoomAllocations = (int)($room['full_room_allocations'] ?? 0);
            $isEligible = false;

            if ($type === 'FULL_ROOM') {
                $isEligible = $activeAllocations === 0 && $fullRoomAllocations === 0 && $effectiveOccupied === 0;
            } else {
                $isEligible = $totalBeds > 0 && $effectiveOccupied < $totalBeds && $availableBeds > 0;
            }

            if ($isEligible) {
                $filtered[] = $room;
            }
        }

        return array_slice($filtered, (int)$offset, (int)$limit);
    }

    public function getAvailableBedsForRoom($roomId) {
        $room = $this->findById($roomId);
        if (!$room) {
            return [];
        }

        $summary = $this->db->prepare("
            SELECT
                COUNT(*) AS active_allocations,
                COALESCE(SUM(CASE
                    WHEN bed_number = 0 THEN :total_beds
                    WHEN bed_number > 0 THEN 1
                    ELSE 0
                END), 0) AS effective_occupied_beds,
                COALESCE(SUM(CASE WHEN bed_number = 0 THEN 1 ELSE 0 END), 0) AS full_room_allocations
            FROM room_allocations
            WHERE room_id = :room_id AND status = 'Active'
        ");
        $summary->execute([
            'total_beds' => (int)$room['total_beds'],
            'room_id' => (int)$roomId,
        ]);
        $row = $summary->fetch();
        $effectiveOccupied = max((int)($row['effective_occupied_beds'] ?? 0), (int)($room['occupied_beds'] ?? 0));
        $fullRoomAllocations = (int)($row['full_room_allocations'] ?? 0);

        if ($fullRoomAllocations > 0 || $effectiveOccupied >= (int)$room['total_beds']) {
            return [];
        }

        $occupied = [];
        $stmt = $this->db->prepare("SELECT DISTINCT bed_number FROM room_allocations WHERE room_id = ? AND status = 'Active' AND bed_number > 0 ORDER BY bed_number ASC");
        $stmt->execute([$roomId]);
        foreach ($stmt->fetchAll() as $rowBed) {
            if ((int)$rowBed['bed_number'] > 0) {
                $occupied[(int)$rowBed['bed_number']] = true;
            }
        }

        $available = [];
        for ($bed = 1; $bed <= (int)$room['total_beds']; $bed++) {
            if (!isset($occupied[$bed])) {
                $available[] = $bed;
            }
        }

        return $available;
    }

    public function getRoomAvailabilityMeta($roomId) {
        $room = $this->findById($roomId);
        if (!$room) {
            return [];
        }

        $summary = $this->db->prepare("
            SELECT
                COUNT(*) AS active_allocations,
                COALESCE(SUM(CASE
                    WHEN bed_number = 0 THEN :total_beds
                    WHEN bed_number > 0 THEN 1
                    ELSE 0
                END), 0) AS effective_occupied_beds
            FROM room_allocations
            WHERE room_id = :room_id AND status = 'Active'
        ");
        $summary->execute([
            'total_beds' => (int)$room['total_beds'],
            'room_id' => (int)$roomId,
        ]);
        $row = $summary->fetch();
        $effectiveOccupied = (int)($row['effective_occupied_beds'] ?? 0);

        return [
            'room_id' => (int)$roomId,
            'total_beds' => (int)$room['total_beds'],
            'active_allocations' => (int)($row['active_allocations'] ?? 0),
            'effective_occupied_beds' => $effectiveOccupied,
            'available_beds' => max(0, (int)$room['total_beds'] - $effectiveOccupied),
            'occupied_bed_numbers' => $this->getOccupiedBedNumbersForRoom($roomId),
            'available_bed_numbers' => $this->getAvailableBedsForRoom($roomId),
        ];
    }

    public function getOccupiedBedNumbersForRoom($roomId) {
        $stmt = $this->db->prepare("SELECT DISTINCT bed_number FROM room_allocations WHERE room_id = ? AND status = 'Active' AND bed_number > 0 ORDER BY bed_number ASC");
        $stmt->execute([$roomId]);
        $beds = [];
        foreach ($stmt->fetchAll() as $row) {
            $beds[] = (int)$row['bed_number'];
        }
        return $beds;
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
