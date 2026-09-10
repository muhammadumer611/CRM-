<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Session;
use App\Core\CSRF;
use App\Services\FeeService;

class FeeController {
    private $feeService;

    public function __construct() {
        Auth::check();
        $this->feeService = new FeeService();
    }

    public function index() {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 15;
        
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'month'  => $_GET['month']  ?? '',
            'year'   => $_GET['year']   ?? ''
        ];

        $result   = $this->feeService->getAllFees($filters, $page, $perPage);
        $summary  = $this->feeService->getFinancialSummary();
        
        View::render('admin/fees/index', [
            'title'   => 'Fee Management',
            'fees'    => $result['data'],
            'total'   => $result['total'],
            'page'    => $page,
            'perPage' => $perPage,
            'filters' => $filters,
            'summary' => $summary
        ], 'admin');
    }

    public function pay($id) {
        $fee = $this->feeService->getFee($id);
        if (!$fee) {
            Session::set('error', 'Fee record not found.');
            $config = require APP_ROOT . '/config/app.php';
            header('Location: ' . $config['base_url'] . '/fees');
            exit;
        }

        $payments = $this->feeService->getFeePayments($id);

        View::render('admin/fees/pay', [
            'title'      => 'Record Payment',
            'fee'        => $fee,
            'payments'   => $payments,
            'csrf_token' => CSRF::generateToken()
        ], 'admin');
    }

    public function collection() {
        $filters = [
            'date_filter' => $_GET['date_filter'] ?? 'this_month',
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? '',
            'payment_method' => $_GET['payment_method'] ?? '',
        ];

        $payments = $this->feeService->getCollectionRows($filters, 50, 0);
        $summary = $this->feeService->getCollectionSummary($filters);

        View::render('admin/fees/collection', [
            'title' => 'Total Collection',
            'filters' => $filters,
            'payments' => $payments,
            'summary' => $summary,
        ], 'admin');
    }

    public function paid() {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'room'   => $_GET['room'] ?? '',
            'month'  => $_GET['month'] ?? '',
            'year'   => $_GET['year'] ?? '',
        ];

        $overview = $this->feeService->getPaidFeeStudentsOverview($filters);

        View::render('admin/fees/paid', [
            'title'    => 'Paid Fee',
            'filters'  => $filters,
            'students' => $overview['students'],
            'summary'  => $overview['summary'],
        ], 'admin');
    }

    public function pending() {
        $filters = [
            'search' => $_GET['search'] ?? '',
            'room'   => $_GET['room'] ?? '',
            'month'  => $_GET['month'] ?? '',
            'year'   => $_GET['year'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];

        $overview = $this->feeService->getPendingFeeStudentsOverview($filters);

        View::render('admin/fees/pending', [
            'title'    => 'Pending Fee',
            'filters'  => $filters,
            'students' => $overview['students'],
            'summary'  => $overview['summary'],
        ], 'admin');
    }

    public function pendingDetail($studentId) {
        $details = $this->feeService->getStudentPendingFeeDetails((int)$studentId);
        if (!$details) {
            Session::set('error', 'Student not found or has no pending fee records.');
            $config = require APP_ROOT . '/config/app.php';
            header('Location: ' . $config['base_url'] . '/fees/pending');
            exit;
        }

        View::render('admin/fees/pending-detail', [
            'title'    => 'Pending Fee Details — ' . htmlspecialchars($details['student']['full_name']),
            'student'  => $details['student'],
            'invoices' => $details['pending_invoices'],
            'payments' => $details['payment_history'],
            'summary'  => $details['summary'],
        ], 'admin');
    }

    public function securityDeposits() {
        $filters = [
            'student_id' => $_GET['student_id'] ?? '',
            'status' => $_GET['status'] ?? '',
        ];

        $summary = $this->feeService->getSecurityDepositSummary();
        $deposits = $this->feeService->getSecurityDeposits($filters);

        View::render('admin/fees/security-deposits', [
            'title' => 'Security Deposits',
            'filters' => $filters,
            'summary' => $summary,
            'deposits' => $deposits,
        ], 'admin');
    }

    public function storePayment($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }
        
        CSRF::verifyToken($_POST['csrf_token'] ?? '');
        $config = require APP_ROOT . '/config/app.php';

        if (empty($_POST['paid_amount']) || empty($_POST['payment_method'])) {
            Session::set('error', 'Amount and payment method are required.');
            header('Location: ' . $config['base_url'] . '/fees/pay/' . $id);
            exit;
        }

        $result = $this->feeService->payFee($id, $_POST);
        
        if ($result['success']) {
            $msg = 'Payment recorded successfully.';
            if (!empty($result['receipt_number'])) {
                $msg .= ' Receipt: ' . $result['receipt_number'];
            }
            Session::set('success', $msg);
            if (!empty($result['payment_id'])) {
                header('Location: ' . $config['base_url'] . '/fees/receipt/' . $result['payment_id']);
            } else {
                header('Location: ' . $config['base_url'] . '/fees/pay/' . $id);
            }
        } else {
            Session::set('error', $result['error']);
            header('Location: ' . $config['base_url'] . '/fees/pay/' . $id);
        }
        exit;
    }

    public function receipt($paymentId) {
        $db = \App\Core\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT fp.*, fr.invoice_number, fr.student_id, fr.amount AS invoice_amount, fr.additional_charges, fr.discount, fr.paid_amount AS invoice_paid, fr.billing_month, fr.billing_year, fr.due_date, s.full_name, s.student_id_str, a.username AS admin_username FROM fee_payments fp JOIN fee_records fr ON fr.id = fp.invoice_id JOIN students s ON s.id = fr.student_id LEFT JOIN admins a ON a.id = fp.received_by_admin WHERE fp.id = ?");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();

        if (!$payment) {
            Session::set('error', 'Receipt not found.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/fees');
            exit;
        }

        $totalInvoice = (float)$payment['invoice_amount'] + (float)$payment['additional_charges'] - (float)$payment['discount'];
        $allocations = $this->feeService->getPaymentAllocations($paymentId);
        $previousOutstanding = max(0, $totalInvoice - (float)$payment['invoice_paid']);
        $remainingOutstanding = max(0, $totalInvoice - ((float)$payment['invoice_paid'] + (float)$payment['amount']));

        View::render('admin/fees/receipt', [
            'title' => 'Payment Receipt',
            'payment' => $payment,
            'allocations' => $allocations,
            'total_invoice' => $totalInvoice,
            'previous_outstanding' => $previousOutstanding,
            'remaining_outstanding' => $remainingOutstanding
        ], 'admin');
    }
}
