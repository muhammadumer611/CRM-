<?php
namespace App\Services;

use App\Repositories\FeeRepository;
use App\Repositories\StudentRepository;
use App\Repositories\AdminRepository;
use App\Core\Session;
use App\Core\Database;
use App\Services\AuditLogger;
use Exception;
use PDO;

class FeeService {
    private $feeRepo;
    private $studentRepo;
    private $adminRepo;
    private $db;

    public function __construct() {
        $this->feeRepo     = new FeeRepository();
        $this->studentRepo = new StudentRepository();
        $this->adminRepo   = new AdminRepository();
        $this->db          = Database::getInstance()->getConnection();
    }

    public function getAllFees($filters, $page, $perPage) {
        $offset = ($page - 1) * $perPage;
        // Mark overdue invoices before displaying
        $this->feeRepo->markOverdueInvoices();
        return [
            'data'  => $this->feeRepo->findAll($filters, $perPage, $offset),
            'total' => $this->feeRepo->count($filters)
        ];
    }

    public function getCollectionSummary(array $filters = []) {
        $summary = $this->feeRepo->getCollectionSummary($filters);
        return [
            'total_collection' => (float)($summary['total_collection'] ?? 0),
            'payment_count' => (int)($summary['payment_count'] ?? 0),
            'latest_payment_date' => $summary['latest_payment_date'] ?? null,
        ];
    }

    public function getCollectionRows(array $filters = [], $limit = 50, $offset = 0) {
        return $this->feeRepo->getCollectionRows($filters, $limit, $offset);
    }

    public function getPendingFeesSummary(array $filters = []) {
        $summary = $this->feeRepo->getPendingFeesSummary($filters);
        return [
            'total_pending_amount' => (float)($summary['total_pending_amount'] ?? 0),
            'pending_student_count' => (int)($summary['pending_student_count'] ?? 0),
            'invoice_count' => (int)($summary['invoice_count'] ?? 0),
        ];
    }

    public function getPendingFees(array $filters = []) {
        $rows = $this->feeRepo->getPendingFeeRows($filters);
        foreach ($rows as &$row) {
            $row['monthly_fee'] = (float)($row['monthly_fee'] ?? ($row['amount'] ?? 0));
            $row['amount_paid'] = (float)($row['paid_amount'] ?? 0);
            $row['pending_amount'] = (float)($row['pending_amount'] ?? max(0, ((float)($row['invoice_total'] ?? 0)) - $row['amount_paid']));
            $row['display_status'] = $this->normalizePendingDisplayStatus($row['status'] ?? 'Pending');
            $row['billing_period'] = date('F', mktime(0, 0, 0, (int)($row['billing_month'] ?? 1), 1, (int)($row['billing_year'] ?? date('Y')))) . ' ' . ($row['billing_year'] ?? date('Y'));
        }
        unset($row);
        return $rows;
    }

    public function getPendingFeeStudentsOverview(array $filters = []) {
        return $this->feeRepo->getPendingFeeStudentsOverview($filters);
    }

    public function getStudentPendingFeeDetails($studentId) {
        return $this->feeRepo->getStudentPendingFeeDetails((int)$studentId);
    }

    public function getFee($id) {
        return $this->feeRepo->findById($id);
    }

    public function getFeePayments($invoiceId) {
        return $this->feeRepo->getPayments($invoiceId);
    }

    public function createFee($data) {
        $student = $this->studentRepo->findById($data['student_id']);
        if (!$student) return ['success' => false, 'error' => 'Student not found.'];
        if ($student['status'] !== 'Active') return ['success' => false, 'error' => 'Cannot create fee for an inactive student.'];

        $billingMonth = (int)$data['billing_month'];
        $billingYear  = (int)$data['billing_year'];

        if ($this->feeRepo->findByStudentAndMonthYear($data['student_id'], $billingMonth, $billingYear)) {
            return ['success' => false, 'error' => 'A monthly fee invoice already exists for this student for ' . date('F Y', mktime(0,0,0,$billingMonth,1,$billingYear)) . '.'];
        }

        // Use student's fixed monthly_fee if amount not specified differently
        $amount = (float)($data['amount'] ?? $student['monthly_fee'] ?? 0);
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Fee amount must be greater than zero. Set a monthly fee on the student profile first.'];
        }

