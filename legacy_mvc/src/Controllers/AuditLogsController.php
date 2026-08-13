<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Services\AuditLogService;

class AuditLogsController {
    private $auditLogService;

    public function __construct() {
        Auth::check();
        $this->auditLogService = new AuditLogService();
    }

    public function index() {
        $filters = [
            'action' => trim((string)($_GET['action'] ?? '')),
            'entity_type' => trim((string)($_GET['entity_type'] ?? '')),
            'admin' => trim((string)($_GET['admin'] ?? '')),
            'date_from' => trim((string)($_GET['date_from'] ?? '')),
            'date_to' => trim((string)($_GET['date_to'] ?? '')),
            'search' => trim((string)($_GET['search'] ?? '')),
        ];

        $validationError = $this->auditLogService->validateFilters($filters);
        if ($validationError) {
            Session::set('error', $validationError);
        }

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 20;
        $sort = in_array($_GET['sort'] ?? '', ['created_at', 'action', 'admin_id', 'entity_type'], true) ? $_GET['sort'] : 'created_at';
        $direction = strtoupper((string)($_GET['direction'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $result = $this->auditLogService->getLogs($filters, $page, $perPage, $sort, $direction);

        View::render('admin/audit-logs/index', [
            'title' => 'Audit Logs',
            'filters' => $filters,
            'logs' => $result['records'],
            'pagination' => $result['pagination'],
            'sort' => $sort,
            'direction' => $direction,
        ], 'admin');
    }

    public function show($id) {
        $record = $this->auditLogService->getLogById($id);
        if (!$record) {
            Session::set('error', 'Audit log not found.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/audit-logs');
            exit;
        }

        View::render('admin/audit-logs/show', [
            'title' => 'Audit Log Details',
            'record' => $record,
        ], 'admin');
    }
}
