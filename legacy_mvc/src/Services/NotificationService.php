<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class NotificationService {
    private $db;

    public static function newInstance() {
        return new self();
    }

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function createNotification($title, $message, $type = 'system', $priority = 'medium', $entityType = null, $entityId = null, $notificationKey = null) {
        $title = trim((string)$title);
        $message = trim((string)$message);
        $type = strtolower(trim((string)$type));
        $priority = strtolower(trim((string)$priority));

        $allowedTypes = ['fee', 'payment', 'room', 'allocation', 'student', 'system'];
        $allowedPriorities = ['low', 'medium', 'high', 'critical'];

        if ($title === '' || $message === '') {
            return false;
        }

        if (!in_array($type, $allowedTypes, true)) {
            $type = 'system';
        }

        if (!in_array($priority, $allowedPriorities, true)) {
            $priority = 'medium';
        }

        $notificationKey = $this->resolveNotificationKey($notificationKey, $title, $type, $priority, $entityType, $entityId);
        $existing = $this->findByKey($notificationKey);
        if ($existing) {
            return (int)$existing['id'];
        }

        $sql = "INSERT INTO notifications (
                    title,
                    message,
                    type,
                    priority,
                    entity_type,
                    entity_id,
                    notification_key,
                    is_read,
                    created_at
                ) VALUES (
                    :title,
                    :message,
                    :type,
                    :priority,
                    :entity_type,
                    :entity_id,
                    :notification_key,
                    0,
                    NOW()
                )";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'priority' => $priority,
            'entity_type' => $entityType ? trim((string)$entityType) : null,
            'entity_id' => $entityId !== null && $entityId !== '' ? (int)$entityId : null,
            'notification_key' => $notificationKey,
        ]);

        if (!$result) {
            return false;
        }

        return (int)$this->db->lastInsertId();
    }

    public function findByKey($notificationKey) {
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE notification_key = :notification_key LIMIT 1");
        $stmt->execute(['notification_key' => trim((string)$notificationKey)]);
        return $stmt->fetch();
    }

    public function getUnreadCount() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
        return (int)$stmt->fetchColumn();
    }

    public function getRecentUnread($limit = 5) {
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', max(1, (int)$limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getNotifications(array $filters = [], $page = 1, $perPage = 20) {
        $page = max(1, (int)$page);
        $perPage = max(1, min(50, (int)$perPage));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM notifications WHERE 1 = 1";
        $params = [];

        if (!empty($filters['type'])) {
            $sql .= " AND type = :type";
            $params['type'] = strtolower(trim((string)$filters['type']));
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND priority = :priority";
            $params['priority'] = strtolower(trim((string)$filters['priority']));
        }

        if (isset($filters['read_status']) && $filters['read_status'] !== '') {
            $sql .= " AND is_read = :is_read";
            $params['is_read'] = $filters['read_status'] === 'read' ? 1 : 0;
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(created_at) >= :date_from";
            $params['date_from'] = trim((string)$filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(created_at) <= :date_to";
            $params['date_to'] = trim((string)$filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim((string)$filters['search']) . '%';
            $sql .= " AND (title LIKE :search OR message LIKE :search OR notification_key LIKE :search)";
            $params['search'] = $term;
        }

        $countSql = $sql;
        $countStmt = $this->db->prepare($countSql);
        $this->bindParams($countStmt, $params);
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $this->bindParams($stmt, $params);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, (int)ceil($total / $perPage)),
        ];
    }

    public function markAsRead($id) {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = :id AND is_read = 0");
        return $stmt->execute(['id' => (int)$id]);
    }

    public function markAllRead() {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE is_read = 0");
        return $stmt->execute();
    }

    public function generateOperationalAlerts($targetDate = null) {
        $today = $targetDate ? date('Y-m-d', strtotime($targetDate)) : date('Y-m-d');
        $daysAhead = $this->getReminderWindowDays();

        $this->generateMonthlyPendingFeeAlerts($targetDate);
        $this->generateOverdueFeeAlerts();
        $this->generateFeeDueSoonAlerts($today, $daysAhead);
        $this->generateRoomCapacityAlerts();
        $this->generateAllocationAlerts();
        $this->generateStudentStatusAlerts();
    }

    public function getDashboardAlertSummary() {
        $summary = [
            'overdue_fees' => 0,
            'due_soon' => 0,
            'rooms_nearly_full' => 0,
            'students_without_allocation' => 0,
        ];

        $summary['overdue_fees'] = (int)$this->db->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND type = 'fee' AND priority IN ('high', 'critical') AND title IN ('Overdue Fee', 'Monthly Fee Pending')")->fetchColumn();
        $summary['due_soon'] = (int)$this->db->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND type = 'fee' AND title = 'Fee Due Soon'")->fetchColumn();
        $summary['rooms_nearly_full'] = (int)$this->db->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND type = 'room' AND priority IN ('medium', 'high')")->fetchColumn();
        $summary['students_without_allocation'] = (int)$this->db->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND type = 'student' AND title = 'Student Without Allocation'")->fetchColumn();

        return $summary;
    }

    public function pruneNotifications($days = 90) {
        if ((int)$days <= 0) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM notifications WHERE is_read = 1 AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY)");
        $stmt->bindValue(':days', (int)$days, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function generateMonthlyPendingFeeAlerts($targetDate = null) {
        $dateObj = $targetDate ? new \DateTime($targetDate) : new \DateTime();
        $currentDay = (int)$dateObj->format('j');
        $billingMonth = (int)$dateObj->format('n');
        $billingYear = (int)$dateObj->format('Y');

        // Business Rule: Alerts run on or after the 10th of every month for the current billing month
        if ($currentDay < 10) {
            return [
                'executed' => false,
                'reason' => 'Monthly pending fee alerts run on the 10th of each month (current day: ' . $currentDay . ')',
                'created_count' => 0,
                'resolved_count' => 0
            ];
        }

        $createdCount = 0;
        $resolvedCount = 0;

        // Query active students only with room/bed info and current month fee records
        $sql = "
            SELECT 
                s.id AS student_id,
                s.student_id_str,
                s.full_name,
                s.status AS student_status,
                s.monthly_fee AS student_monthly_fee,
                r.room_number,
                ra.bed_number,
                fr.id AS fee_record_id,
                fr.billing_month,
                fr.billing_year,
                fr.amount,
                fr.additional_charges,
                fr.discount,
                fr.paid_amount,
                fr.status AS fee_status,
                fr.due_date,
                (COALESCE(fr.amount, 0) + COALESCE(fr.additional_charges, 0) - COALESCE(fr.discount, 0)) AS total_fee,
                (COALESCE(fr.amount, 0) + COALESCE(fr.additional_charges, 0) - COALESCE(fr.discount, 0) - COALESCE(fr.paid_amount, 0)) AS outstanding_amount
            FROM students s
            LEFT JOIN room_allocations ra ON ra.student_id = s.id AND ra.status = 'Active'
            LEFT JOIN rooms r ON r.id = ra.room_id
            LEFT JOIN fee_records fr ON fr.student_id = s.id 
                AND fr.billing_month = :billing_month 
                AND fr.billing_year = :billing_year
                AND fr.charge_type = 'MONTHLY_FEE'
            WHERE s.status = 'Active'
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'billing_month' => $billingMonth,
            'billing_year' => $billingYear
        ]);
        $rows = $stmt->fetchAll();

        $monthYearName = date('F Y', mktime(0, 0, 0, $billingMonth, 1, $billingYear));

        foreach ($rows as $row) {
            $studentId = (int)$row['student_id'];
            $notificationKey = 'monthly_fee_pending_student_' . $studentId . '_' . $billingYear . '_' . $billingMonth;
            
            $totalFee = (float)($row['total_fee'] ?? 0);
            $paidAmount = (float)($row['paid_amount'] ?? 0);
            $outstanding = (float)($row['outstanding_amount'] ?? 0);

            // If no fee record exists for this month, fallback to configured student monthly fee
            if ($row['fee_record_id'] === null) {
                $totalFee = (float)($row['student_monthly_fee'] ?? 0);
                $paidAmount = 0.0;
                $outstanding = $totalFee;
            }

            // Room / Bed formatting
            $roomBed = 'Not Allocated';
            if (!empty($row['room_number'])) {
                $roomBed = 'Room ' . $row['room_number'];
                if (!empty($row['bed_number'])) {
                    $roomBed .= ' (Bed ' . $row['bed_number'] . ')';
                }
            }

            if ($outstanding > 0) {
                $title = 'Monthly Fee Pending';
                $message = "Monthly fee for {$row['full_name']} ({$row['student_id_str']}) is pending for {$monthYearName}. [{$roomBed}] Total Fee: Rs. " . number_format($totalFee, 0) . ", Paid: Rs. " . number_format($paidAmount, 0) . ", Outstanding: Rs. " . number_format($outstanding, 0) . ".";

                $existing = $this->findByKey($notificationKey);
                if ($existing) {
                    // Update notification content to reflect latest amounts idempotently
                    $updateStmt = $this->db->prepare("
                        UPDATE notifications 
                        SET message = :message, 
                            title = :title,
                            priority = :priority,
                            entity_type = 'fee_pending',
                            entity_id = :student_id
                        WHERE notification_key = :notification_key
                    ");
                    $updateStmt->execute([
                        'message' => $message,
                        'title' => $title,
                        'priority' => (!empty($row['due_date']) && $row['due_date'] < date('Y-m-d')) ? 'high' : 'medium',
                        'student_id' => $studentId,
                        'notification_key' => $notificationKey
                    ]);
                } else {
                    $this->createNotification(
                        $title,
                        $message,
                        'fee',
                        (!empty($row['due_date']) && $row['due_date'] < date('Y-m-d')) ? 'high' : 'medium',
                        'fee_pending',
                        $studentId,
                        $notificationKey
                    );
                    $createdCount++;
                }
            } else {
                // Fully paid: if an unread notification exists for this month, resolve it
                $existing = $this->findByKey($notificationKey);
                if ($existing && (int)$existing['is_read'] === 0) {
                    $resolveStmt = $this->db->prepare("
                        UPDATE notifications 
                        SET is_read = 1, read_at = NOW() 
                        WHERE notification_key = :notification_key AND is_read = 0
                    ");
                    $resolveStmt->execute(['notification_key' => $notificationKey]);
                    $resolvedCount++;
                }
            }
        }

        return [
            'executed' => true,
            'billing_month' => $billingMonth,
            'billing_year' => $billingYear,
            'created_count' => $createdCount,
            'resolved_count' => $resolvedCount
        ];
    }

    private function generateOverdueFeeAlerts() {
        $stmt = $this->db->query("SELECT f.*, s.full_name, s.student_id_str
            FROM fee_records f
            JOIN students s ON s.id = f.student_id
            WHERE f.status IN ('Pending', 'Partial', 'Overdue')
              AND f.due_date < CURDATE()
              AND (f.amount - f.paid_amount) > 0");
        $fees = $stmt->fetchAll();

        foreach ($fees as $fee) {
            $daysLate = (int)floor((time() - strtotime($fee['due_date'])) / 86400);
            $notificationKey = 'fee_overdue_student_' . (int)$fee['student_id'] . '_' . (int)$fee['billing_year'] . '_' . (int)$fee['billing_month'];
            $this->createNotification(
                'Overdue Fee',
                $fee['full_name'] . "'s " . date('F Y', mktime(0, 0, 0, (int)$fee['billing_month'], 1, (int)$fee['billing_year'])) . ' fee is overdue by ' . max(1, $daysLate) . ' day(s).',
                'fee',
                'high',
                'fee',
                (int)$fee['id'],
                $notificationKey
            );
        }
    }

    private function generateFeeDueSoonAlerts($today, $daysAhead) {
        $threshold = date('Y-m-d', strtotime('+' . (int)$daysAhead . ' days', strtotime($today)));
        $stmt = $this->db->prepare("SELECT f.*, s.full_name, s.student_id_str
            FROM fee_records f
            JOIN students s ON s.id = f.student_id
            WHERE f.status IN ('Pending', 'Partial')
              AND f.due_date >= :today
              AND f.due_date <= :threshold
              AND (f.amount - f.paid_amount) > 0");
        $stmt->execute(['today' => $today, 'threshold' => $threshold]);
        $fees = $stmt->fetchAll();

        foreach ($fees as $fee) {
            $daysLeft = (int)floor((strtotime($fee['due_date']) - strtotime($today)) / 86400);
            $notificationKey = 'fee_due_soon_student_' . (int)$fee['student_id'] . '_' . (int)$fee['billing_year'] . '_' . (int)$fee['billing_month'];
            $this->createNotification(
                'Fee Due Soon',
                $fee['full_name'] . "'s monthly fee is due in " . max(1, $daysLeft) . ' day(s).',
                'fee',
                'medium',
                'fee',
                (int)$fee['id'],
                $notificationKey
            );
        }
    }

    private function generateRoomCapacityAlerts() {
        $stmt = $this->db->query("SELECT * FROM rooms WHERE status != 'Disabled'");
        $rooms = $stmt->fetchAll();

        foreach ($rooms as $room) {
            $remaining = (int)$room['total_beds'] - (int)$room['occupied_beds'];

            if ((int)$room['occupied_beds'] >= (int)$room['total_beds']) {
                $this->createNotification(
                    'Room Full',
                    'Room ' . $room['room_number'] . ' has reached full capacity.',
                    'room',
                    'high',
                    'room',
                    (int)$room['id'],
                    'room_full_' . (int)$room['id']
                );
            }

            if ($remaining <= 1 && $remaining >= 0) {
                $this->createNotification(
                    'Low Bed Availability',
                    'Room ' . $room['room_number'] . ' has only ' . $remaining . ' bed(s) remaining.',
                    'room',
                    'medium',
                    'room',
                    (int)$room['id'],
                    'room_low_availability_' . (int)$room['id']
                );
            }

            if ((int)$room['occupied_beds'] > (int)$room['total_beds']) {
                $this->createNotification(
                    'Room Capacity Mismatch',
                    'Room ' . $room['room_number'] . ' has occupancy data that exceeds the configured capacity.',
                    'room',
                    'critical',
                    'room',
                    (int)$room['id'],
                    'room_capacity_mismatch_' . (int)$room['id']
                );
            }
        }
    }

    private function generateAllocationAlerts() {
        $stmt = $this->db->query("SELECT s.id, s.full_name, s.student_id_str
            FROM students s
            LEFT JOIN room_allocations a ON a.student_id = s.id AND a.status = 'Active'
            WHERE s.status = 'Active' AND a.id IS NULL");
        $students = $stmt->fetchAll();

        foreach ($students as $student) {
            $this->createNotification(
                'Student Without Allocation',
                $student['full_name'] . ' does not currently have an active room allocation.',
                'student',
                'medium',
                'student',
                (int)$student['id'],
                'student_without_allocation_' . (int)$student['id']
            );
        }
    }

    private function generateStudentStatusAlerts() {
        $stmt = $this->db->query("SELECT * FROM students WHERE status = 'Inactive'");
        $students = $stmt->fetchAll();

        foreach ($students as $student) {
            $this->createNotification(
                'Student Inactive',
                $student['full_name'] . ' is currently marked inactive and may require review.',
                'student',
                'medium',
                'student',
                (int)$student['id'],
                'student_inactive_' . (int)$student['id']
            );
        }
    }

    private function getReminderWindowDays() {
        $config = require APP_ROOT . '/config/app.php';
        return (int)($config['notification_reminder_days'] ?? 3);
    }

    private function bindParams($stmt, array $params) {
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
                continue;
            }

            if (is_bool($value)) {
                $stmt->bindValue(':' . $key, $value ? 1 : 0, PDO::PARAM_INT);
                continue;
            }

            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
    }

    private function resolveNotificationKey($notificationKey, $title, $type, $priority, $entityType, $entityId) {
        if (!empty($notificationKey)) {
            return trim((string)$notificationKey);
        }

        $seed = strtolower(trim((string)$title)) . '|' . strtolower(trim((string)$type)) . '|' . strtolower(trim((string)$priority));
        if ($entityType !== null) {
            $seed .= '|' . strtolower(trim((string)$entityType));
        }
        if ($entityId !== null && $entityId !== '') {
            $seed .= '|' . (int)$entityId;
        }

        return 'generated_' . md5($seed);
    }
}
