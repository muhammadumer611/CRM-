<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Session;
use App\Core\CSRF;
use App\Services\RoomService;

class RoomController {
    private $roomService;

    public function __construct() {
        Auth::check();
        $this->roomService = new RoomService();
    }

    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = $page > 0 ? $page : 1;
        $perPage = 15;
        
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];

        $result = $this->roomService->getAllRooms($filters, $page, $perPage);
        
        View::render('admin/rooms/index', [
            'title' => 'Rooms',
            'rooms' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => $perPage,
            'filters' => $filters
        ], 'admin');
    }

    public function create() {
        View::render('admin/rooms/create', [
            'title' => 'Add Room',
            'csrf_token' => CSRF::generateToken()
        ], 'admin');
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }
        
        CSRF::verifyToken($_POST['csrf_token'] ?? '');

        $required = ['room_number', 'block', 'floor', 'room_type', 'total_beds', 'monthly_fee', 'security_deposit'];
        foreach ($required as $field) {
            if (!isset($_POST[$field]) || $_POST[$field] === '') {
                Session::set('error', 'Please fill all required fields.');
                header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/rooms/create');
                exit;
            }
        }

        $result = $this->roomService->createRoom($_POST);
        
        if ($result['success']) {
            Session::set('success', 'Room added successfully.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/rooms');
        } else {
            Session::set('error', $result['error']);
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/rooms/create');
        }
        exit;
    }

    public function edit($id) {
        $room = $this->roomService->getRoom($id);
        if (!$room) {
            Session::set('error', 'Room not found.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/rooms');
            exit;
        }

        View::render('admin/rooms/edit', [
            'title' => 'Edit Room',
            'room' => $room,
            'csrf_token' => CSRF::generateToken()
        ], 'admin');
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }
        
        CSRF::verifyToken($_POST['csrf_token'] ?? '');

        $result = $this->roomService->updateRoom($id, $_POST);
        
        if ($result['success']) {
            Session::set('success', 'Room updated successfully.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/rooms');
        } else {
            Session::set('error', $result['error']);
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/rooms/edit/' . $id);
        }
        exit;
    }
}
