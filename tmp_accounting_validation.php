<?php

define('APP_ROOT', __DIR__ . '/legacy_mvc');

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = APP_ROOT . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) require $file;
});

use App\Core\Database;
use App\Core\Session;
use App\Services\FeeService;
use App\Services\StudentService;

Session::init();
Session::set('admin_id', 1);

$db = Database::getInstance()->getConnection();
$tests = [];
$createdRoomIds = [];
$createdStudentIds = [];
$createdInvoiceIds = [];
$createdPaymentIds = [];

function recordResult(&$tests, $name, $status, $details) {
    $tests[] = ['name' => $name, 'status' => $status, 'details' => $details];
}

function cleanupAll($db, &$createdRoomIds, &$createdStudentIds, &$createdInvoiceIds, &$createdPaymentIds) {
    foreach ($createdPaymentIds as $id) {
        $db->exec("DELETE FROM payment_allocations WHERE payment_id = " . (int)$id);
        $db->exec("DELETE FROM fee_payments WHERE id = " . (int)$id);
    }
    foreach ($createdInvoiceIds as $id) {
        $db->exec("DELETE FROM fee_records WHERE id = " . (int)$id);
    }
    foreach ($createdStudentIds as $id) {
        $db->exec("DELETE FROM room_allocations WHERE student_id = " . (int)$id);
        $db->exec("DELETE FROM students WHERE id = " . (int)$id);
    }
    foreach ($createdRoomIds as $id) {
        $db->exec("DELETE FROM rooms WHERE id = " . (int)$id);
    }
    $createdRoomIds = [];
    $createdStudentIds = [];
    $createdInvoiceIds = [];
    $createdPaymentIds = [];
}

function uniqueSeed($prefix) {
    return $prefix . '-' . date('YmdHis') . '-' . random_int(1000, 9999);
}

