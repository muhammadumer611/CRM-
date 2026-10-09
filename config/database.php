<?php
$environment = strtolower((string)(getenv('HMS_ENV') ?: 'production'));
$host = getenv('HMS_DB_HOST');
$database = getenv('HMS_DB_NAME');
$username = getenv('HMS_DB_USER');
$password = getenv('HMS_DB_PASSWORD');

if ($environment !== 'development') {
    if (!$host || !$database || !$username || $username === 'root' || $password === false || $password === '') {
        throw new RuntimeException('Production database credentials must be provided through HMS_DB_HOST, HMS_DB_NAME, HMS_DB_USER, and HMS_DB_PASSWORD.');
    }
} else {
    $host = $host ?: 'localhost';
    $database = $database ?: 'hms_db';
    $username = $username ?: 'root';
    $password = $password === false ? '' : $password;
}

return [
    'host' => $host,
    'dbname' => $database,
    'database' => $database,
    'user' => $username,
    'username' => $username,
    'password' => $password,
    'charset' => 'utf8mb4'
];

