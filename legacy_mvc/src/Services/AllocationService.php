<?php
namespace App\Services;

use App\Repositories\AllocationRepository;
use App\Repositories\StudentRepository;
use App\Repositories\RoomRepository;
use App\Repositories\AdminRepository;
use App\Core\Session;
use App\Core\Database;
use App\Services\StudentHistoryService;
use App\Services\AuditLogger;
use Exception;
use PDO;

class AllocationService {
    private $allocRepo;
    private $studentRepo;
    private $roomRepo;
    private $adminRepo;
    private $db;

    public function __construct() {
        $this->allocRepo = new AllocationRepository();
        $this->studentRepo = new StudentRepository();
        $this->roomRepo = new RoomRepository();
        $this->adminRepo = new AdminRepository();
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllActiveAllocations() {
        return $this->allocRepo->findAllActive();
    }

    public function allocateRoom($data) {
        $studentId = $data['student_id'];
        $roomId = $data['room_id'];
        $bedNumber = (int)$data['bed_number'];
        $date = $data['joining_date'];

        $this->db->beginTransaction();

        try {
            $student = $this->studentRepo->findById($studentId);
            if (!$student || $student['status'] !== 'Active') {
                throw new Exception("Student is not active or does not exist.");
            }

            // Lock room for update to prevent race conditions in occupancy calculation
            $stmtRoom = $this->db->prepare("SELECT * FROM rooms WHERE id = ? FOR UPDATE");
            $stmtRoom->execute([$roomId]);
            $room = $stmtRoom->fetch();

            if (!$room || $room['status'] === 'Disabled') {
                throw new Exception("Room is not available.");
            }

            if ($room['occupied_beds'] >= $room['total_beds']) {
                throw new Exception("Room is full. No available beds.");
            }

            if ($bedNumber < 1 || $bedNumber > $room['total_beds']) {
                throw new Exception("Invalid bed number for this room.");
            }

            if ($this->allocRepo->getActiveAllocationByStudent($studentId, $this->db)) {
                throw new Exception("Student is already allocated to a room.");
            }

            if ($this->allocRepo->getActiveAllocationByRoomAndBed($roomId, $bedNumber, $this->db)) {
                throw new Exception("This bed is already occupied.");
            }

            // Create Allocation
            $allocationId = $this->allocRepo->create($studentId, $roomId, $bedNumber, $date, $this->db);

            // Update Room Occupancy
            $newOccupied = $room['occupied_beds'] + 1;
            $newStatus = ($newOccupied >= $room['total_beds']) ? 'Occupied' : 'Partially Occupied';
            
            $stmtUpdateRoom = $this->db->prepare("UPDATE rooms SET occupied_beds = ?, status = ? WHERE id = ?");
            $stmtUpdateRoom->execute([$newOccupied, $newStatus, $roomId]);

            // Create History Record
            $roomStr = $room['block'] . '-' . $room['room_number'];
            StudentHistoryService::record(
                $studentId,
                'ROOM_ALLOCATED',
                "Allocated to Room {$roomStr}, Bed {$bedNumber}.",
                null,
                ['room' => $roomStr, 'bed' => $bedNumber, 'joining_date' => $date],
                $this->db
            );

            $this->db->commit();

            AuditLogger::logAdminAction(
                'ROOM_ALLOCATED',
                'allocation',
                $allocationId,
                'Student ' . $student['student_id_str'] . ' allocated to room ' . $room['room_number'] . ', bed ' . $bedNumber,
                null,
                ['student_id' => $studentId, 'room_id' => $roomId, 'bed_number' => $bedNumber, 'joining_date' => $date]
            );
            
            $this->adminRepo->logAction(Session::get('admin_id'), 'Room Allocation', "Allocated {$student['student_id_str']} to Room {$room['room_number']}", $_SERVER['REMOTE_ADDR']);
            
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deallocateRoom($allocationId, $date) {
        $this->db->beginTransaction();

        try {
            $alloc = $this->allocRepo->getActiveAllocationById($allocationId, $this->db);

            if (!$alloc) {
                throw new Exception("Active allocation not found.");
            }

            $stmtRoom = $this->db->prepare("SELECT * FROM rooms WHERE id = ? FOR UPDATE");
            $stmtRoom->execute([$alloc['room_id']]);
            $room = $stmtRoom->fetch();

            if (!$room) {
                throw new Exception("Associated room not found.");
            }

            $this->allocRepo->closeAllocation($allocationId, $date, $this->db);

            // Update Room Occupancy
            $newOccupied = max(0, $room['occupied_beds'] - 1);
            $newStatus = ($newOccupied == 0) ? 'Available' : 'Partially Occupied';
            
            if ($room['status'] === 'Disabled') {
                $newStatus = 'Disabled';
            }
            
            $stmtUpdateRoom = $this->db->prepare("UPDATE rooms SET occupied_beds = ?, status = ? WHERE id = ?");
            $stmtUpdateRoom->execute([$newOccupied, $newStatus, $room['id']]);

            // Create History Record
            $roomStr = $room['block'] . '-' . $room['room_number'];
            StudentHistoryService::record(
                $alloc['student_id'],
                'ROOM_DEALLOCATED',
                "Deallocated from Room {$roomStr}, Bed {$alloc['bed_number']}.",
                ['room' => $roomStr, 'bed' => $alloc['bed_number']],
                ['leaving_date' => $date],
                $this->db
            );

            $this->db->commit();

            AuditLogger::logAdminAction(
                'ROOM_ALLOCATION_CLOSED',
                'allocation',
                $allocationId,
                'Allocation closed for student ID ' . $alloc['student_id'] . ' from room ' . $room['room_number'],
                ['room_id' => $alloc['room_id'], 'bed_number' => $alloc['bed_number'], 'joining_date' => $alloc['joining_date']],
                ['leaving_date' => $date]
            );
            
            $this->adminRepo->logAction(Session::get('admin_id'), 'Room Deallocation', "Deallocated ID: $allocationId", $_SERVER['REMOTE_ADDR']);
            
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
