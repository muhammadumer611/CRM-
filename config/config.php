<?php
$environment = strtolower((string)(getenv('HMS_ENV') ?: 'production'));

return [
    'app_name' => 'Hostel Management System',
    'env' => $environment,
    'debug' => $environment === 'development',
    'timezone' => 'Asia/Karachi',
    'upload_max_size' => 5 * 1024 * 1024,
];
