<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class FeeRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll($filters = [], $limit = 50, $offset = 0) {
        $query = "
            SELECT f.*, s.full_name, s.student_id_str 
            FROM fee_records f 
            JOIN students s ON f.student_id = s.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $query .= " AND (s.full_name LIKE ? OR s.student_id_str LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND f.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['month'])) {
            $query .= " AND f.billing_month = ?";
            $params[] = $filters['month'];
        }
        
        if (!empty($filters['year'])) {
            $query .= " AND f.billing_year = ?";
            $params[] = $filters['year'];
        }

        $query .= " ORDER BY f.billing_year DESC, f.billing_month DESC, f.id DESC LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($query);
        
        $i = 1;
        foreach ($params as $param) {
            $stmt->bindValue($i++, $param);
        }
        $stmt->bindValue($i++, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue($i, (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function count($filters = []) {
        $query = "
            SELECT COUNT(*) 
            FROM fee_records f 
            JOIN students s ON f.student_id = s.id 
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $query .= " AND (s.full_name LIKE ? OR s.student_id_str LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND f.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['month'])) {
            $query .= " AND f.billing_month = ?";
            $params[] = $filters['month'];
        }
        
        if (!empty($filters['year'])) {
            $query .= " AND f.billing_year = ?";
            $params[] = $filters['year'];
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT f.*, s.full_name, s.student_id_str 
            FROM fee_records f 
            JOIN students s ON f.student_id = s.id 
            WHERE f.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function findByStudentAndMonthYear($studentId, $month, $year) {
        $stmt = $this->db->prepare("SELECT * FROM fee_records WHERE student_id = ? AND billing_month = ? AND billing_year = ?");
        $stmt->execute([$studentId, $month, $year]);
        return $stmt->fetch();
    }

    public function create($data, $pdo = null) {
        $db = $pdo ?? $this->db;
        $sql = "INSERT INTO fee_records (
            invoice_number, student_id, billing_month, billing_year, invoice_date, amount,
            additional_charges, discount, paid_amount, due_date, payment_date, status,
            payment_method, transaction_ref, remarks, charge_type
        ) VALUES (
            :invoice_number, :student_id, :billing_month, :billing_year, :invoice_date, :amount,
            :additional_charges, :discount, :paid_amount, :due_date, :payment_date, :status,
            :payment_method, :transaction_ref, :remarks, :charge_type
        )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'invoice_number' => $data['invoice_number'] ?? null,
            'student_id' => $data['student_id'],
            'billing_month' => $data['billing_month'],
            'billing_year' => $data['billing_year'],
            'invoice_date' => $data['invoice_date'] ?? date('Y-m-d'),
            'amount' => $data['amount'],
            'additional_charges' => $data['additional_charges'] ?? 0,
            'discount' => $data['discount'] ?? 0,
            'paid_amount' => $data['paid_amount'] ?? 0,
            'due_date' => $data['due_date'],
            'payment_date' => $data['payment_date'] ?? null,
            'status' => $data['status'] ?? 'Pending',
            'payment_method' => $data['payment_method'] ?? null,
            'transaction_ref' => $data['transaction_ref'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'charge_type' => $data['charge_type'] ?? 'MONTHLY_FEE'
        ]);
        return $db->lastInsertId();
    }

    public function updatePayment($id, $paidAmount, $paymentMethod, $transactionRef, $status, $paymentDate, $pdo = null) {
        $db = $pdo ?? $this->db;
        $sql = "UPDATE fee_records SET paid_amount = paid_amount + ?, payment_method = ?, transaction_ref = ?, status = ?, payment_date = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$paidAmount, $paymentMethod, $transactionRef, $status, $paymentDate, $id]);
    }

    public function createPayment($invoiceId, $data, $pdo = null) {
        $db = $pdo ?? $this->db;
        $receiptNumber = $data['receipt_number'] ?? $this->generateReceiptNumber($db);
        $stmt = $db->prepare("INSERT INTO fee_payments (invoice_id, receipt_number, amount, payment_date, payment_method, transaction_ref, remarks, received_by_admin, status) VALUES (:invoice_id, :receipt_number, :amount, :payment_date, :payment_method, :transaction_ref, :remarks, :received_by_admin, :status)");
        $ok = $stmt->execute([
            'invoice_id' => $invoiceId,
            'receipt_number' => $receiptNumber,
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'transaction_ref' => $data['transaction_ref'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'received_by_admin' => $data['received_by_admin'] ?? null,
            'status' => $data['status'] ?? 'Completed'
        ]);

        if (!$ok) {
            return false;
        }

        return (int)$db->lastInsertId();
    }

    public function createPaymentAllocation($paymentId, $invoiceId, $amount, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("INSERT INTO payment_allocations (payment_id, invoice_id, amount, allocated_at) VALUES (?, ?, ?, NOW())");
        return $stmt->execute([$paymentId, $invoiceId, $amount]);
    }

    public function findPaymentById($paymentId, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("SELECT fp.*, fr.invoice_number, fr.student_id, fr.billing_month, fr.billing_year, s.full_name, s.student_id_str, a.username AS admin_username FROM fee_payments fp JOIN fee_records fr ON fr.id = fp.invoice_id JOIN students s ON s.id = fr.student_id LEFT JOIN admins a ON a.id = fp.received_by_admin WHERE fp.id = ?");
        $stmt->execute([(int)$paymentId]);
        return $stmt->fetch();
    }

    public function findPaymentAllocations($paymentId, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("SELECT pa.*, fr.invoice_number, fr.billing_month, fr.billing_year FROM payment_allocations pa JOIN fee_records fr ON fr.id = pa.invoice_id WHERE pa.payment_id = ? ORDER BY fr.due_date ASC, pa.id ASC");
        $stmt->execute([$paymentId]);
        return $stmt->fetchAll();
    }

    public function generateReceiptNumber($pdo = null) {
        $db = $pdo ?? $this->db;
        $prefix = 'PAY-' . date('Y') . '-';
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $suffix = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $candidate = $prefix . $suffix;
            $stmt = $db->prepare("SELECT id FROM fee_payments WHERE receipt_number = ? LIMIT 1");
            $stmt->execute([$candidate]);
            if (!$stmt->fetch()) {
                return $candidate;
            }
        }
        throw new \Exception('Unable to generate a unique payment receipt number.');
    }

    public function setInvoicePaymentTotals($id, $paidAmount, $status, $paymentMethod, $transactionRef, $paymentDate, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("UPDATE fee_records SET paid_amount = :paid_amount, payment_method = :payment_method, transaction_ref = :transaction_ref, status = :status, payment_date = :payment_date WHERE id = :id");
        return $stmt->execute([
            'paid_amount' => $paidAmount,
            'payment_method' => $paymentMethod,
            'transaction_ref' => $transactionRef,
            'status' => $status,
            'payment_date' => $paymentDate,
            'id' => $id
        ]);
    }

    public function getStudentCreditBalance($studentId, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) AS balance FROM student_credits WHERE student_id = ?");
        $stmt->execute([(int)$studentId]);
        return (float)$stmt->fetchColumn();
    }

    public function applyStudentCredit($studentId, $amount, $sourceType, $sourceId, $reason, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("INSERT INTO student_credits (student_id, amount, source_type, source_id, reason) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([(int)$studentId, (float)$amount, trim((string)$sourceType), $sourceId !== null ? (int)$sourceId : null, trim((string)$reason)]);
    }

    public function consumeStudentCredit($studentId, $amount, $sourceType, $sourceId, $reason, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("INSERT INTO student_credits (student_id, amount, source_type, source_id, reason) VALUES (?, ?, ?, ?, ?)");
        $signedAmount = -1 * abs((float)$amount);
        return $stmt->execute([(int)$studentId, $signedAmount, trim((string)$sourceType), $sourceId !== null ? (int)$sourceId : null, trim((string)$reason)]);
    }

    public function reversePayment($paymentId, $reversedByAdmin, $reason, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("UPDATE fee_payments SET status = 'Reversed', reversed_by_admin = ?, reversed_at = NOW(), reversal_reason = ? WHERE id = ? AND status <> 'Reversed'");
        return $stmt->execute([(int)$reversedByAdmin, trim((string)$reason), (int)$paymentId]);
    }

    public function createRefund($data, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("INSERT INTO refunds (refund_number, student_id, amount, refund_date, reason, payment_method, reference_number, processed_by_admin, status) VALUES (:refund_number, :student_id, :amount, :refund_date, :reason, :payment_method, :reference_number, :processed_by_admin, :status)");
        $ok = $stmt->execute([
            'refund_number' => $data['refund_number'],
            'student_id' => (int)$data['student_id'],
            'amount' => (float)$data['amount'],
            'refund_date' => $data['refund_date'] ?? date('Y-m-d'),
            'reason' => trim((string)($data['reason'] ?? 'Refund')),
            'payment_method' => $data['payment_method'] ?? 'Cash',
            'reference_number' => $data['reference_number'] ?? null,
            'processed_by_admin' => $data['processed_by_admin'] ?? null,
            'status' => $data['status'] ?? 'Processed'
        ]);
        return $ok ? (int)$db->lastInsertId() : false;
    }

    public function getOutstandingBalance($studentId, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("SELECT COALESCE(SUM((amount + additional_charges - discount) - paid_amount), 0) as outstanding_balance FROM fee_records WHERE student_id = ? AND (status IN ('Pending', 'Partial', 'Overdue') OR (amount + additional_charges - discount) > paid_amount)");
        $stmt->execute([$studentId]);
        $result = $stmt->fetchColumn();
        return $result ? (float)$result : 0.0;
    }

    public function updateOverdueStatuses($studentId = null, $pdo = null) {
        $db = $pdo ?? $this->db;
        $sql = "UPDATE fee_records SET status = 'Overdue' WHERE status IN ('Pending', 'Partial') AND due_date < CURDATE() AND (amount + additional_charges - discount) > paid_amount";
        $params = [];
        if ($studentId !== null) {
            $sql .= " AND student_id = ?";
            $params[] = (int)$studentId;
        }
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public function findOverdueInvoices($studentId = null, $pdo = null) {
        $db = $pdo ?? $this->db;
        $sql = "SELECT * FROM fee_records WHERE due_date < CURDATE() AND (amount + additional_charges - discount) > paid_amount";
        $params = [];
        if ($studentId !== null) {
            $sql .= " AND student_id = ?";
            $params[] = (int)$studentId;
        }
        $sql .= " ORDER BY due_date ASC, id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function createLateFee($invoiceId, $amount, $gracePeriodDays, $feeType, $reason, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("SELECT id FROM late_fees WHERE invoice_id = ? AND reason = ? AND amount = ? LIMIT 1");
        $stmt->execute([(int)$invoiceId, trim((string)$reason), (float)$amount]);
        if ($stmt->fetch()) {
            return false;
        }

        $insert = $db->prepare("INSERT INTO late_fees (invoice_id, amount, grace_period_days, fee_type, reason, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $ok = $insert->execute([(int)$invoiceId, (float)$amount, (int)$gracePeriodDays, trim((string)$feeType), trim((string)$reason)]);
        if (!$ok) {
            return false;
        }

        $invoiceStmt = $db->prepare("UPDATE fee_records SET additional_charges = additional_charges + ?, status = CASE WHEN (amount + additional_charges - discount) <= paid_amount THEN 'Paid' ELSE CASE WHEN paid_amount > 0 THEN 'Partial' ELSE 'Overdue' END END WHERE id = ?");
        $invoiceStmt->execute([(float)$amount, (int)$invoiceId]);
        return (int)$db->lastInsertId();
    }

    public function getStudentArrearsSummary($studentId, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("SELECT COALESCE(SUM((amount + additional_charges - discount) - paid_amount), 0) AS outstanding_balance, COALESCE(SUM(CASE WHEN due_date < CURDATE() AND (amount + additional_charges - discount) > paid_amount THEN (amount + additional_charges - discount) - paid_amount ELSE 0 END), 0) AS overdue_balance FROM fee_records WHERE student_id = ? AND (amount + additional_charges - discount) > paid_amount");
        $stmt->execute([(int)$studentId]);
        return $stmt->fetch();
    }

    public function findStudentInvoices($studentId, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("SELECT f.*, s.full_name, s.student_id_str FROM fee_records f JOIN students s ON s.id = f.student_id WHERE f.student_id = ? ORDER BY f.due_date ASC, f.id ASC");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    public function findStudentPayments($studentId, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("SELECT fp.*, fr.invoice_number, fr.billing_month, fr.billing_year FROM fee_payments fp JOIN fee_records fr ON fr.id = fp.invoice_id WHERE fr.student_id = ? ORDER BY fp.payment_date DESC, fp.id DESC");
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    public function getLastPaymentDate($studentId, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("SELECT MAX(payment_date) FROM fee_payments fp JOIN fee_records fr ON fr.id = fp.invoice_id WHERE fr.student_id = ?");
        $stmt->execute([$studentId]);
        $date = $stmt->fetchColumn();
        return $date ?: null;
    }

    public function createAdditionalCharge($data, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("INSERT INTO additional_charges (student_id, charge_type, description, amount, charge_date, reference_number, created_by_admin, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ok = $stmt->execute([
            (int)($data['student_id'] ?? 0),
            trim((string)($data['charge_type'] ?? 'MISC')),
            trim((string)($data['description'] ?? 'Additional charge')),
            (float)($data['amount'] ?? 0),
            $data['charge_date'] ?? date('Y-m-d'),
            $data['reference_number'] ?? null,
            $data['created_by_admin'] ?? null,
            $data['status'] ?? 'Pending'
        ]);
        return $ok ? (int)$db->lastInsertId() : false;
    }

    public function createInvoiceDiscount($data, $pdo = null) {
        $db = $pdo ?? $this->db;
        $invoiceId = (int)($data['invoice_id'] ?? 0);
        if ($invoiceId <= 0) {
            return false;
        }

        $invoiceStmt = $db->prepare("SELECT amount, additional_charges, discount FROM fee_records WHERE id = ?");
        $invoiceStmt->execute([$invoiceId]);
        $invoice = $invoiceStmt->fetch();
        if (!$invoice) {
            return false;
        }

        $discountValue = (float)($data['discount_value'] ?? 0);
        $discountType = strtoupper(trim((string)($data['discount_type'] ?? 'FIXED')));
        $currentTotal = (float)$invoice['amount'] + (float)$invoice['additional_charges'];
        $discountLimit = max(0.0, $currentTotal - (float)$invoice['discount']);
        if ($discountValue <= 0 || $discountValue > $discountLimit) {
            return false;
        }

        $stmt = $db->prepare("INSERT INTO discounts (invoice_id, discount_type, discount_value, reason, created_by_admin, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $ok = $stmt->execute([
            $invoiceId,
            $discountType,
            $discountValue,
            trim((string)($data['reason'] ?? 'Discount applied')),
            $data['created_by_admin'] ?? null
        ]);

        if ($ok) {
            $db->prepare("UPDATE fee_records SET discount = discount + ?, status = CASE WHEN (amount + additional_charges - discount) <= paid_amount THEN 'Paid' ELSE CASE WHEN paid_amount > 0 THEN 'Partial' ELSE 'Pending' END END WHERE id = ?")->execute([$discountValue, $invoiceId]);
            return (int)$db->lastInsertId();
        }

        return false;
    }

    public function getStudentStatementRows($studentId, $pdo = null) {
        $db = $pdo ?? $this->db;
        $rows = [];

        $invoiceStmt = $db->prepare("SELECT id, invoice_number, billing_month, billing_year, invoice_date, due_date, amount, additional_charges, discount, paid_amount, status, 'INVOICE' AS entry_type, (amount + additional_charges - discount) AS invoice_total FROM fee_records WHERE student_id = ? ORDER BY invoice_date ASC, id ASC");
        $invoiceStmt->execute([(int)$studentId]);
        foreach ($invoiceStmt->fetchAll() as $invoice) {
            $rows[] = [
                'date' => $invoice['invoice_date'],
                'reference' => $invoice['invoice_number'],
                'description' => 'Invoice ' . $invoice['billing_month'] . '/' . $invoice['billing_year'],
                'debit' => (float)$invoice['invoice_total'],
                'credit' => (float)$invoice['paid_amount'],
                'balance' => 0.0,
                'entry_type' => 'INVOICE',
            ];
        }

        $paymentStmt = $db->prepare("SELECT fp.payment_date AS entry_date, fp.receipt_number AS reference, CONCAT('Payment against ', fr.invoice_number) AS description, 0 AS debit, fp.amount AS credit, 'PAYMENT' AS entry_type FROM fee_payments fp JOIN fee_records fr ON fr.id = fp.invoice_id WHERE fr.student_id = ? ORDER BY fp.payment_date ASC, fp.id ASC");
        $paymentStmt->execute([(int)$studentId]);
        foreach ($paymentStmt->fetchAll() as $payment) {
            $rows[] = [
                'date' => $payment['entry_date'],
                'reference' => $payment['reference'],
                'description' => $payment['description'],
                'debit' => (float)$payment['debit'],
                'credit' => (float)$payment['credit'],
                'balance' => 0.0,
                'entry_type' => 'PAYMENT',
            ];
        }

        $chargeStmt = $db->prepare("SELECT charge_date AS entry_date, reference_number AS reference, CONCAT(charge_type, ': ', description) AS description, amount AS debit, 0 AS credit, 'CHARGE' AS entry_type FROM additional_charges WHERE student_id = ? ORDER BY charge_date ASC, id ASC");
        $chargeStmt->execute([(int)$studentId]);
        foreach ($chargeStmt->fetchAll() as $charge) {
            $rows[] = [
                'date' => $charge['entry_date'],
                'reference' => $charge['reference'] ?: 'ADDITIONAL-CHARGE',
                'description' => $charge['description'],
                'debit' => (float)$charge['debit'],
                'credit' => (float)$charge['credit'],
                'balance' => 0.0,
                'entry_type' => 'CHARGE',
            ];
        }

        usort($rows, function($a, $b) {
            return strcmp($a['date'], $b['date']) ?: strcmp($a['reference'], $b['reference']);
        });

        $runningBalance = 0.0;
        foreach ($rows as &$row) {
            $runningBalance += (float)$row['debit'] - (float)$row['credit'];
            $row['balance'] = round($runningBalance, 2);
        }
        unset($row);

        return $rows;
    }
}
