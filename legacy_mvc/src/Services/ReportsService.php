<?php
namespace App\Services;

use App\Repositories\ReportsRepository;
use App\Core\Session;

class ReportsService {
    private $repository;

    public function __construct() {
        $this->repository = new ReportsRepository();
    }

    public function validateFilters(array $filters = []) {
        $dateFilter = strtolower(trim((string)($filters['date_filter'] ?? 'this_month')));
        $fromDate = trim((string)($filters['from_date'] ?? ''));
        $toDate = trim((string)($filters['to_date'] ?? ''));

        if ($dateFilter === 'custom' && $fromDate !== '' && $toDate !== '' && $fromDate > $toDate) {
            return 'From date cannot be greater than the to date.';
        }

        return null;
    }

    public function getReportData(array $filters = []) {
        $year = isset($filters['year']) && (int)$filters['year'] > 0 ? (int)$filters['year'] : date('Y');

        return [
            'financial_summary' => $this->repository->getFinancialSummary($filters),
            'monthly_revenue' => $this->repository->getMonthlyRevenue($year, $filters),
            'occupancy_summary' => $this->repository->getOccupancySummary(),
            'room_occupancy' => $this->repository->getRoomOccupancy($filters),
            'student_analytics' => $this->repository->getStudentAnalytics($filters),
            'fee_performance' => $this->repository->getFeePerformance($filters),
            'overdue_fees' => $this->repository->getOverdueFees($filters),
            'top_outstanding_students' => $this->repository->getTopOutstandingStudents($filters),
            'payment_methods' => $this->repository->getPaymentMethodAnalytics($filters),
            'recent_activity' => $this->repository->getRecentActivity(5),
        ];
    }
}
