<?php
namespace Services;

use Repositories\RoomAllocationRepository;
use Repositories\RoomRepository;
use Utils\TransactionHelper;
use Core\Logger;
use Services\StudentHistoryService;
use Exception;
use PDO;

class RoomAllocationService {
    private $repository;
    private $roomRepository;

    public function __construct() {
        $this->repository = new RoomAllocationRepository();
        $this->roomRepository = new RoomRepository();
    }

    public function allocateStudent($data) {
        return TransactionHelper::execute(function(PDO $db) use ($data) {
            $active = $this->repository->findActiveByStudent($data['student_id']);
            if ($active) {
                throw new Exception("Student already has an active room allocation.");
            }

            $stmt = $db->prepare("SELECT * FROM rooms WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $data['room_id']]);
            $room = $stmt->fetch();

            if (!$room) {
                throw new Exception("Room not found.");
            }

            if ($room['status'] === 'Disabled') {
                throw new Exception("Cannot allocate to a disabled room.");
            }

            if ($data['bed_number'] < 1 || $data['bed_number'] > $room['total_beds']) {
                throw new Exception("Invalid bed number. Room only has {$room['total_beds']} beds.");
            }

            if ($this->repository->isBedOccupied($room['id'], $data['bed_number'], $db)) {
                throw new Exception("Selected bed is already occupied.");
            }

            $id = $this->repository->create($data, $db);

            $newOccupied = $room['occupied_beds'] + 1;
            if ($newOccupied > $room['total_beds']) {
                throw new Exception("Room capacity exceeded.");
            }

            $newStatus = $this->calculateRoomStatus($room['total_beds'], $newOccupied);

            $updateRoom = $db->prepare("UPDATE rooms SET occupied_beds = :occ, status = :status WHERE id = :id");
            $updateRoom->execute([
                'occ' => $newOccupied,
                'status' => $newStatus,
                'id' => $room['id']
            ]);

            Logger::info("ROOM_ALLOCATED: Student {$data['student_id']} allocated to Room {$room['room_number']} Bed {$data['bed_number']}");
            
            StudentHistoryService::record(
                $data['student_id'],
                'ROOM_ALLOCATED',
                "Allocated to Room {$room['room_number']} Bed {$data['bed_number']}",
                null,
                ['room_id' => $room['id'], 'room_number' => $room['room_number'], 'bed_number' => $data['bed_number'], 'joining_date' => $data['joining_date']],
                null,
                $db
            );
            
            return $id;
        });
    }

    public function transferStudent($allocationId, $data) {
        return TransactionHelper::execute(function(PDO $db) use ($allocationId, $data) {
            $allocation = $this->repository->findById($allocationId);
            if (!$allocation || $allocation['status'] !== 'Active') {
                throw new Exception("Active allocation not found.");
            }

            $stmtOld = $db->prepare("SELECT * FROM rooms WHERE id = :id FOR UPDATE");
            $stmtOld->execute(['id' => $allocation['room_id']]);
            $oldRoom = $stmtOld->fetch();

            $stmtNew = $db->prepare("SELECT * FROM rooms WHERE id = :id FOR UPDATE");
            $stmtNew->execute(['id' => $data['new_room_id']]);
            $newRoom = $stmtNew->fetch();

            if (!$newRoom || $newRoom['status'] === 'Disabled') {
                throw new Exception("New room is invalid or disabled.");
            }

            if ($data['new_bed_number'] < 1 || $data['new_bed_number'] > $newRoom['total_beds']) {
                throw new Exception("Invalid new bed number.");
            }

            if ($this->repository->isBedOccupied($newRoom['id'], $data['new_bed_number'], $db)) {
                throw new Exception("Selected new bed is already occupied.");
            }

            $this->repository->closeAllocation($allocationId, $data['transfer_date'], "Transferred to another room", $db);

            $oldOccupied = max(0, $oldRoom['occupied_beds'] - 1);
            $oldStatus = $this->calculateRoomStatus($oldRoom['total_beds'], $oldOccupied);
            $updateOld = $db->prepare("UPDATE rooms SET occupied_beds = :occ, status = :status WHERE id = :id");
            $updateOld->execute(['occ' => $oldOccupied, 'status' => $oldStatus, 'id' => $oldRoom['id']]);

            $newId = $this->repository->create([
                'student_id' => $allocation['student_id'],
                'room_id' => $newRoom['id'],
                'bed_number' => $data['new_bed_number'],
                'joining_date' => $data['transfer_date'],
                'remarks' => $data['remarks'] ?? 'Transferred'
            ], $db);

            $newOccupied = $newRoom['occupied_beds'] + 1;
            $newStatus = $this->calculateRoomStatus($newRoom['total_beds'], $newOccupied);
            $updateNew = $db->prepare("UPDATE rooms SET occupied_beds = :occ, status = :status WHERE id = :id");
            $updateNew->execute(['occ' => $newOccupied, 'status' => $newStatus, 'id' => $newRoom['id']]);

            Logger::info("ROOM_TRANSFERRED: Student {$allocation['student_id']} transferred from Room {$oldRoom['room_number']} to {$newRoom['room_number']}");

            StudentHistoryService::record(
                $allocation['student_id'],
                'ROOM_TRANSFERRED',
                "Transferred from Room {$oldRoom['room_number']} to {$newRoom['room_number']}",
                ['room_id' => $oldRoom['id'], 'room_number' => $oldRoom['room_number'], 'bed_number' => $allocation['bed_number']],
                ['room_id' => $newRoom['id'], 'room_number' => $newRoom['room_number'], 'bed_number' => $data['new_bed_number'], 'transfer_date' => $data['transfer_date']],
                null,
                $db
            );

            return $newId;
        });
    }

    public function changeBed($allocationId, $newBedNumber) {
        return TransactionHelper::execute(function(PDO $db) use ($allocationId, $newBedNumber) {
            $allocation = $this->repository->findById($allocationId);
            if (!$allocation || $allocation['status'] !== 'Active') {
                throw new Exception("Active allocation not found.");
            }

            if ($allocation['bed_number'] == $newBedNumber) {
                return true; 
            }

            $stmt = $db->prepare("SELECT * FROM rooms WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $allocation['room_id']]);
            $room = $stmt->fetch();

            if ($newBedNumber < 1 || $newBedNumber > $room['total_beds']) {
                throw new Exception("Invalid bed number.");
            }

            if ($this->repository->isBedOccupied($room['id'], $newBedNumber, $db)) {
                throw new Exception("Selected bed is already occupied.");
            }

            $this->repository->changeBed($allocationId, $newBedNumber, $db);
            Logger::info("BED_CHANGED: Student {$allocation['student_id']} changed to bed {$newBedNumber}");

            StudentHistoryService::record(
                $allocation['student_id'],
                'BED_CHANGED',
                "Changed to Bed {$newBedNumber} in Room {$room['room_number']}",
                ['bed_number' => $allocation['bed_number']],
                ['bed_number' => $newBedNumber],
                null,
                $db
            );

            return true;
        });
    }

    public function closeAllocation($allocationId, $leavingDate, $remarks) {
        return TransactionHelper::execute(function(PDO $db) use ($allocationId, $leavingDate, $remarks) {
            $allocation = $this->repository->findById($allocationId);
            if (!$allocation || $allocation['status'] !== 'Active') {
                throw new Exception("Active allocation not found.");
            }

            $stmt = $db->prepare("SELECT * FROM rooms WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $allocation['room_id']]);
            $room = $stmt->fetch();

            $this->repository->closeAllocation($allocationId, $leavingDate, $remarks, $db);

            $newOccupied = max(0, $room['occupied_beds'] - 1);
            $newStatus = $this->calculateRoomStatus($room['total_beds'], $newOccupied);

            $updateRoom = $db->prepare("UPDATE rooms SET occupied_beds = :occ, status = :status WHERE id = :id");
            $updateRoom->execute(['occ' => $newOccupied, 'status' => $newStatus, 'id' => $room['id']]);

            Logger::info("ROOM_ALLOCATION_CLOSED: Allocation {$allocationId} closed for student {$allocation['student_id']}");

            StudentHistoryService::record(
                $allocation['student_id'],
                'ROOM_ALLOCATION_CLOSED',
                "Allocation closed in Room {$room['room_number']} Bed {$allocation['bed_number']}",
                ['room_id' => $room['id'], 'bed_number' => $allocation['bed_number'], 'status' => 'Active'],
                ['leaving_date' => $leavingDate, 'status' => 'Closed'],
                null,
                $db
            );

            return true;
        });
    }

    public function getAvailableBeds($roomId) {
        $room = $this->roomRepository->findById($roomId);
        if (!$room) throw new Exception("Room not found");
        
        $activeAllocations = $this->repository->getActiveByRoom($roomId);
        $occupiedBeds = array_column($activeAllocations, 'bed_number');
        
        $available = [];
        for ($i = 1; $i <= $room['total_beds']; $i++) {
            if (!in_array($i, $occupiedBeds)) {
                $available[] = $i;
            }
        }
        
        return [
            'room_id' => $room['id'],
            'room_number' => $room['room_number'],
            'total_beds' => $room['total_beds'],
            'available_beds' => $available
        ];
    }

    private function calculateRoomStatus($totalBeds, $occupiedBeds) {
        if ($occupiedBeds == 0) return 'Available';
        if ($occupiedBeds >= $totalBeds) return 'Occupied';
        return 'Partially Occupied';
    }

    public function getAllActive() { return $this->repository->getAllActive(); }
    public function getActiveByRoom($roomId) { return $this->repository->getActiveByRoom($roomId); }
    public function getActiveByStudent($studentId) { return $this->repository->findActiveByStudent($studentId); }
    public function getHistoryByStudent($studentId) { return $this->repository->getHistoryByStudent($studentId); }
    public function getHistoryByRoom($roomId) { return $this->repository->getHistoryByRoom($roomId); }
    public function getStudentsWithoutRoom() { return $this->repository->getStudentsWithoutRoom(); }
    public function getStatistics() { return $this->repository->getStatistics(); }
    public function getAllocation($id) { 
        $alloc = $this->repository->findById($id);
        if (!$alloc) throw new Exception("Allocation not found");
        return $alloc;
    }
}
