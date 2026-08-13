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

    public function generateOperationalAlerts() {
        $today = date('Y-m-d');
        $daysAhead = $this->getReminderWindowDays();

        $this->generatePendingFeeAlerts();
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

        $summary['overdue_fees'] = (int)$this->db->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND type = 'fee' AND priority IN ('high', 'critical') AND title = 'Overdue Fee'")->fetchColumn();
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

    private function generatePendingFeeAlerts() {
        $stmt = $this->db->query("SELECT f.*, s.full_name, s.student_id_str
            FROM fee_records f
            JOIN students s ON s.id = f.student_id
            WHERE f.status IN ('Pending', 'Partial')");
        $fees = $stmt->fetchAll();

        foreach ($fees as $fee) {
            $notificationKey = 'fee_pending_student_' . (int)$fee['student_id'] . '_' . (int)$fee['billing_year'] . '_' . (int)$fee['billing_month'];
            $this->createNotification(
                'Fee Pending',
                $fee['full_name'] . "'s monthly fee for " . date('F Y', mktime(0, 0, 0, (int)$fee['billing_month'], 1, (int)$fee['billing_year'])) . ' is still pending.',
                'fee',
                'medium',
                'fee',
                (int)$fee['id'],
                $notificationKey
            );
        }
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
