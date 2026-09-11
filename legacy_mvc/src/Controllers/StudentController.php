<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Session;
use App\Core\CSRF;
use App\Services\StudentService;
use App\Services\StudentAccountService;
use App\Services\FeeService;
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
            'status' => 'Active',
            'district' => $_GET['district'] ?? '',
            'room' => $_GET['room'] ?? '',
            'bed' => $_GET['bed'] ?? ''
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
            $required = ['full_name', 'cnic', 'phone', 'address', 'guardian_name', 'guardian_phone', 'guardian_cnic', 'relation', 'room_id', 'bed_number', 'joining_date', 'monthly_fee', 'added_by_name'];
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
            if (!isset($_POST['added_by_name']) || trim((string)$_POST['added_by_name']) === '') {
                Session::set('error', 'Added By is required. Please enter the staff member name.');
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
            http_response_code(404);
            Session::set('error', 'Student not found.');
            $config = require APP_ROOT . '/config/app.php';
            header('Location: ' . $config['base_url'] . '/students');
            exit;
        }
        if ($student['status'] !== 'Active') {
            Session::set('error', 'This student is inactive/alumni. Active student editing is unavailable.');
            $config = require APP_ROOT . '/config/app.php';
            header('Location: ' . $config['base_url'] . '/students/view/' . (int)$id);
            exit;
        }

        $feeHistory      = $this->studentService->getStudentFeeHistory($id);
        $securityDeposit = $this->studentService->getStudentSecurityDeposit($id);

        // Propagate back URL so Cancel/Save can return to origin (active list or student list)
        $backUrl = !empty($_GET['from']) ? htmlspecialchars($_GET['from'], ENT_QUOTES, 'UTF-8') : null;

        View::render('admin/students/edit', [
            'title'           => 'Edit Student — ' . htmlspecialchars($student['full_name']),
            'student'         => $student,
            'feeHistory'      => $feeHistory,
            'securityDeposit' => $securityDeposit,
            'csrf_token'      => CSRF::generateToken(),
            'backUrl'         => $backUrl
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
            // Redirect to student profile view so the admin sees the saved data immediately
            $from = !empty($_POST['_back_url']) ? $_POST['_back_url'] : '';
            header('Location: ' . $config['base_url'] . '/students/view/' . (int)$id . (!empty($from) ? '?from=' . urlencode($from) : ''));
        } else {
            Session::set('error', $result['error']);
            $from = !empty($_POST['_back_url']) ? '?from=' . urlencode($_POST['_back_url']) : '';
            header('Location: ' . $config['base_url'] . '/students/edit/' . (int)$id . $from);
        }
        exit;
    }


    public function activeList() {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;

        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => 'Active',   // locked — this page is Active-only
            'district' => $_GET['district'] ?? '',
            'room' => $_GET['room'] ?? '',
            'bed' => $_GET['bed'] ?? ''
        ];

        $result = $this->studentService->getAllStudents($filters, $page, $perPage);

        View::render('admin/students/active', [
            'title'    => 'Active Students',
            'students' => $result['data'],
            'total'    => $result['total'],
            'page'     => $page,
            'perPage'  => $perPage,
            'filters'  => $filters
        ], 'admin');
    }

    public function show($id) {
        $student = $this->studentService->getStudent($id);
        if (!$student) {
            http_response_code(404);
            Session::set('error', 'Student not found.');
            $config = require APP_ROOT . '/config/app.php';
            header('Location: ' . $config['base_url'] . '/students');
            exit;
        }

        $feeHistory      = $this->studentService->getStudentFeeHistory($id);
        $securityDeposit = $this->studentService->getStudentSecurityDeposit($id);

        // Allow callers to pass a custom back URL (e.g. from active list)
        $backUrl = !empty($_GET['from']) ? htmlspecialchars($_GET['from'], ENT_QUOTES, 'UTF-8') : null;

        View::render('admin/students/show', [
            'title'           => 'Student Profile — ' . htmlspecialchars($student['full_name']),
            'student'         => $student,
            'feeHistory'      => $feeHistory,
            'securityDeposit' => $securityDeposit,
            'csrf_token'      => CSRF::generateToken(),
            'backUrl'         => $backUrl
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
        $checkedOutByName = trim((string)($_POST['checked_out_by_name'] ?? ''));
        $processedByName = trim((string)($_POST['processed_by_name'] ?? ''));

        if ($checkedOutByName === '') {
            Session::set('error', 'Checked Out By is required. Please enter the staff member name who processed the checkout.');
            header('Location: ' . $config['base_url'] . '/students/view/' . $id);
            exit;
        }

        if ($processedByName === '') {
            Session::set('error', 'Processed By is required. Please enter the staff member name.');
            header('Location: ' . $config['base_url'] . '/students/view/' . $id);
            exit;
        }

        $alumniService = new \App\Services\AlumniService();
        $result = $alumniService->convertToAlumni(
            $id,
            $leavingDate,
            $leavingReason,
            $remarks,
            $securityDeduction,
            $securityRefundRemarks,
            $checkedOutByName,
            $processedByName
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
        if ($student['status'] !== 'Active') {
            Session::set('error', 'This student is inactive/alumni. Current resident account actions are unavailable.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/students/view/' . (int)$id);
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

    public function accountPayment($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }

        CSRF::verifyToken($_POST['csrf_token'] ?? '');
        $config = require APP_ROOT . '/config/app.php';
        $accountService = new StudentAccountService();
        $account = $accountService->getStudentAccount((int)$id);
        $currentFee = $account['active_fee'] ?? $account['current_fee'] ?? null;

        if (!$currentFee) {
            Session::set('error', 'This student does not have a valid monthly fee configured.');
            header('Location: ' . $config['base_url'] . '/students/account/' . (int)$id);
            exit;
        }

        $result = (new FeeService())->payFee((int)$currentFee['id'], $_POST);
        Session::set($result['success'] ? 'success' : 'error', $result['success']
            ? 'Payment recorded successfully. Receipt: ' . ($result['receipt_number'] ?? 'created')
            : $result['error']);
        header('Location: ' . $config['base_url'] . '/students/account/' . (int)$id);
        exit;
    }

    public function accountInvoice($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }

        CSRF::verifyToken($_POST['csrf_token'] ?? '');
        $config = require APP_ROOT . '/config/app.php';
        $result = (new StudentAccountService())->createNextMonthlyFee((int)$id);
        Session::set($result['success'] ? 'success' : 'error', $result['success']
            ? 'Monthly fee created for ' . date('F Y', mktime(0, 0, 0, $result['month'], 1, $result['year'])) . '.'
            : $result['error']);
        header('Location: ' . $config['base_url'] . '/students/account/' . (int)$id);
        exit;
    }
}
