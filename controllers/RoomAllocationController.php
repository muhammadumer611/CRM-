<?php
namespace Controllers;

use Core\Response;
use Core\Validator;
use Services\RoomAllocationService;
use Exception;

class RoomAllocationController {
    private $service;

    public function __construct() {
        $this->service = new RoomAllocationService();
    }

    public function create() {
        global $requestBody;
        
        $validator = new Validator($requestBody);
        $rules = [
            'student_id' => 'required|integer',
            'room_id' => 'required|integer',
            'bed_number' => 'required|integer|positive_number',
            'joining_date' => 'required|date'
        ];

        if (!$validator->validate($rules)) {
            Response::error('Validation failed.', 422, $validator->getErrors());
        }

        try {
            $id = $this->service->allocateStudent($requestBody);
            Response::json(true, 'Student allocated successfully.', ['id' => $id], null, 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 409);
        }
    }

    public function transfer($params) {
        global $requestBody;
        
        $validator = new Validator($requestBody);
        $rules = [
            'new_room_id' => 'required|integer',
            'new_bed_number' => 'required|integer|positive_number',
            'transfer_date' => 'required|date'
        ];

        if (!$validator->validate($rules)) {
            Response::error('Validation failed.', 422, $validator->getErrors());
        }

        try {
            $newId = $this->service->transferStudent($params['id'], $requestBody);
            Response::success('Student transferred successfully.', ['new_allocation_id' => $newId]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 409);
        }
    }

    public function changeBed($params) {
        global $requestBody;
        if (empty($requestBody['bed_number']) || !is_numeric($requestBody['bed_number']) || $requestBody['bed_number'] < 1) {
            Response::error('Invalid bed number.', 422);
        }

        try {
            $this->service->changeBed($params['id'], $requestBody['bed_number']);
            Response::success('Bed changed successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 409);
        }
    }

    public function close($params) {
        global $requestBody;
        
        $validator = new Validator($requestBody);
        $rules = [
            'leaving_date' => 'required|date'
        ];

        if (!$validator->validate($rules)) {
            Response::error('Validation failed.', 422, $validator->getErrors());
        }

        try {
            $this->service->closeAllocation($params['id'], $requestBody['leaving_date'], $requestBody['remarks'] ?? '');
            Response::success('Allocation closed successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function index() {
        try {
            $data = $this->service->getAllActive();
            Response::success('Active allocations retrieved.', ['allocations' => $data]);
        } catch (Exception $e) {
            Response::error('Failed to retrieve allocations.', 500);
        }
    }

    public function show($params) {
        try {
            $data = $this->service->getAllocation($params['id']);
            Response::success('Allocation retrieved.', ['allocation' => $data]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    public function getActiveByStudent($params) {
        try {
            $data = $this->service->getActiveByStudent($params['student_id']);
            if (!$data) Response::error('No active allocation found.', 404);
            Response::success('Allocation retrieved.', ['allocation' => $data]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    public function getActiveByRoom($params) {
        try {
            $data = $this->service->getActiveByRoom($params['room_id']);
            Response::success('Allocations retrieved.', ['allocations' => $data]);
        } catch (Exception $e) {
            Response::error('Failed to retrieve allocations.', 500);
        }
    }

    public function studentHistory($params) {
        try {
            $data = $this->service->getHistoryByStudent($params['student_id']);
            Response::success('Student allocation history retrieved.', ['allocations' => $data]);
        } catch (Exception $e) {
            Response::error('Failed to retrieve history.', 500);
        }
    }

    public function roomHistory($params) {
        try {
            $data = $this->service->getHistoryByRoom($params['room_id']);
            Response::success('Room allocation history retrieved.', ['allocations' => $data]);
        } catch (Exception $e) {
            Response::error('Failed to retrieve history.', 500);
        }
    }

    public function availableBeds($params) {
        try {
            $data = $this->service->getAvailableBeds($params['room_id']);
            Response::success('Available beds retrieved.', ['data' => $data]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    public function withoutRoom() {
        try {
            $data = $this->service->getStudentsWithoutRoom();
            Response::success('Students without room retrieved.', ['students' => $data]);
        } catch (Exception $e) {
            Response::error('Failed to retrieve students.', 500);
        }
    }

    public function statistics() {
        try {
            $data = $this->service->getStatistics();
            Response::success('Statistics retrieved.', ['statistics' => $data]);
        } catch (Exception $e) {
            Response::error('Failed to retrieve statistics.', 500);
        }
    }
}
