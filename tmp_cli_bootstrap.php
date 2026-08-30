<?php

define('APP_ROOT', 'd:/m.umer/CRM-/legacy_mvc');

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = APP_ROOT . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Database;
use App\Core\Session;
use App\Services\StudentService;
use App\Services\FeeService;

Session::init();
Session::set('admin_id', 1);

$db = Database::getInstance()->getConnection();
$cnic = (string) random_int(1000000000000, 9999999999999);
$roomNo = 'CLI-ROOM-' . date('His');

$roomChk = $db->prepare("SELECT id FROM rooms WHERE room_number = ? AND block = ? LIMIT 1");
$roomChk->execute([$roomNo, 'CLI']);
$roomRow = $roomChk->fetch();
if (!$roomRow) {
    $db->exec("INSERT INTO rooms (room_number, block, floor, room_type, total_beds, occupied_beds, monthly_fee, security_deposit, status) VALUES ('{$roomNo}', 'CLI', 1, 'Shared', 2, 0, 15000, 20000, 'Available')");
    $roomId = (int) $db->lastInsertId();
} else {
    $roomId = (int) $roomRow['id'];
    $db->exec("UPDATE rooms SET occupied_beds = 0, status = 'Available' WHERE id = {$roomId}");
}

$studentSvc = new StudentService();
$admission = $studentSvc->createStudent([
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

echo 'ADMISSION_RESULT:' . json_encode($admission) . PHP_EOL;

$student = $db->query("SELECT * FROM students WHERE cnic = '{$cnic}' LIMIT 1")->fetch();
$studentId = (int) $student['id'];
$alloc = $db->query("SELECT * FROM room_allocations WHERE student_id = {$studentId} ORDER BY id DESC LIMIT 1")->fetch();
$fees = $db->query("SELECT * FROM fee_records WHERE student_id = {$studentId} ORDER BY id")->fetchAll();
$payments = $db->query("SELECT * FROM fee_payments WHERE invoice_id IN (SELECT id FROM fee_records WHERE student_id = {$studentId}) ORDER BY id")->fetchAll();
$history = $db->query("SELECT * FROM student_history WHERE student_id = {$studentId} ORDER BY id")->fetchAll();

echo 'STUDENT:' . json_encode($student) . PHP_EOL;
echo 'ALLOCATION:' . json_encode($alloc) . PHP_EOL;
echo 'FEES:' . json_encode($fees) . PHP_EOL;
echo 'PAYMENTS:' . json_encode($payments) . PHP_EOL;
echo 'HISTORY:' . json_encode($history) . PHP_EOL;

$feeSvc = new FeeService();
$invoice = $feeSvc->createFee([
    'student_id' => $studentId,
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

echo 'INVOICE_RESULT:' . json_encode($invoice) . PHP_EOL;

$payment = $feeSvc->recordStudentPayment($studentId, [
    'paid_amount' => 5000,
    'payment_method' => 'Cash',
    'transaction_ref' => 'CLI-PAY-001',
    'payment_date' => '2026-08-31',
    'remarks' => 'CLI partial payment'
]);

echo 'PAYMENT_RESULT:' . json_encode($payment) . PHP_EOL;

$outstanding = $db->query("SELECT COALESCE(SUM((amount + additional_charges - discount) - paid_amount), 0) AS outstanding FROM fee_records WHERE student_id = {$studentId}")->fetch();
echo 'OUTSTANDING:' . json_encode($outstanding) . PHP_EOL;
