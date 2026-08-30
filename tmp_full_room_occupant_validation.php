<?php

define('APP_ROOT', __DIR__ . '/legacy_mvc');

spl_autoload_register(function ($class) {
    $prefixes = [
        'App\\' => APP_ROOT . '/src/',
        'Services\\' => __DIR__ . '/services/',
        'Repositories\\' => __DIR__ . '/repositories/',
        'Controllers\\' => __DIR__ . '/controllers/',
        'Core\\' => __DIR__ . '/core/',
        'Utils\\' => __DIR__ . '/utils/'
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($class, $prefix, $len) !== 0) continue;
        $relative = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

use App\Core\Database;
use App\Core\Session;
use App\Services\StudentService;

Session::init();
Session::set('admin_id', 1);

$db = Database::getInstance()->getConnection();
$studentService = new StudentService();

$db->exec("DELETE FROM room_occupants WHERE room_allocation_id IN (SELECT id FROM room_allocations WHERE student_id IN (SELECT id FROM students WHERE student_id_str LIKE 'FULLROOM-%'))");
$db->exec("DELETE FROM room_allocations WHERE student_id IN (SELECT id FROM students WHERE student_id_str LIKE 'FULLROOM-%')");
$db->exec("DELETE FROM fee_payments WHERE invoice_id IN (SELECT id FROM fee_records WHERE student_id IN (SELECT id FROM students WHERE student_id_str LIKE 'FULLROOM-%'))");
$db->exec("DELETE FROM fee_records WHERE student_id IN (SELECT id FROM students WHERE student_id_str LIKE 'FULLROOM-%')");
$db->exec("DELETE FROM students WHERE student_id_str LIKE 'FULLROOM-%'");
$db->exec("DELETE FROM rooms WHERE room_number LIKE 'FULLROOM-%'");

function makeCnic($seed) {
    $suffix = str_pad((string)random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT);
    $base = '90000000000' . $suffix . '-' . $seed;
    $clean = preg_replace('/[^0-9]/', '', $base);
    return substr($clean, -13);
}

$roomNumber = 'FULLROOM-TEST-' . time();
$db->exec("INSERT INTO rooms (room_number, block, floor, room_type, total_beds, occupied_beds, monthly_fee, security_deposit, status) VALUES ('{$roomNumber}', 'B', '1', 'Shared', 4, 0, 15000, 20000, 'Available')");
$roomId = (int)$db->lastInsertId();

$primaryResult = $studentService->createStudent([
    'full_name' => 'Full Room Primary',
    'cnic' => makeCnic('1001'),
    'phone' => '03000000001',
    'email' => 'fullprimary@example.com',
    'address' => 'Test address',
    'guardian_name' => 'Guardian',
    'guardian_phone' => '03000000002',
    'guardian_cnic' => '3333333333333',
    'relation' => 'Father',
    'status' => 'Active',
    'room_allocation_enabled' => '1',
    'room_id' => $roomId,
    'allocation_type' => 'FULL_ROOM',
    'bed_number' => 0,
    'joining_date' => '2026-08-30',
    'monthly_fee' => 15000,
    'security_deposit' => 0,
    'discount' => 0,
    'initial_payment' => 0,
    'payment_method' => 'Cash',
    'transaction_ref' => 'FULLROOM-PRIMARY',
    'first_billing_month' => 8,
    'first_billing_year' => 2026,
    'due_date' => '2026-09-05',
    'financial_setup_enabled' => '1',
    'room_occupants' => [
        ['full_name' => 'Occupant One', 'cnic' => makeCnic('1011'), 'phone' => '03000000003', 'relation' => 'Brother'],
        ['full_name' => 'Occupant Two', 'cnic' => makeCnic('1012'), 'phone' => '03000000004', 'relation' => 'Friend'],
    ],
]);

if (!($primaryResult['success'] ?? false)) {
    throw new RuntimeException('Primary full-room admission failed: ' . json_encode($primaryResult));
}

$allocationId = (int)($primaryResult['allocation_id'] ?? 0);
$studentId = (int)($primaryResult['id'] ?? 0);
$occupantCount = (int)$db->query("SELECT COUNT(*) FROM room_occupants WHERE room_allocation_id = {$allocationId}")->fetchColumn();
$invoiceCount = (int)$db->query("SELECT COUNT(*) FROM fee_records WHERE student_id = {$studentId}")->fetchColumn();

if ($occupantCount !== 2) {
    throw new RuntimeException('Expected 2 saved room occupants, found ' . $occupantCount);
}

if ($invoiceCount < 1) {
    throw new RuntimeException('Primary full-room tenant did not receive the normal monthly invoice.');
}

$duplicateResult = $studentService->createStudent([
    'full_name' => 'Duplicate Occupant Guard',
    'cnic' => makeCnic('1002'),
    'phone' => '03000000005',
    'email' => 'fullroomduplicate@example.com',
    'address' => 'Duplicate check',
    'guardian_name' => 'Guardian',
    'guardian_phone' => '03000000006',
    'guardian_cnic' => '4444444444444',
    'relation' => 'Father',
    'status' => 'Active',
    'room_allocation_enabled' => '1',
    'room_id' => $roomId,
    'allocation_type' => 'FULL_ROOM',
    'bed_number' => 0,
    'joining_date' => '2026-08-30',
    'monthly_fee' => 15000,
    'security_deposit' => 0,
    'discount' => 0,
    'initial_payment' => 0,
    'payment_method' => 'Cash',
    'transaction_ref' => 'FULLROOM-DUPLICATE',
    'first_billing_month' => 8,
    'first_billing_year' => 2026,
    'due_date' => '2026-09-05',
    'financial_setup_enabled' => '1',
    'room_occupants' => [
        ['full_name' => 'Dup Person', 'cnic' => '1111111111111', 'phone' => '03000000007', 'relation' => 'Sibling'],
        ['full_name' => 'Another Dup', 'cnic' => '1111111111111', 'phone' => '03000000008', 'relation' => 'Friend'],
    ],
]);

if (($duplicateResult['success'] ?? false)) {
    throw new RuntimeException('Duplicate occupant CNIC unexpectedly passed validation.');
}

echo "PASS :: full-room occupant validation succeeded; occupants={$occupantCount}; invoices={$invoiceCount};" . PHP_EOL;
