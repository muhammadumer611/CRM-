<?php
/**
 * CLI Cron Job: Generate Monthly Pending Fee Alerts
 * 
 * Schedule via crontab / cPanel cron:
 * Example (runs on the 10th of every month at 08:00 AM):
 * 0 8 10 * * /usr/bin/php /path/to/legacy_mvc/scripts/cron_monthly_fee_alerts.php
 */

define('APP_ROOT', dirname(__DIR__));

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = APP_ROOT . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use App\Services\NotificationService;

$targetDate = $argv[1] ?? null;

echo "=== Monthly Pending Fee Alerts Cron Job ===" . PHP_EOL;
echo "Execution Time: " . date('Y-m-d H:i:s') . PHP_EOL;

$service = new NotificationService();
$result = $service->generateMonthlyPendingFeeAlerts($targetDate);

echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
