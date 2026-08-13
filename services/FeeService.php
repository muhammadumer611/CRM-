<?php
namespace Services;

use Repositories\FeeRepository;
use Utils\TransactionHelper;
use Core\Logger;
use Services\StudentHistoryService;
use Exception;
use PDO;

class FeeService {
    private $repository;

    public function __construct() {
        $this->repository = new FeeRepository();
    }

    public function createFee($data) {
        $studentId = (int)($data['student_id'] ?? 0);
        $month = (int)($data['billing_month'] ?? 0);
        $year = (int)($data['billing_year'] ?? 0);

        if ($studentId <= 0 || $month < 1 || $month > 12 || $year <= 0) {
            throw new Exception("Invalid invoice information.");
        }

        $existing = $this->repository->findByStudentAndBillingPeriod($studentId, $month, $year);
        if ($existing) {
            throw new Exception("Invoice already exists for this student for the selected billing period.");
        }

        $baseAmount = (float)($data['amount'] ?? 0);
        $additionalCharges = (float)($data['additional_charges'] ?? 0);
        $discount = (float)($data['discount'] ?? 0);
        $dueDate = $data['due_date'] ?? date('Y-m-d', strtotime('+15 days'));
        $invoiceDate = $data['invoice_date'] ?? date('Y-m-d');

        if ($baseAmount < 0 || $additionalCharges < 0 || $discount < 0) {
            throw new Exception("Fee values cannot be negative.");
        }

        $totalAmount = $baseAmount + $additionalCharges - $discount;
        if ($totalAmount <= 0) {
            throw new Exception("Invoice total must be greater than zero.");
        }

        $invoiceNumber = $this->generateInvoiceNumber();
        $data['invoice_number'] = $invoiceNumber;
        $data['amount'] = $baseAmount;
        $data['additional_charges'] = $additionalCharges;
        $data['discount'] = $discount;
        $data['invoice_date'] = $invoiceDate;
        $data['due_date'] = $dueDate;
        $data['status'] = 'Pending';

        $id = $this->repository->create($data);
        Logger::info("Created invoice #{$invoiceNumber} for student #{$studentId}");

        StudentHistoryService::record(
            $studentId,
            'FEE_INVOICE_CREATED',
            "Generated invoice {$invoiceNumber} for " . $data['billing_month'] . "/" . $data['billing_year'],
            null,
            $data
        );

        return $id;
    }

    public function getFee($id) {
        $fee = $this->repository->findById($id);
        if (!$fee) {
            throw new Exception("Fee invoice not found.");
        }

        $totalAmount = (float)$fee['amount'] + (float)$fee['additional_charges'] - (float)$fee['discount'];
        $fee['total_amount'] = $totalAmount;
        $fee['remaining_balance'] = max(0, $totalAmount - (float)$fee['paid_amount']);
        if ($fee['status'] === 'Pending' && date('Y-m-d') > $fee['due_date']) {
            $fee['status'] = 'Overdue';
        }
        return $fee;
    }

    public function searchFees($filters) {
        $fees = $this->repository->search($filters);
        $today = date('Y-m-d');
        foreach ($fees as &$fee) {
            $fee['total_amount'] = (float)$fee['amount'] + (float)$fee['additional_charges'] - (float)$fee['discount'];
            $fee['remaining_balance'] = max(0, $fee['total_amount'] - (float)$fee['paid_amount']);
            if ($fee['status'] === 'Pending' && $today > $fee['due_date']) {
                $fee['status'] = 'Overdue';
            }
        }
        return $fees;
    }

