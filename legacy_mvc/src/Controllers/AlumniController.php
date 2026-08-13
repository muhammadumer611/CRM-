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
            'title' => 'Alumni Management'
        ], 'admin');
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
            $input['remarks'] ?? ''
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
}
