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
        if (strncmp($prefix, $class, $len) !== 0) continue;
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
use App\Repositories\RoomRepository;
use App\Services\StudentService;
use Services\RoomAllocationService;

Session::init();
Session::set('admin_id', 1);

$db = Database::getInstance()->getConnection();

$db->exec("DELETE fee_payments FROM fee_payments JOIN fee_records ON fee_payments.invoice_id = fee_records.id WHERE fee_records.student_id IN (SELECT id FROM students WHERE student_id_str LIKE 'AVAILV-%' OR student_id_str LIKE 'VERIFY-%')");
$db->exec("DELETE FROM fee_records WHERE student_id IN (SELECT id FROM students WHERE student_id_str LIKE 'AVAILV-%' OR student_id_str LIKE 'VERIFY-%')");
$db->exec("DELETE FROM room_allocations WHERE student_id IN (SELECT id FROM students WHERE student_id_str LIKE 'AVAILV-%' OR student_id_str LIKE 'VERIFY-%')");
$db->exec("DELETE FROM room_allocations WHERE room_id IN (SELECT id FROM rooms WHERE room_number LIKE 'AVAILV-%' OR room_number LIKE 'VERIFY-%')");
$db->exec("DELETE FROM students WHERE student_id_str LIKE 'AVAILV-%' OR student_id_str LIKE 'VERIFY-%'");
$db->exec("DELETE FROM rooms WHERE room_number LIKE 'AVAILV-%' OR room_number LIKE 'VERIFY-%'");

$tests = [];
$createdRoomIds = [];
$createdStudentIds = [];
$createdAllocationIds = [];
$createdFeeRecordIds = [];
$createdPaymentIds = [];

function recordResult(&$tests, $name, $status, $detail) {
    $tests[] = ['name' => $name, 'status' => $status, 'detail' => $detail];
    echo strtoupper($status) . ' :: ' . $name . ' :: ' . $detail . PHP_EOL;
}

function cleanupAll($db, &$createdRoomIds, &$createdStudentIds, &$createdAllocationIds, &$createdFeeRecordIds, &$createdPaymentIds) {
    if (!empty($createdPaymentIds)) {
        $ids = implode(',', array_map('intval', $createdPaymentIds));
        if ($ids !== '') {
            $db->exec("DELETE FROM fee_payments WHERE id IN ($ids)");
        }
        $createdPaymentIds = [];
    }

    if (!empty($createdFeeRecordIds)) {
        $ids = implode(',', array_map('intval', $createdFeeRecordIds));
        if ($ids !== '') {
            $db->exec("DELETE FROM fee_records WHERE id IN ($ids)");
        }
        $createdFeeRecordIds = [];
    }

    if (!empty($createdAllocationIds)) {
        $ids = implode(',', array_map('intval', $createdAllocationIds));
        if ($ids !== '') {
            $db->exec("DELETE FROM room_allocations WHERE id IN ($ids)");
        }
        $createdAllocationIds = [];
    }

    if (!empty($createdStudentIds)) {
        $ids = implode(',', array_map('intval', $createdStudentIds));
        if ($ids !== '') {
            $db->exec("DELETE FROM room_allocations WHERE student_id IN ($ids)");
            $db->exec("DELETE FROM fee_payments WHERE invoice_id IN (SELECT id FROM fee_records WHERE student_id IN ($ids))");
            $db->exec("DELETE FROM fee_records WHERE student_id IN ($ids)");
            $db->exec("DELETE FROM students WHERE id IN ($ids)");
        }
        $createdStudentIds = [];
    }

    if (!empty($createdRoomIds)) {
        $ids = implode(',', array_map('intval', $createdRoomIds));
        if ($ids !== '') {
            $db->exec("DELETE FROM rooms WHERE id IN ($ids)");
        }
        $createdRoomIds = [];
    }
}

