<?php
namespace App\Services;

use App\Repositories\RoomRepository;
use App\Repositories\AdminRepository;
use App\Core\Session;

class RoomService {
    private $roomRepo;
    private $adminRepo;

    public function __construct() {
        $this->roomRepo = new RoomRepository();
        $this->adminRepo = new AdminRepository();
    }

    public function getAllRooms($filters, $page, $perPage) {
        $this->roomRepo->reconcileOccupancy();
        $offset = ($page - 1) * $perPage;
        return [
            'data' => $this->roomRepo->findAll($filters, $perPage, $offset),
            'total' => $this->roomRepo->count($filters)
        ];
    }

    public function getRoom($id) {
        $this->roomRepo->reconcileOccupancy($id);
        return $this->roomRepo->findById($id);
    }

    public function getAllAvailableRooms() {
        $this->roomRepo->reconcileOccupancy();
        return $this->roomRepo->getAllAvailableRooms();
    }

    public function getAvailableBeds($roomId) {
        $room = $this->roomRepo->findById($roomId);
        if (!$room) {
            return ['success' => false, 'error' => 'Room not found.'];
        }

        $this->roomRepo->reconcileOccupancy($roomId);
        $room = $this->roomRepo->findById($roomId);

        $activeAllocations = $this->roomRepo->getActiveAllocationsForRoom($roomId);
        $occupiedBedsMap = [];
        foreach ($activeAllocations as $alloc) {
            $occupiedBedsMap[(int)$alloc['bed_number']] = [
                'allocation_id' => (int)$alloc['id'],
                'student_id' => (int)$alloc['student_id'],
                'student_name' => $alloc['student_name'] ?? 'Occupied',
                'student_id_str' => $alloc['student_id_str'] ?? '',
                'joining_date' => $alloc['joining_date'] ?? ''
            ];
        }

        $totalBeds = (int)$room['total_beds'];
        $beds = [];
        $occupiedBedNumbers = [];
        $availableBedNumbers = [];

        for ($i = 1; $i <= $totalBeds; $i++) {
            $isOccupied = isset($occupiedBedsMap[$i]);
            if ($isOccupied) {
                $occupiedBedNumbers[] = $i;
                $beds[] = [
                    'bed_number' => $i,
                    'is_occupied' => true,
                    'status' => 'Occupied',
                    'occupant' => $occupiedBedsMap[$i]
                ];
            } else {
                $availableBedNumbers[] = $i;
                $beds[] = [
                    'bed_number' => $i,
                    'is_occupied' => false,
                    'status' => 'Available',
                    'occupant' => null
                ];
            }
        }

        return [
            'success' => true,
            'data' => [
                'room_id' => (int)$room['id'],
                'room_number' => $room['room_number'],
                'block' => $room['block'],
                'floor' => $room['floor'],
                'room_type' => $room['room_type'],
                'monthly_fee' => (float)$room['monthly_fee'],
                'security_deposit' => (float)$room['security_deposit'],
                'total_beds' => $totalBeds,
                'occupied_beds' => count($occupiedBedNumbers),
                'available_beds' => count($availableBedNumbers),
                'status' => $room['status'],
                'occupied_bed_numbers' => $occupiedBedNumbers,
                'available_bed_numbers' => $availableBedNumbers,
                'beds' => $beds
            ]
        ];
    }

