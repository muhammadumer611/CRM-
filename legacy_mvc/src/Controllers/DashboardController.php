<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\DashboardService;

class DashboardController {
    public function index() {
        Auth::check();

        try {
            (new \App\Services\FeeService())->generateRecurringMonthlyBillingForActiveResidents();
        } catch (\Throwable $e) {
            error_log('Monthly billing generation failed: ' . $e->getMessage());
        }
        $dashboardService = new DashboardService();
        $stats = $dashboardService->getStats();
        $alerts = $dashboardService->getDashboardAlerts();
        $searchTerm = trim((string)($_GET['student_search'] ?? ''));
        $studentResults = $dashboardService->searchStudents($searchTerm);

        View::render('admin/dashboard', [
            'title' => 'Dashboard',
            'stats' => $stats,
            'alerts' => $alerts,
            'studentSearch' => $searchTerm,
            'studentResults' => $studentResults
        ], 'admin');
    }
}
