<?php
namespace Services;

use Repositories\RoomRepository;
use Exception;

class RoomService {
    private $repository;

    public function __construct() {
        $this->repository = new RoomRepository();
    }

    public function createRoom($data) {
        $existing = $this->repository->findByRoomNumberAndBlock($data['room_number'], $data['block']);
        if ($existing) {
            throw new Exception("Room number {$data['room_number']} already exists in {$data['block']}");
        }

        return $this->repository->create($data);
    }

    public function getRoom($id) {
        $room = $this->repository->findById($id);
        if (!$room) {
            throw new Exception("Room not found");
        }
        return $room;
    }

    public function searchRooms($filters) {
        return $this->repository->search($filters);
    }

    public function updateRoom($id, $data) {
        $room = $this->repository->findById($id);
        if (!$room) {
            throw new Exception("Room not found");
        }

        if ($room['room_number'] !== $data['room_number'] || $room['block'] !== $data['block']) {
            $existing = $this->repository->findByRoomNumberAndBlock($data['room_number'], $data['block']);
            if ($existing && $existing['id'] != $id) {
                throw new Exception("Room number {$data['room_number']} already exists in {$data['block']}");
            }
        }

        if ($data['total_beds'] < $room['occupied_beds']) {
            throw new Exception("Total beds cannot be less than currently occupied beds ({$room['occupied_beds']})");
        }

        $status = $room['status'];
        if ($status !== 'Disabled') {
            $status = $this->calculateStatus($data['total_beds'], $room['occupied_beds']);
        }

        $data['status'] = $status;
        $this->repository->update($id, $data);
        return true;
    }

    public function disableRoom($id) {
        $room = $this->repository->findById($id);
        if (!$room) {
            throw new Exception("Room not found");
        }
        if ($room['status'] === 'Disabled') {
            throw new Exception("Room is already disabled");
        }

        $this->repository->updateStatus($id, 'Disabled');
        return true;
    }

    public function enableRoom($id) {
        $room = $this->repository->findById($id);
        if (!$room) {
            throw new Exception("Room not found");
        }
        if ($room['status'] !== 'Disabled') {
            throw new Exception("Room is not disabled");
        }

        $status = $this->calculateStatus($room['total_beds'], $room['occupied_beds']);
        $this->repository->updateStatus($id, $status);
        return true;
    }

    public function getStatistics() {
        return $this->repository->getStatistics();
    }

    private function calculateStatus($totalBeds, $occupiedBeds) {
        if ($occupiedBeds == 0) {
            return 'Available';
        } elseif ($occupiedBeds >= $totalBeds) {
            return 'Occupied';
        } else {
            return 'Partially Occupied';
        }
    }
}
