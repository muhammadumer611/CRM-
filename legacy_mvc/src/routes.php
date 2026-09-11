<?php
/** @var \App\Core\Router $router */

$router->add('GET', '/', 'AuthController', 'loginForm');
$router->add('GET', '/login', 'AuthController', 'loginForm');
$router->add('POST', '/login', 'AuthController', 'login');
$router->add('GET', '/logout', 'AuthController', 'logout');
$router->add('POST', '/logout', 'AuthController', 'logout');

$router->add('GET', '/dashboard', 'DashboardController', 'index');

// Student routes
$router->add('GET', '/students', 'StudentController', 'index');
$router->add('GET', '/students/active', 'StudentController', 'activeList');
$router->add('GET', '/students/create', 'StudentController', 'create');
$router->add('POST', '/students/store', 'StudentController', 'store');
$router->add('GET', '/students/view/{id}', 'StudentController', 'show');
$router->add('GET', '/students/edit/{id}', 'StudentController', 'edit');
$router->add('POST', '/students/update/{id}', 'StudentController', 'update');
$router->add('GET', '/students/account/{id}', 'StudentController', 'account');
$router->add('POST', '/students/account/payment/{id}', 'StudentController', 'accountPayment');
$router->add('POST', '/students/account/invoice/{id}', 'StudentController', 'accountInvoice');
$router->add('POST', '/students/checkout/{id}', 'StudentController', 'checkout');

// Room routes
$router->add('GET', '/rooms', 'RoomController', 'index');
$router->add('GET', '/rooms/available-beds', 'RoomController', 'availableBeds');
$router->add('GET', '/rooms/create', 'RoomController', 'create');
$router->add('POST', '/rooms/store', 'RoomController', 'store');
$router->add('GET', '/rooms/edit/{id}', 'RoomController', 'edit');
$router->add('POST', '/rooms/update/{id}', 'RoomController', 'update');
$router->add('POST', '/rooms/delete/{id}', 'RoomController', 'delete');

// Availability API routes
$router->add('GET', '/api/allocations/available-beds/{id}', 'AllocationController', 'apiAvailableBeds');

// Fee routes
$router->add('GET', '/fees', 'FeeController', 'index');
$router->add('GET', '/fees/collection', 'FeeController', 'collection');
$router->add('GET', '/fees/paid', 'FeeController', 'paid');
$router->add('GET', '/fees/pending', 'FeeController', 'pending');
$router->add('GET', '/fees/pending/{id}', 'FeeController', 'pendingDetail');
$router->add('GET', '/fees/security-deposits', 'FeeController', 'securityDeposits');
$router->add('GET', '/fees/pay/{id}', 'FeeController', 'pay');
$router->add('POST', '/fees/storePayment/{id}', 'FeeController', 'storePayment');
$router->add('GET', '/fees/receipt/{id}', 'FeeController', 'receipt');

// Reservation routes
$router->add('GET', '/reservations', 'ReservationController', 'index');
$router->add('GET', '/reservations/create', 'ReservationController', 'create');
$router->add('POST', '/reservations/store', 'ReservationController', 'store');
$router->add('GET', '/reservations/view/{id}', 'ReservationController', 'show');
$router->add('GET', '/reservations/convert/{id}', 'ReservationController', 'convert');
$router->add('POST', '/reservations/convert/{id}', 'ReservationController', 'processConvert');
$router->add('POST', '/reservations/confirm/{id}', 'ReservationController', 'confirm');
$router->add('POST', '/reservations/cancel/{id}', 'ReservationController', 'cancel');

// Reports routes
$router->add('GET', '/reports', 'ReportsController', 'index');
$router->add('GET', '/reports/export/csv', 'ReportsController', 'exportCsv');

// Notification routes
$router->add('GET', '/notifications', 'NotificationController', 'index');
$router->add('POST', '/notifications/mark-read/{id}', 'NotificationController', 'markRead');
$router->add('POST', '/notifications/mark-all-read', 'NotificationController', 'markAllRead');

// Database status routes
$router->add('GET', '/db-status', 'DatabaseStatusController', 'index');

// Account settings routes
$router->add('GET', '/account-settings', 'AdminSettingsController', 'index');
$router->add('POST', '/account-settings/username', 'AdminSettingsController', 'updateUsername');
$router->add('POST', '/account-settings/password', 'AdminSettingsController', 'updatePassword');

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



