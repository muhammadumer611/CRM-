<?php
namespace App\Services;

use App\Repositories\AllocationRepository;
use App\Repositories\StudentRepository;
use App\Repositories\RoomRepository;
use App\Repositories\AdminRepository;
use App\Core\Session;
use App\Core\Database;
use App\Services\StudentHistoryService;
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

    public function getUnallocatedActiveStudents() {
        $stmt = $this->db->query("
            SELECT s.id, s.full_name, s.student_id_str, s.cnic, s.phone 
            FROM students s
            LEFT JOIN room_allocations ra ON s.id = ra.student_id AND ra.status = 'Active'
            WHERE s.status = 'Active' AND ra.id IS NULL
            ORDER BY s.full_name ASC
        ");
        return $stmt->fetchAll();
    }

    public function allocateRoom($data) {
        $studentId = (int)($data['student_id'] ?? 0);
        $roomId = (int)($data['room_id'] ?? 0);
        $bedNumber = (int)($data['bed_number'] ?? 0);
        $date = !empty($data['joining_date']) ? $data['joining_date'] : date('Y-m-d');
        $remarks = !empty($data['remarks']) ? trim($data['remarks']) : null;
        $allocatedByName = trim((string)($data['allocated_by_name'] ?? ''));

        if ($studentId <= 0) {
            return ['success' => false, 'error' => 'Please select a valid student.'];
        }

        if ($allocatedByName === '') {
            return ['success' => false, 'error' => 'Allocated By is required. Please enter the staff member name.'];
        }

        if ($roomId <= 0) {
            return ['success' => false, 'error' => 'Please select a valid room.'];
        }

        if ($bedNumber <= 0) {
            return ['success' => false, 'error' => 'Please select a valid bed number.'];
        }

        $this->db->beginTransaction();

        try {
            // Lock student check
            $stmtStudent = $this->db->prepare("SELECT * FROM students WHERE id = ? FOR UPDATE");
            $stmtStudent->execute([$studentId]);
            $student = $stmtStudent->fetch();

            if (!$student || $student['status'] !== 'Active') {
                throw new Exception("Student is not active or does not exist.");
            }

            // Check if student already has an active allocation
            $stmtStudentAlloc = $this->db->prepare("SELECT id FROM room_allocations WHERE student_id = ? AND status = 'Active' FOR UPDATE");
            $stmtStudentAlloc->execute([$studentId]);
            if ($stmtStudentAlloc->fetch()) {
                throw new Exception("Student already has an active room allocation.");
            }

            // Lock room row for update to prevent concurrent duplicate allocations
            $stmtRoom = $this->db->prepare("SELECT * FROM rooms WHERE id = ? FOR UPDATE");
            $stmtRoom->execute([$roomId]);
            $room = $stmtRoom->fetch();

            if (!$room) {
                throw new Exception("Room could not be found.");
            }

            if ($room['status'] === 'Disabled') {
                throw new Exception("This room is currently disabled and unavailable for allocation.");
            }

            $totalBeds = (int)$room['total_beds'];

            if ($bedNumber < 1 || $bedNumber > $totalBeds) {
                throw new Exception("Selected bed does not exist in this room. Valid beds are 1 to {$totalBeds}.");
            }

            // Check if that bed already has an ACTIVE allocation
            $stmtBedAlloc = $this->db->prepare("SELECT id FROM room_allocations WHERE room_id = ? AND bed_number = ? AND status = 'Active' FOR UPDATE");
            $stmtBedAlloc->execute([$roomId, $bedNumber]);
            if ($stmtBedAlloc->fetch()) {
                throw new Exception("Selected bed is already occupied.");
            }

            // Verify room has capacity
            $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM room_allocations WHERE room_id = ? AND status = 'Active'");
            $stmtCount->execute([$roomId]);
            $currentOccupied = (int)$stmtCount->fetchColumn();

            if ($currentOccupied >= $totalBeds) {
                throw new Exception("Room is already at full capacity ({$totalBeds}/{$totalBeds} beds occupied).");
            }

            // Insert new Allocation
            $stmtInsert = $this->db->prepare("
                INSERT INTO room_allocations (student_id, room_id, bed_number, joining_date, status, remarks, allocated_by_name)
                VALUES (?, ?, ?, ?, 'Active', ?, ?)
            ");
            $stmtInsert->execute([$studentId, $roomId, $bedNumber, $date, $remarks, $allocatedByName]);
            $allocationId = $this->db->lastInsertId();

            // Reconcile Room Occupancy
            $newOccupied = $currentOccupied + 1;
            $newStatus = ($newOccupied >= $totalBeds) ? 'Occupied' : 'Partially Occupied';
            
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
                Session::get('admin_id'),
                $this->db
            );

            $this->db->commit();

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->adminRepo->logAction(Session::get('admin_id'), 'Room Allocation', "Allocated {$student['student_id_str']} to Room {$room['room_number']} Bed {$bedNumber}", $ip);
            
            return ['success' => true, 'id' => $allocationId];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function deallocateRoom($allocationId, $date = null) {
        $allocationId = (int)$allocationId;
        $date = !empty($date) ? $date : date('Y-m-d');

        $this->db->beginTransaction();

        try {
            $stmtAlloc = $this->db->prepare("SELECT * FROM room_allocations WHERE id = ? AND status = 'Active' FOR UPDATE");
            $stmtAlloc->execute([$allocationId]);
            $alloc = $stmtAlloc->fetch();

            if (!$alloc) {
                throw new Exception("Active allocation record not found.");
            }

            $stmtRoom = $this->db->prepare("SELECT * FROM rooms WHERE id = ? FOR UPDATE");
            $stmtRoom->execute([$alloc['room_id']]);
            $room = $stmtRoom->fetch();

            if (!$room) {
                throw new Exception("Associated room not found.");
            }

            // Close allocation
            $stmtClose = $this->db->prepare("UPDATE room_allocations SET status = 'Closed', leaving_date = ? WHERE id = ?");
            $stmtClose->execute([$date, $allocationId]);

            // Reconcile Room Occupancy
            $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM room_allocations WHERE room_id = ? AND status = 'Active'");
            $stmtCount->execute([$alloc['room_id']]);
            $newOccupied = (int)$stmtCount->fetchColumn();

            $newStatus = $room['status'];
            if ($newStatus !== 'Disabled') {
                if ($newOccupied === 0) {
                    $newStatus = 'Available';
                } elseif ($newOccupied < (int)$room['total_beds']) {
                    $newStatus = 'Partially Occupied';
                } else {
                    $newStatus = 'Occupied';
                }
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
                Session::get('admin_id'),
                $this->db
            );

            $this->db->commit();

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->adminRepo->logAction(Session::get('admin_id'), 'Room Deallocation', "Deallocated ID: $allocationId", $ip);
            
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

