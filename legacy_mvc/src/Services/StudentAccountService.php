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

        $this->ensureCurrentMonthFee($student);

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

        $currentMonth = (int)date('n');
        $currentYear = (int)date('Y');
        $currentFee = null;
        foreach ($invoiceRows as $invoice) {
            if ((int)$invoice['billing_month'] === $currentMonth && (int)$invoice['billing_year'] === $currentYear && ($invoice['charge_type'] ?? 'MONTHLY_FEE') === 'MONTHLY_FEE') {
                $currentFee = $invoice;
                break;
            }
        }
        $summary['current_month'] = $currentMonth;
        $summary['current_year'] = $currentYear;
        $summary['current_fee'] = $currentFee;

        return $summary;
    }

    private function ensureCurrentMonthFee(array $student) {
        $month = (int)date('n');
        $year = (int)date('Y');
        $monthlyFee = (float)($student['monthly_fee'] ?? 0);
        if ($monthlyFee <= 0 || $student['status'] !== 'Active') {
            return;
        }

        if ($this->feeRepo->findByStudentAndMonthYear((int)$student['id'], $month, $year)) {
            return;
        }

        $dueDate = date('Y-m-10');
        $invoiceNumber = 'INV-' . strtoupper(substr(uniqid(), -8));
        try {
            $stmt = $this->db->prepare("INSERT INTO fee_records (invoice_number, student_id, billing_month, billing_year, invoice_date, amount, due_date, status, charge_type) VALUES (?, ?, ?, ?, CURDATE(), ?, ?, 'Pending', 'MONTHLY_FEE')");
            $stmt->execute([$invoiceNumber, (int)$student['id'], $month, $year, $monthlyFee, $dueDate]);
        } catch (\PDOException $e) {
            if ((int)$e->errorInfo[1] !== 1062) {
                throw $e;
            }
        }
    }

    public function getStudentOutstandingBalance($studentId) {
        return $this->feeRepo->getOutstandingBalance($studentId, $this->db);
    }
}
