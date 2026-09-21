<?php
namespace Services;

use Repositories\StudentHistoryRepository;
use Core\Database;
use Exception;

class StudentHistoryService {
    private $repository;

    public function __construct() {
        $this->repository = new StudentHistoryRepository();
    }

    public static function record($studentId, $eventType, $description, $oldValue = null, $newValue = null, $adminId = null, $pdo = null) {
        if ($adminId === null && isset($_SESSION['admin_id'])) {
            $adminId = $_SESSION['admin_id'];
        }

        $repo = new StudentHistoryRepository();
        return $repo->create([
            'student_id' => $studentId,
            'event_type' => $eventType,
            'description' => $description,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'admin_id' => $adminId
        ], $pdo);
    }

    public function searchHistory($filters, $page = 1, $perPage = 50) {
        if ($page < 1) $page = 1;
        if ($perPage < 1) $perPage = 10;
        if ($perPage > 100) $perPage = 100;

        $offset = ($page - 1) * $perPage;
        
        $result = $this->repository->search($filters, $perPage, $offset);
        
        return [
            'records' => $result['data'],
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total_records' => (int)$result['total'],
                'total_pages' => ceil($result['total'] / $perPage)
            ]
        ];
    }

    public function getHistoryRecord($id) {
        $record = $this->repository->findById($id);
        if (!$record) {
            throw new Exception("History record not found.");
        }
        if ($record['old_value']) $record['old_value'] = json_decode($record['old_value'], true);
        if ($record['new_value']) $record['new_value'] = json_decode($record['new_value'], true);
        return $record;
    }

    public function getStatistics() {
        return $this->repository->getStatistics();
    }

    public function getRecentHistory($studentId = null, $limit = 10) {
        if ($studentId) {
            return $this->repository->getByStudentId($studentId, $limit, 0);
        }
        return $this->repository->getRecent($limit);
    }

    public function getByEventType($eventType, $page = 1, $perPage = 50) {
        $offset = ($page - 1) * $perPage;
        return $this->repository->getByEventType($eventType, $perPage, $offset);
    }

    public function getByAdmin($adminId, $page = 1, $perPage = 50) {
        $offset = ($page - 1) * $perPage;
        return $this->repository->getByAdmin($adminId, $perPage, $offset);
    }
}