function trackSyntheticStudentLifecycle($db, &$createdStudentIds, &$createdFeeRecordIds, &$createdPaymentIds, $studentId) {
    $studentId = (int)$studentId;
    if ($studentId <= 0) {
        return;
    }
    if (!in_array($studentId, $createdStudentIds, true)) {
        $createdStudentIds[] = $studentId;
    }

    $stmtFee = $db->query("SELECT id FROM fee_records WHERE student_id = {$studentId}");
    foreach ($stmtFee->fetchAll(PDO::FETCH_COLUMN) as $feeId) {
        $feeId = (int)$feeId;
        if ($feeId > 0 && !in_array($feeId, $createdFeeRecordIds, true)) {
            $createdFeeRecordIds[] = $feeId;
        }
    }

    $stmtPayment = $db->query("SELECT fp.id FROM fee_payments fp JOIN fee_records fr ON fr.id = fp.invoice_id WHERE fr.student_id = {$studentId}");
    foreach ($stmtPayment->fetchAll(PDO::FETCH_COLUMN) as $paymentId) {
        $paymentId = (int)$paymentId;
        if ($paymentId > 0 && !in_array($paymentId, $createdPaymentIds, true)) {
            $createdPaymentIds[] = $paymentId;
        }
    }
}

function makeCnic($seed) {
    $suffix = str_pad((string)random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT);
    $base = '90000000000' . $suffix . '-' . $seed;
    $clean = preg_replace('/[^0-9]/', '', $base);
    return substr($clean, -13);
}

function makeUniqueEmail($seed) {
    return 'verify.' . $seed . '.' . time() . '.' . random_int(1000, 9999) . '@example.test';
}

