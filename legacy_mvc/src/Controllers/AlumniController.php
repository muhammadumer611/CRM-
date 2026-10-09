<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\AlumniService;

class AlumniController {
    private $alumniService;

    public function __construct() {
        $this->alumniService = new AlumniService();
    }

    public function index() {
        Auth::check();
        View::render('admin/alumni/index', [
            'title' => 'Alumni Management',
            'csrf_token' => \App\Core\CSRF::generateToken()
        ], 'admin');
    }

    public function reactivateForm($id) {
        Auth::check();
        try {
            $data = $this->alumniService->getReactivationFormData((int)$id);
        } catch (\Throwable $e) {
            \App\Core\Session::set('error', $e->getMessage());
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/alumni');
            exit;
        }

        $oldInput = \App\Core\Session::get('old_reactivation_input', []);
        \App\Core\Session::remove('old_reactivation_input');
        View::render('admin/alumni/reactivate', [
            'title' => 'Student Re-Admission',
            'csrf_token' => \App\Core\CSRF::generateToken(),
            'data' => $data,
            'oldInput' => is_array($oldInput) ? $oldInput : [],
            'processed_by_name' => Auth::user() ?: 'Admin'
        ], 'admin');
    }

    public function reactivate($id) {
        Auth::check();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        \App\Core\CSRF::verifyToken($_POST['csrf_token'] ?? '');
        $config = require APP_ROOT . '/config/app.php';
        $input = [
            'joining_date' => $_POST['joining_date'] ?? '',
            'room_id' => $_POST['room_id'] ?? '',
            'bed_number' => $_POST['bed_number'] ?? '',
            'first_month_billing_mode' => $_POST['first_month_billing_mode'] ?? 'full',
            'first_month_discount' => $_POST['first_month_discount'] ?? '0',
            'first_month_discount_reason' => $_POST['first_month_discount_reason'] ?? '',
            'remarks' => $_POST['remarks'] ?? '',
            'allocated_by_name' => $_POST['allocated_by_name'] ?? (Auth::user() ?: 'Admin'),
        ];
        \App\Core\Session::set('old_reactivation_input', $input);

        $result = $this->alumniService->reactivateAlumni((int)$id, $input);
        if (!$result['success']) {
            \App\Core\Session::set('error', $result['error']);
            header('Location: ' . $config['base_url'] . '/alumni/readmit/' . (int)$id);
            exit;
        }

        \App\Core\Session::remove('old_reactivation_input');
        $invoiceNotice = $result['invoice_created']
            ? ' First-month invoice created using the existing billing rules.'
            : ' The existing invoice for this calendar month was preserved; no duplicate invoice was created.';
        \App\Core\Session::set(
            'success',
            'Student re-admitted to ' . $result['room'] . ', Bed ' . $result['bed'] . '.' . $invoiceNotice . ' No new security deposit was created.'
        );
        header('Location: ' . $config['base_url'] . '/alumni');
        exit;
    }

    public function apiGetAll() {
        Auth::checkAPI();
        header('Content-Type: application/json');
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
        
        $filters = [
            'search' => $_GET['search'] ?? null
        ];

        try {
            $data = $this->alumniService->getAllAlumni($filters, $page, $limit);
            echo json_encode([
                'success' => true,
                'message' => 'Alumni retrieved successfully.',
                'data' => $data,
                'errors' => null
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Unable to retrieve alumni.',
                'data' => null,
                'errors' => [$e->getMessage()]
            ]);
        }
        exit;
    }

    public function apiGetById($id) {
        Auth::checkAPI();
        header('Content-Type: application/json');
        try {
            $data = $this->alumniService->getAlumniById($id);
            if (!$data) throw new \Exception("Alumni record not found.");
            
            echo json_encode([
                'success' => true,
                'message' => 'Alumni details retrieved successfully.',
                'data' => $data,
                'errors' => null
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Unable to retrieve alumni details.',
                'data' => null,
                'errors' => [$e->getMessage()]
            ]);
        }
        exit;
    }

    public function apiGetByStudentId($studentIdStr) {
        Auth::checkAPI();
        header('Content-Type: application/json');
        try {
            $data = $this->alumniService->getAlumniByOriginalStudentId($studentIdStr);
            if (!$data) {
                // Return success=true but data=null so frontend knows they aren't alumni yet
                echo json_encode([
                    'success' => true,
                    'message' => 'Student is not an alumni.',
                    'data' => null,
                    'errors' => null
                ]);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Alumni details retrieved successfully.',
                'data' => $data,
                'errors' => null
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Unable to retrieve alumni details.',
                'data' => null,
                'errors' => [$e->getMessage()]
            ]);
        }
        exit;
    }

    public function apiTransfer() {
        Auth::checkAPI();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }

        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $token = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        try {
            \App\Core\CSRF::verifyTokenJson($token);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'CSRF validation failed.',
                'data' => null,
                'errors' => ['CSRF token missing or invalid.']
            ]);
            exit;
        }
        
        if (empty($input['student_id']) || empty($input['leaving_date']) || empty($input['leaving_reason'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Student ID, leaving date, and reason are required.',
                'data' => null,
                'errors' => ['Missing required fields.']
            ]);
            exit;
        }

        $result = $this->alumniService->convertToAlumni(
            $input['student_id'],
            $input['leaving_date'],
            $input['leaving_reason'],
            $input['remarks'] ?? '',
            Auth::user() ?: 'Admin',
            Auth::user() ?: 'Admin',
            $input['security_option'] ?? 'KEEP_FOR_LATER',
            $input['security_deduction'] ?? 0,
            $input['security_remarks'] ?? ''
        );

        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => 'Student successfully marked as alumni.',
                'data' => ['alumni_id' => $result['alumni_id']],
                'errors' => null
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['error'],
                'data' => null,
                'errors' => [$result['error']]
            ]);
        }
        exit;
    }

    public function apiSecurityReturn($id) {
        Auth::checkAPI();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        try {
            \App\Core\CSRF::verifyTokenJson($input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        } catch (\Exception $e) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'CSRF validation failed.',
                'errors' => ['CSRF token missing or invalid.']
            ]);
            exit;
        }

        $result = $this->alumniService->settleAlumniSecurity(
            (int)$id,
            $input['security_deduction'] ?? null,
            $input['remarks'] ?? '',
            $input['processed_by_name'] ?? ''
        );

        if (!$result['success']) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => $result['error'],
                'errors' => [$result['error']]
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Security return recorded. Remaining held amount is Rs. 0.00.',
            'data' => $result,
            'errors' => null
        ]);
        exit;
    }
}
