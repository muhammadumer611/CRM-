<?php
namespace App\Services;

use App\Core\Database;
use App\Repositories\FeeRepository;
use App\Repositories\StudentRepository;
use App\Repositories\AllocationRepository;

class StudentAccountService {
    private $db;
    private $feeRepo;
    private $studentRepo;
    private $allocationRepo;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->feeRepo = new FeeRepository();
        $this->studentRepo = new StudentRepository();
        $this->allocationRepo = new AllocationRepository();
    }

    public function getStudentAccount($studentId) {
        $student = $this->studentRepo->findById($studentId);
        if (!$student) {
            throw new \Exception('Student not found.');
        }

        $activeAllocation = $this->allocationRepo->getActiveAllocationByStudent($studentId, $this->db);
        $room = null;
        if ($activeAllocation) {
            $roomStmt = $this->db->prepare("SELECT * FROM rooms WHERE id = ?");
            $roomStmt->execute([$activeAllocation['room_id']]);
            $room = $roomStmt->fetch();
        }

        $invoiceRows = $this->feeRepo->findStudentInvoices($studentId, $this->db);
        $paymentRows = $this->feeRepo->findStudentPayments($studentId, $this->db);

        $totalCharges = 0.0;
        $totalPaid = 0.0;
        $totalOutstanding = 0.0;
        $totalOverdue = 0.0;

        foreach ($invoiceRows as $invoice) {
            $total = (float)$invoice['amount'] + (float)$invoice['additional_charges'] - (float)$invoice['discount'];
            $paid = (float)$invoice['paid_amount'];
            $outstanding = max(0, $total - $paid);
            $totalCharges += $total;
            $totalPaid += $paid;
            $totalOutstanding += $outstanding;
            if ($outstanding > 0 && $invoice['due_date'] && date('Y-m-d') > $invoice['due_date']) {
                $totalOverdue += $outstanding;
            }
        }

        $summary = [
            'student' => $student,
            'active_allocation' => $activeAllocation,
            'room' => $room,
            'invoices' => $invoiceRows,
            'payments' => $paymentRows,
            'total_charges' => $totalCharges,
            'total_paid' => $totalPaid,
            'total_outstanding' => $totalOutstanding,
            'total_overdue' => $totalOverdue,
            'last_payment_date' => $this->feeRepo->getLastPaymentDate($studentId, $this->db),
        ];

        return $summary;
    }

    public function getStudentOutstandingBalance($studentId) {
        return $this->feeRepo->getOutstandingBalance($studentId, $this->db);
    }
}
