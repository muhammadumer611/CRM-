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

        $currentMonth = (int)date('n');
        $currentYear = (int)date('Y');
        $currentFee = null;
        $latestFee = $invoiceRows[0] ?? null;
        foreach ($invoiceRows as $invoice) {
            if ((int)$invoice['billing_month'] === $currentMonth && (int)$invoice['billing_year'] === $currentYear && ($invoice['charge_type'] ?? 'MONTHLY_FEE') === 'MONTHLY_FEE') {
                $currentFee = $invoice;
                break;
            }
        }
        $summary['current_month'] = $currentMonth;
        $summary['current_year'] = $currentYear;
        $summary['current_fee'] = $currentFee;
        $summary['latest_fee'] = $latestFee;
        $summary['active_fee'] = ($currentFee && $currentFee['status'] !== 'Paid') ? $currentFee : $latestFee;
        $summary['next_billing_period'] = $this->getNextBillingPeriod($invoiceRows);

        return $summary;
    }

    private function getNextBillingPeriod(array $invoiceRows) {
        $currentMonth = (int)date('n');
        $currentYear = (int)date('Y');
        if (empty($invoiceRows)) {
            return ['month' => $currentMonth, 'year' => $currentYear];
        }

        $latest = $invoiceRows[0];
        $latestPeriod = ((int)$latest['billing_year'] * 12) + (int)$latest['billing_month'];
        $currentPeriod = ($currentYear * 12) + $currentMonth;
        if ($latestPeriod < $currentPeriod) {
            return ['month' => $currentMonth, 'year' => $currentYear];
        }

        $next = mktime(0, 0, 0, (int)$latest['billing_month'] + 1, 1, (int)$latest['billing_year']);
        return ['month' => (int)date('n', $next), 'year' => (int)date('Y', $next)];
    }

    public function createNextMonthlyFee($studentId) {
        $studentId = (int)$studentId;
        $stmtStudent = $this->db->prepare("SELECT * FROM students WHERE id = ? FOR UPDATE");
        $this->db->beginTransaction();
        try {
            $stmtStudent->execute([$studentId]);
            $student = $stmtStudent->fetch();
            if (!$student) {
                throw new \Exception('Student not found.');
            }
            if ($student['status'] !== 'Active') {
                throw new \Exception('Monthly fees can only be created for an active student.');
            }
            $monthlyFee = (float)($student['monthly_fee'] ?? 0);
            if ($monthlyFee <= 0) {
                throw new \Exception('Set a valid monthly fee on the student profile first.');
            }

            $stmtFees = $this->db->prepare("SELECT * FROM fee_records WHERE student_id = ? AND charge_type = 'MONTHLY_FEE' ORDER BY billing_year DESC, billing_month DESC, id DESC FOR UPDATE");
            $stmtFees->execute([$studentId]);
            $invoiceRows = $stmtFees->fetchAll();
            $period = $this->getNextBillingPeriod($invoiceRows);
            foreach ($invoiceRows as $invoice) {
                if ((int)$invoice['billing_month'] === $period['month'] && (int)$invoice['billing_year'] === $period['year']) {
                    $this->db->commit();
                    return ['success' => false, 'duplicate' => true, 'fee' => $invoice, 'error' => date('F Y', mktime(0, 0, 0, $period['month'], 1, $period['year'])) . ' fee has already been created.'];
                }
            }

            $invoiceNumber = 'INV-' . strtoupper(substr(uniqid(), -8));
            $dueDate = date('Y-m-d', mktime(0, 0, 0, $period['month'], 10, $period['year']));
            $stmtInsert = $this->db->prepare("INSERT INTO fee_records (invoice_number, student_id, billing_month, billing_year, invoice_date, amount, due_date, status, charge_type) VALUES (?, ?, ?, ?, CURDATE(), ?, ?, 'Pending', 'MONTHLY_FEE')");
            $stmtInsert->execute([$invoiceNumber, $studentId, $period['month'], $period['year'], $monthlyFee, $dueDate]);
            $feeId = (int)$this->db->lastInsertId();
            $this->db->commit();
            return ['success' => true, 'fee_id' => $feeId, 'month' => $period['month'], 'year' => $period['year']];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if ($e instanceof \PDOException && (int)($e->errorInfo[1] ?? 0) === 1062) {
                return ['success' => false, 'duplicate' => true, 'error' => 'This monthly fee has already been created.'];
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getStudentOutstandingBalance($studentId) {
        return $this->feeRepo->getOutstandingBalance($studentId, $this->db);
    }
}
