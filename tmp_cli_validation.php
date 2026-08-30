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

use App\Core\Session;
use App\Services\StudentService;
use App\Services\FeeService;
use App\Core\Database;
use PDO;

Session::init();
Session::set('admin_id', 1);

$db = Database::getInstance()->getConnection();
$cnic = (string)(date('ymdHis') . '123');
$roomNo = 'CLI-ROOM-' . date('His');
$roomStmt = $db->prepare("SELECT id FROM rooms WHERE room_number = ? AND block = ? LIMIT 1");
$roomStmt->execute([$roomNo, 'CLI']);
$room = $roomStmt->fetch();
if (!$room) {
    $db->exec("INSERT INTO rooms (room_number, block, floor, room_type, total_beds, occupied_beds, monthly_fee, security_deposit, status) VALUES ('{$roomNo}', 'CLI', 1, 'Shared', 2, 0, 15000, 20000, 'Available')");
    $roomId = (int)$db->lastInsertId();
} else {
    $roomId = (int)$room['id'];
    $db->exec("UPDATE rooms SET occupied_beds = 0, status = 'Available' WHERE id = {$roomId}");
}

$studentSvc = new StudentService();
$result = $studentSvc->createStudent([
    'full_name' => 'CLI ADMISSION TEST',
    'cnic' => $cnic,
    'phone' => '03000000000',
    'email' => 'cliuser@example.com',
    'address' => 'CLI Test Address',
    'guardian_name' => 'CLI Guardian',
    'guardian_phone' => '03001111111',
    'guardian_cnic' => '1234567890123',
    'relation' => 'Father',
    'status' => 'Active',
    'room_allocation_enabled' => '1',
    'room_id' => $roomId,
    'bed_number' => 1,
    'joining_date' => '2026-08-30',
    'allocation_remarks' => 'CLI validation',
    'monthly_fee' => 15000,
    'security_deposit' => 20000,
    'discount' => 0,
    'initial_payment' => 25000,
    'payment_method' => 'Cash',
    'transaction_ref' => 'CLI-INIT-001',
    'first_billing_month' => 8,
    'first_billing_year' => 2026,
    'due_date' => '2026-09-05',
    'financial_setup_enabled' => '1'
]);

echo "ADMISSION_RESULT=" . json_encode($result) . PHP_EOL;

$studentRow = $db->query("SELECT * FROM students WHERE cnic = '{$cnic}' LIMIT 1")->fetch();
$allocRow = $db->query("SELECT * FROM room_allocations WHERE student_id = " . (int)$studentRow['id'] . " ORDER BY id DESC LIMIT 1")->fetch();
$feeRows = $db->query("SELECT * FROM fee_records WHERE student_id = " . (int)$studentRow['id'] . " ORDER BY id")->fetchAll();
$paymentRows = $db->query("SELECT * FROM fee_payments WHERE invoice_id IN (SELECT id FROM fee_records WHERE student_id = " . (int)$studentRow['id'] . ") ORDER BY id")->fetchAll();

$feeSvc = new FeeService();
$secondInvoice = $feeSvc->createFee([
    'student_id' => $studentRow['id'],
    'billing_month' => 9,
    'billing_year' => 2026,
    'amount' => 15000,
    'additional_charges' => 0,
    'discount' => 0,
    'paid_amount' => 0,
    'due_date' => '2026-09-25',
    'remarks' => 'CLI monthly billing',
    'payment_method' => 'Cash',
    'transaction_ref' => 'CLI-FEE-001',
    'invoice_number' => 'CLI-INV-001',
    'invoice_date' => '2026-08-30',
    'charge_type' => 'MONTHLY_FEE'
]);

echo "SECOND_INVOICE_RESULT=" . json_encode($secondInvoice) . PHP_EOL;

$partialPayment = $feeSvc->recordStudentPayment($studentRow['id'], [
    'paid_amount' => 5000,
    'payment_method' => 'Cash',
    'transaction_ref' => 'CLI-PAY-001',
    'payment_date' => '2026-08-31',
    'remarks' => 'CLI partial payment'
]);

echo "PARTIAL_PAYMENT_RESULT=" . json_encode($partialPayment) . PHP_EOL;

$studentOut = $db->query("SELECT COALESCE(SUM((amount + additional_charges - discount) - paid_amount),0) AS bal FROM fee_records WHERE student_id = " . (int)$studentRow['id'])->fetch();

echo "OUTSTANDING_BALANCE=" . json_encode($studentOut) . PHP_EOL;

echo json_encode([
    'student' => $studentRow,
    'allocation' => $allocRow,
    'fees' => $feeRows,
    'payments' => $paymentRows,
], JSON_PRETTY_PRINT) . PHP_EOL;
