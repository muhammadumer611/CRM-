<?php
/** @var \App\Core\Router $router */

$router->add('GET', '/', 'AuthController', 'loginForm');
$router->add('POST', '/login', 'AuthController', 'login');
$router->add('GET', '/logout', 'AuthController', 'logout');

$router->add('GET', '/dashboard', 'DashboardController', 'index');

// Student routes
$router->add('GET', '/students', 'StudentController', 'index');
$router->add('GET', '/students/create', 'StudentController', 'create');
$router->add('POST', '/students/store', 'StudentController', 'store');
$router->add('GET', '/students/view/{id}', 'StudentController', 'show');
$router->add('GET', '/students/edit/{id}', 'StudentController', 'edit');
$router->add('POST', '/students/update/{id}', 'StudentController', 'update');
<<<<<<< HEAD
$router->add('GET', '/students/account/{id}', 'StudentController', 'account');
=======
$router->add('POST', '/students/checkout/{id}', 'StudentController', 'checkout');
>>>>>>> 962ef01 (Update HMS)

// Room routes
$router->add('GET', '/rooms', 'RoomController', 'index');
$router->add('GET', '/rooms/create', 'RoomController', 'create');
$router->add('POST', '/rooms/store', 'RoomController', 'store');
$router->add('GET', '/rooms/edit/{id}', 'RoomController', 'edit');
$router->add('POST', '/rooms/update/{id}', 'RoomController', 'update');

// Allocation routes
$router->add('GET', '/allocations', 'AllocationController', 'index');
$router->add('GET', '/allocations/create', 'AllocationController', 'create');
$router->add('POST', '/allocations/store', 'AllocationController', 'store');
$router->add('POST', '/allocations/remove/{id}', 'AllocationController', 'remove');
$router->add('GET', '/api/allocations/available-beds/{room_id}', 'AllocationController', 'apiAvailableBeds');

// Fee routes
$router->add('GET', '/fees', 'FeeController', 'index');
$router->add('GET', '/fees/collection', 'FeeController', 'collection');
$router->add('GET', '/fees/pending', 'FeeController', 'pending');
$router->add('GET', '/fees/security-deposits', 'FeeController', 'securityDeposits');
$router->add('GET', '/fees/create', 'FeeController', 'create');
$router->add('POST', '/fees/store', 'FeeController', 'store');
$router->add('GET', '/fees/pay/{id}', 'FeeController', 'pay');
$router->add('POST', '/fees/storePayment/{id}', 'FeeController', 'storePayment');
$router->add('GET', '/fees/receipt/{id}', 'FeeController', 'receipt');

// Reports routes
$router->add('GET', '/reports', 'ReportsController', 'index');
$router->add('GET', '/reports/export/csv', 'ReportsController', 'exportCsv');

// Notification routes
$router->add('GET', '/notifications', 'NotificationController', 'index');
$router->add('POST', '/notifications/mark-read/{id}', 'NotificationController', 'markRead');
$router->add('POST', '/notifications/mark-all-read', 'NotificationController', 'markAllRead');

// Audit log routes
$router->add('GET', '/audit-logs', 'AuditLogsController', 'index');
$router->add('GET', '/audit-logs/{id}', 'AuditLogsController', 'show');

// Database status routes
$router->add('GET', '/db-status', 'DatabaseStatusController', 'index');

// Alumni routes
$router->add('GET', '/alumni', 'AlumniController', 'index');
$router->add('GET', '/api/alumni', 'AlumniController', 'apiGetAll');
$router->add('POST', '/api/alumni/transfer', 'AlumniController', 'apiTransfer');
$router->add('GET', '/api/alumni/student/{id}', 'AlumniController', 'apiGetByStudentId');
$router->add('GET', '/api/alumni/{id}', 'AlumniController', 'apiGetById');

// History routes
$router->add('GET', '/history', 'HistoryController', 'index');
$router->add('GET', '/student-history', 'HistoryController', 'apiGetAll');
$router->add('GET', '/student-history/count', 'HistoryController', 'apiCount');
$router->add('GET', '/student-history/student/{id}', 'HistoryController', 'apiGetStudent');
$router->add('GET', '/student-history/{id}', 'HistoryController', 'apiGetById');



