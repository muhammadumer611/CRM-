<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Session;
use App\Core\CSRF;
use App\Services\StudentService;
use App\Services\StudentAccountService;
use App\Services\RoomService;

class StudentController {
    private $studentService;

    public function __construct() {
        Auth::check();
        $this->studentService = new StudentService();
    }

    public function index() {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 15;
        
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];

        $result = $this->studentService->getAllStudents($filters, $page, $perPage);
        
        View::render('admin/students/index', [
            'title'    => 'Students',
            'students' => $result['data'],
            'total'    => $result['total'],
            'page'     => $page,
            'perPage'  => $perPage,
            'filters'  => $filters
        ], 'admin');
    }

    public function create() {
        $roomService = new RoomService();
        $rooms = $roomService->getAllAvailableRooms();

        View::render('admin/students/create', [
            'title'      => 'New Student Onboarding',
            'csrf_token' => CSRF::generateToken(),
            'rooms'      => $rooms
        ], 'admin');
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }

        CSRF::verifyToken($_POST['csrf_token'] ?? '');
        $config = require APP_ROOT . '/config/app.php';

        $accommodationType = strtolower(trim((string)($_POST['accommodation_type'] ?? '')));
        if ($accommodationType === '') {
            Session::set('error', 'Please select accommodation type.');
            header('Location: ' . $config['base_url'] . '/students/create');
            exit;
        }

        if ($accommodationType === 'single') {
            $required = ['full_name', 'cnic', 'phone', 'address', 'guardian_name', 'guardian_phone', 'guardian_cnic', 'relation', 'room_id', 'bed_number', 'joining_date', 'monthly_fee'];
            foreach ($required as $field) {
                if (!isset($_POST[$field]) || trim((string)$_POST[$field]) === '') {
                    Session::set('error', 'Please fill all required fields: ' . str_replace('_', ' ', $field) . '.');
                    header('Location: ' . $config['base_url'] . '/students/create');
                    exit;
                }
            }
        } elseif ($accommodationType === 'full_room' || $accommodationType === 'full') {
            if (!isset($_POST['room_id']) || trim((string)$_POST['room_id']) === '') {
                Session::set('error', 'Please select a room.');
                header('Location: ' . $config['base_url'] . '/students/create');
                exit;
            }
            $occupants = $_POST['occupants'] ?? [];
            if (empty($occupants) || !is_array($occupants)) {
                Session::set('error', 'Please add at least one occupant.');
                header('Location: ' . $config['base_url'] . '/students/create');
                exit;
            }
            foreach ($occupants as $index => $occupant) {
                if (empty($occupant['full_name'] ?? '') || empty($occupant['cnic'] ?? '')) {
                    Session::set('error', 'Please enter a valid name and CNIC for each person.');
                    header('Location: ' . $config['base_url'] . '/students/create');
                    exit;
                }
            }
            if (!isset($_POST['monthly_room_fee']) || trim((string)$_POST['monthly_room_fee']) === '') {
                Session::set('error', 'Please enter a monthly room fee.');
                header('Location: ' . $config['base_url'] . '/students/create');
                exit;
            }
        } else {
            Session::set('error', 'Please select accommodation type.');
            header('Location: ' . $config['base_url'] . '/students/create');
            exit;
        }

        $result = $this->studentService->onboardStudent($_POST);

        if ($result['success']) {
            $studentStr = !empty($result['student_id_str']) ? ' (' . htmlspecialchars($result['student_id_str']) . ')' : '';
            Session::set('success', 'Student onboarded successfully' . $studentStr . '.');
            header('Location: ' . $config['base_url'] . '/students');
        } else {
            Session::set('error', $result['error']);
            header('Location: ' . $config['base_url'] . '/students/create');
        }
        exit;
    }

    public function edit($id) {
        $student = $this->studentService->getStudent($id);
        if (!$student) {
            Session::set('error', 'Student not found.');
            $config = require APP_ROOT . '/config/app.php';
            header('Location: ' . $config['base_url'] . '/students');
            exit;
        }

        $feeHistory      = $this->studentService->getStudentFeeHistory($id);
        $securityDeposit = $this->studentService->getStudentSecurityDeposit($id);

        View::render('admin/students/edit', [
            'title'           => 'Edit Student — ' . htmlspecialchars($student['full_name']),
            'student'         => $student,
            'feeHistory'      => $feeHistory,
            'securityDeposit' => $securityDeposit,
            'csrf_token'      => CSRF::generateToken()
        ], 'admin');
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }
        
        CSRF::verifyToken($_POST['csrf_token'] ?? '');
        $config = require APP_ROOT . '/config/app.php';

        $result = $this->studentService->updateStudent($id, $_POST);
        
        if ($result['success']) {
            Session::set('success', 'Student updated successfully.');
            header('Location: ' . $config['base_url'] . '/students');
        } else {
            Session::set('error', $result['error']);
            header('Location: ' . $config['base_url'] . '/students/edit/' . $id);
        }
        exit;
    }

    public function show($id) {
        $student = $this->studentService->getStudent($id);
        if (!$student) {
            Session::set('error', 'Student not found.');
            $config = require APP_ROOT . '/config/app.php';
            header('Location: ' . $config['base_url'] . '/students');
            exit;
        }

        $feeHistory      = $this->studentService->getStudentFeeHistory($id);
        $securityDeposit = $this->studentService->getStudentSecurityDeposit($id);

        View::render('admin/students/show', [
            'title'           => 'Student Profile — ' . htmlspecialchars($student['full_name']),
            'student'         => $student,
            'feeHistory'      => $feeHistory,
            'securityDeposit' => $securityDeposit,
            'csrf_token'      => CSRF::generateToken()
        ], 'admin');
    }

    public function checkout($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }
        
        CSRF::verifyToken($_POST['csrf_token'] ?? '');
        $config = require APP_ROOT . '/config/app.php';

        $leavingDate = !empty($_POST['leaving_date']) ? $_POST['leaving_date'] : date('Y-m-d');
        $leavingReason = !empty($_POST['leaving_reason']) ? trim($_POST['leaving_reason']) : 'Course Completed';
        $remarks = trim($_POST['remarks'] ?? '');
        $securityDeduction = isset($_POST['security_deduction']) && $_POST['security_deduction'] !== '' ? (float)$_POST['security_deduction'] : 0.0;
        $securityRefundRemarks = trim($_POST['security_refund_remarks'] ?? '');

        $alumniService = new \App\Services\AlumniService();
        $result = $alumniService->convertToAlumni(
            $id,
            $leavingDate,
            $leavingReason,
            $remarks,
            $securityDeduction,
            $securityRefundRemarks
        );

        if ($result['success']) {
            Session::set('success', 'Student checkout completed successfully and marked as alumni.');
            header('Location: ' . $config['base_url'] . '/alumni');
        } else {
            Session::set('error', $result['error']);
            header('Location: ' . $config['base_url'] . '/students/view/' . $id);
        }
        exit;
    }

    public function account($id) {
        $student = $this->studentService->getStudent($id);
        if (!$student) {
            Session::set('error', 'Student not found.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/students');
            exit;
        }

        $accountService = new StudentAccountService();
        $account = $accountService->getStudentAccount($id);

        View::render('admin/students/account', [
            'title' => 'Student Account Statement',
            'student' => $student,
            'account' => $account,
            'csrf_token' => CSRF::generateToken()
        ], 'admin');
    }
}
