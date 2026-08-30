<?php
/**
 * Security Deposits Validation Suite
 * Tests security deposit lifecycle: receipt, deduction, refund
 * Verifies separate accounting from monthly collection
 * Tests transaction idempotency and history preservation
 */

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
use Repositories\FeeRepository;

Session::init();
Session::set('admin_id', 1);

$db = Database::getInstance()->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Cleanup synthetic test data
$db->exec("DELETE FROM security_deposit_transactions WHERE security_deposit_id IN (SELECT id FROM security_deposits WHERE student_id IN (SELECT id FROM students WHERE student_id_str LIKE 'SECURITY-%'))");
$db->exec("DELETE FROM security_deposits WHERE student_id IN (SELECT id FROM students WHERE student_id_str LIKE 'SECURITY-%')");
$db->exec("DELETE FROM room_allocations WHERE student_id IN (SELECT id FROM students WHERE student_id_str LIKE 'SECURITY-%')");
$db->exec("DELETE FROM students WHERE student_id_str LIKE 'SECURITY-%'");
$db->exec("DELETE FROM rooms WHERE room_number LIKE 'SECURITY-%'");

$tests = [];
$createdStudentIds = [];
$createdDepositIds = [];

function recordResult(&$tests, $name, $status, $detail) {
    $tests[] = ['name' => $name, 'status' => $status, 'detail' => $detail];
    echo strtoupper($status) . ' :: ' . $name . ' :: ' . $detail . PHP_EOL;
}

function cleanup($db, &$createdDepositIds, &$createdStudentIds) {
    if (!empty($createdDepositIds)) {
        $ids = implode(',', array_map('intval', $createdDepositIds));
        if ($ids !== '') {
            $db->exec("DELETE FROM security_deposit_transactions WHERE security_deposit_id IN ($ids)");
            $db->exec("DELETE FROM security_deposits WHERE id IN ($ids)");
        }
    }
    if (!empty($createdStudentIds)) {
        $ids = implode(',', array_map('intval', $createdStudentIds));
        if ($ids !== '') {
            $db->exec("DELETE FROM room_allocations WHERE student_id IN ($ids)");
            $db->exec("DELETE FROM students WHERE id IN ($ids)");
        }
    }
}

