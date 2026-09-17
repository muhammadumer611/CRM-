<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use PDO;

class DatabaseStatusController {
    public function __construct() {
        Auth::check();
    }

    public function index() {
        $config = require APP_ROOT . '/config/database.php';
        $requiredTables = [
            'admins',
            'students',
            'rooms',
            'room_allocations',
            'fee_records',
            'fee_payments',
            'student_history',
            'alumni',
            'system_logs'
        ];

        $status = [
            'connected' => false,
            'database' => $config['dbname'],
            'server' => $config['host'],
            'tables' => [],
            'error' => null
        ];

        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            $pdo = new PDO($dsn, $config['user'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $databaseName = $pdo->query('SELECT DATABASE()')->fetchColumn();
            $allTables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            $tableSet = array_flip($allTables);

            foreach ($requiredTables as $table) {
                $status['tables'][] = [
                    'name' => $table,
                    'exists' => isset($tableSet[$table]),
                ];
            }

            $status['connected'] = true;
            $status['database'] = $databaseName ?: $config['dbname'];
        } catch (\Throwable $e) {
            $status['error'] = $e->getMessage();
        }

        View::render('admin/db-status', [
            'title' => 'Database Status',
            'dbStatus' => $status,
        ], 'admin');
    }
}
