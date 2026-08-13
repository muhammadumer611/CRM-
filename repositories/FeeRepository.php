<?php
namespace Repositories;

use Core\Database;
use PDO;

class FeeRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO fee_records (
                invoice_number, student_id, billing_month, billing_year, invoice_date,
                amount, additional_charges, discount, due_date, paid_amount, status, remarks
            ) VALUES (
                :invoice_number, :student_id, :billing_month, :billing_year, :invoice_date,
                :amount, :additional_charges, :discount, :due_date, :paid_amount, :status, :remarks
            )
        ");
        $stmt->execute([
            'invoice_number' => $data['invoice_number'],
            'student_id' => $data['student_id'],
            'billing_month' => $data['billing_month'],
            'billing_year' => $data['billing_year'],
            'invoice_date' => $data['invoice_date'] ?? date('Y-m-d'),
            'amount' => $data['amount'],
            'additional_charges' => $data['additional_charges'] ?? 0,
            'discount' => $data['discount'] ?? 0,
            'due_date' => $data['due_date'],
            'paid_amount' => $data['paid_amount'] ?? 0,
            'status' => $data['status'],
            'remarks' => $data['remarks'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT f.*, s.full_name, s.student_id_str, r.room_number, r.block,
                   (f.amount + f.additional_charges - f.discount) AS total_amount
            FROM fee_records f
            JOIN students s ON f.student_id = s.id
            LEFT JOIN room_allocations ra ON ra.student_id = s.id AND ra.status = 'Active'
            LEFT JOIN rooms r ON r.id = ra.room_id
            WHERE f.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function findByStudentAndBillingPeriod($studentId, $month, $year) {
        $stmt = $this->db->prepare("
            SELECT * FROM fee_records 
            WHERE student_id = :student_id AND billing_month = :month AND billing_year = :year
        ");
        $stmt->execute(['student_id' => $studentId, 'month' => $month, 'year' => $year]);
        return $stmt->fetch();
    }

    public function findByInvoiceNumber($invoiceNumber) {
        $stmt = $this->db->prepare("SELECT * FROM fee_records WHERE invoice_number = :invoice_number");
        $stmt->execute(['invoice_number' => $invoiceNumber]);
        return $stmt->fetch();
    }

    public function search($filters) {
        $query = "SELECT f.*, s.full_name, s.student_id_str,
                  (f.amount + f.additional_charges - f.discount) AS total_amount
                  FROM fee_records f 
                  JOIN students s ON f.student_id = s.id 
                  WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $query .= " AND f.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['student_id'])) {
            $query .= " AND f.student_id = :student_id";
            $params['student_id'] = $filters['student_id'];
        }
        if (!empty($filters['student'])) {
            $query .= " AND (s.full_name LIKE :search_name OR s.student_id_str LIKE :search_student_id OR f.invoice_number LIKE :search_invoice)";
            $searchTerm = '%' . trim($filters['student']) . '%';
            $params['search_name'] = $searchTerm;
            $params['search_student_id'] = $searchTerm;
            $params['search_invoice'] = $searchTerm;
        }
        if (!empty($filters['month'])) {
            $query .= " AND f.billing_month = :month";
            $params['month'] = $filters['month'];
        }
        if (!empty($filters['year'])) {
            $query .= " AND f.billing_year = :year";
            $params['year'] = $filters['year'];
        }
        if (!empty($filters['invoice_number'])) {
            $query .= " AND f.invoice_number = :invoice_number";
            $params['invoice_number'] = $filters['invoice_number'];
        }
        if (!empty($filters['start_date'])) {
            $query .= " AND f.invoice_date >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $query .= " AND f.invoice_date <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }

        $query .= " ORDER BY f.billing_year DESC, f.billing_month DESC, f.id DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getStatistics() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_fee_records,
                COALESCE(SUM(amount + additional_charges - discount), 0) as total_invoiced,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM((amount + additional_charges - discount) - paid_amount), 0) as total_pending,
                SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) as paid_records,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_records,
                SUM(CASE WHEN status = 'Partial' THEN 1 ELSE 0 END) as partial_records,
                SUM(CASE WHEN status = 'Overdue' THEN 1 ELSE 0 END) as overdue_records
            FROM fee_records
        ");
        return $stmt->fetch();
    }

    public function getStudentFeeSummary($studentId) {
        $stmt = $this->db->prepare("
            SELECT 
                s.id as student_id,
                s.full_name as student_name,
                COALESCE(SUM(f.amount + f.additional_charges - f.discount), 0) as total_fee_amount,
                COALESCE(SUM(f.paid_amount), 0) as total_paid,
                COALESCE(SUM((f.amount + f.additional_charges - f.discount) - f.paid_amount), 0) as total_outstanding,
                SUM(CASE WHEN f.status = 'Paid' THEN 1 ELSE 0 END) as paid_records,
                SUM(CASE WHEN f.status = 'Pending' THEN 1 ELSE 0 END) as pending_records,
                SUM(CASE WHEN f.status = 'Partial' THEN 1 ELSE 0 END) as partial_records,
                SUM(CASE WHEN f.status = 'Overdue' THEN 1 ELSE 0 END) as overdue_records,
                MAX(f.payment_date) as latest_payment_date
            FROM students s
            LEFT JOIN fee_records f ON s.id = f.student_id
            WHERE s.id = :id
            GROUP BY s.id, s.full_name
        ");
        $stmt->execute(['id' => $studentId]);
        return $stmt->fetch();
    }

    public function getPaymentHistory($invoiceId) {
        $stmt = $this->db->prepare("
            SELECT p.*, a.username as admin_username
            FROM fee_payments p
            LEFT JOIN admins a ON a.id = p.received_by_admin
            WHERE p.invoice_id = :invoice_id
            ORDER BY p.payment_date DESC, p.id DESC
        ");
        $stmt->execute(['invoice_id' => $invoiceId]);
        return $stmt->fetchAll();
    }

    public function getRecentPayments($limit = 5) {
        $stmt = $this->db->prepare("
            SELECT p.*, f.invoice_number, s.full_name, s.student_id_str, a.username AS admin_username
            FROM fee_payments p
            JOIN fee_records f ON f.id = p.invoice_id
            JOIN students s ON s.id = f.student_id
            LEFT JOIN admins a ON a.id = p.received_by_admin
            ORDER BY p.payment_date DESC, p.id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRecentInvoices($limit = 5) {
        $stmt = $this->db->prepare("
            SELECT f.*, s.full_name, s.student_id_str,
                   (f.amount + f.additional_charges - f.discount) AS total_amount
            FROM fee_records f
            JOIN students s ON s.id = f.student_id
            ORDER BY f.invoice_date DESC, f.id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getOverdueInvoices($limit = 10) {
        $stmt = $this->db->prepare("
            SELECT f.*, s.full_name, s.student_id_str,
                   (f.amount + f.additional_charges - f.discount) AS total_amount
            FROM fee_records f
            JOIN students s ON s.id = f.student_id
            WHERE f.status = 'Overdue' OR (f.status != 'Paid' AND f.due_date < CURDATE())
            ORDER BY f.due_date ASC, f.id ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getDashboardSummary() {
        $stmt = $this->db->query("
            SELECT
                COALESCE(SUM(amount + additional_charges - discount), 0) AS total_invoiced,
                COALESCE(SUM(paid_amount), 0) AS total_collected,
                COALESCE(SUM(CASE WHEN status IN ('Pending', 'Partial', 'Overdue') THEN (amount + additional_charges - discount) - paid_amount ELSE 0 END), 0) AS total_pending,
                COALESCE(SUM(CASE WHEN status = 'Overdue' THEN (amount + additional_charges - discount) - paid_amount ELSE 0 END), 0) AS total_overdue
            FROM fee_records
        ");
        return $stmt->fetch();
    }

    public function createPayment($invoiceId, $data, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("
            INSERT INTO fee_payments (invoice_id, amount, payment_date, payment_method, transaction_ref, remarks, received_by_admin)
            VALUES (:invoice_id, :amount, :payment_date, :payment_method, :transaction_ref, :remarks, :received_by_admin)
        ");
        return $stmt->execute([
            'invoice_id' => $invoiceId,
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'transaction_ref' => $data['transaction_ref'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'received_by_admin' => $data['received_by_admin'] ?? null
        ]);
    }

    public function updateInvoicePayment($invoiceId, $paidAmount, $status, $paymentMethod, $transactionRef, $paymentDate, $pdo = null) {
        $db = $pdo ?? $this->db;
        $stmt = $db->prepare("
            UPDATE fee_records
            SET paid_amount = :paid_amount,
                status = :status,
                payment_date = :payment_date,
                payment_method = :payment_method,
                transaction_ref = :transaction_ref
            WHERE id = :id
        ");
        return $stmt->execute([
            'paid_amount' => $paidAmount,
            'status' => $status,
            'payment_date' => $paymentDate,
            'payment_method' => $paymentMethod,
            'transaction_ref' => $transactionRef,
            'id' => $invoiceId
        ]);
    }
}
