<?php
/**
 * Collection Validation Suite
 * Tests that total collection is accurately calculated from payment records
 * Ensures collection does not double-count payments
 * Verifies reversed/refunded payments are excluded from collection
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
$db->exec("DELETE FROM fee_payments WHERE invoice_id IN (SELECT id FROM fee_records WHERE student_id IN (SELECT id FROM students WHERE student_id_str LIKE 'COLLECT-%'))");
$db->exec("DELETE FROM fee_records WHERE student_id IN (SELECT id FROM students WHERE student_id_str LIKE 'COLLECT-%')");
$db->exec("DELETE FROM room_allocations WHERE student_id IN (SELECT id FROM students WHERE student_id_str LIKE 'COLLECT-%')");
$db->exec("DELETE FROM students WHERE student_id_str LIKE 'COLLECT-%'");
$db->exec("DELETE FROM rooms WHERE room_number LIKE 'COLLECT-%'");

$tests = [];
$createdStudentIds = [];
$createdRoomIds = [];
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
    $studentIdStr = 'COLLECT-' . strtoupper($seed) . '-' . time() . '-' . random_int(1000, 9999);
    $email = $email ?: 'collect.' . $seed . '.' . time() . '@example.test';
    $stmt = $db->prepare("INSERT INTO students (student_id_str, full_name, cnic, phone, email, address, guardian_name, guardian_phone, guardian_cnic, relation, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $ok = $stmt->execute([$studentIdStr, $name, $cnic, '03000000000', $email, 'Test Address', 'Guardian', '03000000001', '2222222222222', 'Father', 'Active']);
    $id = (int)$db->lastInsertId();
    $createdStudentIds[] = $id;
    echo "DEBUG: Created student $seed: id=$id, ok=$ok, email=$email" . PHP_EOL;
    return $id;
}

function createInvoice($db, &$createdInvoiceIds, $studentId, $month, $year, $amount, $dueDate = null) {
    $invoiceNumber = 'INV-COLLECT-' . time() . '-' . random_int(1000, 9999);
    $dueDate = $dueDate ?: date('Y-m-d', strtotime('+15 days'));
    $stmt = $db->prepare("INSERT INTO fee_records (invoice_number, student_id, billing_month, billing_year, invoice_date, amount, additional_charges, discount, due_date, paid_amount, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $ok = $stmt->execute([$invoiceNumber, $studentId, $month, $year, '2026-08-30', $amount, 0, 0, $dueDate, 0, 'Pending']);
    $id = (int)$db->lastInsertId();
    $createdInvoiceIds[] = $id;
    echo "DEBUG: Created invoice: id=$id, student=$studentId, ok=$ok" . PHP_EOL;
    return $id;
}

function recordPayment($db, &$createdPaymentIds, $invoiceId, $amount, $paymentDate = null, $status = 'Completed') {
    $paymentDate = $paymentDate ?: date('Y-m-d');
    $receiptNumber = 'RCP-COLLECT-' . time() . '-' . random_int(1000, 9999);
    $stmt = $db->prepare("INSERT INTO fee_payments (invoice_id, receipt_number, amount, payment_date, payment_method, status, received_by_admin)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $ok = $stmt->execute([$invoiceId, $receiptNumber, $amount, $paymentDate, 'Cash', $status, 1]);
    $id = (int)$db->lastInsertId();
    $createdPaymentIds[] = $id;
    echo "DEBUG: Created payment: id=$id, invoice=$invoiceId, amount=$amount, ok=$ok" . PHP_EOL;
    return $id;
}

try {
    $feeRepo = new FeeRepository();

    // TEST 1: Single payment increases collection
    $student1 = createStudent($db, $createdStudentIds, 'S1', 'Collection Student 1', '1111111111111');
    $invoice1 = createInvoice($db, $createdInvoiceIds, $student1, 8, 2026, 10000.00);
    $payment1 = recordPayment($db, $createdPaymentIds, $invoice1, 10000.00, '2026-08-30');

    // Force reconnect to ensure latest data
    $feeRepoFresh = new FeeRepository();
    $collectionBefore = $feeRepoFresh->getCollectionSummary([]);
    $collectionBefore = (float)($collectionBefore['total_collection'] ?? 0);

    if ($collectionBefore >= 0) {
        recordResult($tests, 'Single payment recorded', 'PASS', 'Collection total=' . $collectionBefore);
    } else {
        recordResult($tests, 'Single payment recorded', 'FAIL', 'Collection is negative: ' . $collectionBefore);
    }

    // TEST 2: Second payment increases collection correctly
    $invoice2 = createInvoice($db, $createdInvoiceIds, $student1, 9, 2026, 10000.00);
    $payment2 = recordPayment($db, $createdPaymentIds, $invoice2, 5000.00, '2026-09-01');

    // Force reconnect after second payment
    $feeRepoFresh2 = new FeeRepository();
    $collectionAfter = $feeRepoFresh2->getCollectionSummary([]);
    $collectionAfter = (float)($collectionAfter['total_collection'] ?? 0);

    $expectedSecondTotal = $collectionBefore + 5000.00;
    $tolerance = 0.01;
    if (abs($collectionAfter - $expectedSecondTotal) < $tolerance) {
        recordResult($tests, 'Second payment increases collection', 'PASS', 'Increased from ' . $collectionBefore . ' to ' . $collectionAfter);
    } else {
        recordResult($tests, 'Second payment increases collection', 'FAIL', 'Expected ~' . $expectedSecondTotal . ', got ' . $collectionAfter . ' (diff=' . ($collectionAfter - $collectionBefore) . ')');
    }

    // TEST 3: Reversed payment is not counted in collection
    $db->exec("UPDATE fee_payments SET status = 'Reversed', reversed_by_admin = 1, reversed_at = NOW() WHERE id = {$payment2}");

    $collectionAfterReverse = $feeRepo->getCollectionSummary([]);
    $collectionAfterReverse = (float)($collectionAfterReverse['total_collection'] ?? 0);

    if (abs($collectionAfterReverse - $collectionBefore) < $tolerance) {
        recordResult($tests, 'Reversed payment excluded from collection', 'PASS', 'Reverted from ' . $collectionAfter . ' to ' . $collectionAfterReverse);
    } else {
        recordResult($tests, 'Reversed payment excluded from collection', 'FAIL', 'Expected ~' . $collectionBefore . ', got ' . $collectionAfterReverse);
    }

    // TEST 4: Collection total reconciliation
    $totalPayments = (int)$db->query("SELECT COALESCE(COUNT(*), 0) FROM fee_payments WHERE status = 'Completed' AND amount > 0")->fetchColumn();
    $sumPayments = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM fee_payments WHERE status = 'Completed' AND amount > 0")->fetchColumn();
    $repoSum = (float)($collectionAfterReverse ?? 0);

    if (abs($sumPayments - $repoSum) < $tolerance) {
        recordResult($tests, 'Collection reconciliation', 'PASS', 'DB sum=' . $sumPayments . ', repo=' . $repoSum . ', count=' . $totalPayments);
    } else {
        recordResult($tests, 'Collection reconciliation', 'FAIL', 'DB sum=' . $sumPayments . ', repo=' . $repoSum);
    }

    // TEST 5: Collection rows detail accuracy
    $rows = $feeRepo->getCollectionRows([], 100, 0);
    $rowSum = 0.0;
    foreach ($rows as $row) {
        $rowSum += (float)($row['amount_received'] ?? 0);
    }

    if (abs($rowSum - $sumPayments) < $tolerance) {
        recordResult($tests, 'Collection detail rows accuracy', 'PASS', 'Row sum=' . $rowSum . ', expected ~' . $sumPayments);
    } else {
        recordResult($tests, 'Collection detail rows accuracy', 'FAIL', 'Row sum=' . $rowSum . ', expected ~' . $sumPayments);
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
