<?php
return [
    'env' => getenv('HMS_ENV') ?: 'production',
    'debug' => false,
    'base_url' => 'http://localhost/CRM',
    'timezone' => 'Asia/Karachi',
    'upload_max_size' => 5242880, // 5MB
    'session' => [
        'name' => 'HOSTEL_SESSION',
        'lifetime' => 86400,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Strict'
    ],
];
