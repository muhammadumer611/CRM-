<?php
define('APP_ROOT', __DIR__ . '/legacy_mvc');
spl_autoload_register(function ($class) {
    $prefixes = ['App\\' => APP_ROOT . '/src/'];
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

try {
    $db = Database::getInstance()->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connection established" . PHP_EOL;
    
    $result = $db->query("SELECT COUNT(*) FROM students");
    $count = $result->fetchColumn();
    echo "Student count: " . $count . PHP_EOL;
    
    // Try a simple insert with error checking
    $testId = 'DBTEST-' . time();
    $stmt = $db->prepare("INSERT INTO students (student_id_str, full_name, cnic, phone, email, address, guardian_name, guardian_phone, guardian_cnic, relation, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $ok = $stmt->execute([$testId, 'Test Name', '1234567890123', '03000000000', 'test@test.com', 'addr', 'Guard', '03111111111', '9999999999999', 'Father', 'Active']);
    echo "Insert result: " . ($ok ? "success" : "failed") . PHP_EOL;
    
    if ($ok) {
        $id = $db->lastInsertId();
        echo "Inserted student ID: " . $id . PHP_EOL;
        $db->exec("DELETE FROM students WHERE id = {$id}");
        echo "Cleanup done" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
}
