<?php

define('APP_ROOT', __DIR__ . '/legacy_mvc');

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = APP_ROOT . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
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
    echo strtoupper($status) . ' :: ' . $name . ' :: ' . $details . PHP_EOL;
}

function cleanupAll($db, &$createdRoomIds, &$createdStudentIds, &$createdInvoiceIds, &$createdPaymentIds) {
    foreach ($createdPaymentIds as $id) {
        $db->exec("DELETE FROM payment_allocations WHERE payment_id = " . (int)$id);
        $db->exec("DELETE FROM fee_payments WHERE id = " . (int)$id);
    }
    foreach ($createdInvoiceIds as $id) {
        $db->exec("DELETE FROM late_fees WHERE invoice_id = " . (int)$id);
        $db->exec("DELETE FROM discounts WHERE invoice_id = " . (int)$id);
        $db->exec("DELETE FROM fee_records WHERE id = " . (int)$id);
    }
    foreach ($createdStudentIds as $id) {
        $db->exec("DELETE FROM room_allocations WHERE student_id = " . (int)$id);
        $db->exec("DELETE FROM additional_charges WHERE student_id = " . (int)$id);
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

function makeCnic($seed) {
    $base = '90000000000' . str_pad((string)$seed, 7, '0', STR_PAD_LEFT);
    return substr(preg_replace('/[^0-9]/', '', $base), -13);
}

try {
    $roomNumber = uniqueSeed('PH1-ROOM');
    $roomInsert = $db->prepare("INSERT INTO rooms (room_number, block, floor, room_type, total_beds, occupied_beds, monthly_fee, security_deposit, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $roomInsert->execute([$roomNumber, 'A', '1', 'Shared', 2, 0, 20000, 25000, 'Available']);
    $roomId = (int)$db->lastInsertId();
    $createdRoomIds[] = $roomId;

    $studentService = new StudentService();
    $studentUid = uniqueSeed('PH1-STD');
    $studentResult = $studentService->createStudent([
        'full_name' => 'Phase 1 Validation Student',
        'cnic' => makeCnic((int)date('His')),
        'phone' => '03000000011',
        'email' => strtolower($studentUid) . '@example.test',
        'address' => 'Phase 1 validation address',
        'guardian_name' => 'Guardian',
        'guardian_phone' => '03000000012',
        'guardian_cnic' => '3344556677889',
        'relation' => 'Father',
        'status' => 'Active',
        'room_allocation_enabled' => '1',
        'room_id' => $roomId,
        'allocation_type' => 'BED',
        'bed_number' => 1,
        'joining_date' => '2026-08-30',
        'monthly_fee' => 20000,
        'security_deposit' => 25000,
        'discount' => 0,
        'initial_payment' => 0,
        'payment_method' => 'Cash',
        'transaction_ref' => 'PH1-INIT',
        'first_billing_month' => 8,
        'first_billing_year' => 2026,
        'due_date' => '2026-09-05',
        'financial_setup_enabled' => '1'
    ]);

    if (!($studentResult['success'] ?? false)) {
        throw new RuntimeException('Student creation failed: ' . json_encode($studentResult));
    }

    $studentId = (int)$studentResult['id'];
    $createdStudentIds[] = $studentId;

    $invoiceId = (int)$db->query("SELECT id FROM fee_records WHERE student_id = {$studentId} ORDER BY id DESC LIMIT 1")->fetchColumn();
    $createdInvoiceIds[] = $invoiceId;

    $db->prepare("UPDATE fee_records SET due_date = '2026-08-20', status = 'Pending' WHERE id = ?")->execute([$invoiceId]);

    $feeService = new FeeService();
    $lateResult = $feeService->applyLateFeesToOverdueInvoices($studentId, 5, 500.0, 'FIXED', 'PH1 late fee test');
    $latePass = ($lateResult['success'] ?? false) && (!empty($lateResult['applied']));
    recordResult($tests, 'Late fee application on overdue invoice', $latePass ? 'PASS' : 'FAIL', json_encode($lateResult));

    $chargeResult = $feeService->addAdditionalChargeToStudent($studentId, [
        'charge_type' => 'ELECTRICITY',
        'description' => 'Phase 1 electricity charge',
        'amount' => 1500,
        'charge_date' => '2026-08-24',
        'reference_number' => 'PH1-EL-001',
        'status' => 'Pending'
    ]);
    recordResult($tests, 'Additional charge creation', ($chargeResult['success'] ?? false) ? 'PASS' : 'FAIL', json_encode($chargeResult));

    $discountResult = $feeService->applyInvoiceDiscount($invoiceId, [
        'discount_type' => 'FIXED',
        'discount_value' => 1000,
        'reason' => 'Phase 1 audit discount test'
    ]);
    recordResult($tests, 'Fixed discount application', ($discountResult['success'] ?? false) ? 'PASS' : 'FAIL', json_encode($discountResult));

    $statement = $feeService->getStudentStatement($studentId);
    $statementPass = !empty($statement['rows']) && isset($statement['totals']['balance']);
    recordResult($tests, 'Student statement with running balance', $statementPass ? 'PASS' : 'FAIL', json_encode(['rows' => count($statement['rows']), 'balance' => $statement['totals']['balance'] ?? 0]));

    $dbLateCheck = $db->query("SELECT COUNT(*) FROM late_fees WHERE invoice_id = {$invoiceId}")->fetchColumn();
    $dbChargeCheck = $db->query("SELECT COUNT(*) FROM additional_charges WHERE student_id = {$studentId}")->fetchColumn();
    $dbDiscountCheck = $db->query("SELECT COUNT(*) FROM discounts WHERE invoice_id = {$invoiceId}")->fetchColumn();
    $dbBalanceCheck = $db->query("SELECT (amount + additional_charges - discount) - paid_amount AS balance FROM fee_records WHERE id = {$invoiceId}")->fetchColumn();

    echo PHP_EOL . 'SUMMARY=' . json_encode([
        'total' => count($tests),
        'passed' => count(array_filter($tests, fn($t) => $t['status'] === 'PASS')),
        'failed' => count(array_filter($tests, fn($t) => $t['status'] !== 'PASS')),
        'late_fees' => (int)$dbLateCheck,
        'additional_charges' => (int)$dbChargeCheck,
        'discounts' => (int)$dbDiscountCheck,
        'invoice_balance' => (float)$dbBalanceCheck,
    ]) . PHP_EOL;

    cleanupAll($db, $createdRoomIds, $createdStudentIds, $createdInvoiceIds, $createdPaymentIds);
    exit(0);
} catch (Throwable $e) {
    cleanupAll($db, $createdRoomIds, $createdStudentIds, $createdInvoiceIds, $createdPaymentIds);
    echo 'ERROR :: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
