<?php
namespace Utils;

use PDO;
use PDOException;
use Exception;

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $config = require __DIR__ . '/../config/database.php';
        
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            $this->pdo = new PDO($dsn, $config['user'], $config['password'], $options);
        } catch (PDOException $e) {
            Logger::error('Database Connection Failed: ' . $e->getMessage());
            throw new Exception("Database connection failed. Please check the logs.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }
}
