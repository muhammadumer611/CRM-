<?php
use Controllers\RoomController;
use Utils\Router;

$router = new Router();

// Room Management Routes
$router->add('GET', '/api/rooms/statistics', function() { (new RoomController())->statistics(); });
$router->add('GET', '/api/rooms', function() { (new RoomController())->index(); });
$router->add('POST', '/api/rooms', function() { (new RoomController())->create(); });
$router->add('GET', '/api/rooms/{id}', function($params) { (new RoomController())->show($params); });
$router->add('PUT', '/api/rooms/{id}', function($params) { (new RoomController())->update($params); });
$router->add('PUT', '/api/rooms/{id}/disable', function($params) { (new RoomController())->disable($params); });
$router->add('PUT', '/api/rooms/{id}/enable', function($params) { (new RoomController())->enable($params); });

// Fee Management Routes
use Controllers\FeeController;
$router->add('GET', '/api/fees/statistics', function() { (new FeeController())->statistics(); });
$router->add('GET', '/api/fees', function() { (new FeeController())->index(); });
$router->add('POST', '/api/fees', function() { (new FeeController())->create(); });
$router->add('GET', '/api/fees/{id}', function($params) { (new FeeController())->show($params); });
$router->add('GET', '/api/fees/student/{student_id}', function($params) { (new FeeController())->studentFees($params); });
$router->add('GET', '/api/fees/student/{student_id}/summary', function($params) { (new FeeController())->studentSummary($params); });
$router->add('POST', '/api/fees/{id}/payment', function($params) { (new FeeController())->recordPayment($params); });

// Room Allocation Routes
use Controllers\RoomAllocationController;
$router->add('POST', '/api/allocations', function() { (new RoomAllocationController())->create(); });
$router->add('GET', '/api/allocations', function() { (new RoomAllocationController())->index(); });
$router->add('GET', '/api/allocations/statistics', function() { (new RoomAllocationController())->statistics(); });
$router->add('GET', '/api/allocations/without-room', function() { (new RoomAllocationController())->withoutRoom(); });
$router->add('GET', '/api/allocations/{id}', function($params) { (new RoomAllocationController())->show($params); });
$router->add('GET', '/api/allocations/student/{student_id}', function($params) { (new RoomAllocationController())->getActiveByStudent($params); });
$router->add('GET', '/api/allocations/student/{student_id}/history', function($params) { (new RoomAllocationController())->studentHistory($params); });
$router->add('GET', '/api/allocations/room/{room_id}', function($params) { (new RoomAllocationController())->getActiveByRoom($params); });
$router->add('GET', '/api/allocations/room/{room_id}/history', function($params) { (new RoomAllocationController())->roomHistory($params); });
$router->add('GET', '/api/allocations/available-beds/{room_id}', function($params) { (new RoomAllocationController())->availableBeds($params); });
$router->add('PUT', '/api/allocations/{id}/bed', function($params) { (new RoomAllocationController())->changeBed($params); });
$router->add('PUT', '/api/allocations/{id}/transfer', function($params) { (new RoomAllocationController())->transfer($params); });
$router->add('PUT', '/api/allocations/{id}/close', function($params) { (new RoomAllocationController())->close($params); });

// Student History Routes
use Controllers\StudentHistoryController;
$router->add('GET', '/api/student-history/statistics', function() { (new StudentHistoryController())->statistics(); });
$router->add('GET', '/api/student-history', function() { (new StudentHistoryController())->index(); });
$router->add('GET', '/api/student-history/{id}', function($params) { (new StudentHistoryController())->show($params); });
$router->add('GET', '/api/student-history/student/{student_id}', function($params) { (new StudentHistoryController())->studentHistory($params); });
$router->add('GET', '/api/student-history/student/{student_id}/recent', function($params) { (new StudentHistoryController())->studentRecent($params); });
$router->add('GET', '/api/student-history/event/{event_type}', function($params) { (new StudentHistoryController())->byEventType($params); });
$router->add('GET', '/api/student-history/admin/{admin_id}', function($params) { (new StudentHistoryController())->byAdmin($params); });

// Execute Router
$router->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
