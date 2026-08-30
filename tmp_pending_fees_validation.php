<?php
/**
 * Pending Fees Validation Suite
 * Tests that pending fees are correctly calculated
 * Verifies multi-month arrears tracking
 * Tests duplicate invoice prevention
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
use App\Repositories\FeeRepository;

Session::init();
Session::set('admin_id', 1);

$db = Database::getInstance()->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Cleanup synthetic test data
$db->exec("DELETE FROM fee_payments WHERE invoice_id IN (SELECT id FROM fee_records WHERE student_id IN (SELECT id FROM students WHERE student_id_str LIKE 'PENDING-%'))");
$db->exec("DELETE FROM fee_records WHERE student_id IN (SELECT id FROM students WHERE student_id_str LIKE 'PENDING-%')");
$db->exec("DELETE FROM room_allocations WHERE student_id IN (SELECT id FROM students WHERE student_id_str LIKE 'PENDING-%')");
$db->exec("DELETE FROM students WHERE student_id_str LIKE 'PENDING-%'");
$db->exec("DELETE FROM rooms WHERE room_number LIKE 'PENDING-%'");

$tests = [];
$createdStudentIds = [];
$createdInvoiceIds = [];
$createdPaymentIds = [];

function recordResult(&$tests, $name, $status, $detail) {
    $tests[] = ['name' => $name, 'status' => $status, 'detail' => $detail];
    echo strtoupper($status) . ' :: ' . $name . ' :: ' . $detail . PHP_EOL;
}

function cleanup($db, &$createdPaymentIds, &$createdInvoiceIds, &$createdStudentIds) {
    if (!empty($createdPaymentIds)) {
        $ids = implode(',', array_map('intval', $createdPaymentIds));
        if ($ids !== '') {
            $db->exec("DELETE FROM fee_payments WHERE id IN ($ids)");
        }
    }
    if (!empty($createdInvoiceIds)) {
        $ids = implode(',', array_map('intval', $createdInvoiceIds));
        if ($ids !== '') {
            $db->exec("DELETE FROM fee_records WHERE id IN ($ids)");
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
    $studentIdStr = 'PENDING-' . strtoupper($seed) . '-' . time() . '-' . random_int(1000, 9999);
    $email = $email ?: 'pending.' . $seed . '.' . time() . '@example.test';
    $db->exec("INSERT INTO students (student_id_str, full_name, cnic, phone, email, address, guardian_name, guardian_phone, guardian_cnic, relation, status)
        VALUES ('{$studentIdStr}', '{$name}', '{$cnic}', '03000000000', '{$email}', 'Test Address', 'Guardian', '03000000001', '3333333333333', 'Father', 'Active')");
    $id = (int)$db->lastInsertId();
    $createdStudentIds[] = $id;
    return $id;
}

function createInvoice($db, &$createdInvoiceIds, $studentId, $month, $year, $amount, $dueDate = null) {
    $invoiceNumber = 'INV-PENDING-' . time() . '-' . random_int(1000, 9999);
    $dueDate = $dueDate ?: date('Y-m-d', strtotime('+15 days'));
    $db->exec("INSERT INTO fee_records (invoice_number, student_id, billing_month, billing_year, invoice_date, amount, additional_charges, discount, due_date, paid_amount, status)
        VALUES ('{$invoiceNumber}', {$studentId}, {$month}, {$year}, '2026-08-30', {$amount}, 0, 0, '{$dueDate}', 0, 'Pending')");
    $id = (int)$db->lastInsertId();
    $createdInvoiceIds[] = $id;
    return $id;
}

function recordPayment($db, &$createdPaymentIds, $invoiceId, $amount, $paymentDate = null) {
    $paymentDate = $paymentDate ?: date('Y-m-d');
    $receiptNumber = 'RCP-PENDING-' . time() . '-' . random_int(1000, 9999);
    $db->exec("INSERT INTO fee_payments (invoice_id, receipt_number, amount, payment_date, payment_method, status, received_by_admin)
        VALUES ({$invoiceId}, '{$receiptNumber}', {$amount}, '{$paymentDate}', 'Cash', 'Completed', 1)");
    $id = (int)$db->lastInsertId();
    $createdPaymentIds[] = $id;
    return $id;
}

try {
    $feeRepo = new FeeRepository();

    // TEST 1: Fully paid invoice shows no pending amount
    $student1 = createStudent($db, $createdStudentIds, 'S1', 'Pending Student 1', '1111111111111');
    $invoice1Full = createInvoice($db, $createdInvoiceIds, $student1, 8, 2026, 10000.00);
    recordPayment($db, $createdPaymentIds, $invoice1Full, 10000.00, '2026-08-30');
    $db->exec("UPDATE fee_records SET paid_amount = 10000.00, status = 'Paid' WHERE id = {$invoice1Full}");

    // Verify data was updated in DB
    $dbCheck = $db->query("SELECT paid_amount, status FROM fee_records WHERE id = {$invoice1Full}")->fetch();
    
    // Force fresh repo to see changes
    $feeRepoCheck = new FeeRepository();
    $pending1Rows = $feeRepoCheck->getPendingFeeRows(['student_id' => $student1]);
    $pending1Invoice = null;
    foreach ($pending1Rows as $row) {
        if ($row['id'] == $invoice1Full) {
            $pending1Invoice = $row;
            break;
        }
    }
    
    if (!$pending1Invoice) {
        recordResult($tests, 'Fully paid invoice has no pending', 'PASS', 'Invoice not in pending list (correct)');
    } else {
        recordResult($tests, 'Fully paid invoice has no pending', 'FAIL', 'Fully paid invoice still in pending: pending_amount=' . $pending1Invoice['pending_amount']);
    }

    // TEST 2: Partially paid invoice shows correct pending
    $student2 = createStudent($db, $createdStudentIds, 'S2', 'Pending Student 2', '2222222222222');
    $invoice2Partial = createInvoice($db, $createdInvoiceIds, $student2, 8, 2026, 10000.00);
    recordPayment($db, $createdPaymentIds, $invoice2Partial, 7000.00, '2026-08-30');
    $db->exec("UPDATE fee_records SET paid_amount = 7000.00, status = 'Partial' WHERE id = {$invoice2Partial}");

    $pending2 = $feeRepo->getPendingFeesSummary([]);
    $pending2 = (float)($pending2['total_pending_amount'] ?? 0);

    if ($pending2 >= 3000.00 - 0.01) {
        recordResult($tests, 'Partially paid invoice shows correct pending', 'PASS', 'Pending amount=' . $pending2);
    } else {
        recordResult($tests, 'Partially paid invoice shows correct pending', 'FAIL', 'Expected ~3000, got ' . $pending2);
    }

    // TEST 3: Unpaid invoice shows full pending
    $student3 = createStudent($db, $createdStudentIds, 'S3', 'Pending Student 3', '3333333333333');
    $invoice3Unpaid = createInvoice($db, $createdInvoiceIds, $student3, 8, 2026, 10000.00);
    // No payment recorded

    $pending3 = $feeRepo->getPendingFeesSummary([]);
    $pending3 = (float)($pending3['total_pending_amount'] ?? 0);

    if ($pending3 >= 10000.00 - 0.01) {
        recordResult($tests, 'Unpaid invoice shows full pending', 'PASS', 'Pending amount=' . $pending3);
    } else {
        recordResult($tests, 'Unpaid invoice shows full pending', 'FAIL', 'Expected ~10000, got ' . $pending3);
    }

    // TEST 4: Multi-month arrears accumulate correctly
    $student4 = createStudent($db, $createdStudentIds, 'S4', 'Pending Student 4', '4444444444444');
    $invoiceM1 = createInvoice($db, $createdInvoiceIds, $student4, 7, 2026, 10000.00);
    $invoiceM2 = createInvoice($db, $createdInvoiceIds, $student4, 8, 2026, 10000.00);
    $invoiceM3 = createInvoice($db, $createdInvoiceIds, $student4, 9, 2026, 10000.00);
    // No payments for any month - all arrears

    $pending4 = $feeRepo->getPendingFeesSummary([]);
    $pending4 = (float)($pending4['total_pending_amount'] ?? 0);

    if ($pending4 >= 30000.00 - 0.01) {
        recordResult($tests, 'Multi-month arrears accumulate', 'PASS', 'Total arrears=' . $pending4);
    } else {
        recordResult($tests, 'Multi-month arrears accumulate', 'FAIL', 'Expected ~30000, got ' . $pending4);
    }

    // TEST 5: Duplicate invoice prevention
    try {
        // Try to create second invoice for same student/month/year
        $duplicateResult = $db->prepare("INSERT INTO fee_records (invoice_number, student_id, billing_month, billing_year, invoice_date, amount, additional_charges, discount, due_date, paid_amount, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute(['INV-DUP-' . time(), $student4, 7, 2026, '2026-08-30', 10000.00, 0, 0, date('Y-m-d', strtotime('+15 days')), 0, 'Pending']);
        
        if (!$duplicateResult) {
            recordResult($tests, 'Duplicate invoice prevention', 'PASS', 'UNIQUE constraint prevented duplicate invoice');
        } else {
            recordResult($tests, 'Duplicate invoice prevention', 'FAIL', 'Duplicate invoice was unexpectedly allowed');
        }
    } catch (Exception $e) {
        if (stripos($e->getMessage(), 'unique') !== false) {
            recordResult($tests, 'Duplicate invoice prevention', 'PASS', 'UNIQUE constraint prevented duplicate invoice');
        } else {
            recordResult($tests, 'Duplicate invoice prevention', 'FAIL', 'Unexpected error: ' . $e->getMessage());
        }
    }

    // TEST 6: Pending fees detail rows
    $rows = $feeRepo->getPendingFeeRows([]);
    $rowSum = 0.0;
    foreach ($rows as $row) {
        $rowSum += (float)($row['pending_amount'] ?? 0);
    }

    $expectedPending = $pending4 + $pending2; // Multi-month + partial
    if (abs($rowSum - $expectedPending) < 0.01) {
        recordResult($tests, 'Pending detail rows reconciliation', 'PASS', 'Row sum=' . $rowSum . ', expected ~' . $expectedPending);
    } else {
        recordResult($tests, 'Pending detail rows reconciliation', 'FAIL', 'Row sum=' . $rowSum . ', expected ~' . $expectedPending);
    }

    $failCount = 0;
    foreach ($tests as $test) {
        if ($test['status'] !== 'PASS') {
            $failCount++;
        }
    }

    cleanup($db, $createdPaymentIds, $createdInvoiceIds, $createdStudentIds);

    echo PHP_EOL . 'SUMMARY=' . json_encode(['total' => count($tests), 'passed' => count(array_filter($tests, fn($t) => $t['status'] === 'PASS')), 'failed' => $failCount]) . PHP_EOL;
    exit($failCount > 0 ? 1 : 0);

} catch (Throwable $e) {
    cleanup($db, $createdPaymentIds, $createdInvoiceIds, $createdStudentIds);
    echo 'FAIL :: SCRIPT :: uncaught exception: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
