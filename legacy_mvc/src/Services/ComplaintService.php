<?php
namespace App\Services;

use App\Repositories\ComplaintRepository;
use App\Repositories\AdminRepository;
use App\Core\Session;

class ComplaintService {
    private $complaintRepo;
    private $adminRepo;

    public function __construct() {
        $this->complaintRepo = new ComplaintRepository();
        $this->adminRepo = new AdminRepository();
    }

    public function getAllComplaints($filters, $page, $perPage) {
        $offset = ($page - 1) * $perPage;
        return [
            'data' => $this->complaintRepo->findAll($filters, $perPage, $offset),
            'total' => $this->complaintRepo->count($filters)
        ];
    }

    public function getComplaint($id) {
        return $this->complaintRepo->findById($id);
    }

    public function updateComplaint($id, $data) {
        $complaint = $this->complaintRepo->findById($id);
        if (!$complaint) return ['success' => false, 'error' => 'Complaint not found.'];

        $status = $data['status'];
        $adminResponse = trim($data['admin_response'] ?? '');

        if ($this->complaintRepo->update($id, $status, $adminResponse)) {
            $this->adminRepo->logAction(Session::get('admin_id'), 'Update Complaint', "Updated status of complaint ID: {$id} to {$status}", $_SERVER['REMOTE_ADDR']);
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Failed to update complaint.'];
    }
}
