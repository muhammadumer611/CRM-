<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Repositories\ReservationRepository;
use App\Repositories\AdminRepository;
use Exception;

class ReservationService {
    private $db;
    private $repo;
    private $adminRepo;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->repo = new ReservationRepository();
        $this->adminRepo = new AdminRepository();
    }

    public function getAllReservations($filters = [], $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        return [
            'data' => $this->repo->findAll($filters, $perPage, $offset),
            'total' => $this->repo->count($filters),
        ];
    }

    public function getReservation($id) {
        $reservation = $this->repo->findById((int)$id);
        if (!$reservation) {
            return null;
        }

        $reservation['payments'] = $this->repo->getReservationPaymentHistory((int)$id);
        return $reservation;
    }

    public function createReservation(array $data) {
        $fullName = trim((string)($data['full_name'] ?? ''));
        $cnic = preg_replace('/[^0-9]/', '', (string)($data['cnic'] ?? ''));
        $phone = trim((string)($data['phone'] ?? ''));
        $district = trim((string)($data['district'] ?? ''));
        $roomId = (int)($data['room_id'] ?? 0);
        $bedNumber = (int)($data['bed_number'] ?? 0);
        $reservationAmount = isset($data['reservation_amount']) && $data['reservation_amount'] !== '' ? (float)$data['reservation_amount'] : 0.0;
        $reservationDate = !empty($data['reservation_date']) ? $data['reservation_date'] : date('Y-m-d');
        $expectedArrivalDate = !empty($data['expected_arrival_date']) ? $data['expected_arrival_date'] : $reservationDate;
        $status = strtoupper(trim((string)($data['status'] ?? 'PENDING')));
        $notes = trim((string)($data['notes'] ?? ''));
        $reservedByName = trim((string)($data['reserved_by_name'] ?? ''));

        if ($fullName === '' || $cnic === '' || $phone === '' || $district === '' || $roomId <= 0 || $bedNumber <= 0) {
            return ['success' => false, 'error' => 'Please fill in all required reservation fields.'];
        }

        if ($reservedByName === '') {
            return ['success' => false, 'error' => 'Reserved By is required. Please enter the staff member name.'];
        }

        if (strlen($cnic) !== 13) {
            return ['success' => false, 'error' => 'Please enter a valid 13-digit CNIC.'];
        }

        if ($reservationAmount < 0) {
            return ['success' => false, 'error' => 'Reservation amount cannot be negative.'];
        }

        $this->db->beginTransaction();
        try {
            $roomStmt = $this->db->prepare("SELECT * FROM rooms WHERE id = ? FOR UPDATE");
            $roomStmt->execute([$roomId]);
            $room = $roomStmt->fetch();
            if (!$room) {
                throw new Exception('Selected room was not found.');
            }
            if (($room['status'] ?? '') === 'Disabled') {
                throw new Exception('This room is disabled.');
            }
            if ($bedNumber < 1 || $bedNumber > (int)$room['total_beds']) {
                throw new Exception('Selected bed is invalid for this room.');
            }

            $existingStudentStmt = $this->db->prepare("SELECT id FROM students WHERE cnic = ? AND status = 'Active' LIMIT 1 FOR UPDATE");
            $existingStudentStmt->execute([$cnic]);
            if ($existingStudentStmt->fetch()) {
                throw new Exception('A student with this CNIC already exists in the active student list. Please review the existing student before creating a reservation.');
            }

            $duplicateStmt = $this->db->prepare("SELECT id FROM reservations WHERE room_id = ? AND bed_number = ? AND status IN ('PENDING', 'CONFIRMED') FOR UPDATE");
            $duplicateStmt->execute([$roomId, $bedNumber]);
            if ($duplicateStmt->fetch()) {
                throw new Exception('This room and bed already has an active reservation.');
            }

            $allocStmt = $this->db->prepare("SELECT id FROM room_allocations WHERE room_id = ? AND bed_number = ? AND status = 'Active' FOR UPDATE");
            $allocStmt->execute([$roomId, $bedNumber]);
            if ($allocStmt->fetch()) {
                throw new Exception('This bed is already occupied by an active student and cannot be reserved.');
            }

            if (!in_array($status, ['PENDING', 'CONFIRMED'], true)) {
                throw new Exception('Invalid reservation status provided.');
            }

            $reservationId = $this->repo->create([
                'full_name' => $fullName,
                'cnic' => $cnic,
                'phone' => $phone,
                'district' => $district,
                'room_id' => $roomId,
                'bed_number' => $bedNumber,
                'reservation_amount' => number_format((float)$reservationAmount, 2, '.', ''),
                'reservation_date' => $reservationDate,
                'expected_arrival_date' => $expectedArrivalDate,
                'status' => $status,
                'notes' => $notes,
                'reserved_by_name' => $reservedByName,
            ]);

            if ($reservationAmount > 0) {
                $paymentStmt = $this->db->prepare("INSERT INTO reservation_payments (reservation_id, amount, payment_date, payment_method, transaction_ref, notes, created_by_admin) VALUES (?, ?, ?, 'Cash', ?, ?, ?)");
                $transactionRef = 'RES-' . $reservationId . '-' . date('YmdHis');
                $paymentStmt->execute([$reservationId, number_format((float)$reservationAmount, 2, '.', ''), $reservationDate, $transactionRef, 'Reservation booking payment', (int)(Session::get('admin_id') ?? 0)]);
            }

            $this->db->commit();
            return ['success' => true, 'reservation_id' => $reservationId, 'message' => 'Reservation created successfully.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateReservation($id, array $data) {
        $reservation = $this->repo->findById((int)$id);
        if (!$reservation) {
            return ['success' => false, 'error' => 'Reservation not found.'];
        }

        $status = strtoupper(trim((string)($data['status'] ?? $reservation['status'])));

        $allowedTransitions = [
            'PENDING' => ['PENDING', 'CONFIRMED', 'CANCELLED'],
            'CONFIRMED' => ['CONFIRMED', 'CANCELLED'],
            'ARRIVED' => ['ARRIVED'],
            'CANCELLED' => ['CANCELLED'],
            'EXPIRED' => ['EXPIRED'],
        ];
        if (!in_array($status, $allowedTransitions[$reservation['status']] ?? [], true)) {
            return ['success' => false, 'error' => 'Invalid reservation status transition.'];
        }

        $this->db->beginTransaction();
        try {
            $existing = $this->repo->findById((int)$id);
            if (!$existing) {
                throw new Exception('Reservation not found.');
            }

            if ($status === 'CANCELLED') {
                $releaseStmt = $this->db->prepare("UPDATE reservations SET status = 'CANCELLED', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $releaseStmt->execute([(int)$id]);
            } else {
                $updateStmt = $this->db->prepare("UPDATE reservations SET status = ?, full_name = ?, cnic = ?, phone = ?, district = ?, room_id = ?, bed_number = ?, reservation_amount = ?, expected_arrival_date = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $updateStmt->execute([
                    $status,
                    trim((string)($data['full_name'] ?? $reservation['full_name'])),
                    preg_replace('/[^0-9]/', '', (string)($data['cnic'] ?? $reservation['cnic'])),
                    trim((string)($data['phone'] ?? $reservation['phone'])),
                    trim((string)($data['district'] ?? $reservation['district'])),
                    (int)($data['room_id'] ?? $reservation['room_id']),
                    (int)($data['bed_number'] ?? $reservation['bed_number']),
                    (float)($data['reservation_amount'] ?? $reservation['reservation_amount']),
                    !empty($data['expected_arrival_date']) ? $data['expected_arrival_date'] : $reservation['expected_arrival_date'],
                    trim((string)($data['notes'] ?? $reservation['notes'])),
                    (int)$id,
                ]);
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Reservation updated successfully.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function confirmReservation($id) {
        $reservation = $this->getReservation($id);
        if (!$reservation) {
            return ['success' => false, 'error' => 'Reservation not found.'];
        }

        if ($reservation['status'] !== 'PENDING') {
            return ['success' => false, 'error' => 'Only pending reservations can be confirmed.'];
        }

        $this->db->beginTransaction();
        try {
            $lock = $this->db->prepare("SELECT status FROM reservations WHERE id = ? FOR UPDATE");
            $lock->execute([(int)$id]);
            if (($lock->fetchColumn() ?? '') !== 'PENDING') {
                throw new Exception('Reservation is no longer pending.');
            }
            $stmt = $this->db->prepare("UPDATE reservations SET status = 'CONFIRMED', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([(int)$id]);
            $this->db->commit();
            return ['success' => true, 'message' => 'Reservation confirmed.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function convertReservationToStudent($reservationId, array $studentPayload) {
        $reservation = $this->getReservation((int)$reservationId);
        if (!$reservation) {
            return ['success' => false, 'error' => 'Reservation not found.'];
        }

        if (!in_array($reservation['status'], ['PENDING', 'CONFIRMED'], true)) {
            return ['success' => false, 'error' => 'Only pending or confirmed reservations can be converted to a student.'];
        }

        $cleanCnic = preg_replace('/[^0-9]/', '', (string)($studentPayload['cnic'] ?? $reservation['cnic']));
        if (strlen($cleanCnic) !== 13) {
            return ['success' => false, 'error' => 'A valid CNIC is required before conversion.'];
        }

        $this->db->beginTransaction();
        try {
            $reservationStmt = $this->db->prepare("SELECT * FROM reservations WHERE id = ? FOR UPDATE");
            $reservationStmt->execute([(int)$reservationId]);
            $reservation = $reservationStmt->fetch();
            if (!$reservation || !in_array($reservation['status'], ['PENDING', 'CONFIRMED'], true)) {
                throw new Exception('Only active reservations can be converted.');
            }

            $convertedByName = trim((string)($studentPayload['converted_by_name'] ?? ''));
            if ($convertedByName === '') {
                throw new Exception('Converted By is required. Please enter the staff member who converted the reservation.');
            }

            $existingStudentStmt = $this->db->prepare("SELECT id, full_name FROM students WHERE cnic = ? AND status = 'Active' LIMIT 1 FOR UPDATE");
            $existingStudentStmt->execute([$cleanCnic]);
            $existing = $existingStudentStmt->fetch();
            if ($existing) {
                throw new Exception('This person already exists as an active student. Please review student: ' . $existing['full_name'] . '.');
            }

            $roomStmt = $this->db->prepare("SELECT * FROM rooms WHERE id = ? FOR UPDATE");
            $roomStmt->execute([(int)$reservation['room_id']]);
            $room = $roomStmt->fetch();
            if (!$room) {
                throw new Exception('The reservation room was not found.');
            }

            $bedStmt = $this->db->prepare("SELECT id FROM room_allocations WHERE room_id = ? AND bed_number = ? AND status = 'Active' FOR UPDATE");
            $bedStmt->execute([(int)$reservation['room_id'], (int)$reservation['bed_number']]);
            if ($bedStmt->fetch()) {
                throw new Exception('The reserved bed is no longer available. Another allocation has taken it.');
            }

            $reservationStmt = $this->db->prepare("SELECT * FROM reservations WHERE id = ? FOR UPDATE");
            $reservationStmt->execute([(int)$reservationId]);
            $reservationLocked = $reservationStmt->fetch();
            if (!$reservationLocked) {
                throw new Exception('Reservation could not be locked for conversion.');
            }

            $studentService = new StudentService();
            $payload = [
                'accommodation_type' => 'single',
                'full_name' => trim((string)($studentPayload['full_name'] ?? $reservation['full_name'])),
                'cnic' => $cleanCnic,
                'phone' => trim((string)($studentPayload['phone'] ?? $reservation['phone'])),
                'address' => trim((string)($studentPayload['address'] ?? '')),
                'guardian_name' => trim((string)($studentPayload['guardian_name'] ?? '')),
                'guardian_phone' => trim((string)($studentPayload['guardian_phone'] ?? '')),
                'guardian_cnic' => preg_replace('/[^0-9]/', '', (string)($studentPayload['guardian_cnic'] ?? '')),
                'relation' => trim((string)($studentPayload['relation'] ?? 'Father')),
                'room_id' => (int)$reservation['room_id'],
                'bed_number' => (int)$reservation['bed_number'],
                'joining_date' => !empty($studentPayload['joining_date']) ? $studentPayload['joining_date'] : date('Y-m-d'),
                'monthly_fee' => !empty($studentPayload['monthly_fee']) ? (float)$studentPayload['monthly_fee'] : (float)$room['monthly_fee'],
                'security_deposit' => !empty($studentPayload['security_deposit']) ? (float)$studentPayload['security_deposit'] : 0.0,
                'added_by_name' => $convertedByName,
                'exclude_reservation_id' => (int)$reservationId,
            ];

            $studentResult = $studentService->onboardSinglePerson($payload);
            if (!$studentResult['success']) {
                throw new Exception($studentResult['error']);
            }

            $reservUpdate = $this->db->prepare("UPDATE reservations SET status = 'ARRIVED', converted_student_id = ?, converted_by_name = ?, converted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND status IN ('PENDING', 'CONFIRMED')");
            $reservUpdate->execute([(int)$studentResult['id'], $convertedByName, (int)$reservationId]);

            $this->db->commit();
            return ['success' => true, 'student_id' => $studentResult['id'], 'message' => 'Reservation converted to active student.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function cancelReservation($id, array $data = []) {
        $reservation = $this->getReservation((int)$id);
        if (!$reservation) {
            return ['success' => false, 'error' => 'Reservation not found.'];
        }

        if (in_array($reservation['status'], ['CANCELLED', 'EXPIRED'], true)) {
            return ['success' => false, 'error' => 'This reservation is already cancelled or expired.'];
        }

        $cancelledByName = trim((string)($data['cancelled_by_name'] ?? ''));
        if ($cancelledByName === '') {
            return ['success' => false, 'error' => 'Cancelled By is required. Please enter the staff member name.'];
        }

        $this->db->beginTransaction();
        try {
            $lock = $this->db->prepare("SELECT status FROM reservations WHERE id = ? FOR UPDATE");
            $lock->execute([(int)$id]);
            $lockedStatus = $lock->fetchColumn();
            if (!$lockedStatus || in_array($lockedStatus, ['CANCELLED', 'EXPIRED', 'ARRIVED'], true)) {
                throw new Exception('This reservation cannot be cancelled in its current state.');
            }
            $stmt = $this->db->prepare("UPDATE reservations SET status = 'CANCELLED', cancelled_by_name = ?, cancelled_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$cancelledByName, (int)$id]);
            $this->db->commit();
            return ['success' => true, 'message' => 'Reservation cancelled successfully.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getReservationSummary() {
        $stmt = $this->db->query("SELECT status, COUNT(*) AS total FROM reservations GROUP BY status ORDER BY status");
        return $stmt->fetchAll();
    }
}
