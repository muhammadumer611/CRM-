<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\StudentHistoryService;

class HistoryController {
    private $historyService;

    public function __construct() {
        Auth::check();
        $this->historyService = new StudentHistoryService();
    }

    public function index() {
        View::render('admin/history/index', [
            'title' => 'Student History & Audit Trail'
        ], 'admin');
    }

    public function apiGetAll() {
        header('Content-Type: application/json');
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
        
        $filters = [
            'student_id' => $_GET['student_id'] ?? null,
            'event_type' => $_GET['event_type'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'keyword' => $_GET['search'] ?? null
        ];

        try {
            $data = $this->historyService->searchHistory($filters, $page, $limit);
            echo json_encode([
                'success' => true,
                'message' => 'Student history retrieved successfully.',
                'data' => $data,
                'errors' => null
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Unable to retrieve student history.',
                'data' => null,
                'errors' => [$e->getMessage()]
            ]);
        }
        exit;
    }

    public function apiGetStudent($studentId) {
        header('Content-Type: application/json');
        try {
            $data = $this->historyService->searchHistory(['student_id' => $studentId], 1, 100);
            echo json_encode([
                'success' => true,
                'message' => 'Student history retrieved successfully.',
                'data' => $data,
                'errors' => null
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Unable to retrieve student history.',
                'data' => null,
                'errors' => [$e->getMessage()]
            ]);
        }
        exit;
    }

    public function apiGetById($id) {
        header('Content-Type: application/json');
        try {
            $data = $this->historyService->getHistoryRecord($id);
            echo json_encode([
                'success' => true,
                'message' => 'History record retrieved successfully.',
                'data' => $data,
                'errors' => null
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Unable to retrieve history record.',
                'data' => null,
                'errors' => [$e->getMessage()]
            ]);
        }
        exit;
    }

    public function apiCount() {
        header('Content-Type: application/json');
        try {
            $data = $this->historyService->searchHistory([], 1, 1);
            echo json_encode([
                'success' => true,
                'message' => 'History count retrieved successfully.',
                'data' => ['total' => $data['pagination']['total_records']],
                'errors' => null
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Unable to retrieve history count.',
                'data' => null,
                'errors' => [$e->getMessage()]
            ]);
        }
        exit;
    }
}
