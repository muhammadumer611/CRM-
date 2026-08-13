<?php
namespace App\Services;

use App\Repositories\AuditLogRepository;
use App\Core\Session;

class AuditLogService {
    private $repository;

    public function __construct() {
        $this->repository = new AuditLogRepository();
    }

    public function getLogs(array $filters = [], $page = 1, $perPage = 20, $sort = 'created_at', $direction = 'DESC') {
        $page = max(1, (int)$page);
        $perPage = max(1, min(50, (int)$perPage));
        $offset = ($page - 1) * $perPage;

        $validatedSort = in_array($sort, ['created_at', 'action', 'admin_id', 'entity_type'], true) ? $sort : 'created_at';
        $validatedDirection = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        $records = $this->repository->findAll($filters, $perPage, $offset, $validatedSort, $validatedDirection);
        $total = $this->repository->count($filters);

        return [
            'records' => $records,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => (int)$total,
                'total_pages' => (int)max(1, ceil($total / $perPage)),
            ]
        ];
    }

    public function getLogById($id) {
        $record = $this->repository->findById($id);
        if (!$record) {
            return null;
        }

        $record['old_values'] = $this->decodeJsonField($record['old_values'] ?? null);
        $record['new_values'] = $this->decodeJsonField($record['new_values'] ?? null);

        return $record;
    }

    public function validateFilters(array $filters = []) {
        $dateFrom = trim((string)($filters['date_from'] ?? ''));
        $dateTo = trim((string)($filters['date_to'] ?? ''));
        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            return 'Date from cannot be after date to.';
        }

        $search = trim((string)($filters['search'] ?? ''));
        if (strlen($search) > 255) {
            return 'Search text is too long.';
        }

        return null;
    }

    private function decodeJsonField($value) {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : $value;
    }
}
