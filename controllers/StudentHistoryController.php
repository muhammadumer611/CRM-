<?php
namespace Controllers;

use Core\Response;
use Services\StudentHistoryService;
use Exception;

class StudentHistoryController {
    private $service;

    public function __construct() {
        $this->service = new StudentHistoryService();
    }

    public function index() {
        $filters = $_GET;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;

        try {
            $data = $this->service->searchHistory($filters, $page, $perPage);
            Response::success('Student history retrieved successfully.', $data);
        } catch (Exception $e) {
            Response::error('Failed to retrieve history.', 500);
        }
    }

    public function show($params) {
        try {
            $record = $this->service->getHistoryRecord($params['id']);
            Response::success('History record retrieved.', ['record' => $record]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    public function studentHistory($params) {
        $filters = $_GET;
        $filters['student_id'] = $params['student_id'];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;

        try {
            $data = $this->service->searchHistory($filters, $page, $perPage);
            Response::success('Student history retrieved.', $data);
        } catch (Exception $e) {
            Response::error('Failed to retrieve history.', 500);
        }
    }

    public function statistics() {
        try {
            $stats = $this->service->getStatistics();
            Response::success('Statistics retrieved.', ['statistics' => $stats]);
        } catch (Exception $e) {
            Response::error('Failed to retrieve statistics.', 500);
        }
    }

    public function studentRecent($params) {
        try {
            $data = $this->service->getRecentHistory($params['student_id']);
            Response::success('Recent student history retrieved.', $data);
        } catch (Exception $e) {
            Response::error('Failed to retrieve recent history.', 500);
        }
    }

    public function byEventType($params) {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;
        try {
            $data = $this->service->getByEventType($params['event_type'], $page, $perPage);
            Response::success('Event history retrieved.', $data);
        } catch (Exception $e) {
            Response::error('Failed to retrieve event history.', 500);
        }
    }

    public function byAdmin($params) {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;
        try {
            $data = $this->service->getByAdmin($params['admin_id'], $page, $perPage);
            Response::success('Admin history retrieved.', $data);
        } catch (Exception $e) {
            Response::error('Failed to retrieve admin history.', 500);
        }
    }
}
