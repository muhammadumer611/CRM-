<?php
namespace App\Services;

use App\Repositories\RoomRepository;
use App\Repositories\AdminRepository;
use App\Core\Session;
use App\Services\AuditLogger;

class RoomService {
    private $roomRepo;
    private $adminRepo;

    public function __construct() {
        $this->roomRepo = new RoomRepository();
        $this->adminRepo = new AdminRepository();
    }

    public function getAllRooms($filters, $page, $perPage) {
        $offset = ($page - 1) * $perPage;
        return [
            'data' => $this->roomRepo->findAll($filters, $perPage, $offset),
            'total' => $this->roomRepo->count($filters)
        ];
    }

    public function getRoom($id) {
        return $this->roomRepo->findById($id);
    }

    public function createRoom($data) {
        if ($data['total_beds'] <= 0) {
            return ['success' => false, 'error' => 'Total beds must be greater than zero.'];
        }

        if ($this->roomRepo->findByRoomNumberAndBlock($data['room_number'], $data['block'])) {
            return ['success' => false, 'error' => 'A room with this number already exists in this block.'];
        }

        $dbData = [
            'room_number' => trim($data['room_number']),
            'block' => trim($data['block']),
            'floor' => trim($data['floor']),
            'room_type' => trim($data['room_type']),
            'total_beds' => (int)$data['total_beds'],
            'monthly_fee' => (float)$data['monthly_fee'],
            'security_deposit' => (float)$data['security_deposit'],
            'status' => $data['status'] ?? 'Available'
        ];

        $id = $this->roomRepo->create($dbData);
        
        if ($id) {
            AuditLogger::logAdminAction(
                'ROOM_CREATED',
                'room',
                $id,
                'Room created: ' . $dbData['room_number'] . ' in block ' . $dbData['block'],
                null,
                $dbData
            );
            $this->adminRepo->logAction(Session::get('admin_id'), 'Create Room', "Created room {$dbData['room_number']} in Block {$dbData['block']}", $_SERVER['REMOTE_ADDR']);
            return ['success' => true, 'id' => $id];
        }
        
        return ['success' => false, 'error' => 'Failed to create room.'];
    }

    public function updateRoom($id, $data) {
        $room = $this->roomRepo->findById($id);
        if (!$room) return ['success' => false, 'error' => 'Room not found.'];

        if ($data['total_beds'] <= 0) {
            return ['success' => false, 'error' => 'Total beds must be greater than zero.'];
        }
        
        if ((int)$data['total_beds'] < $room['occupied_beds']) {
            return ['success' => false, 'error' => 'Total beds cannot be less than currently occupied beds.'];
        }

        if ($this->roomRepo->findByRoomNumberAndBlock($data['room_number'], $data['block'], $id)) {
            return ['success' => false, 'error' => 'A room with this number already exists in this block.'];
        }
        
        if ($data['status'] === 'Disabled' && $room['occupied_beds'] > 0) {
            return ['success' => false, 'error' => 'Cannot disable a room while students are allocated to it.'];
        }
        
        $status = $data['status'];
        if ($status !== 'Disabled') {
            if ($room['occupied_beds'] == 0) {
                $status = 'Available';
            } elseif ($room['occupied_beds'] < (int)$data['total_beds']) {
                $status = 'Partially Occupied';
            } else {
                $status = 'Occupied';
            }
        }

        $dbData = [
            'room_number' => trim($data['room_number']),
            'block' => trim($data['block']),
            'floor' => trim($data['floor']),
            'room_type' => trim($data['room_type']),
            'total_beds' => (int)$data['total_beds'],
            'monthly_fee' => (float)$data['monthly_fee'],
            'security_deposit' => (float)$data['security_deposit'],
            'status' => $status
        ];

        if ($this->roomRepo->update($id, $dbData)) {
            $changes = [];
            $oldValues = [];
            foreach ($dbData as $key => $value) {
                if (isset($room[$key]) && $room[$key] !== $value) {
                    $changes[$key] = $value;
                    $oldValues[$key] = $room[$key];
                }
            }

            if (!empty($changes)) {
                AuditLogger::logAdminAction(
                    'ROOM_UPDATED',
                    'room',
                    $id,
                    'Room configuration updated: ' . $dbData['room_number'],
                    $oldValues,
                    $changes
                );
            }

            $this->adminRepo->logAction(Session::get('admin_id'), 'Update Room', "Updated room ID: {$id}", $_SERVER['REMOTE_ADDR']);
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Failed to update room.'];
    }
}