        $additionalCharges = (float)($data['additional_charges'] ?? 0);
        $discount = (float)($data['discount'] ?? 0);
        $paidAmount = (float)($data['paid_amount'] ?? 0);
        $totalAmount = max(0, $amount + $additionalCharges - $discount);
        $dueDate = !empty($data['due_date']) ? $data['due_date'] : date('Y-m-d', mktime(0,0,0,$billingMonth+1,10,$billingYear));

        $dbData = [
            'student_id'    => $data['student_id'],
            'billing_month' => $billingMonth,
            'billing_year'  => $billingYear,
            'amount'        => $amount,
            'additional_charges' => $additionalCharges,
            'discount'      => $discount,
            'paid_amount'   => $paidAmount,
            'due_date'      => $dueDate,
            'status'        => $paidAmount >= $totalAmount ? 'Paid' : ($paidAmount > 0 ? 'Partial' : 'Pending'),
            'charge_type'   => 'MONTHLY_FEE',
            'remarks'       => trim($data['remarks'] ?? '')
        ];

        $id = $this->feeRepo->create($dbData);
        
        if ($id) {
            if ($paidAmount > 0) {
                $this->feeRepo->createPayment($id, [
                    'amount' => $paidAmount,
                    'payment_date' => $data['payment_date'] ?? date('Y-m-d'),
                    'payment_method' => $data['payment_method'] ?? 'Cash',
                    'transaction_ref' => $data['transaction_ref'] ?? null,
                    'remarks' => 'Initial payment on invoice creation',
                    'received_by_admin' => Session::get('admin_id')
                ]);
            }

            AuditLogger::logAdminAction('INVOICE_CREATED', 'fee', $id,
                "Invoice created for {$student['student_id_str']} — {$billingMonth}/{$billingYear} — Rs. " . number_format($amount, 2),
                null, $dbData
            );
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->adminRepo->logAction(Session::get('admin_id'), 'Create Fee Invoice', "Invoice for {$student['student_id_str']}", $ip);
            return ['success' => true, 'id' => $id];
        }
        
