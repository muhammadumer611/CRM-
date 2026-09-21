<?php
namespace Controllers;

use Core\Response;
use Core\Validator;
use Services\RoomService;
use Exception;

class RoomController {
    private $service;

    public function __construct() {
        $this->service = new RoomService();
    }

    public function create() {
        global $requestBody;
        
        $validator = new Validator($requestBody);
        $rules = [
            'room_number' => 'required|string|max:20',
            'block' => 'required|string|max:50',
            'floor' => 'required|string|max:20',
            'room_type' => 'required|string|max:50',
            'total_beds' => 'required|integer|positive_number',
            'monthly_fee' => 'required|numeric|positive_number',
            'security_deposit' => 'required|numeric|positive_number'
        ];

        if (!$validator->validate($rules)) {
            Response::error('Validation failed.', 400, $validator->getErrors());
        }

        if ($requestBody['total_beds'] == 0) {
            Response::error('Validation failed.', 400, ['total_beds' => ['Total beds must be greater than 0.']]);
        }

        try {
            $id = $this->service->createRoom($requestBody);
            Response::success('Room created successfully.', ['id' => $id]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function index() {
        $filters = $_GET;
        try {
            $rooms = $this->service->searchRooms($filters);
            Response::success('Rooms retrieved successfully.', ['rooms' => $rooms]);
        } catch (Exception $e) {
            Response::error('Failed to retrieve rooms.', 500);
        }
    }

    public function show($params) {
        try {
            $room = $this->service->getRoom($params['id']);
            Response::success('Room retrieved successfully.', ['room' => $room]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    public function update($params) {
        global $requestBody;

        $validator = new Validator($requestBody);
        $rules = [
            'room_number' => 'required|string|max:20',
            'block' => 'required|string|max:50',
            'floor' => 'required|string|max:20',
            'room_type' => 'required|string|max:50',
            'total_beds' => 'required|integer|positive_number',
            'monthly_fee' => 'required|numeric|positive_number',
            'security_deposit' => 'required|numeric|positive_number'
        ];

        if (!$validator->validate($rules)) {
            Response::error('Validation failed.', 400, $validator->getErrors());
        }

        if ($requestBody['total_beds'] == 0) {
            Response::error('Validation failed.', 400, ['total_beds' => ['Total beds must be greater than 0.']]);
        }

        try {
            $this->service->updateRoom($params['id'], $requestBody);
            Response::success('Room updated successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function disable($params) {
        try {
            $this->service->disableRoom($params['id']);
            Response::success('Room disabled successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function enable($params) {
        try {
            $this->service->enableRoom($params['id']);
            Response::success('Room enabled successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function statistics() {
        try {
            $stats = $this->service->getStatistics();
            Response::success('Statistics retrieved successfully.', ['statistics' => $stats]);
        } catch (Exception $e) {
            Response::error('Failed to retrieve statistics.', 500);
        }
    }
}
