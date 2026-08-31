<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Session;
use App\Core\CSRF;
use App\Services\AllocationService;
use App\Repositories\StudentRepository;
use App\Repositories\RoomRepository;

class AllocationController {
    private $allocService;

    public function __construct() {
        Auth::check();
        $this->allocService = new AllocationService();
    }

    public function index() {
        $allocations = $this->allocService->getAllActiveAllocations();
        
        View::render('admin/allocations/index', [
            'title' => 'Room Allocations',
            'allocations' => $allocations,
            'csrf_token' => CSRF::generateToken()
        ], 'admin');
    }

    public function create() {
        $students = $this->allocService->getUnallocatedActiveStudents();
        $roomService = new \App\Services\RoomService();
        $rooms = $roomService->getAllAvailableRooms();

        View::render('admin/allocations/create', [
            'title' => 'New Allocation',
            'students' => $students,
            'rooms' => $rooms,
            'csrf_token' => CSRF::generateToken()
        ], 'admin');
    }

    public function apiAvailableBeds($roomId) {
        header('Content-Type: application/json');
        $roomService = new \App\Services\RoomService();
        $res = $roomService->getAvailableBeds((int)$roomId);
        if ($res['success']) {
            echo json_encode([
                'success' => true,
                'data' => $res['data']
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => $res['error'] ?? 'Room not found.'
            ]);
        }
        exit;
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }
        
        CSRF::verifyToken($_POST['csrf_token'] ?? '');

        if (empty($_POST['student_id']) || empty($_POST['room_id']) || empty($_POST['bed_number']) || empty($_POST['joining_date'])) {
            Session::set('error', 'Please select student, room, bed number, and joining date.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/allocations/create');
            exit;
        }

        $result = $this->allocService->allocateRoom($_POST);
        
        if ($result['success']) {
            Session::set('success', 'Room allocated successfully.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/allocations');
        } else {
            Session::set('error', $result['error']);
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/allocations/create');
        }
        exit;
    }

    public function remove($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }
        
        CSRF::verifyToken($_POST['csrf_token'] ?? '');

        $result = $this->allocService->deallocateRoom($id, date('Y-m-d'));
        
        if ($result['success']) {
            Session::set('success', 'Student removed from room successfully.');
        } else {
            Session::set('error', $result['error']);
        }
        
        header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/allocations');
        exit;
    }
}

