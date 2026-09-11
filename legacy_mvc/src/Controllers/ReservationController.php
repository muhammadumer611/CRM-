<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Session;
use App\Core\View;
use App\Services\ReservationService;
use App\Services\RoomService;

class ReservationController {
    private $service;

    public function __construct() {
        Auth::check();
        $this->service = new ReservationService();
    }

    public function index() {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];

        $result = $this->service->getAllReservations($filters, $page, $perPage);

        View::render('admin/reservations/index', [
            'title' => 'Reservations',
            'reservations' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => $perPage,
            'filters' => $filters,
            'csrf_token' => CSRF::generateToken(),
        ], 'admin');
    }

    public function create() {
        $roomService = new RoomService();
        $rooms = $roomService->getAllAvailableRooms();

        View::render('admin/reservations/create', [
            'title' => 'Create Reservation',
            'rooms' => $rooms,
            'csrf_token' => CSRF::generateToken(),
        ], 'admin');
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        CSRF::verifyToken($_POST['csrf_token'] ?? '');
        $config = require APP_ROOT . '/config/app.php';

        $result = $this->service->createReservation($_POST);
        if ($result['success']) {
            Session::set('success', $result['message'] ?? 'Reservation created successfully.');
            header('Location: ' . $config['base_url'] . '/reservations');
        } else {
            Session::set('error', $result['error']);
            header('Location: ' . $config['base_url'] . '/reservations/create');
        }
        exit;
    }

    public function show($id) {
        $reservation = $this->service->getReservation((int)$id);
        if (!$reservation) {
            Session::set('error', 'Reservation not found.');
            $config = require APP_ROOT . '/config/app.php';
            header('Location: ' . $config['base_url'] . '/reservations');
            exit;
        }

        View::render('admin/reservations/show', [
            'title' => 'Reservation Details',
            'reservation' => $reservation,
            'csrf_token' => CSRF::generateToken(),
        ], 'admin');
    }

    public function convert($id) {
        $reservation = $this->service->getReservation((int)$id);
        if (!$reservation) {
            Session::set('error', 'Reservation not found.');
            $config = require APP_ROOT . '/config/app.php';
            header('Location: ' . $config['base_url'] . '/reservations');
            exit;
        }

        View::render('admin/reservations/convert', [
            'title' => 'Convert Reservation to Student',
            'reservation' => $reservation,
            'csrf_token' => CSRF::generateToken(),
        ], 'admin');
    }

    public function processConvert($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        CSRF::verifyToken($_POST['csrf_token'] ?? '');
        $config = require APP_ROOT . '/config/app.php';

        $result = $this->service->convertReservationToStudent((int)$id, $_POST);
        if ($result['success']) {
            Session::set('success', $result['message'] ?? 'Reservation converted to student.');
            header('Location: ' . $config['base_url'] . '/students/view/' . (int)$result['student_id']);
        } else {
            Session::set('error', $result['error']);
            header('Location: ' . $config['base_url'] . '/reservations/convert/' . (int)$id);
        }
        exit;
    }

    public function confirm($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        CSRF::verifyToken($_POST['csrf_token'] ?? '');
        $config = require APP_ROOT . '/config/app.php';

        $result = $this->service->confirmReservation((int)$id);
        if ($result['success']) {
            Session::set('success', $result['message']);
        } else {
            Session::set('error', $result['error']);
        }

        header('Location: ' . $config['base_url'] . '/reservations');
        exit;
    }

    public function cancel($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        CSRF::verifyToken($_POST['csrf_token'] ?? '');
        $config = require APP_ROOT . '/config/app.php';

        $result = $this->service->cancelReservation((int)$id, $_POST);
        if ($result['success']) {
            Session::set('success', $result['message']);
        } else {
            Session::set('error', $result['error']);
        }

        header('Location: ' . $config['base_url'] . '/reservations');
        exit;
    }
}
