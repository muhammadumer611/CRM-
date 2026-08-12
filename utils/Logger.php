<?php
namespace Utils;

class Logger {
    private static function write($level, $message) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $date = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $logMessage = "[{$date}] [{$ip}] [{$level}] {$message}" . PHP_EOL;
        
        file_put_contents($logDir . '/app-' . date('Y-m-d') . '.log', $logMessage, FILE_APPEND);
    }
    
    public static function info($message) {
        self::write('INFO', $message);
    }
    
    public static function warning($message) {
        self::write('WARNING', $message);
    }
    
    public static function error($message) {
        self::write('ERROR', $message);
    }
    
    public static function exception(\Throwable $e) {
        self::write('EXCEPTION', $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    }
}