    public function createRoom($data) {
        $totalBeds = (int)($data['total_beds'] ?? 0);
        $maxBeds = (int)((require APP_ROOT . '/config/app.php')['max_room_beds'] ?? 10);
        if ($totalBeds <= 0) {
            return ['success' => false, 'error' => 'Total beds must be a positive integer greater than zero.'];
        }
        if ($totalBeds > $maxBeds) {
            return ['success' => false, 'error' => "Total beds cannot exceed {$maxBeds}."];
        }

        if (empty($data['room_number']) || empty($data['floor']) || empty($data['room_type'])) {
            return ['success' => false, 'error' => 'Please fill in all required room details.'];
        }

        if ($this->roomRepo->findByRoomNumber(trim($data['room_number']))) {
            return ['success' => false, 'error' => 'A room with this number already exists.'];
        }

        $dbData = [
            'room_number' => trim($data['room_number']),
            'block' => 'General',
            'floor' => trim($data['floor']),
            'room_type' => trim($data['room_type']),
            'total_beds' => $totalBeds,
            'monthly_fee' => 0.00,
            'security_deposit' => 0.00,
            'status' => $data['status'] ?? 'Available'
        ];

        $id = $this->roomRepo->create($dbData);
        
        if ($id) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->adminRepo->logAction(Session::get('admin_id'), 'Create Room', "Created room {$dbData['room_number']}", $ip);
            return ['success' => true, 'id' => $id];
        }
        
        return ['success' => false, 'error' => 'Failed to create room.'];
    }

    public function updateRoom($id, $data) {
        $room = $this->roomRepo->findById($id);
        if (!$room) return ['success' => false, 'error' => 'Room not found.'];

        $totalBeds = (int)($data['total_beds'] ?? 0);
        $maxBeds = (int)((require APP_ROOT . '/config/app.php')['max_room_beds'] ?? 10);
        if ($totalBeds <= 0) {
            return ['success' => false, 'error' => 'Total beds must be a positive integer greater than zero.'];
        }
        if ($totalBeds > $maxBeds) {
            return ['success' => false, 'error' => "Total beds cannot exceed {$maxBeds}."];
        }
        
        $activeOccupants = $this->roomRepo->countActiveAllocations($id);
        if ($totalBeds < $activeOccupants) {
            return ['success' => false, 'error' => "Total beds cannot be reduced below current active occupancy ({$activeOccupants} occupied)."];
        }

        if ($this->roomRepo->findByRoomNumber(trim($data['room_number']), $id)) {
            return ['success' => false, 'error' => 'A room with this number already exists.'];
        }
        
        if (($data['status'] ?? '') === 'Disabled' && $activeOccupants > 0) {
            return ['success' => false, 'error' => 'Cannot disable a room while students are currently allocated to it.'];
        }
        
        $status = $data['status'] ?? 'Available';
        if ($status !== 'Disabled') {
            if ($activeOccupants === 0) {
                $status = 'Available';
            } elseif ($activeOccupants < $totalBeds) {
                $status = 'Partially Occupied';
            } else {
                $status = 'Occupied';
            }
        }

        $dbData = [
            'room_number' => trim($data['room_number']),
            'block' => 'General',
            'floor' => trim($data['floor']),
            'room_type' => trim($data['room_type']),
            'total_beds' => $totalBeds,
            'monthly_fee' => 0.00,
            'security_deposit' => 0.00,
            'status' => $status
        ];

        if ($this->roomRepo->update($id, $dbData)) {
            $this->roomRepo->reconcileOccupancy($id);

            $changes = [];
            $oldValues = [];
            foreach ($dbData as $key => $value) {
                if (isset($room[$key]) && $room[$key] !== $value) {
                    $changes[$key] = $value;
                    $oldValues[$key] = $room[$key];
                }
            }

            if (!empty($changes)) {
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->adminRepo->logAction(Session::get('admin_id'), 'Update Room', "Updated room ID: {$id}", $ip);
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Failed to update room.'];
    }

    public function deleteRoom($id) {
        $room = $this->roomRepo->findById($id);
        if (!$room) {
            return ['success' => false, 'error' => 'Room not found.'];
        }

        if ($this->roomRepo->countActiveAllocations($id) > 0) {
            return ['success' => false, 'error' => 'This room cannot be deleted because it currently has active occupants.'];
        }

        if ($this->roomRepo->delete($id)) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Failed to delete room.'];
    }

    public function reconcileAllRooms() {
        return $this->roomRepo->reconcileOccupancy();
    }

    public function getAvailableBedsOverview($filters = []) {
        $this->roomRepo->reconcileOccupancy();
        return $this->roomRepo->getAvailableBedsOverview($filters);
    }
}
