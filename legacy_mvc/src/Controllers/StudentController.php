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
        $oldInput = Session::get('student_onboard_old', []);
        $formErrors = Session::get('student_onboard_errors', []);
        Session::remove('student_onboard_old');
        Session::remove('student_onboard_errors');

        View::render('admin/students/create', [
            'title'      => 'New Student Onboarding',
            'csrf_token' => CSRF::generateToken(),
            'rooms'      => $rooms,
            'oldInput'   => is_array($oldInput) ? $oldInput : [],
            'formErrors' => is_array($formErrors) ? $formErrors : [],
        ], 'admin');
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }

        CSRF::verifyToken($_POST['csrf_token'] ?? '');
        $config = require APP_ROOT . '/config/app.php';

        $accommodationType = strtolower(trim((string)($_POST['accommodation_type'] ?? '')));
        $errors = [];
        if ($accommodationType === '') {
            $errors['accommodation_type'] = 'Please select accommodation type.';
        }

        if ($accommodationType === 'single') {
            $required = [
                'full_name' => 'Full Name',
                'cnic' => 'CNIC',
                'phone' => 'Phone Number',
                'address' => 'Address',
                'guardian_name' => 'Guardian Name',
                'guardian_phone' => 'Guardian Phone',
                'relation' => 'Relation',
                'resident_type' => 'Resident Type',
                'room_id' => 'Room',
                'bed_number' => 'Bed',
                'joining_date' => 'Joining Date',
                'monthly_fee' => 'Monthly Fee',
            ];
            foreach ($required as $field => $label) {
                if (!isset($_POST[$field]) || trim((string)$_POST[$field]) === '') {
                    $errors[$field] = 'Please enter ' . $label . '.';
                }
            }
            if (($_POST['resident_type'] ?? '') === 'Student' && trim((string)($_POST['college_university'] ?? '')) === '') {
                $errors['college_university'] = 'Please enter College / University.';
            }
            if (($_POST['resident_type'] ?? '') === 'Job / Working' && trim((string)($_POST['job_workplace'] ?? '')) === '') {
                $errors['job_workplace'] = 'Please enter Job / Workplace.';
            }
            if (!empty($_POST['cnic']) && !StudentService::validateCnic($_POST['cnic'])) {
                $errors['cnic'] = 'Please enter a valid 13-digit CNIC.';
            }
            if (!empty($_POST['phone']) && !StudentService::validatePhone($_POST['phone'])) {
                $errors['phone'] = 'Please enter a valid 11-digit phone number.';
            }
            if (!empty($_POST['guardian_phone']) && !StudentService::validatePhone($_POST['guardian_phone'])) {
                $errors['guardian_phone'] = 'Please enter a valid 11-digit guardian phone number.';
            }
            if (!empty($_POST['blood_group']) && !in_array($_POST['blood_group'], ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], true)) {
                $errors['blood_group'] = 'Please select a valid blood group.';
            }
        } elseif ($accommodationType === 'full_room' || $accommodationType === 'full') {
            if (!isset($_POST['room_id']) || trim((string)$_POST['room_id']) === '') {
                $errors['room_id'] = 'Please select a room.';
            }
            $occupants = $_POST['occupants'] ?? [];
            if (empty($occupants) || !is_array($occupants)) {
                $errors['occupants'] = 'Please add at least one occupant.';
            }
            if (!is_array($occupants)) {
                $occupants = [];
            }
            foreach ($occupants as $index => $occupant) {
                if (!is_array($occupant)) {
                    continue;
                }
                foreach (['full_name' => 'Full Name', 'cnic' => 'CNIC', 'phone' => 'Phone', 'guardian_phone' => 'Guardian Phone'] as $field => $label) {
                    if (trim((string)($occupant[$field] ?? '')) === '') {
                        $errors["occupants[{$index}][{$field}]"] = "Please enter {$label} for person " . ((int)$index + 1) . '.';
                    }
                }
                if (!empty($occupant['cnic']) && !StudentService::validateCnic($occupant['cnic'])) {
                    $errors["occupants[{$index}][cnic]"] = 'Please enter a valid 13-digit CNIC.';
                }
                if (!empty($occupant['phone']) && !StudentService::validatePhone($occupant['phone'])) {
                    $errors["occupants[{$index}][phone]"] = 'Please enter a valid 11-digit phone number.';
                }
                if (!empty($occupant['guardian_phone']) && !StudentService::validatePhone($occupant['guardian_phone'])) {
                    $errors["occupants[{$index}][guardian_phone]"] = 'Please enter a valid 11-digit guardian phone number.';
                }
            }
            if (!isset($_POST['monthly_room_fee']) || trim((string)$_POST['monthly_room_fee']) === '') {
                $errors['monthly_room_fee'] = 'Please enter a monthly room fee.';
            }
        } else {
            if ($accommodationType !== '') {
                $errors['accommodation_type'] = 'Please select a valid accommodation type.';
            }
        }

        if (!empty($errors)) {
            $this->redirectToOnboardingForm($config, $_POST, $errors);
        }

        try {
            $result = $this->studentService->onboardStudent($_POST);
        } catch (\Throwable $e) {
            $message = $this->safeOnboardingError($e->getMessage());
            $errors = [$this->fieldForOnboardingError($message, $_POST) => $message];
            $this->redirectToOnboardingForm($config, $_POST, $errors);
        }

        if ($result['success']) {
            Session::remove('student_onboard_old');
            Session::remove('student_onboard_errors');
            $studentStr = !empty($result['student_id_str']) ? ' (' . htmlspecialchars($result['student_id_str']) . ')' : '';
            Session::set('success', 'Student onboarded successfully' . $studentStr . '.');
            header('Location: ' . $config['base_url'] . '/students');
        } else {
            $message = $this->safeOnboardingError((string)($result['error'] ?? ''));
            $errors = [$this->fieldForOnboardingError($message, $_POST) => $message];
            $this->redirectToOnboardingForm($config, $_POST, $errors);
        }
        exit;
    }

    private function redirectToOnboardingForm(array $config, array $input, array $errors): void {
        Session::set('student_onboard_old', $this->preservableOnboardingInput($input));
        Session::set('student_onboard_errors', $this->normalizeOnboardingErrors($errors, $input));
        Session::set('error', 'Please correct the highlighted onboarding fields and try again.');
        header('Location: ' . $config['base_url'] . '/students/create');
        exit;
    }

    private function preservableOnboardingInput(array $input): array {
        $allowedFields = [
            'accommodation_type', 'room_id', 'bed_number', 'joining_date', 'full_name', 'cnic', 'phone', 'address',
            'blood_group', 'guardian_name', 'guardian_phone', 'relation', 'resident_type', 'college_university',
            'job_workplace', 'vehicle_number', 'vehicle_type', 'vehicle_type_other', 'note', 'monthly_fee',
            'monthly_room_fee', 'security_deposit', 'first_month_billing_mode', 'first_month_discount',
            'first_month_discount_reason',
        ];
        $preserved = [];
        foreach ($allowedFields as $field) {
            if (isset($input[$field]) && is_scalar($input[$field])) {
                $preserved[$field] = substr((string)$input[$field], 0, 2048);
            }
        }

        $occupantFields = ['full_name', 'cnic', 'phone', 'guardian_phone', 'blood_group'];
        if (isset($input['occupants']) && is_array($input['occupants'])) {
            $position = 0;
            foreach (array_slice($input['occupants'], 0, 20, true) as $index => $occupant) {
                if (!is_array($occupant)) {
                    continue;
                }
                foreach ($occupantFields as $field) {
                    if (isset($occupant[$field]) && is_scalar($occupant[$field])) {
                        $preserved['occupants'][$position][$field] = substr((string)$occupant[$field], 0, 2048);
                    }
                }
                $position++;
            }
        }

        return $preserved;
    }

    private function normalizeOnboardingErrors(array $errors, array $input): array {
        if (!isset($input['occupants']) || !is_array($input['occupants'])) {
            return $errors;
        }

        $indexMap = [];
        foreach (array_keys(array_slice($input['occupants'], 0, 20, true)) as $position => $originalIndex) {
            $indexMap[(string)$originalIndex] = $position;
        }

        $normalized = [];
        foreach ($errors as $field => $message) {
            if (preg_match('/^occupants\[([^\]]+)\](.*)$/', (string)$field, $matches)
                && isset($indexMap[$matches[1]])) {
                $field = 'occupants[' . $indexMap[$matches[1]] . ']' . $matches[2];
            }
            $normalized[$field] = $message;
        }
        return $normalized;
    }

    private function fieldForOnboardingError(string $message, array $input): string {
        $message = strtolower($message);
        $isFullRoom = in_array($input['accommodation_type'] ?? '', ['full_room', 'full'], true);
        $occupantKeys = isset($input['occupants']) && is_array($input['occupants']) ? array_keys($input['occupants']) : [];
        $occupantIndex = 0;
        if ($isFullRoom && preg_match('/person\s+(\d+)/', $message, $matches)) {
            $position = max(0, (int)$matches[1] - 1);
            $occupantIndex = $occupantKeys[$position] ?? $position;
        }
        if ($isFullRoom && strpos($message, 'blood group') !== false) return "occupants[{$occupantIndex}][blood_group]";
        if ($isFullRoom && strpos($message, 'cnic') !== false) return "occupants[{$occupantIndex}][cnic]";
        if ($isFullRoom && strpos($message, 'guardian phone') !== false) return "occupants[{$occupantIndex}][guardian_phone]";
        if ($isFullRoom && strpos($message, 'phone') !== false) return "occupants[{$occupantIndex}][phone]";
        if (strpos($message, 'cnic') !== false) return 'cnic';
        if (strpos($message, 'guardian phone') !== false) return 'guardian_phone';
        if (strpos($message, 'phone') !== false) return 'phone';
        if (strpos($message, 'blood group') !== false) return 'blood_group';
        if (strpos($message, 'college') !== false || strpos($message, 'university') !== false) return 'college_university';
        if (strpos($message, 'workplace') !== false) return 'job_workplace';
        if (strpos($message, 'joining date') !== false) return 'joining_date';
        if (strpos($message, 'security') !== false) return 'security_deposit';
        if (strpos($message, 'first month') !== false || strpos($message, 'adjustment') !== false || strpos($message, 'discount') !== false) return 'first_month_discount';
        if (strpos($message, 'fee') !== false || strpos($message, 'billing') !== false) {
            return in_array($input['accommodation_type'] ?? '', ['full_room', 'full'], true) ? 'monthly_room_fee' : 'monthly_fee';
        }
        if (strpos($message, 'bed') !== false || strpos($message, 'reserved') !== false || strpos($message, 'occupied') !== false || strpos($message, 'capacity') !== false) {
            return in_array($input['accommodation_type'] ?? '', ['full_room', 'full'], true) ? 'room_id' : 'bed_number';
        }
        if (strpos($message, 'room') !== false) return 'room_id';
        if (strpos($message, 'relation') !== false) return 'relation';
        if (strpos($message, 'guardian name') !== false) return 'guardian_name';
        if (strpos($message, 'address') !== false) return 'address';
        if (strpos($message, 'name') !== false) return 'full_name';
        return 'form';
    }

    private function safeOnboardingError(string $message): string {
        $message = trim($message);
        if ($message === '' || preg_match('/SQLSTATE|PDOException|database connection|integrity constraint|foreign key constraint/i', $message)) {
            return 'Unable to complete onboarding. No student, allocation, fee, or deposit was saved. Please review the form or contact an administrator.';
        }
        return $message;
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
        $roomOptions     = $this->studentService->getRoomOptionsForStudent($id);

        // Propagate back URL so Cancel/Save can return to origin (active list or student list)
        $backUrl = !empty($_GET['from']) ? htmlspecialchars($_GET['from'], ENT_QUOTES, 'UTF-8') : null;

        View::render('admin/students/edit', [
            'title'           => 'Edit Student — ' . htmlspecialchars($student['full_name']),
            'student'         => $student,
            'feeHistory'      => $feeHistory,
            'securityDeposit' => $securityDeposit,
            'roomOptions'     => $roomOptions,
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
        $remarks = '';
        $securityDeduction = isset($_POST['security_deduction']) && $_POST['security_deduction'] !== '' ? (float)$_POST['security_deduction'] : 0.0;
        $securityRefundRemarks = trim((string)($_POST['security_refund_remarks'] ?? ''));
        $checkedOutByName = trim((string)($_POST['checked_out_by_name'] ?? (Auth::user() ?: 'Admin')));
        $processedByName = trim((string)($_POST['processed_by_name'] ?? $checkedOutByName));

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