function insertRoom($db, &$createdRoomIds, $roomNumber, $block, $capacity = 4, $occupiedBeds = 0, $status = 'Available') {
    $db->exec("INSERT INTO rooms (room_number, block, floor, room_type, total_beds, occupied_beds, monthly_fee, security_deposit, status)
        VALUES ('{$roomNumber}', '{$block}', '1', 'Shared', {$capacity}, {$occupiedBeds}, 15000, 20000, '{$status}')");
    $id = (int)$db->lastInsertId();
    $createdRoomIds[] = $id;
    return $id;
}

function insertStudent($db, &$createdStudentIds, $seed, $name, $cnic, $email = null) {
    $studentIdStr = 'VERIFY-' . strtoupper($seed) . '-' . time() . '-' . random_int(1000, 9999);
    $email = $email ?: makeUniqueEmail($seed);
    $db->exec("INSERT INTO students (student_id_str, full_name, cnic, phone, email, address, guardian_name, guardian_phone, guardian_cnic, relation, status)
        VALUES ('{$studentIdStr}', '{$name}', '{$cnic}', '03000000000', '{$email}', 'Test Address', 'Guardian', '03000000001', '2222222222222', 'Father', 'Active')");
    $id = (int)$db->lastInsertId();
    $createdStudentIds[] = $id;
    return $id;
}

function insertActiveAllocation($db, &$createdAllocationIds, $studentId, $roomId, $bedNumber, $remarks = 'TEST') {
    $db->exec("INSERT INTO room_allocations (student_id, room_id, bed_number, joining_date, status, remarks)
        VALUES ({$studentId}, {$roomId}, {$bedNumber}, '2026-08-30', 'Active', '{$remarks}')");
    $id = (int)$db->lastInsertId();
    $createdAllocationIds[] = $id;
    return $id;
}

function directRoomState($db, $roomId) {
    $row = $db->query("SELECT id, room_number, total_beds, occupied_beds, status FROM rooms WHERE id = {$roomId} LIMIT 1")->fetch();
    $activeAlloc = $db->query("SELECT room_id, bed_number, status FROM room_allocations WHERE room_id = {$roomId} AND status = 'Active' ORDER BY bed_number ASC")->fetchAll();
    return ['room' => $row, 'active_allocations' => $activeAlloc];
}

$repo = new RoomRepository();
$roomService = new RoomAllocationService();
$studentService = new StudentService();

try {
    $roomBedOk = insertRoom($db, $createdRoomIds, 'AVAILV-BEDOK-' . date('His'), 'A', 4, 0, 'Available');
    $roomBedFull = insertRoom($db, $createdRoomIds, 'AVAILV-BEDFULL-' . date('His'), 'A', 4, 4, 'Occupied');

    $bedRooms = $repo->findAvailableRoomsForAdmission('BED');
    $bedIds = array_map(fn($row) => (int)$row['id'], $bedRooms);
    $bedOkMatch = in_array($roomBedOk, $bedIds, true);
    $bedFullExcluded = !in_array($roomBedFull, $bedIds, true);

    if ($bedOkMatch && $bedFullExcluded) {
        recordResult($tests, 'BED room filtering', 'PASS', 'room with available bed returned and fully occupied room excluded; visible=' . json_encode($bedIds));
    } else {
        recordResult($tests, 'BED room filtering', 'FAIL', 'wrong BED room visibility; visible=' . json_encode($bedIds));
    }

    $roomFullEmpty = insertRoom($db, $createdRoomIds, 'AVAILV-FULL-EMPTY-' . date('His'), 'B', 4, 0, 'Available');
    $roomFullPartial = insertRoom($db, $createdRoomIds, 'AVAILV-FULL-PARTIAL-' . date('His'), 'B', 4, 1, 'Partially Occupied');
    $roomFullOccupied = insertRoom($db, $createdRoomIds, 'AVAILV-FULL-OCC-' . date('His'), 'B', 4, 4, 'Occupied');

    $fullRooms = $repo->findAvailableRoomsForAdmission('FULL_ROOM');
    $fullIds = array_map(fn($row) => (int)$row['id'], $fullRooms);
    $fullEmptyMatch = in_array($roomFullEmpty, $fullIds, true);
    $fullPartialExcluded = !in_array($roomFullPartial, $fullIds, true);
    $fullOccupiedExcluded = !in_array($roomFullOccupied, $fullIds, true);

    if ($fullEmptyMatch && $fullPartialExcluded && $fullOccupiedExcluded) {
        recordResult($tests, 'FULL_ROOM room filtering', 'PASS', 'empty room returned, partial & full rooms excluded; visible=' . json_encode($fullIds));
    } else {
        recordResult($tests, 'FULL_ROOM room filtering', 'FAIL', 'wrong FULL_ROOM visibility; visible=' . json_encode($fullIds));
    }

    $roomBedList = insertRoom($db, $createdRoomIds, 'AVAILV-BEDLIST-' . date('His'), 'C', 4, 0, 'Available');
    $studentBed1 = insertStudent($db, $createdStudentIds, 'BED1', 'Bed Occupied 1', makeCnic('1001'));
    $studentBed3 = insertStudent($db, $createdStudentIds, 'BED3', 'Bed Occupied 3', makeCnic('1002'));
    insertActiveAllocation($db, $createdAllocationIds, $studentBed1, $roomBedList, 1, 'occupied-bed-1');
    insertActiveAllocation($db, $createdAllocationIds, $studentBed3, $roomBedList, 3, 'occupied-bed-3');
    $db->exec("UPDATE rooms SET occupied_beds = 2, status = 'Partially Occupied' WHERE id = {$roomBedList}");

    $availableBeds = $repo->getAvailableBedsForRoom($roomBedList);
    $expectedBeds = [2, 4];
    $bedsMatch = json_encode($availableBeds) === json_encode($expectedBeds);

    if ($bedsMatch) {
        recordResult($tests, 'Available bed calculation', 'PASS', 'occupied beds excluded; returned=' . json_encode($availableBeds));
    } else {
        recordResult($tests, 'Available bed calculation', 'FAIL', 'wrong available beds; returned=' . json_encode($availableBeds));
    }

    $invalidRoomId = 999999;
    $invalidRoomResult = $studentService->createStudent([
        'full_name' => 'Invalid Room Test',
        'cnic' => makeCnic('2001'),
        'phone' => '03000000007',
        'email' => 'invalidroom@example.com',
        'address' => 'Test address',
        'guardian_name' => 'Guardian',
        'guardian_phone' => '03000000008',
        'guardian_cnic' => '3333333333333',
        'relation' => 'Father',
        'status' => 'Active',
        'room_allocation_enabled' => '1',
        'room_id' => $invalidRoomId,
        'allocation_type' => 'BED',
        'bed_number' => 1,
        'joining_date' => '2026-08-30',
        'monthly_fee' => 15000,
        'security_deposit' => 0,
        'discount' => 0,
        'initial_payment' => 0,
        'payment_method' => 'Cash',
        'transaction_ref' => 'AVAIL-INVALID-ROOM',
        'first_billing_month' => 8,
        'first_billing_year' => 2026,
        'due_date' => '2026-09-05',
        'financial_setup_enabled' => '1'
    ]);
    $invalidRoomPass = !($invalidRoomResult['success'] ?? false);
    if ($invalidRoomPass) {
        recordResult($tests, 'Server-side invalid room validation', 'PASS', 'invalid room rejected: ' . ($invalidRoomResult['error'] ?? 'unknown error'));
    } else {
        recordResult($tests, 'Server-side invalid room validation', 'FAIL', 'invalid room unexpectedly succeeded: ' . json_encode($invalidRoomResult));
    }

    $occupiedBedRoom = insertRoom($db, $createdRoomIds, 'AVAILV-OCCBED-' . date('His'), 'D', 4, 2, 'Partially Occupied');
    $occupiedStudent = insertStudent($db, $createdStudentIds, 'OCCBED', 'Occupied Bed Student', makeCnic('2002'));
    insertActiveAllocation($db, $createdAllocationIds, $occupiedStudent, $occupiedBedRoom, 2, 'preoccupied-bed-2');
    $occupiedBedResult = $studentService->createStudent([
        'full_name' => 'Occupied Bed Attempt',
        'cnic' => makeCnic('2003'),
        'phone' => '03000000009',
        'email' => 'occupiedbed@example.com',
        'address' => 'Test address',
        'guardian_name' => 'Guardian',
        'guardian_phone' => '03000000010',
        'guardian_cnic' => '4444444444444',
        'relation' => 'Mother',
        'status' => 'Active',
        'room_allocation_enabled' => '1',
        'room_id' => $occupiedBedRoom,
        'allocation_type' => 'BED',
        'bed_number' => 2,
        'joining_date' => '2026-08-30',
        'monthly_fee' => 15000,
        'security_deposit' => 0,
        'discount' => 0,
        'initial_payment' => 0,
        'payment_method' => 'Cash',
        'transaction_ref' => 'AVAIL-OCC-BED',
        'first_billing_month' => 8,
        'first_billing_year' => 2026,
        'due_date' => '2026-09-05',
        'financial_setup_enabled' => '1'
    ]);
    $occupiedBedPass = !($occupiedBedResult['success'] ?? false);
    if ($occupiedBedPass) {
        recordResult($tests, 'Server-side occupied bed validation', 'PASS', 'occupied bed rejected: ' . ($occupiedBedResult['error'] ?? 'unknown error'));
    } else {
        recordResult($tests, 'Server-side occupied bed validation', 'FAIL', 'occupied bed allocation unexpectedly succeeded: ' . json_encode($occupiedBedResult));
    }

    $partialFullRoom = insertRoom($db, $createdRoomIds, 'AVAILV-PARTFULL-' . date('His'), 'E', 4, 1, 'Partially Occupied');
    $partialFullStudent = insertStudent($db, $createdStudentIds, 'PARTFULL', 'Partial Full Room Student', makeCnic('2004'));
    insertActiveAllocation($db, $createdAllocationIds, $partialFullStudent, $partialFullRoom, 1, 'partial-full-room');
    $fullRoomPartialResult = $studentService->createStudent([
        'full_name' => 'Full Room Partial Reject',
        'cnic' => makeCnic('2005'),
        'phone' => '03000000011',
        'email' => 'fullpartial@example.com',
        'address' => 'Test address',
        'guardian_name' => 'Guardian',
        'guardian_phone' => '03000000012',
        'guardian_cnic' => '5555555555555',
        'relation' => 'Brother',
        'status' => 'Active',
        'room_allocation_enabled' => '1',
        'room_id' => $partialFullRoom,
        'allocation_type' => 'FULL_ROOM',
        'bed_number' => 0,
        'joining_date' => '2026-08-30',
        'monthly_fee' => 15000,
        'security_deposit' => 0,
        'discount' => 0,
        'initial_payment' => 0,
        'payment_method' => 'Cash',
        'transaction_ref' => 'AVAIL-FULL-PARTIAL',
        'first_billing_month' => 8,
        'first_billing_year' => 2026,
        'due_date' => '2026-09-05',
        'financial_setup_enabled' => '1'
    ]);
    $fullRoomPartialPass = !($fullRoomPartialResult['success'] ?? false);
    if ($fullRoomPartialPass) {
        recordResult($tests, 'Server-side FULL_ROOM validation', 'PASS', 'FULL_ROOM rejected for partially occupied room: ' . ($fullRoomPartialResult['error'] ?? 'unknown error'));
    } else {
        recordResult($tests, 'Server-side FULL_ROOM validation', 'FAIL', 'FULL_ROOM unexpectedly succeeded: ' . json_encode($fullRoomPartialResult));
    }

    $fullRoomLockRoom = insertRoom($db, $createdRoomIds, 'AVAILV-FULLLOCK-' . date('His'), 'F', 4, 0, 'Available');
    $fullRoomResultA = $studentService->createStudent([
        'full_name' => 'Full Room A',
        'cnic' => makeCnic('3002'),
        'phone' => '03000000013',
        'email' => 'fullrooma@example.com',
        'address' => 'Full room test',
        'guardian_name' => 'Guardian',
        'guardian_phone' => '03000000014',
        'guardian_cnic' => '6666666666666',
        'relation' => 'Father',
        'status' => 'Active',
        'room_allocation_enabled' => '1',
        'room_id' => $fullRoomLockRoom,
        'allocation_type' => 'FULL_ROOM',
        'bed_number' => 0,
        'joining_date' => '2026-08-30',
        'monthly_fee' => 15000,
        'security_deposit' => 0,
        'discount' => 0,
        'initial_payment' => 0,
        'payment_method' => 'Cash',
        'transaction_ref' => 'AVAIL-FULL-LOCK-A',
        'first_billing_month' => 8,
        'first_billing_year' => 2026,
        'due_date' => '2026-09-05',
        'financial_setup_enabled' => '1'
    ]);

    if (!($fullRoomResultA['success'] ?? false)) {
        recordResult($tests, 'FULL_ROOM behavior', 'FAIL', 'could not create initial FULL_ROOM allocation: ' . json_encode($fullRoomResultA));
    } else {
        $allocIdA = (int)($fullRoomResultA['allocation_id'] ?? 0);
        $createdAllocationIds[] = $allocIdA;
        trackSyntheticStudentLifecycle($db, $createdStudentIds, $createdFeeRecordIds, $createdPaymentIds, $fullRoomResultA['id'] ?? 0);
        $fullRoomBlocked = $studentService->createStudent([
            'full_name' => 'Full Room Blocked B',
            'cnic' => makeCnic('3003'),
            'phone' => '03000000015',
            'email' => 'fullblocked@example.com',
            'address' => 'Blocked room',
            'guardian_name' => 'Guardian',
            'guardian_phone' => '03000000016',
            'guardian_cnic' => '7777777777777',
            'relation' => 'Mother',
            'status' => 'Active',
            'room_allocation_enabled' => '1',
            'room_id' => $fullRoomLockRoom,
            'allocation_type' => 'BED',
            'bed_number' => 1,
            'joining_date' => '2026-08-30',
            'monthly_fee' => 15000,
            'security_deposit' => 0,
            'discount' => 0,
            'initial_payment' => 0,
            'payment_method' => 'Cash',
            'transaction_ref' => 'AVAIL-FULL-LOCK-B',
            'first_billing_month' => 8,
            'first_billing_year' => 2026,
            'due_date' => '2026-09-05',
            'financial_setup_enabled' => '1'
        ]);

        $fullRoomBlockedPass = !($fullRoomBlocked['success'] ?? false);
        $afterClose = $roomService->closeAllocation($allocIdA, '2026-08-31', 'TEST closure');
        $stateAfterClose = directRoomState($db, $fullRoomLockRoom);
        $roomAvailableAgain = (($stateAfterClose['room']['occupied_beds'] ?? 0) === 0) && (($stateAfterClose['room']['status'] ?? '') === 'Available');

        if ($fullRoomBlockedPass && $roomAvailableAgain) {
            recordResult($tests, 'FULL_ROOM behavior', 'PASS', 'room remains blocked while active, then becomes available again after close. state_after=' . json_encode($stateAfterClose['room']) . ' blocked_result=' . json_encode($fullRoomBlocked));
        } else {
            recordResult($tests, 'FULL_ROOM behavior', 'FAIL', 'room did not block or reopen correctly; state_after=' . json_encode($stateAfterClose['room']) . ' blocked_result=' . json_encode($fullRoomBlocked));
        }
    }

    $concurrencyRoom = insertRoom($db, $createdRoomIds, 'AVAILV-CONCUR-' . date('His'), 'G', 4, 0, 'Available');

    $resultA = $studentService->createStudent([
        'full_name' => 'Concurrency A',
        'cnic' => makeCnic('4003'),
        'phone' => '03000000017',
        'email' => makeUniqueEmail('concurrency-a'),
        'address' => 'Concurrency address',
        'guardian_name' => 'Guardian',
        'guardian_phone' => '03000000018',
        'guardian_cnic' => makeCnic('8888'),
        'relation' => 'Father',
        'status' => 'Active',
        'room_allocation_enabled' => '1',
        'room_id' => $concurrencyRoom,
        'allocation_type' => 'BED',
        'bed_number' => 1,
        'joining_date' => '2026-08-30',
        'monthly_fee' => 15000,
        'security_deposit' => 0,
        'discount' => 0,
        'initial_payment' => 0,
        'payment_method' => 'Cash',
        'transaction_ref' => 'AVAIL-CONC-A',
        'first_billing_month' => 8,
        'first_billing_year' => 2026,
        'due_date' => '2026-09-05',
        'financial_setup_enabled' => '1'
    ]);
    if (($resultA['success'] ?? false)) {
        trackSyntheticStudentLifecycle($db, $createdStudentIds, $createdFeeRecordIds, $createdPaymentIds, $resultA['id'] ?? 0);
    }
    $resultB = $studentService->createStudent([
        'full_name' => 'Concurrency B',
        'cnic' => makeCnic('4004'),
        'phone' => '03000000019',
        'email' => makeUniqueEmail('concurrency-b'),
        'address' => 'Concurrency address',
        'guardian_name' => 'Guardian',
        'guardian_phone' => '03000000020',
        'guardian_cnic' => makeCnic('9999'),
        'relation' => 'Father',
        'status' => 'Active',
        'room_allocation_enabled' => '1',
        'room_id' => $concurrencyRoom,
        'allocation_type' => 'BED',
        'bed_number' => 1,
        'joining_date' => '2026-08-30',
        'monthly_fee' => 15000,
        'security_deposit' => 0,
        'discount' => 0,
        'initial_payment' => 0,
        'payment_method' => 'Cash',
        'transaction_ref' => 'AVAIL-CONC-B',
        'first_billing_month' => 8,
        'first_billing_year' => 2026,
        'due_date' => '2026-09-05',
        'financial_setup_enabled' => '1'
    ]);
    if (($resultB['success'] ?? false)) {
        trackSyntheticStudentLifecycle($db, $createdStudentIds, $createdFeeRecordIds, $createdPaymentIds, $resultB['id'] ?? 0);
    }

    $concurrencyPass = ($resultA['success'] ?? false) && !($resultB['success'] ?? false) && (stripos($resultB['error'] ?? '', 'no longer available') !== false || stripos($resultB['error'] ?? '', 'already been allocated') !== false || stripos($resultB['error'] ?? '', 'already occupied') !== false);
    if ($concurrencyPass) {
        recordResult($tests, 'Concurrency protection', 'PASS', 'first allocation succeeded, second rejected: ' . json_encode($resultB));
    } else {
        recordResult($tests, 'Concurrency protection', 'FAIL', 'concurrency behavior not enforced; resultA=' . json_encode($resultA) . ' resultB=' . json_encode($resultB));
    }

    $failCount = 0;
    foreach ($tests as $test) {
        if ($test['status'] !== 'PASS') {
            $failCount++;
        }
    }

    cleanupAll($db, $createdRoomIds, $createdStudentIds, $createdAllocationIds, $createdFeeRecordIds, $createdPaymentIds);

    echo PHP_EOL . 'SUMMARY=' . json_encode(['total' => count($tests), 'passed' => count(array_filter($tests, fn($t) => $t['status'] === 'PASS')), 'failed' => $failCount]) . PHP_EOL;
    exit($failCount > 0 ? 1 : 0);
} catch (Throwable $e) {
    cleanupAll($db, $createdRoomIds, $createdStudentIds, $createdAllocationIds, $createdFeeRecordIds, $createdPaymentIds);
    recordResult($tests, 'SCRIPT', 'FAIL', 'uncaught exception: ' . $e->getMessage());
    echo PHP_EOL . 'SUMMARY=' . json_encode(['total' => count($tests), 'passed' => 0, 'failed' => 1]) . PHP_EOL;
    exit(1);
}
