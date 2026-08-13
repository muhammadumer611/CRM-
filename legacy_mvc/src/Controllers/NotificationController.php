<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Session;
use App\Core\View;
use App\Services\NotificationService;

class NotificationController {
    private $notificationService;

    public function __construct() {
        Auth::check();
        $this->notificationService = new NotificationService();
    }

    public function index() {
        $this->notificationService->generateOperationalAlerts();

        $filters = [
            'type' => trim((string)($_GET['type'] ?? '')),
            'priority' => trim((string)($_GET['priority'] ?? '')),
            'read_status' => trim((string)($_GET['read_status'] ?? '')),
            'date_from' => trim((string)($_GET['date_from'] ?? '')),
            'date_to' => trim((string)($_GET['date_to'] ?? '')),
            'search' => trim((string)($_GET['search'] ?? '')),
        ];

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 20;
        $result = $this->notificationService->getNotifications($filters, $page, $perPage);

        View::render('admin/notifications/index', [
            'title' => 'Notifications',
            'filters' => $filters,
            'notifications' => $result['items'],
            'csrf_token' => CSRF::generateToken(),
            'pagination' => [
                'current_page' => $result['page'],
                'per_page' => $result['per_page'],
                'total_pages' => $result['total_pages'],
                'total_records' => $result['total'],
            ],
        ], 'admin');
    }

    public function markRead($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        CSRF::verifyToken($_POST['csrf_token'] ?? '');
        $this->notificationService->markAsRead($id);

        Session::set('success', 'Notification marked as read.');
        header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/notifications');
        exit;
    }

    public function markAllRead() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        CSRF::verifyToken($_POST['csrf_token'] ?? '');
        $this->notificationService->markAllRead();

        Session::set('success', 'All notifications marked as read.');
        header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/notifications');
        exit;
    }
}
