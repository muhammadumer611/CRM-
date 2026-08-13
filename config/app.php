<?php
return [
    'env' => 'development', // change to 'production' on cPanel
    'debug' => true,
    'base_url' => 'http://localhost/CRM',
    'timezone' => 'Asia/Karachi',
    'upload_max_size' => 5242880, // 5MB
    'session' => [
        'name' => 'HOSTEL_SESSION',
        'lifetime' => 86400,
        'secure' => false, // Set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ],
    'notification_reminder_days' => 3
];
