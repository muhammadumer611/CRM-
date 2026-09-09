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
            WHERE f.charge_type = 'MONTHLY_FEE'
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
            WHERE f.charge_type = 'MONTHLY_FEE'
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

    public function getPendingFeesSummary(array $filters = []) {
        $where = $this->buildPendingFeeWhere($filters);
        $sql = "
            SELECT
                COALESCE(SUM((fr.amount + fr.additional_charges - fr.discount) - fr.paid_amount), 0) AS total_pending_amount,
                COUNT(DISTINCT fr.student_id) AS pending_student_count,
                COUNT(*) AS invoice_count
            FROM fee_records fr
            JOIN students s ON s.id = fr.student_id
            {$where['sql']}
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($where['params']);
        return $stmt->fetch();
    }

    public function getPendingFeeRows(array $filters = []) {
        $where = $this->buildPendingFeeWhere($filters);
        $sql = "
            SELECT
                fr.id,
                fr.invoice_number,
                fr.billing_month,
                fr.billing_year,
                fr.amount,
                fr.additional_charges,
                fr.discount,
                fr.paid_amount,
                (fr.amount + fr.additional_charges - fr.discount) AS invoice_total,
                (fr.amount + fr.additional_charges - fr.discount - fr.paid_amount) AS pending_amount,
                fr.due_date,
                fr.status,
                s.full_name AS student_name,
                s.student_id_str AS student_id,
                COALESCE(r.monthly_fee, fr.amount) AS monthly_fee
            FROM fee_records fr
            JOIN students s ON s.id = fr.student_id
            LEFT JOIN room_allocations ra ON ra.student_id = s.id AND ra.status = 'Active'
            LEFT JOIN rooms r ON r.id = ra.room_id
            {$where['sql']}
            ORDER BY fr.billing_year DESC, fr.billing_month DESC, fr.due_date ASC, fr.id DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($where['params']);
        return $stmt->fetchAll();
    }

    public function getPendingFeeStudentsOverview(array $filters = []) {
        $sql = "
            SELECT
                fr.id AS invoice_id,
                fr.invoice_number,
                fr.student_id,
                fr.billing_month,
                fr.billing_year,
                fr.amount,
                fr.additional_charges,
                fr.discount,
                fr.paid_amount,
                (fr.amount + fr.additional_charges - fr.discount) AS invoice_total,
                (fr.amount + fr.additional_charges - fr.discount - fr.paid_amount) AS pending_amount,
                fr.due_date,
                fr.status AS invoice_status,
                s.full_name AS student_name,
                s.student_id_str,
                s.cnic,
                s.phone,
                s.address,
                s.monthly_fee AS student_monthly_fee,
                s.status AS student_status,
                ra.room_id,
                ra.bed_number,
                r.room_number,
                r.block,
                r.floor,
                r.room_type
            FROM fee_records fr
            JOIN students s ON s.id = fr.student_id
            LEFT JOIN room_allocations ra ON ra.student_id = s.id AND ra.status = 'Active'
            LEFT JOIN rooms r ON r.id = ra.room_id
            WHERE s.status = 'Active'
              AND (fr.amount + fr.additional_charges - fr.discount) > fr.paid_amount
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $rawTerm = trim((string)$filters['search']);
            $searchTerm = '%' . $rawTerm . '%';
            $sql .= " AND (s.full_name LIKE ? OR s.student_id_str LIKE ? OR s.cnic LIKE ? OR s.phone LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['room'])) {
            $rawRoom = trim((string)$filters['room']);
            $searchRoom = '%' . $rawRoom . '%';
            $sql .= " AND (r.room_number LIKE ? OR r.block LIKE ?)";
            $params[] = $searchRoom;
            $params[] = $searchRoom;
        }

        if (!empty($filters['month'])) {
            $sql .= " AND fr.billing_month = ?";
            $params[] = (int)$filters['month'];
        }

        if (!empty($filters['year'])) {
            $sql .= " AND fr.billing_year = ?";
            $params[] = (int)$filters['year'];
        }

        if (!empty($filters['status'])) {
            $statusFilter = strtolower(trim((string)$filters['status']));
            if ($statusFilter === 'overdue') {
                $sql .= " AND fr.due_date < CURDATE()";
            } elseif ($statusFilter === 'partial' || $statusFilter === 'partially_paid') {
                $sql .= " AND fr.paid_amount > 0";
            } elseif ($statusFilter === 'pending') {
                $sql .= " AND fr.paid_amount = 0 AND (fr.due_date IS NULL OR fr.due_date >= CURDATE())";
            }
        }

        $sql .= " ORDER BY s.id ASC, fr.billing_year ASC, fr.billing_month ASC, fr.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rawRows = $stmt->fetchAll();

        $studentsMap = [];
        $totalPendingAmount = 0.0;
        $totalInvoicesCount = 0;
        $overdueStudentsSet = [];

        foreach ($rawRows as $row) {
            $studentId = (int)$row['student_id'];
            $invoicePending = (float)$row['pending_amount'];
            $invoiceTotal = (float)$row['invoice_total'];
            $invoicePaid = (float)$row['paid_amount'];
            $dueDate = $row['due_date'];
            $isOverdue = $dueDate && (date('Y-m-d') > $dueDate);

            $monthName = date('M', mktime(0, 0, 0, (int)$row['billing_month'], 1, (int)$row['billing_year']));
            $periodStr = $monthName . ' ' . $row['billing_year'];

            $invoiceStatus = 'Pending';
            if ($isOverdue) {
                $invoiceStatus = 'Overdue';
            } elseif ($invoicePaid > 0) {
                $invoiceStatus = 'Partial';
            }

            $invoiceItem = [
                'id' => (int)$row['invoice_id'],
                'invoice_number' => $row['invoice_number'],
                'billing_month' => (int)$row['billing_month'],
                'billing_year' => (int)$row['billing_year'],
                'billing_period' => $periodStr,
                'amount' => (float)$row['amount'],
                'additional_charges' => (float)$row['additional_charges'],
                'discount' => (float)$row['discount'],
                'invoice_total' => $invoiceTotal,
                'paid_amount' => $invoicePaid,
                'pending_amount' => $invoicePending,
                'due_date' => $dueDate,
                'is_overdue' => $isOverdue,
                'status' => $invoiceStatus
            ];

            if (!isset($studentsMap[$studentId])) {
                $studentsMap[$studentId] = [
                    'student_id' => $studentId,
                    'student_name' => $row['student_name'],
                    'student_id_str' => $row['student_id_str'],
                    'cnic' => $row['cnic'],
                    'phone' => $row['phone'],
                    'address' => $row['address'],
                    'room_id' => $row['room_id'] ? (int)$row['room_id'] : null,
                    'room_number' => $row['room_number'],
                    'block' => $row['block'],
                    'floor' => $row['floor'],
                    'room_type' => $row['room_type'],
                    'bed_number' => $row['bed_number'] ? (int)$row['bed_number'] : null,
                    'monthly_fee' => $row['student_monthly_fee'] !== null ? (float)$row['student_monthly_fee'] : null,
                    'total_due' => 0.0,
                    'total_paid' => 0.0,
                    'total_pending' => 0.0,
                    'earliest_due_date' => $dueDate,
                    'has_overdue' => false,
                    'has_partial' => false,
                    'invoices' => [],
                    'pending_months' => []
                ];
            }

            $studentsMap[$studentId]['total_due'] += $invoiceTotal;
            $studentsMap[$studentId]['total_paid'] += $invoicePaid;
            $studentsMap[$studentId]['total_pending'] += $invoicePending;
            $studentsMap[$studentId]['invoices'][] = $invoiceItem;
            $studentsMap[$studentId]['pending_months'][] = $periodStr;

            if ($isOverdue) {
                $studentsMap[$studentId]['has_overdue'] = true;
                $overdueStudentsSet[$studentId] = true;
            }
            if ($invoicePaid > 0) {
                $studentsMap[$studentId]['has_partial'] = true;
            }

            if ($dueDate && (!$studentsMap[$studentId]['earliest_due_date'] || $dueDate < $studentsMap[$studentId]['earliest_due_date'])) {
                $studentsMap[$studentId]['earliest_due_date'] = $dueDate;
            }

            $totalPendingAmount += $invoicePending;
            $totalInvoicesCount++;
        }

        $studentsList = [];
        foreach ($studentsMap as $stu) {
            $overallStatus = 'Pending';
            if ($stu['has_overdue']) {
                $overallStatus = 'Overdue';
            } elseif ($stu['has_partial']) {
                $overallStatus = 'Partial';
            }
            $stu['overall_status'] = $overallStatus;
            $stu['pending_months_summary'] = implode(', ', $stu['pending_months']);
            $studentsList[] = $stu;
        }

        return [
            'students' => $studentsList,
            'summary' => [
                'total_pending_amount' => $totalPendingAmount,
                'pending_student_count' => count($studentsList),
                'overdue_student_count' => count($overdueStudentsSet),
                'total_invoices_count' => $totalInvoicesCount
            ]
        ];
    }

    public function getStudentPendingFeeDetails($studentId) {
        $stmtStudent = $this->db->prepare("
            SELECT s.*, 
                   ra.id AS allocation_id, ra.room_id, ra.bed_number, ra.joining_date AS allocation_date,
                   r.room_number, r.block, r.floor, r.room_type, r.monthly_fee AS room_monthly_fee
            FROM students s
            LEFT JOIN room_allocations ra ON s.id = ra.student_id AND ra.status = 'Active'
            LEFT JOIN rooms r ON ra.room_id = r.id
            WHERE s.id = ?
        ");
        $stmtStudent->execute([$studentId]);
        $student = $stmtStudent->fetch();

        if (!$student) {
            return null;
        }

        $stmtInvoices = $this->db->prepare("
            SELECT fr.*,
                   (fr.amount + fr.additional_charges - fr.discount) AS invoice_total,
                   (fr.amount + fr.additional_charges - fr.discount - fr.paid_amount) AS pending_amount
            FROM fee_records fr
            WHERE fr.student_id = ?
              AND (fr.amount + fr.additional_charges - fr.discount) > fr.paid_amount
            ORDER BY fr.billing_year ASC, fr.billing_month ASC, fr.id ASC
        ");
        $stmtInvoices->execute([$studentId]);
        $rawInvoices = $stmtInvoices->fetchAll();

        $pendingInvoices = [];
        $totalPendingBalance = 0.0;
        $totalAmountDue = 0.0;
        $totalAmountPaid = 0.0;
        $hasOverdue = false;
        $hasPartial = false;
        $earliestDueDate = null;
        $oldestMonthStr = null;

        foreach ($rawInvoices as $inv) {
            $invoiceId = (int)$inv['id'];
            $invoiceTotal = (float)$inv['invoice_total'];
            $paidAmount = (float)$inv['paid_amount'];
            $pendingAmount = (float)$inv['pending_amount'];
            $dueDate = $inv['due_date'];
            $isOverdue = $dueDate && (date('Y-m-d') > $dueDate);

            $monthFullName = date('F', mktime(0, 0, 0, (int)$inv['billing_month'], 1, (int)$inv['billing_year']));
            $periodStr = $monthFullName . ' ' . $inv['billing_year'];

            if (!$oldestMonthStr) {
                $oldestMonthStr = $periodStr;
            }

            $invStatus = 'Pending';
            if ($isOverdue) {
                $invStatus = 'Overdue';
                $hasOverdue = true;
            } elseif ($paidAmount > 0) {
                $invStatus = 'Partial';
                $hasPartial = true;
            }

            if ($dueDate && (!$earliestDueDate || $dueDate < $earliestDueDate)) {
                $earliestDueDate = $dueDate;
            }

            $totalPendingBalance += $pendingAmount;
            $totalAmountDue += $invoiceTotal;
            $totalAmountPaid += $paidAmount;

            // Fetch payments for this invoice
            $stmtInvPayments = $this->db->prepare("
                SELECT fp.*, a.username AS received_by_admin_username
                FROM fee_payments fp
                LEFT JOIN admins a ON a.id = fp.received_by_admin
                WHERE fp.invoice_id = ? AND fp.status <> 'Reversed'
                ORDER BY fp.payment_date DESC, fp.id DESC
            ");
            $stmtInvPayments->execute([$invoiceId]);
            $invPayments = $stmtInvPayments->fetchAll();

            $pendingInvoices[] = [
                'id' => $invoiceId,
                'invoice_number' => $inv['invoice_number'],
                'billing_month' => (int)$inv['billing_month'],
                'billing_year' => (int)$inv['billing_year'],
                'billing_period' => $periodStr,
                'amount' => (float)$inv['amount'],
                'additional_charges' => (float)$inv['additional_charges'],
                'discount' => (float)$inv['discount'],
                'invoice_total' => $invoiceTotal,
                'paid_amount' => $paidAmount,
                'pending_amount' => $pendingAmount,
                'due_date' => $dueDate,
                'is_overdue' => $isOverdue,
                'status' => $invStatus,
                'remarks' => $inv['remarks'] ?? '',
                'payments' => $invPayments
            ];
        }

        // Fetch all payment history for this student
        $stmtAllPayments = $this->db->prepare("
            SELECT fp.*, fr.invoice_number, fr.billing_month, fr.billing_year,
                   a.username AS received_by_admin_username
            FROM fee_payments fp
            JOIN fee_records fr ON fr.id = fp.invoice_id
            LEFT JOIN admins a ON a.id = fp.received_by_admin
            WHERE fr.student_id = ? AND fp.status <> 'Reversed'
            ORDER BY fp.payment_date DESC, fp.id DESC
        ");
        $stmtAllPayments->execute([$studentId]);
        $allPayments = $stmtAllPayments->fetchAll();

        $overallStatus = 'Settled';
        if ($totalPendingBalance > 0) {
            if ($hasOverdue) {
                $overallStatus = 'Overdue';
            } elseif ($hasPartial) {
                $overallStatus = 'Partial';
            } else {
                $overallStatus = 'Pending';
            }
        }

        return [
            'student' => $student,
            'pending_invoices' => $pendingInvoices,
            'payment_history' => $allPayments,
            'summary' => [
                'monthly_fee' => $student['monthly_fee'] !== null ? (float)$student['monthly_fee'] : 0.0,
                'total_pending_balance' => $totalPendingBalance,
                'total_amount_due' => $totalAmountDue,
                'total_amount_paid' => $totalAmountPaid,
                'pending_months_count' => count($pendingInvoices),
                'oldest_pending_month' => $oldestMonthStr ?? '—',
                'earliest_due_date' => $earliestDueDate,
                'overall_status' => $overallStatus
            ]
        ];
    }

    private function buildPendingFeeWhere(array $filters = []) {
        $sql = " WHERE (fr.amount + fr.additional_charges - fr.discount) > fr.paid_amount ";
        $params = [];

        if (!empty($filters['student_id'])) {
            $sql .= " AND fr.student_id = :student_id";
            $params['student_id'] = (int)$filters['student_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (s.full_name LIKE :search OR s.student_id_str LIKE :search)";
            $params['search'] = '%' . trim((string)$filters['search']) . '%';
        }

        if (!empty($filters['status'])) {
            $sql .= " AND fr.status = :status";
            $params['status'] = $this->normalizePendingStatusFilter($filters['status']);
        }

        if (!empty($filters['month'])) {
            $sql .= " AND fr.billing_month = :month";
            $params['month'] = (int)$filters['month'];
        }

        if (!empty($filters['year'])) {
            $sql .= " AND fr.billing_year = :year";
            $params['year'] = (int)$filters['year'];
        }

        if (!empty($filters['period'])) {
            $period = strtolower((string)$filters['period']);
            if ($period === 'current_month') {
                $sql .= " AND fr.billing_year = YEAR(CURDATE()) AND fr.billing_month = MONTH(CURDATE())";
            } elseif ($period === 'previous_months') {
                $sql .= " AND (fr.billing_year < YEAR(CURDATE()) OR (fr.billing_year = YEAR(CURDATE()) AND fr.billing_month < MONTH(CURDATE())))";
            }
        }

        return ['sql' => $sql, 'params' => $params];
    }

    private function normalizePendingStatusFilter($status) {
        $status = strtolower(trim((string)$status));
        $map = [
            'pending' => 'Pending',
            'partially_paid' => 'Partial',
            'partial' => 'Partial',
            'overdue' => 'Overdue'
        ];
        return $map[$status] ?? $status;
    }

    public function getCollectionSummary(array $filters = []) {
        $where = $this->buildCollectionWhere($filters);
        $sql = "
            SELECT
                COALESCE(SUM(fp.amount), 0) AS total_collection,
                COUNT(fp.id) AS payment_count,
                MAX(fp.payment_date) AS latest_payment_date
            FROM fee_payments fp
            JOIN fee_records fr ON fr.id = fp.invoice_id
            JOIN students s ON s.id = fr.student_id
            WHERE fp.status <> 'Reversed'
              AND fp.amount > 0
              {$where['sql']}
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($where['params']);
        return $stmt->fetch();
    }

    public function getCollectionRows(array $filters = [], $limit = 50, $offset = 0) {
        $where = $this->buildCollectionWhere($filters);
        $sql = "
            SELECT
                fp.id,
                s.full_name AS student_name,
                s.student_id_str AS student_id,
                fp.amount AS amount_received,
                fp.payment_date,
                fp.payment_method,
                fp.receipt_number,
                fr.billing_month,
                fr.billing_year,
                CONCAT(fr.billing_month, '/', fr.billing_year) AS billing_period,
                fp.status,
                fp.transaction_ref
            FROM fee_payments fp
            JOIN fee_records fr ON fr.id = fp.invoice_id
            JOIN students s ON s.id = fr.student_id
            WHERE fp.status <> 'Reversed'
              AND fp.amount > 0
              {$where['sql']}
            ORDER BY fp.payment_date DESC, fp.id DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->db->prepare($sql);
        foreach ($where['params'] as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function buildCollectionWhere(array $filters = []) {
        $sql = '';
        $params = [];
        $dateFilter = $filters['date_filter'] ?? 'this_month';

        if (!empty($filters['student_id'])) {
            $sql .= ' AND fr.student_id = :student_id';
            $params['student_id'] = (int)$filters['student_id'];
        }

        if (!empty($filters['payment_method'])) {
            $sql .= ' AND fp.payment_method = :payment_method';
            $params['payment_method'] = trim((string)$filters['payment_method']);
        }

        if (!empty($filters['start_date'])) {
            $sql .= ' AND fp.payment_date >= :start_date';
            $params['start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= ' AND fp.payment_date <= :end_date';
            $params['end_date'] = $filters['end_date'];
        }

        if (empty($filters['start_date']) && empty($filters['end_date'])) {
            switch ($dateFilter) {
                case 'today':
                    $sql .= ' AND fp.payment_date = CURDATE()';
                    break;
                case 'this_month':
                    $sql .= ' AND fp.payment_date >= DATE_FORMAT(CURDATE(), "%Y-%m-01") AND fp.payment_date <= LAST_DAY(CURDATE())';
                    break;
                case 'this_year':
                    $sql .= ' AND fp.payment_date >= DATE_FORMAT(CURDATE(), "%Y-01-01") AND fp.payment_date <= DATE_FORMAT(CURDATE(), "%Y-12-31")';
                    break;
                case 'custom':
                    break;
                default:
                    $sql .= ' AND fp.payment_date >= DATE_FORMAT(CURDATE(), "%Y-%m-01") AND fp.payment_date <= LAST_DAY(CURDATE())';
                    break;
            }
        }

        return ['sql' => $sql, 'params' => $params];
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT f.*, s.full_name, s.student_id_str, s.phone, s.monthly_fee AS student_monthly_fee
            FROM fee_records f 
            JOIN students s ON f.student_id = s.id 
            WHERE f.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function findByStudentAndMonthYear($studentId, $month, $year) {
        $stmt = $this->db->prepare("SELECT * FROM fee_records WHERE student_id = ? AND billing_month = ? AND billing_year = ? AND charge_type = 'MONTHLY_FEE'");
        $stmt->execute([$studentId, $month, $year]);
        return $stmt->fetch();
    }

    public function create($data, $pdo = null) {
        $db = $pdo ?? $this->db;

        // Generate invoice_number if not provided
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = 'INV-' . strtoupper(substr(uniqid(), -8));
        }

        $fields = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));
        $sql = "INSERT INTO fee_records ($fields) VALUES ($placeholders)";
        $stmt = $db->prepare($sql);
        $stmt->execute($data);
        return $db->lastInsertId();
    }

    /**
     * Record a payment against an invoice. Stores in fee_payments, updates fee_records.
     * Returns ['success', 'receipt_number', 'new_status'] or ['success' => false, 'error']
     */
    public function recordPayment($invoiceId, $amount, $paymentMethod, $transactionRef, $remarks, $adminId, $pdo = null) {
        $db = $pdo ?? $this->db;

        // Get current invoice (locked)
        $stmt = $db->prepare("SELECT * FROM fee_records WHERE id = ? FOR UPDATE");
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch();

        if (!$invoice) return ['success' => false, 'error' => 'Invoice not found.'];
        if ($invoice['status'] === 'Paid') return ['success' => false, 'error' => 'Invoice is already fully paid.'];

        $remaining = (float)$invoice['amount'] - (float)$invoice['paid_amount'];
        if ($amount <= 0) return ['success' => false, 'error' => 'Payment amount must be greater than zero.'];
        if ($amount > $remaining + 0.01) return ['success' => false, 'error' => "Payment amount (Rs. {$amount}) exceeds remaining balance (Rs. " . number_format($remaining, 2) . ').'];

        $receiptNum = 'RCP-' . strtoupper(substr(uniqid(), -8));
        $paymentDate = date('Y-m-d');

        // Insert payment record
        $stmtP = $db->prepare("
            INSERT INTO fee_payments (invoice_id, receipt_number, amount, payment_date, payment_method, transaction_ref, remarks, received_by_admin, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Completed')
        ");
        $stmtP->execute([$invoiceId, $receiptNum, $amount, $paymentDate, $paymentMethod, $transactionRef ?: null, $remarks ?: null, $adminId ?: null]);

        // Update invoice totals
        $newTotalPaid = (float)$invoice['paid_amount'] + $amount;
        $newStatus = ($newTotalPaid >= (float)$invoice['amount']) ? 'Paid' : 'Partial';

        $stmtU = $db->prepare("UPDATE fee_records SET paid_amount = ?, status = ?, payment_date = ?, payment_method = ? WHERE id = ?");
        $stmtU->execute([$newTotalPaid, $newStatus, $paymentDate, $paymentMethod, $invoiceId]);

        return ['success' => true, 'receipt_number' => $receiptNum, 'new_status' => $newStatus, 'new_paid' => $newTotalPaid];
    }

    public function getPayments($invoiceId) {
        $stmt = $this->db->prepare("
            SELECT fp.*, a.username AS received_by_name
            FROM fee_payments fp
            LEFT JOIN admins a ON fp.received_by_admin = a.id
            WHERE fp.invoice_id = ? AND fp.status = 'Completed'
            ORDER BY fp.payment_date DESC, fp.id DESC
        ");
        $stmt->execute([$invoiceId]);
        return $stmt->fetchAll();
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
        $stmt = $db->prepare("SELECT COALESCE(SUM((amount + additional_charges - discount) - paid_amount), 0) FROM fee_records WHERE student_id = ? AND status IN ('Pending', 'Partial', 'Overdue') AND charge_type = 'MONTHLY_FEE'");
        $stmt->execute([$studentId]);
        return (float)$stmt->fetchColumn();
    }

    /**
     * Mark overdue invoices (past due date, not fully paid) as Overdue
     */
    public function markOverdueInvoices() {
        $stmt = $this->db->prepare("
            UPDATE fee_records 
            SET status = 'Overdue' 
            WHERE status IN ('Pending','Partial') 
              AND due_date < CURDATE()
              AND charge_type = 'MONTHLY_FEE'
        ");
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function getFinancialSummary() {
        $stmt = $this->db->query("
            SELECT 
                COALESCE(SUM(amount), 0) AS total_billed,
                COALESCE(SUM(paid_amount), 0) AS total_collected,
                COALESCE(SUM(CASE WHEN status IN ('Pending','Partial','Overdue') THEN amount - paid_amount ELSE 0 END), 0) AS total_outstanding,
                COALESCE(SUM(CASE WHEN status = 'Overdue' THEN amount - paid_amount ELSE 0 END), 0) AS total_overdue
            FROM fee_records 
            WHERE charge_type = 'MONTHLY_FEE'
        ");
        return $stmt->fetch();
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

    public function getSecurityDepositSummary() {
        $stmtHeld = $this->db->query("SELECT COALESCE(SUM(remaining_amount), 0) AS total_held, COUNT(DISTINCT student_id) AS held_students FROM security_deposits WHERE status IN ('HELD', 'ADJUSTED', 'PARTIALLY_REFUNDED')");
        $heldData = $stmtHeld->fetch() ?: ['total_held' => 0, 'held_students' => 0];

        $stmtRefunded = $this->db->query("SELECT COALESCE(SUM(original_amount - remaining_amount), 0) FROM security_deposits WHERE status = 'REFUNDED'");
        $totalRefunded = (float)$stmtRefunded->fetchColumn();

        $stmtDeducted = $this->db->query("SELECT COALESCE(SUM(original_amount - remaining_amount), 0) FROM security_deposits WHERE status = 'DEDUCTED'");
        $totalDeducted = (float)$stmtDeducted->fetchColumn();

        return [
            'total_held' => (float)($heldData['total_held'] ?? 0),
            'held_students' => (int)($heldData['held_students'] ?? 0),
            'total_refunded' => $totalRefunded,
            'total_deducted' => $totalDeducted
        ];
    }

    public function getSecurityDeposits($filters = []) {
        $sql = "
            SELECT 
                sd.id,
                sd.student_id,
                s.full_name AS student_name,
                s.student_id_str,
                s.cnic,
                s.created_at AS admission_date,
                sd.original_amount AS security_amount,
                CASE WHEN sd.status = 'DEDUCTED' THEN (sd.original_amount - sd.remaining_amount) ELSE 0 END AS amount_deducted,
                CASE WHEN sd.status = 'REFUNDED' THEN (sd.original_amount - sd.remaining_amount) ELSE 0 END AS amount_refunded,
                sd.remaining_amount AS current_balance,
                sd.status AS deposit_status
            FROM security_deposits sd
            JOIN students s ON sd.student_id = s.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['student_id'])) {
            $sql .= " AND (s.student_id_str LIKE ? OR s.full_name LIKE ?)";
            $params[] = "%{$filters['student_id']}%";
            $params[] = "%{$filters['student_id']}%";
        }

        if (!empty($filters['status'])) {
            $sql .= " AND sd.status = ?";
            $params[] = strtoupper($filters['status']);
        }

        $sql .= " ORDER BY sd.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
