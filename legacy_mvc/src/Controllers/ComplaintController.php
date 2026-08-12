<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Session;
use App\Core\CSRF;
use App\Services\ComplaintService;

class ComplaintController {
    private $complaintService;

    public function __construct() {
        Auth::check();
        $this->complaintService = new ComplaintService();
    }

    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = $page > 0 ? $page : 1;
        $perPage = 15;
        
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? ''
        ];

        $result = $this->complaintService->getAllComplaints($filters, $page, $perPage);
        
        View::render('admin/complaints/index', [
            'title' => 'Complaints',
            'complaints' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => $perPage,
            'filters' => $filters
        ], 'admin');
    }

    public function edit($id) {
        $complaint = $this->complaintService->getComplaint($id);
        if (!$complaint) {
            Session::set('error', 'Complaint not found.');
            header('Location: ' . (require __DIR__ . '/../../config/app.php')['base_url'] . '/complaints');
            exit;
        }

        View::render('admin/complaints/edit', [
            'title' => 'Update Complaint',
            'complaint' => $complaint,
            'csrf_token' => CSRF::generateToken()
        ], 'admin');
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }
        
        CSRF::verifyToken($_POST['csrf_token'] ?? '');

        if (empty($_POST['status'])) {
            Session::set('error', 'Status is required.');
            header('Location: ' . (require __DIR__ . '/../../config/app.php')['base_url'] . '/complaints/edit/' . $id);
            exit;
        }

        $result = $this->complaintService->updateComplaint($id, $_POST);
        
        if ($result['success']) {
            Session::set('success', 'Complaint updated successfully.');
            header('Location: ' . (require __DIR__ . '/../../config/app.php')['base_url'] . '/complaints');
        } else {
            Session::set('error', $result['error']);
            header('Location: ' . (require __DIR__ . '/../../config/app.php')['base_url'] . '/complaints/edit/' . $id);
        }
        exit;
    }
}
