<?php

define('APP_ROOT', 'd:/m.umer/CRM-');
require 'd:/m.umer/CRM-/legacy_mvc/public/index.php';

use App\Core\Session;
use App\Services\StudentService;
use PDO;

Session::init();
Session::set('admin_id', 1);

$db = new PDO('mysql:host=localhost;dbname=hms_db;charset=utf8mb4', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$roomNumber = 'TEST-ADMISSION-001';
$block = 'TEST-BLOCK';
$room = $db->query("SELECT * FROM rooms WHERE room_number = '{$roomNumber}' AND block = '{$block}' LIMIT 1")->fetch();
if (!$room) {
    $db->exec("INSERT INTO rooms (room_number, block, floor, room_type, total_beds, occupied_beds, monthly_fee, security_deposit, status) VALUES ('{$roomNumber}', '{$block}', '1', 'Shared', 2, 0, 15000.00, 20000.00, 'Available')");
    $roomId = (int)$db->lastInsertId();
} else {
    $roomId = (int)$room['id'];
    $db->exec("UPDATE rooms SET occupied_beds = 0, status = 'Available' WHERE id = {$roomId}");
}

$studentService = new StudentService();
$result = $studentService->createStudent([
    'full_name' => 'TEST ADMISSION FLOW',
    'cnic' => '7777777777777',
    'phone' => '03001234567',
    'email' => 'testadmission@example.com',
    'address' => 'Test Address',
    'guardian_name' => 'Guardian Name',
    'guardian_phone' => '03009999999',
    'guardian_cnic' => '1111111111111',
    'relation' => 'Father',
    'status' => 'Active',
    'room_allocation_enabled' => '1',
    'room_id' => $roomId,
    'bed_number' => 1,
    'joining_date' => '2026-08-30',
    'allocation_remarks' => 'Integration test allocation',
    'monthly_fee' => 15000,
    'security_deposit' => 20000,
    'discount' => 0,
    'initial_payment' => 25000,
    'payment_method' => 'Cash',
    'transaction_ref' => 'TEST-INIT-001',
    'first_billing_month' => 8,
    'first_billing_year' => 2026,
    'due_date' => '2026-09-05',
    'financial_setup_enabled' => '1'
]);

echo json_encode(['room_id' => $roomId, 'result' => $result], JSON_PRETTY_PRINT) . PHP_EOL;

$studentRow = $db->query("SELECT * FROM students WHERE cnic='7777777777777'")->fetch();
$allocRow = $db->query("SELECT * FROM room_allocations WHERE student_id=" . (int)($studentRow['id'] ?? 0) . " ORDER BY id DESC LIMIT 1")->fetch();
$feeRows = $db->query("SELECT * FROM fee_records WHERE student_id=" . (int)($studentRow['id'] ?? 0) . " ORDER BY id")->fetchAll();
$paymentRows = $db->query("SELECT * FROM fee_payments WHERE invoice_id IN (SELECT id FROM fee_records WHERE student_id=" . (int)($studentRow['id'] ?? 0) . ") ORDER BY id")->fetchAll();
$historyRows = $db->query("SELECT * FROM student_history WHERE student_id=" . (int)($studentRow['id'] ?? 0) . " ORDER BY id")->fetchAll();
$logRows = $db->query("SELECT * FROM system_logs WHERE action IN ('ADMISSION_COMPLETED','ROOM_CREATED') ORDER BY id DESC LIMIT 20")->fetchAll();

echo json_encode([
    'student' => $studentRow,
    'allocation' => $allocRow,
    'fees' => $feeRows,
    'payments' => $paymentRows,
    'history' => $historyRows,
    'logs' => $logRows,
], JSON_PRETTY_PRINT) . PHP_EOL;
