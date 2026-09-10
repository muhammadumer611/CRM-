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
            $query .= " AND (room_number LIKE ? OR floor LIKE ? OR room_type LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
        }

        $query .= " ORDER BY room_number ASC LIMIT ? OFFSET ?";
        
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
            $query .= " AND (room_number LIKE ? OR floor LIKE ? OR room_type LIKE ?)";
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
            ORDER BY r.room_number ASC
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
    
    public function findByRoomNumber($roomNumber, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT * FROM rooms WHERE room_number = ? AND id != ?");
            $stmt->execute([$roomNumber, $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM rooms WHERE room_number = ?");
            $stmt->execute([$roomNumber]);
        }
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

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM rooms WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getActiveAllocationsForRoom($roomId) {
        $stmt = $this->db->prepare("
            SELECT ra.id, ra.student_id, ra.room_id, ra.bed_number, ra.joining_date, ra.status,
                   s.full_name as student_name, s.student_id_str
            FROM room_allocations ra
            JOIN students s ON ra.student_id = s.id
            WHERE ra.room_id = ? AND ra.status = 'Active'
            ORDER BY ra.bed_number ASC
        ");
        $stmt->execute([$roomId]);
        return $stmt->fetchAll();
    }

    public function countActiveAllocations($roomId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM room_allocations WHERE room_id = ? AND status = 'Active'");
        $stmt->execute([$roomId]);
        return (int)$stmt->fetchColumn();
    }

    public function countTotalAllocations($roomId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM room_allocations WHERE room_id = ?");
        $stmt->execute([$roomId]);
        return (int)$stmt->fetchColumn();
    }

    public function updateOccupancyAndStatus($roomId, $occupiedBeds, $status) {
        $stmt = $this->db->prepare("UPDATE rooms SET occupied_beds = :occupied_beds, status = :status WHERE id = :id");
        return $stmt->execute([
            'id' => $roomId,
            'occupied_beds' => (int)$occupiedBeds,
            'status' => $status
        ]);
    }

    public function getAllAvailableRooms() {
        $stmt = $this->db->query("
            SELECT * FROM rooms 
            WHERE status != 'Disabled' AND status != 'Occupied' AND total_beds > occupied_beds
            ORDER BY room_number ASC
        ");
        return $stmt->fetchAll();
    }

    public function reconcileOccupancy($roomId = null) {
        if ($roomId) {
            $room = $this->findById($roomId);
            if (!$room) return false;
            $activeCount = $this->countActiveAllocations($roomId);
            $status = $room['status'];
            if ($status !== 'Disabled') {
                if ($activeCount === 0) {
                    $status = 'Available';
                } elseif ($activeCount >= (int)$room['total_beds']) {
                    $status = 'Occupied';
                } else {
                    $status = 'Partially Occupied';
                }
            }
            $this->updateOccupancyAndStatus($roomId, $activeCount, $status);
            return true;
        }

        $rooms = $this->db->query("SELECT id, total_beds, status FROM rooms")->fetchAll();
        foreach ($rooms as $r) {
            $activeCount = $this->countActiveAllocations($r['id']);
            $status = $r['status'];
            if ($status !== 'Disabled') {
                if ($activeCount === 0) {
                    $status = 'Available';
                } elseif ($activeCount >= (int)$r['total_beds']) {
                    $status = 'Occupied';
                } else {
                    $status = 'Partially Occupied';
                }
            }
            $this->updateOccupancyAndStatus($r['id'], $activeCount, $status);
        }
        return true;
    }

    public function getAvailableBedsOverview($filters = []) {
        $query = "SELECT * FROM rooms WHERE status != 'Disabled'";
        $params = [];

        if (!empty($filters['search'])) {
            $rawTerm = trim((string)$filters['search']);
            $searchTerm = '%' . $rawTerm . '%';
            $query .= " AND (room_number LIKE ? OR block LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['room_type'])) {
            $query .= " AND room_type = ?";
            $params[] = $filters['room_type'];
        }

        if (!empty($filters['floor'])) {
            $query .= " AND floor = ?";
            $params[] = $filters['floor'];
        }

        $query .= " ORDER BY block ASC, room_number ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $rooms = $stmt->fetchAll();

        $resultRooms = [];
        $totalAvailableBeds = 0;

        foreach ($rooms as $room) {
            $roomId = (int)$room['id'];
            $totalBeds = (int)$room['total_beds'];

            $stmtAlloc = $this->db->prepare("
                SELECT ra.id, ra.bed_number, ra.student_id, ra.joining_date,
                       s.full_name as student_name, s.student_id_str
                FROM room_allocations ra
                JOIN students s ON ra.student_id = s.id
                WHERE ra.room_id = ? AND ra.status = 'Active'
                ORDER BY ra.bed_number ASC
            ");
            $stmtAlloc->execute([$roomId]);
            $activeAllocations = $stmtAlloc->fetchAll();

            $occupiedBedsMap = [];
            $hasFullRoomAlloc = false;

            foreach ($activeAllocations as $alloc) {
                $bedNum = (int)$alloc['bed_number'];
                if ($bedNum === 0) {
                    $hasFullRoomAlloc = true;
                } else {
                    $occupiedBedsMap[$bedNum] = $alloc;
                }
            }

            $availableBedNumbers = [];
            $occupiedBedNumbers = [];

            if ($hasFullRoomAlloc) {
                for ($i = 1; $i <= $totalBeds; $i++) {
                    $occupiedBedNumbers[] = $i;
                }
            } else {
                for ($i = 1; $i <= $totalBeds; $i++) {
                    if (isset($occupiedBedsMap[$i])) {
                        $occupiedBedNumbers[] = $i;
                    } else {
                        $availableBedNumbers[] = $i;
                    }
                }
            }

            $availableCount = count($availableBedNumbers);
            $occupiedCount = count($occupiedBedNumbers);

            $roomStatus = $room['status'];
            if ($roomStatus !== 'Disabled') {
                if ($occupiedCount === 0) {
                    $roomStatus = 'Available';
                } elseif ($occupiedCount >= $totalBeds) {
                    $roomStatus = 'Occupied';
                } else {
                    $roomStatus = 'Partially Occupied';
                }
            }

            if ($availableCount > 0) {
                $resultRooms[] = [
                    'id' => $roomId,
                    'room_number' => $room['room_number'],
                    'block' => $room['block'],
                    'floor' => $room['floor'],
                    'room_type' => $room['room_type'],
                    'total_beds' => $totalBeds,
                    'occupied_beds' => $occupiedCount,
                    'available_beds' => $availableCount,
                    'status' => $roomStatus,
                    'available_bed_numbers' => $availableBedNumbers,
                    'occupied_bed_numbers' => $occupiedBedNumbers
                ];
                $totalAvailableBeds += $availableCount;
            }
        }

        $distinctTypes = $this->db->query("SELECT DISTINCT room_type FROM rooms WHERE status != 'Disabled' AND room_type IS NOT NULL AND room_type != '' ORDER BY room_type ASC")->fetchAll(PDO::FETCH_COLUMN);
        $distinctFloors = $this->db->query("SELECT DISTINCT floor FROM rooms WHERE status != 'Disabled' AND floor IS NOT NULL AND floor != '' ORDER BY floor ASC")->fetchAll(PDO::FETCH_COLUMN);

        return [
            'rooms' => $resultRooms,
            'total_available_beds' => $totalAvailableBeds,
            'room_types' => $distinctTypes,
            'floors' => $distinctFloors
        ];
    }
}

