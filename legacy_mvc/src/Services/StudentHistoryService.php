<?php
namespace App\Services;

use App\Repositories\StudentHistoryRepository;
use App\Core\Session;
use Exception;

class StudentHistoryService {
    private $repository;

    public function __construct() {
        $this->repository = new StudentHistoryRepository();
    }

    public static function record($studentId, $eventType, $description, $oldValue = null, $newValue = null, $pdo = null) {
        $adminId = Session::get('admin_id');

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

    public function searchHistory($filters, $page = 1, $perPage = 25) {
        if ($page < 1) $page = 1;
        if ($perPage < 1) $perPage = 25;
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
}
