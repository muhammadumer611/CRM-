<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class ReservationRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll($filters = [], $limit = 20, $offset = 0) {
        $query = "
            SELECT r.*, rm.room_number, rm.block, rm.floor, rm.room_type
            FROM reservations r
            JOIN rooms rm ON rm.id = r.room_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['status'])) {
            $query .= " AND r.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim((string)$filters['search']) . '%';
            $query .= " AND (r.full_name LIKE ? OR r.cnic LIKE ? OR r.phone LIKE ? OR rm.room_number LIKE ?)";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $query .= " ORDER BY r.reservation_date DESC, r.id DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function count($filters = []) {
        $query = "SELECT COUNT(*) FROM reservations r JOIN rooms rm ON rm.id = r.room_id WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $query .= " AND r.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim((string)$filters['search']) . '%';
            $query .= " AND (r.full_name LIKE ? OR r.cnic LIKE ? OR r.phone LIKE ? OR rm.room_number LIKE ?)";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT r.*, rm.room_number, rm.block, rm.floor, rm.room_type FROM reservations r JOIN rooms rm ON rm.id = r.room_id WHERE r.id = ? LIMIT 1");
        $stmt->execute([(int)$id]);
        return $stmt->fetch();
    }

    public function getActiveBedNumbersForRoom($roomId) {
        $stmt = $this->db->prepare("SELECT DISTINCT bed_number FROM reservations WHERE room_id = ? AND status IN ('PENDING', 'CONFIRMED') ORDER BY bed_number ASC");
        $stmt->execute([(int)$roomId]);
        $beds = [];
        foreach ($stmt->fetchAll() as $row) {
            $beds[] = (int)$row['bed_number'];
        }
        return $beds;
    }

    public function isBedAvailable($roomId, $bedNumber, $excludeReservationId = null) {
        $stmt = $this->db->prepare("SELECT id FROM reservations WHERE room_id = ? AND bed_number = ? AND status IN ('PENDING', 'CONFIRMED') AND (? IS NULL OR id != ?) LIMIT 1");
        $stmt->execute([(int)$roomId, (int)$bedNumber, $excludeReservationId, $excludeReservationId]);
        if ($stmt->fetch()) {
            return false;
        }

        $allocStmt = $this->db->prepare("SELECT id FROM room_allocations WHERE room_id = ? AND bed_number = ? AND status = 'Active' LIMIT 1");
        $allocStmt->execute([(int)$roomId, (int)$bedNumber]);
        return !$allocStmt->fetch();
    }

    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO reservations (
                full_name, cnic, phone, district, room_id, bed_number, reservation_amount,
                reservation_date, expected_arrival_date, status, notes, reserved_by_name
            ) VALUES (
                :full_name, :cnic, :phone, :district, :room_id, :bed_number, :reservation_amount,
                :reservation_date, :expected_arrival_date, :status, :notes, :reserved_by_name
            )
        ");
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE reservations SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$status, (int)$id]);
    }

    public function countActiveReservationsForRoom($roomId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM reservations WHERE room_id = ? AND status IN ('PENDING', 'CONFIRMED')");
        $stmt->execute([(int)$roomId]);
        return (int)$stmt->fetchColumn();
    }

    public function getReservationPaymentHistory($reservationId) {
        $stmt = $this->db->prepare("SELECT * FROM reservation_payments WHERE reservation_id = ? ORDER BY payment_date DESC, id DESC");
        $stmt->execute([(int)$reservationId]);
        return $stmt->fetchAll();
    }

    public function addPayment($data) {
        $stmt = $this->db->prepare("
            INSERT INTO reservation_payments (
                reservation_id, amount, payment_date, payment_method, transaction_ref, notes, created_by_admin
            ) VALUES (
                :reservation_id, :amount, :payment_date, :payment_method, :transaction_ref, :notes, :created_by_admin
            )");
        return $stmt->execute($data);
    }
}
