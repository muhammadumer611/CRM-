<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Session;
use App\Core\CSRF;
use App\Services\FeeService;
use App\Repositories\StudentRepository;

class FeeController {
    private $feeService;

    public function __construct() {
        Auth::check();
        $this->feeService = new FeeService();
    }

    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = $page > 0 ? $page : 1;
        $perPage = 15;
        
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'month' => $_GET['month'] ?? '',
            'year' => $_GET['year'] ?? ''
        ];

        $result = $this->feeService->getAllFees($filters, $page, $perPage);
        
        View::render('admin/fees/index', [
            'title' => 'Fee Management',
            'fees' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => $perPage,
            'filters' => $filters
        ], 'admin');
    }

    public function create() {
        $studentRepo = new StudentRepository();
        $students = $studentRepo->findAll(['status' => 'Active'], 1000, 0);

        View::render('admin/fees/create', [
            'title' => 'Create Fee',
            'students' => $students,
            'csrf_token' => CSRF::generateToken()
        ], 'admin');
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }
        
        CSRF::verifyToken($_POST['csrf_token'] ?? '');

        if (empty($_POST['student_id']) || empty($_POST['billing_month']) || empty($_POST['billing_year']) || empty($_POST['amount']) || empty($_POST['due_date'])) {
            Session::set('error', 'All required fields must be filled.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/fees/create');
            exit;
        }

        $result = $this->feeService->createFee($_POST);
        
        if ($result['success']) {
            Session::set('success', 'Fee record created successfully.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/fees');
        } else {
            Session::set('error', $result['error']);
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/fees/create');
        }
        exit;
    }

    public function pay($id) {
        $fee = $this->feeService->getFee($id);
        if (!$fee) {
            Session::set('error', 'Fee record not found.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/fees');
            exit;
        }

        View::render('admin/fees/pay', [
            'title' => 'Record Payment',
            'fee' => $fee,
            'csrf_token' => CSRF::generateToken()
        ], 'admin');
    }

    public function storePayment($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }
        
        CSRF::verifyToken($_POST['csrf_token'] ?? '');

        if (empty($_POST['paid_amount']) || empty($_POST['payment_method'])) {
            Session::set('error', 'Amount and payment method are required.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/fees/pay/' . $id);
            exit;
        }

        $result = $this->feeService->payFee($id, $_POST);
        
        if ($result['success']) {
            Session::set('success', 'Payment recorded successfully.');
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/fees/receipt/' . $result['payment_id']);
        } else {
            Session::set('error', $result['error']);
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/fees/pay/' . $id);
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