        return ['success' => false, 'error' => 'Failed to create fee record.'];
    }

    /**
     * Professional payment recording with fee_payments table.
     */
    public function payFee($id, $data) {
        $invoice = $this->feeRepo->findById($id);
        if (!$invoice) return ['success' => false, 'error' => 'Invoice not found.'];
        if ($invoice['status'] === 'Paid') return ['success' => false, 'error' => 'This invoice is already fully paid.'];

        $paidAmount    = (float)($data['paid_amount'] ?? 0);
        $paymentMethod = trim($data['payment_method'] ?? '');
        $transactionRef = trim($data['transaction_ref'] ?? '');
        $remarks       = trim($data['remarks'] ?? '');

        if ($paidAmount <= 0) {
            return ['success' => false, 'error' => 'Payment amount must be greater than zero.'];
        }

        $totalAmount = (float)$invoice['amount'] + (float)($invoice['additional_charges'] ?? 0) - (float)($invoice['discount'] ?? 0);
        $remaining = max(0, $totalAmount - (float)$invoice['paid_amount']);
        if ($paidAmount > $remaining + 0.01) {
            return ['success' => false, 'error' => 'Payment amount (Rs. ' . number_format($paidAmount, 2) . ') exceeds remaining balance (Rs. ' . number_format($remaining, 2) . ').'];
        }

        $allowedMethods = ['Cash', 'Bank Transfer', 'Online', 'Card', 'JazzCash', 'EasyPaisa', 'Other'];
        if (!in_array($paymentMethod, $allowedMethods)) {
            return ['success' => false, 'error' => 'Invalid payment method.'];
        }

        $this->db->beginTransaction();
        try {
            $result = $this->feeRepo->recordPayment(
                $id,
                $paidAmount,
                $paymentMethod,
                $transactionRef,
                $remarks,
                Session::get('admin_id'),
                $this->db
            );

            if (!$result['success']) throw new Exception($result['error']);

            $this->db->commit();

            AuditLogger::logAdminAction('PAYMENT_RECORDED', 'fee', $id,
                "Payment Rs. " . number_format($paidAmount, 2) . " recorded for {$invoice['student_id_str']} (Invoice #{$invoice['invoice_number']}) via {$paymentMethod}. Status: {$result['new_status']}.",
                ['paid_before' => $invoice['paid_amount']],
                ['amount' => $paidAmount, 'method' => $paymentMethod, 'receipt' => $result['receipt_number']]
            );

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->adminRepo->logAction(Session::get('admin_id'), 'Fee Payment', "Rs. {$paidAmount} for invoice #{$id}. Receipt: {$result['receipt_number']}", $ip);

            return ['success' => true, 'receipt_number' => $result['receipt_number'], 'new_status' => $result['new_status'], 'payment_id' => $result['payment_id'] ?? null];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function allocatePaymentAgainstInvoices(array $invoices, float $paymentAmount): array {
        $remaining = max(0.0, (float)$paymentAmount);
        $allocations = [];
        $appliedTotal = 0.0;

        foreach ($invoices as $invoice) {
            $invoiceId = (int)($invoice['id'] ?? 0);
            $invoiceTotal = (float)($invoice['total_amount'] ?? ((float)($invoice['amount'] ?? 0) + (float)($invoice['additional_charges'] ?? 0) - (float)($invoice['discount'] ?? 0)));
            $paidAmount = (float)($invoice['paid_amount'] ?? 0);
            $outstanding = max(0.0, $invoiceTotal - $paidAmount);

            if ($invoiceId <= 0 || $outstanding <= 0) {
                continue;
            }

            $applied = min($remaining, $outstanding);
            $allocations[] = [
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoice['invoice_number'] ?? null,
                'allocated_amount' => round($applied, 2),
                'outstanding_before' => round($outstanding, 2),
                'due_date' => $invoice['due_date'] ?? null,
            ];

            $remaining = round(max(0.0, $remaining - $applied), 2);
            $appliedTotal += $applied;

            if ($remaining <= 0) {
                break;
            }
        }

        return [
            'allocations' => $allocations,
            'applied_total' => round($appliedTotal, 2),
            'remaining' => round($remaining, 2),
        ];
    }

    public function generateMonthlyInvoiceForStudent($studentId, $data = []) {
        $studentId = (int)$studentId;
        $student = $this->studentRepo->findById($studentId);
        if (!$student) {
            return ['success' => false, 'error' => 'Student not found.'];
        }

        $db = \App\Core\Database::getInstance()->getConnection();
        $roomAllocation = $db->prepare("SELECT ra.*, r.monthly_fee, r.security_deposit, r.room_number, r.block FROM room_allocations ra JOIN rooms r ON r.id = ra.room_id WHERE ra.student_id = ? AND ra.status = 'Active' ORDER BY ra.id DESC LIMIT 1");
        $roomAllocation->execute([$studentId]);
        $allocation = $roomAllocation->fetch();

        if (!$allocation) {
            return ['success' => false, 'error' => 'Student has no active room allocation.'];
        }

        $month = isset($data['billing_month']) ? (int)$data['billing_month'] : (int)date('n');
        $year = isset($data['billing_year']) ? (int)$data['billing_year'] : (int)date('Y');
        $billingMonth = max(1, min(12, $month));
        $billingYear = max(2026, $year);
        $dueDate = !empty($data['due_date']) ? $data['due_date'] : self::getDueDateForBillingPeriod($billingMonth, $billingYear, $data['due_day'] ?? 5);

        if ($this->feeRepo->findByStudentAndMonthYear($studentId, $billingMonth, $billingYear)) {
            return ['success' => false, 'error' => 'Monthly invoice already exists for this student for ' . date('F', mktime(0, 0, 0, $billingMonth, 1)) . ' ' . $billingYear . '.'];
        }

        $invoiceAmount = isset($data['amount']) ? (float)$data['amount'] : (float)($allocation['monthly_fee'] ?? 0);
        $discount = isset($data['discount']) ? (float)$data['discount'] : 0.0;
        $additionalCharges = isset($data['additional_charges']) ? (float)$data['additional_charges'] : 0.0;
        $netAmount = max(0.0, $invoiceAmount + $additionalCharges - $discount);

        if ($netAmount <= 0) {
            return ['success' => false, 'error' => 'Monthly invoice total must be greater than zero.'];
        }

        $invoiceId = $this->feeRepo->create([
            'student_id' => $studentId,
            'billing_month' => $billingMonth,
            'billing_year' => $billingYear,
            'amount' => $invoiceAmount,
            'additional_charges' => $additionalCharges,
            'discount' => $discount,
            'paid_amount' => 0,
            'due_date' => $dueDate,
            'status' => 'Pending',
            'remarks' => trim((string)($data['remarks'] ?? 'Monthly hostel rent')),
            'payment_method' => null,
            'transaction_ref' => null,
            'invoice_number' => $data['invoice_number'] ?? null,
            'invoice_date' => $data['invoice_date'] ?? date('Y-m-d'),
            'charge_type' => 'MONTHLY_FEE'
        ], $db);

        if (!$invoiceId) {
            return ['success' => false, 'error' => 'Monthly invoice creation failed.'];
        }

        return ['success' => true, 'id' => (int)$invoiceId, 'student_id' => $studentId, 'amount' => $netAmount, 'billing_month' => $billingMonth, 'billing_year' => $billingYear];
    }

    public function generateMonthlyInvoicesForActiveResidents($data = []) {
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT s.id FROM students s WHERE s.status = 'Active' AND EXISTS (SELECT 1 FROM room_allocations ra WHERE ra.student_id = s.id AND ra.status = 'Active') ORDER BY s.id ASC");
        $students = $stmt->fetchAll();

        $results = [];
        foreach ($students as $student) {
            $result = $this->generateMonthlyInvoiceForStudent((int)$student['id'], $data);
            $results[] = ['student_id' => (int)$student['id'], 'result' => $result];
        }

        return ['success' => true, 'generated' => $results];
    }

    public static function calculateInvoiceTotal(array $invoice): float {
        $amount = (float)($invoice['amount'] ?? 0);
        $additional = (float)($invoice['additional_charges'] ?? 0);
        $discount = (float)($invoice['discount'] ?? 0);
        return max(0.0, $amount + $additional - $discount);
    }

    public static function calculateInvoiceOutstanding(array $invoice): float {
        $total = self::calculateInvoiceTotal($invoice);
        $paid = (float)($invoice['paid_amount'] ?? 0);
        return max(0.0, $total - $paid);
    }

    public static function getBillingDueDate(int $month, int $year, $dueDay = null): string {
        $config = require APP_ROOT . '/config/app.php';
        $day = $dueDay !== null ? (int)$dueDay : (int)($config['billing_due_day'] ?? 10);
        $month = max(1, min(12, $month));
        $year = max(2025, (int)$year);
        $day = max(1, min(28, $day));
        return date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
    }

    public function generateRecurringMonthlyBillingForActiveResidents($data = []) {
        $month = isset($data['billing_month']) ? (int)$data['billing_month'] : (int)date('n');
        $year = isset($data['billing_year']) ? (int)$data['billing_year'] : (int)date('Y');
        $results = [];
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT s.id, s.full_name, s.status, ra.room_id, ra.bed_number, ra.status AS allocation_status, r.monthly_fee, r.room_number, r.block, r.total_beds FROM students s JOIN room_allocations ra ON ra.student_id = s.id AND ra.status = 'Active' JOIN rooms r ON r.id = ra.room_id WHERE s.status = 'Active' ORDER BY s.id ASC");
        $students = $stmt->fetchAll();

        foreach ($students as $student) {
            $studentId = (int)$student['id'];
            if ($this->feeRepo->findByStudentAndMonthYear($studentId, $month, $year)) {
                $results[] = ['student_id' => $studentId, 'status' => 'skipped', 'reason' => 'invoice_exists'];
                continue;
            }

            $invoiceAmount = isset($data['amount']) ? (float)$data['amount'] : (float)($student['monthly_fee'] ?? 0);
            $discount = isset($data['discount']) ? (float)$data['discount'] : 0.0;
            $additionalCharges = isset($data['additional_charges']) ? (float)$data['additional_charges'] : 0.0;
            $dueDate = !empty($data['due_date']) ? $data['due_date'] : self::getBillingDueDate($month, $year, $data['due_day'] ?? 5);

            $result = $this->generateMonthlyInvoiceForStudent($studentId, [
                'billing_month' => $month,
                'billing_year' => $year,
                'amount' => $invoiceAmount,
                'discount' => $discount,
                'additional_charges' => $additionalCharges,
                'due_date' => $dueDate,
                'remarks' => trim((string)($data['remarks'] ?? 'Recurring monthly hostel fee')),
            ]);

            $results[] = ['student_id' => $studentId, 'status' => $result['success'] ? 'generated' : 'failed', 'result' => $result];
        }

        return ['success' => true, 'generated' => $results];
    }

    public function getPaymentAllocations($paymentId) {
        return $this->feeRepo->findPaymentAllocations((int)$paymentId);
    }

    public function recordStudentPayment($studentId, $data) {
        $studentId = (int)$studentId;
        $paymentAmount = (float)($data['paid_amount'] ?? 0);
        $paymentMethod = trim((string)($data['payment_method'] ?? 'Cash'));
        $transactionRef = trim((string)($data['transaction_ref'] ?? '')) ?: null;
        $paymentDate = !empty($data['payment_date']) ? $data['payment_date'] : date('Y-m-d');
        $remarks = trim((string)($data['remarks'] ?? ''));

        if ($studentId <= 0) {
            return ['success' => false, 'error' => 'Student is required.'];
        }

        if ($paymentAmount <= 0) {
            return ['success' => false, 'error' => 'Payment amount must be greater than zero.'];
        }

        $db = \App\Core\Database::getInstance()->getConnection();
        $db->beginTransaction();

        try {
            $invoiceRows = $db->prepare("SELECT * FROM fee_records WHERE student_id = ? AND ((amount + additional_charges - discount) > paid_amount) ORDER BY due_date ASC, id ASC FOR UPDATE");
            $invoiceRows->execute([$studentId]);
            $invoices = $invoiceRows->fetchAll();

            if (empty($invoices)) {
                throw new \Exception('There are no outstanding invoices to apply this payment to.');
            }

            $outstandingTotal = 0.0;
            foreach ($invoices as $invoice) {
                $outstandingTotal += max(0, (float)$invoice['amount'] + (float)$invoice['additional_charges'] - (float)$invoice['discount'] - (float)$invoice['paid_amount']);
            }
            if ($paymentAmount > $outstandingTotal) {
                throw new \Exception('Payment cannot exceed outstanding balance of Rs. ' . number_format($outstandingTotal, 2) . '.');
            }

            $allocationResult = self::allocatePaymentAgainstInvoices($invoices, $paymentAmount);
            if ($allocationResult['remaining'] > 0.0001) {
                throw new \Exception('Payment exceeds the student outstanding balance.');
            }

            $paymentReceipt = $this->feeRepo->generateReceiptNumber($db);
            $paymentId = $this->feeRepo->createPayment((int)$invoices[0]['id'], [
                'receipt_number' => $paymentReceipt,
                'amount' => $paymentAmount,
                'payment_date' => $paymentDate,
                'payment_method' => $paymentMethod,
                'transaction_ref' => $transactionRef,
                'remarks' => $remarks ?: 'FIFO payment allocation',
                'received_by_admin' => Session::get('admin_id')
            ], $db);

            if (!$paymentId) {
                throw new \Exception('Payment creation failed.');
            }

            $totalAllocated = 0.0;
            foreach ($allocationResult['allocations'] as $allocation) {
                $invoiceId = (int)$allocation['invoice_id'];
                $invoice = null;
                foreach ($invoices as $row) {
                    if ((int)$row['id'] === $invoiceId) {
                        $invoice = $row;
                        break;
                    }
                }

                if (!$invoice) {
                    continue;
                }

                $invoiceTotal = (float)$invoice['amount'] + (float)$invoice['additional_charges'] - (float)$invoice['discount'];
                $newPaidAmount = (float)$invoice['paid_amount'] + (float)$allocation['allocated_amount'];
                $status = self::calculateStatus($invoiceTotal, $newPaidAmount, $invoice['due_date']);

                $allocationSuccess = $this->feeRepo->createPaymentAllocation($paymentId, $invoiceId, (float)$allocation['allocated_amount'], $db);
                if (!$allocationSuccess) {
                    throw new \Exception('Unable to allocate payment across invoices.');
                }

                $this->feeRepo->setInvoicePaymentTotals($invoiceId, $newPaidAmount, $status, $paymentMethod, $transactionRef, $paymentDate, $db);
                $totalAllocated += (float)$allocation['allocated_amount'];
            }

            if (abs($totalAllocated - $paymentAmount) > 0.01) {
                throw new \Exception('Payment allocation mismatch detected. Transaction rolled back.');
            }

            $this->feeRepo->updateOverdueStatuses($studentId, $db);
            $db->commit();
            return ['success' => true, 'payment_id' => $paymentId, 'receipt_number' => $paymentReceipt, 'allocations' => $allocationResult['allocations'], 'applied_total' => $allocationResult['applied_total']];
        } catch (\Exception $e) {
            $db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function getDueDateForBillingPeriod($month, $year, $dueDay = null) {
        $config = require APP_ROOT . '/config/app.php';
        $dueDay = $dueDay !== null ? (int)$dueDay : (int)($config['billing_due_day'] ?? 10);
        $month = max(1, min(12, (int)$month));
        $year = (int)$year;
        $dueDay = max(1, min(28, $dueDay));
        return date('Y-m-d', mktime(0, 0, 0, $month, $dueDay, $year));
    }

    public function getStudentArrearsSummary($studentId) {
        $studentId = (int)$studentId;
        $db = \App\Core\Database::getInstance()->getConnection();
        $summary = $this->feeRepo->getStudentArrearsSummary($studentId, $db);
        return [
            'outstanding_balance' => (float)($summary['outstanding_balance'] ?? 0),
            'overdue_balance' => (float)($summary['overdue_balance'] ?? 0),
            'current_month_due' => 0.0,
        ];
    }

    public function applyLateFeesToOverdueInvoices($studentId = null, $gracePeriodDays = 5, $lateFeeAmount = 500.0, $feeType = 'FIXED', $reason = 'Late fee for overdue invoice') {
        $db = \App\Core\Database::getInstance()->getConnection();
        $db->beginTransaction();

        try {
            $invoices = $this->feeRepo->findOverdueInvoices($studentId, $db);
            $added = [];

            foreach ($invoices as $invoice) {
                $invoiceId = (int)$invoice['id'];
                $alreadyExists = $db->prepare("SELECT id FROM late_fees WHERE invoice_id = ? LIMIT 1");
                $alreadyExists->execute([$invoiceId]);
                if ($alreadyExists->fetch()) {
                    continue;
                }

                $feeId = $this->feeRepo->createLateFee($invoiceId, (float)$lateFeeAmount, (int)$gracePeriodDays, $feeType, $reason, $db);
                if ($feeId) {
                    $added[] = ['invoice_id' => $invoiceId, 'late_fee_id' => $feeId, 'amount' => (float)$lateFeeAmount];
                }
            }

            $db->commit();
            return ['success' => true, 'applied' => $added];
        } catch (\Throwable $e) {
            $db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function addAdditionalChargeToStudent($studentId, $data) {
        $studentId = (int)$studentId;
        $amount = (float)($data['amount'] ?? 0);
        if ($studentId <= 0 || $amount <= 0) {
            return ['success' => false, 'error' => 'Valid student and charge amount are required.'];
        }

        $chargeId = $this->feeRepo->createAdditionalCharge([
            'student_id' => $studentId,
            'charge_type' => $data['charge_type'] ?? 'MISC',
            'description' => $data['description'] ?? 'Additional charge',
            'amount' => $amount,
            'charge_date' => $data['charge_date'] ?? date('Y-m-d'),
            'reference_number' => $data['reference_number'] ?? null,
            'created_by_admin' => Session::get('admin_id'),
            'status' => $data['status'] ?? 'Pending',
        ]);

        if (!$chargeId) {
            return ['success' => false, 'error' => 'Additional charge was not recorded.'];
        }

        return ['success' => true, 'id' => (int)$chargeId];
    }

    public function applyInvoiceDiscount($invoiceId, $data) {
        $invoiceId = (int)$invoiceId;
        $discountValue = (float)($data['discount_value'] ?? 0);
        if ($invoiceId <= 0 || $discountValue <= 0) {
            return ['success' => false, 'error' => 'Valid invoice and discount value are required.'];
        }

        $discountId = $this->feeRepo->createInvoiceDiscount([
            'invoice_id' => $invoiceId,
            'discount_type' => $data['discount_type'] ?? 'FIXED',
            'discount_value' => $discountValue,
            'reason' => $data['reason'] ?? 'Discount applied',
            'created_by_admin' => Session::get('admin_id'),
        ]);

        if (!$discountId) {
            return ['success' => false, 'error' => 'Discount could not be applied without exceeding the invoice total.'];
        }

        return ['success' => true, 'id' => (int)$discountId];
    }

    public function getStudentStatement($studentId) {
        $studentId = (int)$studentId;
        $rows = $this->feeRepo->getStudentStatementRows($studentId, \App\Core\Database::getInstance()->getConnection());
        $totals = ['debit' => 0.0, 'credit' => 0.0, 'balance' => 0.0];
        foreach ($rows as $row) {
            $totals['debit'] += (float)$row['debit'];
            $totals['credit'] += (float)$row['credit'];
            $totals['balance'] = (float)$row['balance'];
        }
        return ['rows' => $rows, 'totals' => $totals];
    }

    private static function calculateStatus($totalAmount, $paidAmount, $dueDate) {
        $totalAmount = (float)$totalAmount;
        $paidAmount = (float)$paidAmount;
        if ($paidAmount >= $totalAmount && $totalAmount > 0) {
            return 'Paid';
        }
        if ($paidAmount > 0) {
            return 'Partial';
        }
        if ($dueDate && date('Y-m-d') > $dueDate) {
            return 'Overdue';
        }
        return 'Pending';
    }

    private function normalizePendingDisplayStatus($status) {
        $status = trim((string)$status);
        if ($status === 'Partial') {
            return 'Partially Paid';
        }
        if ($status === 'Overdue') {
            return 'Overdue';
        }
        if ($status === 'Paid') {
            return 'Paid';
        }
        return 'Pending';
    }

    private function generateInvoiceNumber() {
        $prefix = 'INV-' . date('Y') . '-';
        do {
            $suffix = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $invoiceNumber = $prefix . $suffix;
        } while ($this->feeRepo->findByStudentAndMonthYear((int)($_POST['student_id'] ?? 0), (int)($_POST['billing_month'] ?? 0), (int)($_POST['billing_year'] ?? 0)) || $this->feeRepo->findByInvoiceNumber($invoiceNumber));

        return $invoiceNumber;
    }

    public function getFinancialSummary() {
        return $this->feeRepo->getFinancialSummary();
    }

    public function getActiveStudentsForFee() {
        $stmt = $this->db->query("
            SELECT s.id, s.full_name, s.student_id_str, s.monthly_fee
            FROM students s
            WHERE s.status = 'Active'
            ORDER BY s.full_name ASC
        ");
        return $stmt->fetchAll();
    }

    public function getSecurityDepositSummary() {
        return $this->feeRepo->getSecurityDepositSummary();
    }

    public function getSecurityDeposits($filters = []) {
        return $this->feeRepo->getSecurityDeposits($filters);
    }
}