try {
    $roomNo = uniqueSeed('ACC-ROOM');
    $db->exec("INSERT INTO rooms (room_number, block, floor, room_type, total_beds, occupied_beds, monthly_fee, security_deposit, status) VALUES ('{$roomNo}', 'A', '1', 'Shared', 2, 0, 15000, 20000, 'Available')");
    $roomId = (int)$db->lastInsertId();
    $createdRoomIds[] = $roomId;

    $studentService = new StudentService();
    $studentResult = $studentService->createStudent([
        'full_name' => 'Accounting Validation Student',
        'cnic' => '3520212345678',
        'phone' => '03000001001',
        'email' => 'acct.' . time() . '@example.test',
        'address' => 'Accounting test address',
        'guardian_name' => 'Guardian',
        'guardian_phone' => '03000001002',
        'guardian_cnic' => '2222222222222',
        'relation' => 'Father',
        'status' => 'Active',
        'room_allocation_enabled' => '1',
        'room_id' => $roomId,
        'allocation_type' => 'BED',
        'bed_number' => 1,
        'joining_date' => '2026-08-30',
        'monthly_fee' => 15000,
        'security_deposit' => 20000,
        'discount' => 0,
        'initial_payment' => 7000,
        'payment_method' => 'Cash',
        'transaction_ref' => 'ACC-INIT-001',
        'first_billing_month' => 8,
        'first_billing_year' => 2026,
        'due_date' => '2026-09-05',
        'financial_setup_enabled' => '1'
    ]);

    if (!($studentResult['success'] ?? false)) {
        throw new RuntimeException('Student creation failed: ' . json_encode($studentResult));
    }

    $studentId = (int)($studentResult['id'] ?? 0);
    $createdStudentIds[] = $studentId;
    $invoiceRow = $db->query("SELECT * FROM fee_records WHERE student_id = {$studentId} ORDER BY id DESC LIMIT 1")->fetch();
    if (!$invoiceRow) {
        throw new RuntimeException('Opening invoice not created for student.');
    }
    $createdInvoiceIds[] = (int)$invoiceRow['id'];

    $feeService = new FeeService();
    $secondMonth = $feeService->generateMonthlyInvoiceForStudent($studentId, [
        'billing_month' => 9,
        'billing_year' => 2026,
        'amount' => 15000,
        'discount' => 0,
        'additional_charges' => 0,
        'due_date' => '2026-10-05',
        'remarks' => 'September monthly rent'
    ]);

    if ($secondMonth['success'] ?? false) {
        $createdInvoiceIds[] = (int)$secondMonth['id'];
        recordResult($tests, 'New student monthly invoice', 'PASS', 'second-month invoice generated id=' . $secondMonth['id']);
    } else {
        recordResult($tests, 'New student monthly invoice', 'FAIL', $secondMonth['error'] ?? 'unknown');
    }

    $duplicateMonth = $feeService->generateMonthlyInvoiceForStudent($studentId, [
        'billing_month' => 9,
        'billing_year' => 2026,
        'amount' => 15000,
        'discount' => 0,
        'additional_charges' => 0,
        'due_date' => '2026-10-05',
        'remarks' => 'duplicate generation'
    ]);
    $duplicateOk = !($duplicateMonth['success'] ?? false) && stripos($duplicateMonth['error'] ?? '', 'already exists') !== false;
    recordResult($tests, 'Duplicate monthly invoice prevention', $duplicateOk ? 'PASS' : 'FAIL', json_encode($duplicateMonth));

    $paymentResult = $feeService->recordStudentPayment($studentId, [
        'paid_amount' => 5000,
        'payment_method' => 'Cash',
        'transaction_ref' => 'ACC-PAY-001',
        'payment_date' => '2026-09-01',
        'remarks' => 'Partial payment against oldest invoice'
    ]);
    if (($paymentResult['success'] ?? false)) {
        $createdPaymentIds[] = (int)($paymentResult['payment_id'] ?? 0);
        recordResult($tests, 'Partial payment / FIFO allocation', 'PASS', json_encode($paymentResult));
    } else {
        recordResult($tests, 'Partial payment / FIFO allocation', 'FAIL', json_encode($paymentResult));
    }

    $finalBalance = $db->query("SELECT COALESCE(SUM((amount + additional_charges - discount) - paid_amount), 0) AS balance FROM fee_records WHERE student_id = {$studentId}")->fetchColumn();
    $invoiceCount = $db->query("SELECT COUNT(*) FROM fee_records WHERE student_id = {$studentId}")->fetchColumn();
    $paymentCount = $db->query("SELECT COUNT(*) FROM fee_payments fp JOIN fee_records fr ON fr.id = fp.invoice_id WHERE fr.student_id = {$studentId}")->fetchColumn();

    $accountCheck = ($invoiceCount >= 2) && ($paymentCount >= 1) && ((float)$finalBalance > 0);
    recordResult($tests, 'Student account ledger integrity', $accountCheck ? 'PASS' : 'FAIL', 'invoice_count=' . $invoiceCount . ', payment_count=' . $paymentCount . ', balance=' . $finalBalance);

    echo PHP_EOL . 'SUMMARY=' . json_encode(['total' => count($tests), 'passed' => count(array_filter($tests, fn($t) => $t['status'] === 'PASS')), 'failed' => count(array_filter($tests, fn($t) => $t['status'] !== 'PASS'))]) . PHP_EOL;
    foreach ($tests as $test) {
        echo strtoupper($test['status']) . ' :: ' . $test['name'] . ' :: ' . $test['details'] . PHP_EOL;
    }

    cleanupAll($db, $createdRoomIds, $createdStudentIds, $createdInvoiceIds, $createdPaymentIds);
    exit(0);
} catch (Throwable $e) {
    cleanupAll($db, $createdRoomIds, $createdStudentIds, $createdInvoiceIds, $createdPaymentIds);
    echo 'ERROR :: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
