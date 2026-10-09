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

            $allocationType = strtoupper(trim((string)($data['allocation_type'] ?? 'BED')));
            $bedNumber = isset($data['bed_number']) ? (int)$data['bed_number'] : 0;
            if ($allocationType === 'FULL_ROOM') {
                $bedNumber = 0;
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

            $reservationStmt = $db->prepare("SELECT id FROM reservations WHERE room_id = :room_id AND status IN ('PENDING', 'CONFIRMED') AND (:is_full_room = 1 OR bed_number = :bed_number) FOR UPDATE");
            $reservationStmt->execute(['room_id' => $room['id'], 'is_full_room' => $bedNumber === 0 ? 1 : 0, 'bed_number' => $bedNumber]);
            if ($reservationStmt->fetch()) {
                throw new Exception('The selected bed or room is reserved.');
            }

            $summaryStmt = $db->prepare("SELECT
                    COUNT(*) AS active_allocations,
                    COALESCE(SUM(CASE
                        WHEN bed_number = 0 THEN :total_beds
                        WHEN bed_number > 0 THEN 1
                        ELSE 0
                    END), 0) AS effective_occupied_beds,
                    COALESCE(SUM(CASE WHEN bed_number = 0 THEN 1 ELSE 0 END), 0) AS full_room_allocations
                FROM room_allocations
                WHERE room_id = :room_id AND status = 'Active'");
            $summaryStmt->execute([
                'total_beds' => (int)$room['total_beds'],
                'room_id' => $room['id'],
            ]);
            $occupancy = $summaryStmt->fetch();
            $effectiveOccupied = (int)($occupancy['effective_occupied_beds'] ?? 0);
            $activeAllocations = (int)($occupancy['active_allocations'] ?? 0);
            $fullRoomAllocations = (int)($occupancy['full_room_allocations'] ?? 0);

            if ($allocationType === 'FULL_ROOM') {
                if ($activeAllocations > 0 || $effectiveOccupied > 0 || $fullRoomAllocations > 0) {
                    throw new Exception("This room is not completely available for a full-room allocation.");
                }
                $bedNumber = 0;
            } else {
                if ($bedNumber < 1 || $bedNumber > $room['total_beds']) {
                    throw new Exception("Invalid bed number. Room only has {$room['total_beds']} beds.");
                }
                if ($effectiveOccupied >= $room['total_beds']) {
                    throw new Exception("The selected room is fully occupied.");
                }
                if ($this->repository->isBedOccupied($room['id'], $bedNumber, $db)) {
                    throw new Exception("Selected bed is already occupied.");
                }
            }

            $data['bed_number'] = $bedNumber;
            $data['remarks'] = $data['remarks'] ?? ($allocationType === 'FULL_ROOM' ? 'FULL_ROOM allocation' : 'BED allocation');
            $id = $this->repository->create($data, $db);

            $newOccupied = $allocationType === 'FULL_ROOM'
                ? (int)$room['total_beds']
                : $effectiveOccupied + 1;
            if ($newOccupied > $room['total_beds']) {
                throw new Exception("Room capacity exceeded.");
            }

            $newStatus = $allocationType === 'FULL_ROOM'
                ? 'Occupied'
                : $this->calculateRoomStatus($room['total_beds'], $newOccupied);

            $updateRoom = $db->prepare("UPDATE rooms SET occupied_beds = :occ, status = :status WHERE id = :id");
            $updateRoom->execute([
                'occ' => $newOccupied,
                'status' => $newStatus,
                'id' => $room['id']
            ]);

            $bedLabel = $allocationType === 'FULL_ROOM' ? 'FULL_ROOM' : $bedNumber;
            Logger::info("ROOM_ALLOCATED: Student {$data['student_id']} allocated to Room {$room['room_number']} Bed {$bedLabel}");
            
            StudentHistoryService::record(
                $data['student_id'],
                'ROOM_ALLOCATED',
                "Allocated to Room {$room['room_number']} Bed {$bedLabel}",
                null,
                ['room_id' => $room['id'], 'room_number' => $room['room_number'], 'bed_number' => $bedNumber, 'joining_date' => $data['joining_date'], 'allocation_type' => $allocationType],
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

            $reservationStmt = $db->prepare("SELECT id FROM reservations WHERE room_id = :room_id AND bed_number = :bed_number AND status IN ('PENDING', 'CONFIRMED') FOR UPDATE");
            $reservationStmt->execute(['room_id' => $newRoom['id'], 'bed_number' => $data['new_bed_number']]);
            if ($reservationStmt->fetch()) {
                throw new Exception('The selected bed is reserved.');
            }

            if ($this->repository->isBedOccupied($newRoom['id'], $data['new_bed_number'], $db)) {
                throw new Exception("Selected new bed is already occupied.");
            }

            $fullRoomStmt = $db->prepare("SELECT id FROM room_allocations WHERE room_id = :room_id AND bed_number = 0 AND status = 'Active' FOR UPDATE");
            $fullRoomStmt->execute(['room_id' => $newRoom['id']]);
            if ($fullRoomStmt->fetch() || $this->getEffectiveOccupiedBeds($db, $newRoom['id'], (int)$newRoom['total_beds']) >= (int)$newRoom['total_beds']) {
                throw new Exception("The selected room is fully occupied.");
            }

            $this->repository->closeAllocation($allocationId, $data['transfer_date'], "Transferred to another room", $db);

            $oldOccupied = $this->getEffectiveOccupiedBeds($db, $oldRoom['id'], (int)$oldRoom['total_beds']);
            $oldStatus = $oldRoom['status'] === 'Disabled' ? 'Disabled' : $this->calculateRoomStatus($oldRoom['total_beds'], $oldOccupied);
            $updateOld = $db->prepare("UPDATE rooms SET occupied_beds = :occ, status = :status WHERE id = :id");
            $updateOld->execute(['occ' => $oldOccupied, 'status' => $oldStatus, 'id' => $oldRoom['id']]);

            $newId = $this->repository->create([
                'student_id' => $allocation['student_id'],
                'room_id' => $newRoom['id'],
                'bed_number' => $data['new_bed_number'],
                'joining_date' => $data['transfer_date'],
                'remarks' => $data['remarks'] ?? 'Transferred'
            ], $db);

            $newOccupied = $this->getEffectiveOccupiedBeds($db, $newRoom['id'], (int)$newRoom['total_beds']);
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

            $reservationStmt = $db->prepare("SELECT id FROM reservations WHERE room_id = :room_id AND bed_number = :bed_number AND status IN ('PENDING', 'CONFIRMED') FOR UPDATE");
            $reservationStmt->execute(['room_id' => $room['id'], 'bed_number' => $newBedNumber]);
            if ($reservationStmt->fetch()) {
                throw new Exception('The selected bed is reserved.');
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

            $summaryStmt = $db->prepare("SELECT
                    COALESCE(SUM(CASE
                        WHEN bed_number = 0 THEN :total_beds
                        WHEN bed_number > 0 THEN 1
                        ELSE 0
                    END), 0) AS effective_occupied_beds
                FROM room_allocations
                WHERE room_id = :room_id AND status = 'Active'");
            $summaryStmt->execute([
                'total_beds' => (int)$room['total_beds'],
                'room_id' => $room['id'],
            ]);
            $remainingOccupied = (int)($summaryStmt->fetchColumn() ?: 0);
            $newOccupied = max(0, $remainingOccupied);
            $newStatus = $room['status'] === 'Disabled' ? 'Disabled' : $this->calculateRoomStatus($room['total_beds'], $newOccupied);

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
        $occupiedBeds = [];
        foreach ($activeAllocations as $allocation) {
            $bedNumber = (int)($allocation['bed_number'] ?? 0);
            if ($bedNumber > 0) {
                $occupiedBeds[] = $bedNumber;
            } elseif ($bedNumber === 0) {
                return [
                    'room_id' => $room['id'],
                    'room_number' => $room['room_number'],
                    'total_beds' => $room['total_beds'],
                    'available_beds' => []
                ];
            }
        }

        $occupiedBeds = array_merge($occupiedBeds, $this->repository->getActiveReservedBedsForRoom($roomId));
        if (count($activeAllocations) > 0 && !empty(array_filter($activeAllocations, fn($allocation) => (int)($allocation['bed_number'] ?? 0) === 0))) {
            return [
                'room_id' => $room['id'],
                'room_number' => $room['room_number'],
                'total_beds' => $room['total_beds'],
                'available_beds' => []
            ];
        }

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

    private function getEffectiveOccupiedBeds(PDO $db, $roomId, int $totalBeds): int {
        $stmt = $db->prepare("SELECT COALESCE(SUM(CASE WHEN bed_number = 0 THEN :total_beds ELSE 1 END), 0) FROM room_allocations WHERE room_id = :room_id AND status = 'Active'");
        $stmt->execute(['total_beds' => $totalBeds, 'room_id' => $roomId]);
        return (int)$stmt->fetchColumn();
    }
}