    public function recordPayment($feeId, $paymentAmount, $method, $ref, $remarks, $adminId = null) {
        return TransactionHelper::execute(function(PDO $db) use ($feeId, $paymentAmount, $method, $ref, $remarks, $adminId) {
            $stmt = $db->prepare("SELECT * FROM fee_records WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $feeId]);
            $invoice = $stmt->fetch();

            if (!$invoice) {
                throw new Exception("Invoice not found.");
            }

            $paymentAmount = (float)$paymentAmount;
            if ($paymentAmount <= 0) {
                throw new Exception("Payment amount must be greater than zero.");
            }

            $totalAmount = (float)$invoice['amount'] + (float)$invoice['additional_charges'] - (float)$invoice['discount'];
            $remainingBalance = max(0, $totalAmount - (float)$invoice['paid_amount']);

            if ($remainingBalance <= 0) {
                throw new Exception("Payment cannot be recorded for an already fully paid invoice.");
            }

            if ($paymentAmount > $remainingBalance) {
                throw new Exception("Payment exceeds the remaining balance of " . number_format($remainingBalance, 2) . ".");
            }

            $newPaidAmount = (float)$invoice['paid_amount'] + $paymentAmount;
            $paymentDate = date('Y-m-d');
            $newStatus = $this->calculateStatus($totalAmount, $newPaidAmount, $invoice['due_date']);

            $this->repository->createPayment($feeId, [
                'amount' => $paymentAmount,
                'payment_date' => $paymentDate,
                'payment_method' => $method,
                'transaction_ref' => $ref,
                'remarks' => $remarks,
                'received_by_admin' => $adminId ?? ($_SESSION['admin_id'] ?? null)
            ], $db);

            $this->repository->updateInvoicePayment($feeId, $newPaidAmount, $newStatus, $method, $ref, $paymentDate, $db);

            StudentHistoryService::record(
                $invoice['student_id'],
                'FEE_PAYMENT',
                "Recorded payment of {$paymentAmount} against invoice {$invoice['invoice_number']}",
                ['paid_amount' => $invoice['paid_amount'], 'status' => $invoice['status']],
                ['paid_amount' => $newPaidAmount, 'status' => $newStatus, 'payment_method' => $method],
                $adminId ?? ($_SESSION['admin_id'] ?? null),
                $db
            );

            Logger::info("Payment recorded for invoice #{$invoice['invoice_number']}. Amount: {$paymentAmount}. Method: {$method}");
            return true;
        });
    }

    public function getStatistics() {
        return $this->repository->getStatistics();
    }

    public function getDashboardData() {
        return [
            'summary' => $this->repository->getDashboardSummary(),
            'recent_payments' => $this->repository->getRecentPayments(5),
            'recent_invoices' => $this->repository->getRecentInvoices(5),
            'overdue_invoices' => $this->repository->getOverdueInvoices(5)
        ];
    }

    public function getPaymentHistory($invoiceId) {
        return $this->repository->getPaymentHistory($invoiceId);
    }

    public function getReceipt($invoiceId) {
        $invoice = $this->getFee($invoiceId);
        if (!$invoice) {
            throw new Exception("Invoice not found.");
        }

        $payments = $this->repository->getPaymentHistory($invoiceId);
        $latestPayment = !empty($payments) ? $payments[0] : null;

        return [
            'invoice' => $invoice,
            'payment' => $latestPayment,
            'previous_paid' => (float)$invoice['paid_amount'] - ((float)($latestPayment['amount'] ?? 0)),
            'receipt_number' => $latestPayment['id'] ?? $invoice['invoice_number'],
        ];
    }

    public function getStudentFeeSummary($studentId) {
        return $this->repository->getStudentFeeSummary($studentId);
    }

    private function calculateStatus($totalAmount, $paidAmount, $dueDate) {
        if ($paidAmount >= $totalAmount) {
            return 'Paid';
        }
        if ($paidAmount > 0) {
            return 'Partial';
        }
        if (date('Y-m-d') > $dueDate) {
            return 'Overdue';
        }
        return 'Pending';
    }

    private function generateInvoiceNumber() {
        $prefix = 'INV-' . date('Ym') . '-';
        do {
            $suffix = strtoupper(bin2hex(random_bytes(3)));
            $invoiceNumber = $prefix . $suffix;
        } while ($this->repository->findByInvoiceNumber($invoiceNumber));

        return $invoiceNumber;
    }
}
