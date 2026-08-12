<?php
namespace App\Services;

use App\Repositories\FeeRepository;
use App\Repositories\StudentRepository;
use App\Repositories\AdminRepository;
use App\Core\Session;

class FeeService {
    private $feeRepo;
    private $studentRepo;
    private $adminRepo;

    public function __construct() {
        $this->feeRepo = new FeeRepository();
        $this->studentRepo = new StudentRepository();
        $this->adminRepo = new AdminRepository();
    }

    public function getAllFees($filters, $page, $perPage) {
        $offset = ($page - 1) * $perPage;
        return [
            'data' => $this->feeRepo->findAll($filters, $perPage, $offset),
            'total' => $this->feeRepo->count($filters)
        ];
    }

    public function getFee($id) {
        return $this->feeRepo->findById($id);
    }

    public function createFee($data) {
        $student = $this->studentRepo->findById($data['student_id']);
        if (!$student) return ['success' => false, 'error' => 'Student not found.'];

        if ($this->feeRepo->findByStudentAndMonthYear($data['student_id'], $data['billing_month'], $data['billing_year'])) {
            return ['success' => false, 'error' => 'A fee record already exists for this student for the selected month and year.'];
        }

        $dbData = [
            'student_id' => $data['student_id'],
            'billing_month' => (int)$data['billing_month'],
            'billing_year' => (int)$data['billing_year'],
            'amount' => (float)$data['amount'],
            'due_date' => $data['due_date'],
            'status' => 'Pending',
            'remarks' => trim($data['remarks'] ?? '')
        ];

        $id = $this->feeRepo->create($dbData);
        
        if ($id) {
            $this->adminRepo->logAction(Session::get('admin_id'), 'Create Fee', "Created fee for student ID: {$student['student_id_str']}", $_SERVER['REMOTE_ADDR']);
            return ['success' => true, 'id' => $id];
        }
        
        return ['success' => false, 'error' => 'Failed to create fee record.'];
    }

    public function payFee($id, $data) {
        $fee = $this->feeRepo->findById($id);
        if (!$fee) return ['success' => false, 'error' => 'Fee record not found.'];

        $paidAmount = (float)$data['paid_amount'];
        $remaining = (float)$fee['amount'] - (float)$fee['paid_amount'];

        if ($paidAmount <= 0) {
            return ['success' => false, 'error' => 'Paid amount must be greater than zero.'];
        }

        if ($paidAmount > $remaining) {
            return ['success' => false, 'error' => 'Paid amount cannot exceed remaining balance (Rs. ' . $remaining . ').'];
        }

        $newTotalPaid = (float)$fee['paid_amount'] + $paidAmount;
        $status = ($newTotalPaid >= (float)$fee['amount']) ? 'Paid' : 'Partial';

        $paymentDate = date('Y-m-d');
        
        if ($this->feeRepo->updatePayment($id, $paidAmount, $data['payment_method'], $data['transaction_ref'], $status, $paymentDate)) {
            $this->adminRepo->logAction(Session::get('admin_id'), 'Pay Fee', "Recorded payment of Rs. {$paidAmount} for fee ID: {$id}", $_SERVER['REMOTE_ADDR']);
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Failed to record payment.'];
    }
}
