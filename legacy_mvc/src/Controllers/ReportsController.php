<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Services\ReportsService;

class ReportsController {
    private $reportsService;

    public function __construct() {
        Auth::check();
        $this->reportsService = new ReportsService();
    }

    public function index() {
        $filters = $_GET;
        $validationError = $this->reportsService->validateFilters($filters);

        if ($validationError) {
            Session::set('error', $validationError);
        }

        $reportData = $this->reportsService->getReportData($filters);

        View::render('admin/reports/index', [
            'title' => 'Reports & Analytics',
            'filters' => $filters,
            'reportData' => $reportData,
            'currentYear' => date('Y'),
            'selectedYear' => !empty($filters['year']) ? (int)$filters['year'] : date('Y'),
        ], 'admin');
    }

    public function exportCsv() {
        $filters = $_GET;
        $validationError = $this->reportsService->validateFilters($filters);
        if ($validationError) {
            Session::set('error', $validationError);
            header('Location: ' . (require APP_ROOT . '/config/app.php')['base_url'] . '/reports');
            exit;
        }

        $reportData = $this->reportsService->getReportData($filters);
        $rows = $reportData['monthly_revenue'];

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="hostel-monthly-revenue-' . date('Ymd') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Month', 'Total Invoiced', 'Total Collected', 'Outstanding', 'Number of Payments']);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['month_name'],
                number_format((float)$row['total_invoiced'], 2),
                number_format((float)$row['total_collected'], 2),
                number_format((float)$row['outstanding'], 2),
                (int)$row['payment_count'],
            ]);
        }

        fclose($output);
        exit;
    }
}