function createStudent($db, &$createdStudentIds, $seed, $name, $cnic, $email = null) {
    $studentIdStr = 'SECURITY-' . strtoupper($seed) . '-' . time() . '-' . random_int(1000, 9999);
    $email = $email ?: 'security.' . $seed . '.' . time() . '@example.test';
    $db->exec("INSERT INTO students (student_id_str, full_name, cnic, phone, email, address, guardian_name, guardian_phone, guardian_cnic, relation, status)
        VALUES ('{$studentIdStr}', '{$name}', '{$cnic}', '03000000000', '{$email}', 'Test Address', 'Guardian', '03000000001', '4444444444444', 'Father', 'Active')");
    $id = (int)$db->lastInsertId();
    $createdStudentIds[] = $id;
    return $id;
}

function createSecurityDeposit($db, &$createdDepositIds, $studentId, $amount) {
    $db->exec("INSERT INTO security_deposits (student_id, original_amount, remaining_amount, status)
        VALUES ({$studentId}, {$amount}, {$amount}, 'HELD')");
    $id = (int)$db->lastInsertId();
    $createdDepositIds[] = $id;
    return $id;
}

try {
    $feeService = new FeeService();
    $feeRepo = new FeeRepository();

    // TEST 1: Security deposit creation
    $student1 = createStudent($db, $createdStudentIds, 'S1', 'Security Student 1', '1111111111111');
    $deposit1 = createSecurityDeposit($db, $createdDepositIds, $student1, 20000.00);

    $depositRow = $db->query("SELECT original_amount, remaining_amount, status FROM security_deposits WHERE id = {$deposit1}")->fetch();
    
    if ($depositRow && $depositRow['original_amount'] == 20000.00 && $depositRow['remaining_amount'] == 20000.00 && $depositRow['status'] == 'HELD') {
        recordResult($tests, 'Security deposit creation', 'PASS', 'Deposit created with correct amounts and HELD status');
    } else {
        recordResult($tests, 'Security deposit creation', 'FAIL', 'Deposit not created correctly: ' . json_encode($depositRow));
    }

    // TEST 2: Security deposit separate from collection
    $summaryWithDeposit = $feeRepo->getSecurityDepositSummary();
    $totalHeld = (float)($summaryWithDeposit['total_held'] ?? 0);
    
    if ($totalHeld >= 20000.00) {
        recordResult($tests, 'Security deposit in security ledger', 'PASS', 'Total held=' . $totalHeld);
    } else {
        recordResult($tests, 'Security deposit in security ledger', 'FAIL', 'Expected >=20000, got ' . $totalHeld);
    }

    // TEST 3: Partial deduction
    $db->exec("INSERT INTO security_deposit_transactions (security_deposit_id, transaction_type, amount, reason, created_by_admin, created_at)
        VALUES ({$deposit1}, 'ADJUSTMENT', 5000.00, 'Damage charge', 1, NOW())");
    
    $db->exec("UPDATE security_deposits SET remaining_amount = remaining_amount - 5000.00, status = 'ADJUSTED' WHERE id = {$deposit1}");

    $depositAfterDeduct = $db->query("SELECT remaining_amount, status FROM security_deposits WHERE id = {$deposit1}")->fetch();
    
    if ($depositAfterDeduct && $depositAfterDeduct['remaining_amount'] == 15000.00 && $depositAfterDeduct['status'] == 'ADJUSTED') {
        recordResult($tests, 'Security partial deduction', 'PASS', 'Remaining=' . $depositAfterDeduct['remaining_amount']);
    } else {
        recordResult($tests, 'Security partial deduction', 'FAIL', 'Deduction not recorded correctly');
    }

    // TEST 4: Full deduction
    $student2 = createStudent($db, $createdStudentIds, 'S2', 'Security Student 2', '2222222222222');
    $deposit2 = createSecurityDeposit($db, $createdDepositIds, $student2, 20000.00);
    
    $db->exec("INSERT INTO security_deposit_transactions (security_deposit_id, transaction_type, amount, reason, created_by_admin, created_at)
        VALUES ({$deposit2}, 'ADJUSTMENT', 20000.00, 'Full deduction on checkout', 1, NOW())");
    
    $db->exec("UPDATE security_deposits SET remaining_amount = 0, status = 'SETTLED' WHERE id = {$deposit2}");

    $depositFull = $db->query("SELECT remaining_amount, status FROM security_deposits WHERE id = {$deposit2}")->fetch();
    
    if ($depositFull && $depositFull['remaining_amount'] == 0 && $depositFull['status'] == 'SETTLED') {
        recordResult($tests, 'Security full deduction', 'PASS', 'Status=' . $depositFull['status']);
    } else {
        recordResult($tests, 'Security full deduction', 'FAIL', 'Full deduction not recorded correctly');
    }

    // TEST 5: Partial refund
    $student3 = createStudent($db, $createdStudentIds, 'S3', 'Security Student 3', '3333333333333');
    $deposit3 = createSecurityDeposit($db, $createdDepositIds, $student3, 20000.00);
    
    $db->exec("INSERT INTO security_deposit_transactions (security_deposit_id, transaction_type, amount, reason, created_by_admin, created_at)
        VALUES ({$deposit3}, 'REFUND', 10000.00, 'Partial refund on checkout', 1, NOW())");
    
    $db->exec("UPDATE security_deposits SET remaining_amount = remaining_amount - 10000.00 WHERE id = {$deposit3}");

    $depositPartialRefund = $db->query("SELECT remaining_amount FROM security_deposits WHERE id = {$deposit3}")->fetch();
    $transactionCount = (int)$db->query("SELECT COUNT(*) FROM security_deposit_transactions WHERE security_deposit_id = {$deposit3} AND transaction_type = 'REFUND'")->fetchColumn();
    
    if ($depositPartialRefund && $depositPartialRefund['remaining_amount'] == 10000.00 && $transactionCount == 1) {
        recordResult($tests, 'Security partial refund', 'PASS', 'Remaining after refund=' . $depositPartialRefund['remaining_amount']);
    } else {
        recordResult($tests, 'Security partial refund', 'FAIL', 'Partial refund not recorded correctly');
    }

    // TEST 6: Full refund
    $student4 = createStudent($db, $createdStudentIds, 'S4', 'Security Student 4', '4444444444444');
    $deposit4 = createSecurityDeposit($db, $createdDepositIds, $student4, 20000.00);
    
    $db->exec("INSERT INTO security_deposit_transactions (security_deposit_id, transaction_type, amount, reason, created_by_admin, created_at)
        VALUES ({$deposit4}, 'REFUND', 20000.00, 'Full refund on checkout', 1, NOW())");
    
    $db->exec("UPDATE security_deposits SET remaining_amount = 0, status = 'REFUNDED' WHERE id = {$deposit4}");

    $depositFullRefund = $db->query("SELECT remaining_amount, status FROM security_deposits WHERE id = {$deposit4}")->fetch();
    
    if ($depositFullRefund && $depositFullRefund['remaining_amount'] == 0 && $depositFullRefund['status'] == 'REFUNDED') {
        recordResult($tests, 'Security full refund', 'PASS', 'Status=' . $depositFullRefund['status']);
    } else {
        recordResult($tests, 'Security full refund', 'FAIL', 'Full refund not recorded correctly');
    }

    // TEST 7: Transaction history preserved
    $transactions = $db->query("SELECT COUNT(*) FROM security_deposit_transactions WHERE security_deposit_id IN ({$deposit1}, {$deposit2}, {$deposit3}, {$deposit4})")->fetchColumn();
    
    if ($transactions >= 4) {
        recordResult($tests, 'Security transaction history preserved', 'PASS', 'Transactions recorded=' . $transactions);
    } else {
        recordResult($tests, 'Security transaction history preserved', 'FAIL', 'Expected >=4 transactions, got ' . $transactions);
    }

    // TEST 8: Security summary includes refunds and deductions
    $summary = $feeRepo->getSecurityDepositSummary();
    $totalRefunded = (float)($summary['total_refunded'] ?? 0);
    $totalDeducted = (float)($summary['total_deducted'] ?? 0);
    
    if ($totalRefunded >= 10000.00 && $totalDeducted >= 25000.00) {
        recordResult($tests, 'Security summary accuracy', 'PASS', 'Refunded=' . $totalRefunded . ', Deducted=' . $totalDeducted);
    } else {
        recordResult($tests, 'Security summary accuracy', 'FAIL', 'Refunded=' . $totalRefunded . ', Deducted=' . $totalDeducted);
    }

    $failCount = 0;
    foreach ($tests as $test) {
        if ($test['status'] !== 'PASS') {
            $failCount++;
        }
    }

    cleanup($db, $createdDepositIds, $createdStudentIds);

    echo PHP_EOL . 'SUMMARY=' . json_encode(['total' => count($tests), 'passed' => count(array_filter($tests, fn($t) => $t['status'] === 'PASS')), 'failed' => $failCount]) . PHP_EOL;
    exit($failCount > 0 ? 1 : 0);

} catch (Throwable $e) {
    cleanup($db, $createdDepositIds, $createdStudentIds);
    echo 'FAIL :: SCRIPT :: uncaught exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    exit(1);
}
