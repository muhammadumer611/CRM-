<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class ReportsRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getFinancialSummary(array $filters = []) {
        $invoiceWhere = $this->buildInvoiceWhere($filters);
        $paymentWhere = $this->buildPaymentWhere($filters);

        $totalInvoiced = $this->fetchScalar(
            "SELECT COALESCE(SUM(f.amount + f.additional_charges - f.discount), 0) FROM fee_records f WHERE 1=1 {$invoiceWhere['sql']}",
            $invoiceWhere['params']
        );

        $totalCollected = $this->fetchScalar(
            "SELECT COALESCE(SUM(p.amount), 0) FROM fee_payments p WHERE 1=1 {$paymentWhere['sql']}",
            $paymentWhere['params']
        );

        $totalOutstanding = $this->fetchScalar(
            "SELECT COALESCE(SUM((f.amount + f.additional_charges - f.discount) - f.paid_amount), 0) FROM fee_records f WHERE 1=1 {$invoiceWhere['sql']} AND (f.status IN ('Pending', 'Partial', 'Overdue') OR ((f.amount + f.additional_charges - f.discount) > f.paid_amount))",
            $invoiceWhere['params']
        );

        $totalOverdue = $this->fetchScalar(
            "SELECT COALESCE(SUM((f.amount + f.additional_charges - f.discount) - f.paid_amount), 0) FROM fee_records f WHERE 1=1 {$invoiceWhere['sql']} AND (f.status = 'Overdue' OR (f.due_date < CURDATE() AND (f.amount + f.additional_charges - f.discount) > f.paid_amount))",
            $invoiceWhere['params']
        );

        $totalDiscounts = $this->fetchScalar(
            "SELECT COALESCE(SUM(f.discount), 0) FROM fee_records f WHERE 1=1 {$invoiceWhere['sql']}",
            $invoiceWhere['params']
        );

        $totalPayments = $this->fetchScalar(
            "SELECT COUNT(*) FROM fee_payments p WHERE 1=1 {$paymentWhere['sql']}",
            $paymentWhere['params']
        );

        return [
            'total_invoiced' => (float)$totalInvoiced,
            'total_collected' => (float)$totalCollected,
            'total_outstanding' => (float)$totalOutstanding,
            'total_overdue' => (float)$totalOverdue,
            'total_discounts' => (float)$totalDiscounts,
            'total_payments' => (int)$totalPayments,
        ];
    }

    public function getMonthlyRevenue($year, array $filters = []) {
        $months = [];
        $baseSql = "SELECT MONTH(f.invoice_date) AS month_number,
                    COALESCE(SUM(f.amount + f.additional_charges - f.discount), 0) AS total_invoiced,
                    COALESCE(SUM(f.paid_amount), 0) AS total_collected,
                    COALESCE(SUM((f.amount + f.additional_charges - f.discount) - f.paid_amount), 0) AS outstanding,
                    COALESCE(COUNT(DISTINCT p.id), 0) AS payment_count
                    FROM fee_records f
                    LEFT JOIN fee_payments p ON p.invoice_id = f.id
                    WHERE 1 = 1";

        $params = [];
        $dateClause = $this->buildInvoiceWhere($filters, true);

        if (!empty($dateClause['sql'])) {
            $baseSql .= ' ' . $dateClause['sql'];
            $params = $dateClause['params'];
        } else {
            $baseSql .= ' AND YEAR(f.invoice_date) = :year';
            $params['year'] = $year;
        }

        $baseSql .= ' GROUP BY MONTH(f.invoice_date) ORDER BY MONTH(f.invoice_date) ASC';

        $stmt = $this->db->prepare($baseSql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach (range(1, 12) as $monthNumber) {
            $monthData = null;
            foreach ($rows as $row) {
                if ((int)$row['month_number'] === $monthNumber) {
                    $monthData = $row;
                    break;
                }
            }

            $months[] = [
                'month_number' => $monthNumber,
                'month_name' => date('F', mktime(0, 0, 0, $monthNumber, 1)),
                'total_invoiced' => (float)($monthData['total_invoiced'] ?? 0),
                'total_collected' => (float)($monthData['total_collected'] ?? 0),
                'outstanding' => (float)($monthData['outstanding'] ?? 0),
                'payment_count' => (int)($monthData['payment_count'] ?? 0),
            ];
        }

        return $months;
    }

    public function getOccupancySummary() {
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total_rooms,
                    COALESCE(SUM(total_beds), 0) AS total_beds,
                    COALESCE(SUM(CASE WHEN status != 'Disabled' THEN total_beds ELSE 0 END), 0) AS active_beds,
                    COALESCE(SUM(CASE WHEN status = 'Disabled' THEN total_beds ELSE 0 END), 0) AS disabled_beds
             FROM rooms"
        );
        $summary = $stmt->fetch();

        $roomOccupancy = $this->getRoomOccupancy([]);
        $occupiedBeds = 0;
        foreach ($roomOccupancy as $room) {
            $occupiedBeds += (int)$room['occupied_beds'];
        }

        $totalBeds = (int)($summary['total_beds'] ?? 0);
        $occupancyPct = $totalBeds > 0 ? (($occupiedBeds / $totalBeds) * 100) : 0;

        return [
            'total_rooms' => (int)($summary['total_rooms'] ?? 0),
            'total_beds' => $totalBeds,
            'occupied_beds' => $occupiedBeds,
            'available_beds' => max(0, $totalBeds - $occupiedBeds),
            'occupancy_percentage' => round($occupancyPct, 2),
        ];
    }

    public function getRoomOccupancy(array $filters = []) {
        $query = "SELECT r.id, r.room_number, r.block, r.floor, r.total_beds,
                         COUNT(a.id) AS occupied_beds,
                         (r.total_beds - COUNT(a.id)) AS available_beds,
                         ROUND((COUNT(a.id) / NULLIF(r.total_beds, 0)) * 100, 2) AS occupancy_percentage,
                         CASE
                             WHEN r.status = 'Disabled' THEN 'Disabled'
                             WHEN COUNT(a.id) = 0 THEN 'Available'
                             WHEN COUNT(a.id) >= r.total_beds THEN 'Occupied'
                             ELSE 'Partially Occupied'
                         END AS room_status
                   FROM rooms r
                   LEFT JOIN room_allocations a ON a.room_id = r.id AND a.status = 'Active'
                   WHERE r.status != 'Disabled'";

        $params = [];

        if (!empty($filters['block'])) {
            $query .= ' AND r.block = :block';
            $params['block'] = $filters['block'];
        }

        if (!empty($filters['floor'])) {
            $query .= ' AND r.floor = :floor';
            $params['floor'] = $filters['floor'];
        }

        if (!empty($filters['room_status'])) {
            $query .= ' AND CASE
                            WHEN r.status = "Disabled" THEN "Disabled"
                            WHEN COUNT(a.id) = 0 THEN "Available"
                            WHEN COUNT(a.id) >= r.total_beds THEN "Occupied"
                            ELSE "Partially Occupied"
                        END = :room_status';
            $params['room_status'] = $filters['room_status'];
        }

        $query .= ' GROUP BY r.id, r.room_number, r.block, r.floor, r.total_beds, r.status ORDER BY r.block ASC, r.room_number ASC';

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['occupied_beds'] = (int)$row['occupied_beds'];
            $row['available_beds'] = max(0, (int)$row['available_beds']);
            $row['occupancy_percentage'] = (float)($row['occupancy_percentage'] ?? 0);
            $row['total_beds'] = (int)$row['total_beds'];
        }

        return $rows;
    }

    public function getStudentAnalytics(array $filters = []) {
        $dateClause = $this->buildDateClause('s.created_at', $filters);
        $byMonthClause = $this->buildDateClause('s.created_at', ['date_filter' => 'this_month']);

        $totalStudents = $this->fetchScalar("SELECT COUNT(*) FROM students s WHERE 1=1 {$dateClause['sql']}", $dateClause['params']);
        $activeStudents = $this->fetchScalar("SELECT COUNT(*) FROM students s WHERE s.status = 'Active' {$dateClause['sql']}", $dateClause['params']);
        $inactiveStudents = $this->fetchScalar("SELECT COUNT(*) FROM students s WHERE s.status = 'Inactive' {$dateClause['sql']}", $dateClause['params']);
        $newThisMonth = $this->fetchScalar("SELECT COUNT(*) FROM students s WHERE 1=1 {$byMonthClause['sql']}", $byMonthClause['params']);

        $leftThisMonth = $this->fetchScalar(
            "SELECT COUNT(*) FROM alumni a WHERE 1=1 AND a.leaving_date IS NOT NULL AND MONTH(a.leaving_date) = MONTH(CURDATE()) AND YEAR(a.leaving_date) = YEAR(CURDATE())",
            []
        );

        $totalAlumni = $this->fetchScalar("SELECT COUNT(*) FROM alumni");

        return [
            'total_students' => (int)$totalStudents,
            'active_students' => (int)$activeStudents,
            'inactive_students' => (int)$inactiveStudents,
            'new_students_this_month' => (int)$newThisMonth,
            'students_left_this_month' => (int)$leftThisMonth,
            'total_alumni' => (int)$totalAlumni,
        ];
    }

    public function getFeePerformance(array $filters = []) {
        $baseWhere = $this->buildInvoiceWhere($filters);
        $statuses = ['Paid', 'Pending', 'Partial', 'Overdue'];
        $rows = [];

        foreach ($statuses as $status) {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS record_count,
                        COALESCE(SUM(f.amount + f.additional_charges - f.discount), 0) AS total_amount,
                        COALESCE(SUM(f.paid_amount), 0) AS paid_amount,
                        COALESCE(SUM((f.amount + f.additional_charges - f.discount) - f.paid_amount), 0) AS remaining_amount
                 FROM fee_records f
                 WHERE f.status = :status {$baseWhere['sql']}",
                
            );

            $params = $baseWhere['params'];
            $params['status'] = $status;
            $stmt->execute($params);
            $row = $stmt->fetch();

            $rows[$status] = [
                'status' => $status,
                'record_count' => (int)($row['record_count'] ?? 0),
                'total_amount' => (float)($row['total_amount'] ?? 0),
                'paid_amount' => (float)($row['paid_amount'] ?? 0),
                'remaining_amount' => (float)($row['remaining_amount'] ?? 0),
            ];
        }

        return $rows;
    }

    public function getOverdueFees(array $filters = []) {
        $where = $this->buildInvoiceWhere($filters);
        $sql = "SELECT f.id, f.invoice_number, s.full_name AS student_name, s.student_id_str AS student_id,
                       r.room_number, r.block,
                       f.billing_month, f.due_date,
                       (f.amount + f.additional_charges - f.discount) AS total_amount,
                       f.paid_amount,
                       ((f.amount + f.additional_charges - f.discount) - f.paid_amount) AS remaining_amount,
                       GREATEST(0, DATEDIFF(CURDATE(), f.due_date)) AS days_overdue,
                       f.status
                FROM fee_records f
                JOIN students s ON s.id = f.student_id
                LEFT JOIN room_allocations ra ON ra.student_id = s.id AND ra.status = 'Active'
                LEFT JOIN rooms r ON r.id = ra.room_id
                WHERE 1=1 {$where['sql']} AND (f.status = 'Overdue' OR (f.due_date < CURDATE() AND (f.amount + f.additional_charges - f.discount) > f.paid_amount))
                ORDER BY f.due_date ASC, f.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($where['params']);
        return $stmt->fetchAll();
    }

    public function getTopOutstandingStudents(array $filters = []) {
        $where = $this->buildInvoiceWhere($filters);
        $sql = "SELECT s.id, s.full_name AS student_name, s.student_id_str AS student_id,
                       r.room_number,
                       SUM((f.amount + f.additional_charges - f.discount) - f.paid_amount) AS outstanding_amount
                FROM fee_records f
                JOIN students s ON s.id = f.student_id
                LEFT JOIN room_allocations ra ON ra.student_id = s.id AND ra.status = 'Active'
                LEFT JOIN rooms r ON r.id = ra.room_id
                WHERE 1=1 {$where['sql']}
                GROUP BY s.id, s.full_name, s.student_id_str, r.room_number
                HAVING outstanding_amount > 0
                ORDER BY outstanding_amount DESC, s.full_name ASC
                LIMIT 10";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($where['params']);
        return $stmt->fetchAll();
    }

    public function getPaymentMethodAnalytics(array $filters = []) {
        $where = $this->buildPaymentWhere($filters);
        $sql = "SELECT p.payment_method,
                       COUNT(*) AS transaction_count,
                       COALESCE(SUM(p.amount), 0) AS total_collected
                FROM fee_payments p
                WHERE 1=1 {$where['sql']}
                GROUP BY p.payment_method
                ORDER BY total_collected DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($where['params']);
        return $stmt->fetchAll();
    }

    public function getRecentActivity($limit = 5) {
        $stmt = $this->db->prepare(
            "SELECT l.*, a.username FROM system_logs l LEFT JOIN admins a ON a.id = l.admin_id ORDER BY l.created_at DESC LIMIT :limit"
        );
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function buildInvoiceWhere(array $filters, $allowYearFallback = false) {
        $sql = '';
        $params = [];

        $dateInfo = $this->buildDateClause('f.invoice_date', $filters, $allowYearFallback ? ($filters['year'] ?? date('Y')) : null);
        $sql .= $dateInfo['sql'];
        $params = array_merge($params, $dateInfo['params']);

        if (!empty($filters['student_id'])) {
            $sql .= ' AND f.student_id = :student_id';
            $params['student_id'] = (int)$filters['student_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND f.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['month'])) {
            $sql .= ' AND f.billing_month = :billing_month';
            $params['billing_month'] = (int)$filters['month'];
        }

        if (!empty($filters['year'])) {
            $sql .= ' AND YEAR(f.invoice_date) = :invoice_year';
            $params['invoice_year'] = (int)$filters['year'];
        }

        return ['sql' => $sql, 'params' => $params];
    }

    private function buildPaymentWhere(array $filters) {
        $sql = '';
        $params = [];
        $dateInfo = $this->buildDateClause('p.payment_date', $filters);
        $sql .= $dateInfo['sql'];
        $params = array_merge($params, $dateInfo['params']);

        if (!empty($filters['payment_method'])) {
            $sql .= ' AND p.payment_method = :payment_method';
            $params['payment_method'] = $filters['payment_method'];
        }

        return ['sql' => $sql, 'params' => $params];
    }

    private function buildDateClause($column, array $filters, $yearOverride = null) {
        $sql = '';
        $params = [];

        $dateFilter = strtolower(trim((string)($filters['date_filter'] ?? 'this_month')));
        $fromDate = trim((string)($filters['from_date'] ?? ''));
        $toDate = trim((string)($filters['to_date'] ?? ''));

        if (!empty($fromDate) || !empty($toDate)) {
            $dateFilter = 'custom';
        }

        if ($dateFilter === 'custom') {
            if ($fromDate !== '' && $toDate !== '') {
                $sql = " AND DATE({$column}) BETWEEN :from_date AND :to_date";
                $params['from_date'] = $fromDate;
                $params['to_date'] = $toDate;
            } elseif ($fromDate !== '') {
                $sql = " AND DATE({$column}) >= :from_date";
                $params['from_date'] = $fromDate;
            } elseif ($toDate !== '') {
                $sql = " AND DATE({$column}) <= :to_date";
                $params['to_date'] = $toDate;
            }
            return ['sql' => $sql, 'params' => $params];
        }

        switch ($dateFilter) {
            case 'today':
                $sql = " AND DATE({$column}) = CURDATE()";
                break;
            case 'this_week':
                $sql = " AND DATE({$column}) >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AND DATE({$column}) < DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 7 DAY)";
                break;
            case 'this_month':
                $sql = " AND MONTH({$column}) = MONTH(CURDATE()) AND YEAR({$column}) = YEAR(CURDATE())";
                break;
            case 'last_month':
                $sql = " AND MONTH({$column}) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR({$column}) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
                break;
            case 'this_year':
                $sql = " AND YEAR({$column}) = YEAR(CURDATE())";
                break;
            default:
                $yearValue = $yearOverride ?? date('Y');
                $sql = " AND YEAR({$column}) = :year_filter";
                $params['year_filter'] = (int)$yearValue;
                break;
        }

        return ['sql' => $sql, 'params' => $params];
    }

    private function fetchScalar($sql, array $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchColumn();
        return $result === false ? 0 : $result;
    }
}
