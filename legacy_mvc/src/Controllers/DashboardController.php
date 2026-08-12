<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\DashboardService;

class DashboardController {
    public function index() {
        Auth::check();
        
        $dashboardService = new DashboardService();
        $stats = $dashboardService->getStats();
        $recentActivity = $dashboardService->getRecentActivity();
        
        View::render('admin/dashboard', [
            'title' => 'Dashboard',
            'stats' => $stats,
            'recentActivity' => $recentActivity
        ], 'admin');
    }
}
