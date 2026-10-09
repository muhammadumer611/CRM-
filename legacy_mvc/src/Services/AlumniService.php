<?php
namespace App\Services;

use App\Repositories\AlumniRepository;
use App\Repositories\StudentRepository;
use App\Repositories\FeeRepository;
use App\Core\Database;
use App\Core\Session;
use App\Services\StudentHistoryService;
use Exception;
use PDO;

class AlumniService {
    private $alumniRepo;
    private $studentRepo;
    private $feeRepo;
    private $adminRepo;
    private $db;

    public function __construct() {
        $this->alumniRepo = new AlumniRepository();
        $this->studentRepo = new StudentRepository();
        $this->feeRepo = new FeeRepository();
        $this->adminRepo = new \App\Repositories\AdminRepository();
        $this->db = Database::getInstance()->getConnection();
    }

    public function getReactivationFormData(int $alumniId): array {
        $alumni = $this->alumniRepo->findById($alumniId);
        if (!$alumni) {
            throw new Exception('Alumni record not found.');
        }

        $studentStmt = $this->db->prepare('SELECT * FROM students WHERE student_id_str = ?');
        $studentStmt->execute([$alumni['original_student_id']]);
        $student = $studentStmt->fetch();
        if (!$student || $student['status'] !== 'Inactive') {
            throw new Exception('Only inactive Alumni students can be re-admitted.');
        }

        $allocationStmt = $this->db->prepare("
            SELECT ra.*, r.room_number, r.block
            FROM room_allocations ra
            JOIN rooms r ON r.id = ra.room_id
            WHERE ra.student_id = ?
            ORDER BY ra.id DESC
            LIMIT 1
        ");
        $allocationStmt->execute([(int)$student['id']]);
        $previousStay = $allocationStmt->fetch() ?: null;
        if (!$previousStay || $previousStay['status'] !== 'Closed') {
            throw new Exception('The previous closed stay could not be found.');
        }

        $activeStmt = $this->db->prepare("SELECT id FROM room_allocations WHERE student_id = ? AND status = 'Active' LIMIT 1");
        $activeStmt->execute([(int)$student['id']]);
        if ($activeStmt->fetch()) {
            throw new Exception('This student already has an active room allocation.');
        }

        $securityStmt = $this->db->prepare('SELECT * FROM security_deposits WHERE student_id = ? ORDER BY id DESC LIMIT 1');
        $securityStmt->execute([(int)$student['id']]);
        $securityDeposit = $securityStmt->fetch() ?: null;

        $roomRepo = new \App\Repositories\RoomRepository();
        $rooms = $roomRepo->findAvailableRoomsForAdmission('BED');
        $billingSettings = (new BillingService($this->db))->settings();

        return [
            'alumni' => $alumni,
            'student' => $student,
            'previous_stay' => $previousStay,
            'security_deposit' => $securityDeposit,
            'rooms' => $rooms,
            'billing_settings' => $billingSettings
        ];
    }

    public function reactivateAlumni(int $alumniId, array $data): array {
        $roomId = filter_var($data['room_id'] ?? null, FILTER_VALIDATE_INT);
        $bedNumber = filter_var($data['bed_number'] ?? null, FILTER_VALIDATE_INT);
        $joiningDate = is_string($data['joining_date'] ?? null) ? trim($data['joining_date']) : '';
        $remarks = is_string($data['remarks'] ?? null) ? trim($data['remarks']) : '';
        $allocatedByName = is_string($data['allocated_by_name'] ?? null) ? trim($data['allocated_by_name']) : '';
        $billingMode = is_string($data['first_month_billing_mode'] ?? null)
            ? strtolower(trim($data['first_month_billing_mode']))
            : 'full';
        $discountInput = $data['first_month_discount'] ?? 0;
        $discountReason = is_string($data['first_month_discount_reason'] ?? null)
            ? trim($data['first_month_discount_reason'])
            : '';

        if (!$roomId || !$bedNumber || $bedNumber < 1) {
            return ['success' => false, 'error' => 'Select a valid room and bed.'];
        }
        if ($allocatedByName === '') {
            return ['success' => false, 'error' => 'Processed By is required.'];
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $joiningDate);
        $dateErrors = \DateTimeImmutable::getLastErrors();
        if (!$date || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $date->format('Y-m-d') !== $joiningDate) {
            return ['success' => false, 'error' => 'Enter a valid return date.'];
        }
        if ($joiningDate > date('Y-m-d')) {
            return ['success' => false, 'error' => 'Return date cannot be in the future.'];
        }
        if (!in_array($billingMode, ['full', 'manual'], true)) {
            return ['success' => false, 'error' => 'Select a valid first-month billing option.'];
        }
        if (!is_scalar($discountInput) || !is_numeric($discountInput) || !is_finite((float)$discountInput)) {
            return ['success' => false, 'error' => 'First-month adjustment must be a valid amount.'];
        }
        $discount = (float)$discountInput;
        if ($discount < 0 || abs($discount - round($discount, 2)) > 0.000001) {
            return ['success' => false, 'error' => 'First-month adjustment must be non-negative and have at most two decimal places.'];
        }

        try {
            $this->db->beginTransaction();

            $alumniStmt = $this->db->prepare('SELECT * FROM alumni WHERE id = ? FOR UPDATE');
            $alumniStmt->execute([$alumniId]);
            $alumni = $alumniStmt->fetch();
            if (!$alumni) {
                throw new Exception('Alumni record not found.');
            }

            $studentStmt = $this->db->prepare('SELECT * FROM students WHERE student_id_str = ? FOR UPDATE');
            $studentStmt->execute([$alumni['original_student_id']]);
            $student = $studentStmt->fetch();
            if (!$student || $student['status'] !== 'Inactive') {
                throw new Exception('Only inactive Alumni students can be re-admitted.');
            }

            $activeAllocationStmt = $this->db->prepare("SELECT id FROM room_allocations WHERE student_id = ? AND status = 'Active' FOR UPDATE");
            $activeAllocationStmt->execute([(int)$student['id']]);
            if ($activeAllocationStmt->fetch()) {
                throw new Exception('This student already has an active room allocation.');
            }

            $previousStayStmt = $this->db->prepare("SELECT * FROM room_allocations WHERE student_id = ? ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $previousStayStmt->execute([(int)$student['id']]);
            $previousStay = $previousStayStmt->fetch();
            if (!$previousStay || $previousStay['status'] !== 'Closed' || empty($previousStay['leaving_date'])) {
                throw new Exception('A completed previous stay with a checkout date is required before re-admission.');
            }
            if ($joiningDate < $previousStay['leaving_date']) {
                throw new Exception('Return date cannot be earlier than the previous checkout date.');
            }

            $roomStmt = $this->db->prepare('SELECT * FROM rooms WHERE id = ? FOR UPDATE');
            $roomStmt->execute([$roomId]);
            $room = $roomStmt->fetch();
            if (!$room || $room['status'] === 'Disabled') {
                throw new Exception('Selected room is not available for allocation.');
            }
            $totalBeds = (int)$room['total_beds'];
            if ($bedNumber > $totalBeds) {
                throw new Exception('Selected bed does not exist in this room.');
            }

            $bedStmt = $this->db->prepare("
                SELECT id FROM room_allocations
                WHERE room_id = ? AND (bed_number = ? OR bed_number = 0) AND status = 'Active'
                FOR UPDATE
            ");
            $bedStmt->execute([$roomId, $bedNumber]);
            if ($bedStmt->fetch()) {
                throw new Exception('Selected bed is already occupied.');
            }

            $reservationStmt = $this->db->prepare("
                SELECT id FROM reservations
                WHERE room_id = ? AND bed_number = ? AND status IN ('PENDING', 'CONFIRMED')
                FOR UPDATE
            ");
            $reservationStmt->execute([$roomId, $bedNumber]);
            if ($reservationStmt->fetch()) {
                throw new Exception('Selected bed is reserved and cannot be allocated.');
            }

            $countStmt = $this->db->prepare("
                SELECT COALESCE(SUM(CASE WHEN bed_number = 0 THEN ? ELSE 1 END), 0)
                FROM room_allocations WHERE room_id = ? AND status = 'Active'
            ");
            $countStmt->execute([$totalBeds, $roomId]);
            $occupiedBefore = (int)$countStmt->fetchColumn();
            if ($occupiedBefore >= $totalBeds) {
                throw new Exception('Selected room has no available beds.');
            }
            $monthlyFee = round((float)$room['monthly_fee'], 2);
            if ($monthlyFee <= 0) {
                throw new Exception('Selected room does not have a valid monthly fee.');
            }

            $billingService = new BillingService($this->db);
            $billingInput = [
                'first_month_billing_mode' => $billingMode,
                'first_month_discount' => $discount,
                'first_month_discount_reason' => $discountReason,
            ];
            $billingCalculation = $billingService->calculate($monthlyFee, $joiningDate, $billingInput);
            $invoiceStmt = $this->db->prepare("
                SELECT id FROM fee_records
                WHERE student_id = ? AND charge_type = 'MONTHLY_FEE'
                  AND billing_month = ? AND billing_year = ?
                LIMIT 1 FOR UPDATE
            ");
            $invoiceStmt->execute([
                (int)$student['id'],
                $billingCalculation['billing_month'],
                $billingCalculation['billing_year']
            ]);
            $existingInvoice = $invoiceStmt->fetch();

            $allocationStmt = $this->db->prepare("
                INSERT INTO room_allocations
                    (student_id, room_id, bed_number, joining_date, status, remarks, allocated_by_name)
                VALUES (?, ?, ?, ?, 'Active', ?, ?)
            ");
            $allocationStmt->execute([
                (int)$student['id'],
                (int)$roomId,
                (int)$bedNumber,
                $joiningDate,
                $remarks !== '' ? $remarks : 'Student re-admission',
                $allocatedByName
            ]);
            $allocationId = (int)$this->db->lastInsertId();

            $newOccupied = $occupiedBefore + 1;
            $roomStatus = $newOccupied >= $totalBeds ? 'Occupied' : 'Partially Occupied';
            $roomUpdate = $this->db->prepare('UPDATE rooms SET occupied_beds = ?, status = ? WHERE id = ?');
            $roomUpdate->execute([$newOccupied, $roomStatus, (int)$roomId]);

            $studentUpdate = $this->db->prepare("
                UPDATE students SET status = 'Active', monthly_fee = ?
                WHERE id = ? AND status = 'Inactive'
            ");
            $studentUpdate->execute([$monthlyFee, (int)$student['id']]);
            if ($studentUpdate->rowCount() !== 1) {
                throw new Exception('Student status changed during re-admission. Please reload and retry.');
            }

            if ($existingInvoice) {
                $invoiceId = (int)$existingInvoice['id'];
                $invoiceCreated = false;
            } else {
                $invoice = $billingService->createFirstMonthInvoice(
                    (int)$student['id'],
                    $monthlyFee,
                    $joiningDate,
                    $billingInput + [
                        'created_by_admin' => Session::get('admin_id'),
                        'remarks' => 'First monthly fee after student re-admission'
                    ]
                );
                $invoiceId = (int)$invoice['id'];
                $invoiceCreated = true;
            }

            $roomName = $room['block'] . '-' . $room['room_number'];
            StudentHistoryService::record(
                (int)$student['id'],
                'STUDENT_READMITTED',
                "Student {$student['student_id_str']} was re-admitted to Room {$roomName}, Bed {$bedNumber} effective {$joiningDate}.",
                ['status' => 'Inactive', 'previous_allocation_id' => (int)$previousStay['id'], 'previous_checkout_date' => $previousStay['leaving_date']],
                [
                    'status' => 'Active',
                    'allocation_id' => $allocationId,
                    'room' => $roomName,
                    'bed' => (int)$bedNumber,
                    'joining_date' => $joiningDate,
                    'monthly_fee' => $monthlyFee,
                    'first_month_invoice_id' => $invoiceId,
                    'first_month_invoice_created' => $invoiceCreated,
                    'security_deposit_unchanged' => true
                ],
                Session::get('admin_id'),
                $this->db
            );

            $this->db->commit();
            return [
                'success' => true,
                'allocation_id' => $allocationId,
                'invoice_id' => $invoiceId,
                'invoice_created' => $invoiceCreated,
                'room' => $roomName,
                'bed' => (int)$bedNumber,
                'joining_date' => $joiningDate,
                'monthly_fee' => $monthlyFee,
                'billing' => $billingCalculation
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->adminRepo->logAction(Session::get('admin_id'), 'Error', 'Student re-admission failed for Alumni record ' . $alumniId . ': ' . $e->getMessage(), $ip);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function convertToAlumni($studentId, $leavingDate, $leavingReason, $remarks = '', $checkedOutByName = '', $processedByName = '', $securityOption = 'KEEP_FOR_LATER', $securityDeduction = 0, $securityRemarks = '') {
        if (!is_scalar($studentId) || !is_scalar($leavingDate) || !is_scalar($leavingReason) || !is_scalar($remarks)
            || !is_scalar($checkedOutByName) || !is_scalar($processedByName) || !is_scalar($securityOption) || !is_scalar($securityRemarks)) {
            return ['success' => false, 'error' => 'Checkout details contain invalid values.'];
        }
        $checkedOutByName = trim((string)$checkedOutByName);
        $processedByName = trim((string)$processedByName);
        if ($checkedOutByName === '' || $processedByName === '') {
            return ['success' => false, 'error' => 'Checked Out By and Processed By are required.'];
        }
        $leavingDate = trim((string)$leavingDate);
        $leavingReason = trim((string)$leavingReason);
        $remarks = trim((string)$remarks);
        $securityOption = strtoupper(trim((string)$securityOption));
        if (!in_array($securityOption, ['RETURN_NOW', 'KEEP_FOR_LATER'], true)) {
            return ['success' => false, 'error' => 'Select whether to return security now or keep it for later.'];
        }
        if (!is_scalar($securityDeduction) || !is_numeric($securityDeduction) || !is_finite((float)$securityDeduction)) {
            return ['success' => false, 'error' => 'Security deduction must be a valid amount.'];
        }
        $securityDeduction = (float)$securityDeduction;
        if (abs($securityDeduction - round($securityDeduction, 2)) > 0.000001 || $securityDeduction < 0) {
            return ['success' => false, 'error' => 'Security deduction must be a non-negative amount with no more than two decimal places.'];
        }
        $securityRemarks = trim((string)$securityRemarks);
        if ($securityOption === 'KEEP_FOR_LATER' && ($securityDeduction > 0 || $securityRemarks !== '')) {
            return ['success' => false, 'error' => 'Security settlement details are not allowed when security is kept for later.'];
        }
        if ($securityOption === 'RETURN_NOW' && $securityDeduction > 0 && $securityRemarks === '') {
            return ['success' => false, 'error' => 'Reason / Remarks are required when a security deduction is entered.'];
        }

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("SELECT * FROM students WHERE id = ? FOR UPDATE");
            $stmt->execute([$studentId]);
            $student = $stmt->fetch();

            if (!$student) {
                throw new Exception("Student not found.");
            }
            if ($student['status'] !== 'Active') {
                throw new Exception("Only active students can be converted to alumni.");
            }

            $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM alumni WHERE original_student_id = ? FOR UPDATE");
            $stmtCheck->execute([$student['student_id_str']]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                throw new Exception("Alumni record already exists for this student.");
            }

            $stmtAlloc = $this->db->prepare("SELECT * FROM room_allocations WHERE student_id = ? AND status = 'Active' FOR UPDATE");
            $stmtAlloc->execute([$studentId]);
            $alloc = $stmtAlloc->fetch();
            if (!$alloc) {
                throw new Exception("Only active allocation can be closed for alumni conversion.");
            }

            $prevRoomId = (int)$alloc['room_id'];
            $prevBed = $alloc['bed_number'];
            $joiningDate = $alloc['joining_date'];

            $stmtRoom = $this->db->prepare("SELECT * FROM rooms WHERE id = ? FOR UPDATE");
            $stmtRoom->execute([$prevRoomId]);
            $room = $stmtRoom->fetch();
            if (!$room) {
                throw new Exception("Associated room not found.");
            }

            $prevRoomStr = $room['block'] . '-' . $room['room_number'];
            $outstandingFee = $this->feeRepo->getOutstandingBalance($studentId, $this->db);
            $finalFeeStatus = ($outstandingFee > 0) ? "Has Pending Dues (Rs. " . number_format($outstandingFee, 2) . ")" : "Cleared";

            $activeCountAfterRelease = 0;
            $stmtCloseAlloc = $this->db->prepare("UPDATE room_allocations SET status = 'Closed', leaving_date = ? WHERE id = ?");
            $stmtCloseAlloc->execute([$leavingDate, $alloc['id']]);

            $countStmt = $this->db->prepare("SELECT COALESCE(SUM(CASE WHEN ra.bed_number = 0 THEN r.total_beds ELSE 1 END), 0) FROM room_allocations ra JOIN rooms r ON r.id = ra.room_id WHERE ra.room_id = ? AND ra.status = 'Active'");
            $countStmt->execute([$prevRoomId]);
            $activeCountAfterRelease = (int)$countStmt->fetchColumn();

            $newStatus = $room['status'];
            if ($newStatus !== 'Disabled') {
                if ($activeCountAfterRelease === 0) {
                    $newStatus = 'Available';
                } elseif ($activeCountAfterRelease < (int)$room['total_beds']) {
                    $newStatus = 'Partially Occupied';
                } else {
                    $newStatus = 'Occupied';
                }
            }

            $stmtUpdateRoom = $this->db->prepare("UPDATE rooms SET occupied_beds = ?, status = ? WHERE id = ?");
            $stmtUpdateRoom->execute([$activeCountAfterRelease, $newStatus, $prevRoomId]);

            $stmtSD = $this->db->prepare("SELECT * FROM security_deposits WHERE student_id = ? ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $stmtSD->execute([$studentId]);
            $securityDeposit = $stmtSD->fetch();
            $securityDepositBeforeSettlement = $securityDeposit;
            if ($securityOption === 'RETURN_NOW') {
                if (!$securityDeposit || !in_array($securityDeposit['status'], ['PAID', 'HELD', 'ADJUSTED', 'PARTIALLY_REFUNDED'], true)
                    || (float)$securityDeposit['remaining_amount'] <= 0) {
                    throw new Exception('There is no refundable held security balance to settle at checkout.');
                }
                $settlement = $this->applySecuritySettlement($securityDeposit, $securityDeduction, $securityRemarks, $processedByName);
                $securityDeposit['status'] = $settlement['status'];
                $securityDeposit['remaining_amount'] = 0.00;
            }

            $guardianInfo = json_encode([
                'name' => $student['guardian_name'],
                'phone' => $student['guardian_phone'],
                'relation' => $student['relation']
            ]);

            $alumniId = $this->alumniRepo->create([
                'original_student_id' => $student['student_id_str'],
                'name' => $student['full_name'],
                'cnic' => $student['cnic'],
                'phone' => $student['phone'],
                'guardian_info' => $guardianInfo,
                'previous_room' => $prevRoomStr,
                'previous_bed' => $prevBed,
                'joining_date' => $joiningDate,
                'leaving_date' => $leavingDate,
                'leaving_reason' => $leavingReason,
                'final_fee_status' => $finalFeeStatus,
                'remarks' => $remarks,
                'checked_out_by_name' => trim((string)$checkedOutByName),
                'processed_by_name' => trim((string)($processedByName ?: $checkedOutByName)),
            ], $this->db);

            $stmtUpdateStudent = $this->db->prepare("UPDATE students SET status = 'Inactive' WHERE id = ?");
            $stmtUpdateStudent->execute([$studentId]);

            $oldValue = [
                'status' => 'Active',
                'room_allocation' => ['room' => $prevRoomStr, 'bed' => $prevBed],
                'security_deposit' => $securityDepositBeforeSettlement ? ['status' => $securityDepositBeforeSettlement['status'], 'remaining' => $securityDepositBeforeSettlement['remaining_amount']] : null
            ];
            $newValue = [
                'status' => 'Inactive',
                'alumni_id' => $alumniId,
                'leaving_date' => $leavingDate,
                'security_status_after_checkout' => $securityDeposit['status'] ?? null,
                'security_remaining_after_checkout' => $securityDeposit['remaining_amount'] ?? null
            ];

            StudentHistoryService::record(
                $studentId,
                'STUDENT_MARKED_ALUMNI',
                "Student {$student['student_id_str']} was marked as alumni. Reason: {$leavingReason}. " .
                    ($securityOption === 'RETURN_NOW' ? 'Security was settled at checkout.' : 'Any paid security balance remains available for return from the Alumni record.'),
                $oldValue,
                $newValue,
                Session::get('admin_id'),
                $this->db
            );

            if ($securityOption === 'KEEP_FOR_LATER' && $securityDeposit
                && in_array($securityDeposit['status'], ['PAID', 'HELD', 'ADJUSTED', 'PARTIALLY_REFUNDED'], true)
                && (float)$securityDeposit['remaining_amount'] > 0) {
                StudentHistoryService::record(
                    $studentId,
                    'SECURITY_KEPT_FOR_LATER_RETURN',
                    'Paid security of Rs. ' . number_format((float)$securityDeposit['remaining_amount'], 2) . ' was kept held at Alumni checkout for later return.',
                    ['status' => $securityDeposit['status'], 'remaining_amount' => (float)$securityDeposit['remaining_amount']],
                    ['status' => $securityDeposit['status'], 'remaining_amount' => (float)$securityDeposit['remaining_amount'], 'settlement' => 'KEPT_FOR_LATER'],
                    Session::get('admin_id'),
                    $this->db
                );
            }

            $this->db->commit();
            return ['success' => true, 'alumni_id' => $alumniId];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->adminRepo->logAction(Session::get('admin_id'), 'Error', 'Alumni conversion failed: ' . $e->getMessage(), $ip);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function settleAlumniSecurity($alumniId, $securityDeduction, $remarks, $processedByName) {
        if (!is_scalar($securityDeduction) || !is_numeric($securityDeduction) || !is_finite((float)$securityDeduction)) {
            return ['success' => false, 'error' => 'Security deduction must be a valid amount.'];
        }

        $deductionValue = trim((string)$securityDeduction);
        $securityDeduction = (float)$deductionValue;
        if (abs($securityDeduction - round($securityDeduction, 2)) > 0.000001) {
            return ['success' => false, 'error' => 'Security deduction cannot have more than two decimal places.'];
        }
        if ($securityDeduction < 0) {
            return ['success' => false, 'error' => 'Security deduction cannot be negative.'];
        }

        if (!is_scalar($remarks) || !is_scalar($processedByName)) {
            return ['success' => false, 'error' => 'Security settlement details contain invalid values.'];
        }
        $remarks = trim((string)$remarks);
        $processedByName = trim((string)$processedByName);
        if ($processedByName === '') {
            return ['success' => false, 'error' => 'Processed By is required.'];
        }
        if ($securityDeduction > 0 && $remarks === '') {
            return ['success' => false, 'error' => 'Reason / Remarks are required when a security deduction is entered.'];
        }

        try {
            $this->db->beginTransaction();
            $alumniStmt = $this->db->prepare('SELECT * FROM alumni WHERE id = ? FOR UPDATE');
            $alumniStmt->execute([(int)$alumniId]);
            $alumni = $alumniStmt->fetch();
            if (!$alumni) {
                throw new Exception('Alumni record not found.');
            }

            $depositStmt = $this->db->prepare('
                SELECT sd.*
                FROM security_deposits sd
                JOIN students s ON s.id = sd.student_id
                WHERE s.student_id_str = ?
                ORDER BY sd.id DESC
                LIMIT 1
                FOR UPDATE
            ');
            $depositStmt->execute([$alumni['original_student_id']]);
            $deposit = $depositStmt->fetch();
            if (!$deposit) {
                throw new Exception('No security deposit is associated with this Alumni record.');
            }
            if (!in_array($deposit['status'], ['PAID', 'HELD', 'ADJUSTED', 'PARTIALLY_REFUNDED'], true)) {
                if ($deposit['status'] === 'PENDING') {
                    throw new Exception('This security deposit is still PENDING and has not been received, so it cannot be returned.');
                }
                throw new Exception('Only held security with a remaining balance can be returned.');
            }

            $settlement = $this->applySecuritySettlement($deposit, $securityDeduction, $remarks, $processedByName);
            $returned = $settlement['returned'];
            $newStatus = $settlement['status'];

            $this->db->commit();
            return [
                'success' => true,
                'returned' => $returned,
                'deducted' => $securityDeduction,
                'remaining' => 0,
                'status' => $newStatus
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->adminRepo->logAction(Session::get('admin_id'), 'Error', 'Alumni security return failed: ' . $e->getMessage(), $ip);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function applySecuritySettlement(array $deposit, float $securityDeduction, string $remarks, string $processedByName): array {
        $remaining = round((float)$deposit['remaining_amount'], 2);
        if ($remaining <= 0) {
            throw new Exception('This security deposit has already been settled.');
        }
        if ($securityDeduction > $remaining) {
            throw new Exception('Security deduction cannot exceed the remaining held amount.');
        }

        $returned = round($remaining - $securityDeduction, 2);
        $adminId = Session::get('admin_id');
        if ($securityDeduction > 0) {
            $adjustmentStmt = $this->db->prepare("
                INSERT INTO security_deposit_transactions
                    (security_deposit_id, transaction_type, amount, reason, created_by_admin, processed_by_name, processed_at)
                VALUES (?, 'ADJUSTMENT', ?, ?, ?, ?, NOW())
            ");
            $adjustmentStmt->execute([
                $deposit['id'],
                $securityDeduction,
                $remarks,
                $adminId,
                $processedByName
            ]);
        }

        if ($returned > 0) {
            $refundStmt = $this->db->prepare("
                INSERT INTO security_deposit_transactions
                    (security_deposit_id, transaction_type, amount, reason, created_by_admin, processed_by_name, processed_at)
                VALUES (?, 'REFUND', ?, ?, ?, ?, NOW())
            ");
            $refundReason = $remarks !== '' ? 'Security return to alumni. ' . $remarks : 'Security return to alumni.';
            $refundStmt->execute([
                $deposit['id'],
                $returned,
                $refundReason,
                $adminId,
                $processedByName
            ]);
        }

        $newStatus = $returned > 0 ? 'REFUNDED' : 'FORFEITED';
        $updateStmt = $this->db->prepare('
            UPDATE security_deposits
            SET remaining_amount = 0.00, status = ?, processed_by_name = ?, processed_at = NOW()
            WHERE id = ? AND remaining_amount = ?
        ');
        $updateStmt->execute([$newStatus, $processedByName, $deposit['id'], $remaining]);
        if ($updateStmt->rowCount() !== 1) {
            throw new Exception('Security balance changed during settlement. Please reload and try again.');
        }

        StudentHistoryService::record(
            (int)$deposit['student_id'],
            'ALUMNI_SECURITY_SETTLED',
            'Alumni security returned: Rs. ' . number_format($returned, 2) . '; deducted: Rs. ' . number_format($securityDeduction, 2) . '.',
            ['status' => $deposit['status'], 'remaining_amount' => $remaining],
            ['status' => $newStatus, 'remaining_amount' => 0, 'returned' => $returned, 'deducted' => $securityDeduction],
            $adminId,
            $this->db
        );

        return [
            'returned' => $returned,
            'deducted' => $securityDeduction,
            'remaining' => 0,
            'status' => $newStatus
        ];
    }

    public function getAllAlumni($filters = [], $page = 1, $perPage = 25) {
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;
        $result = $this->alumniRepo->findAll($filters, $perPage, $offset);
        
        return [
            'records' => $result['data'],
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total_records' => (int)$result['total'],
                'total_pages' => ceil($result['total'] / $perPage)
            ]
        ];
    }
    
    public function getAlumniById($id) {
        $alumni = $this->alumniRepo->findById($id);
        return $alumni ? $this->attachSecurityHistory($alumni) : null;
    }
    
    public function getAlumniByOriginalStudentId($studentIdStr) {
        $alumni = $this->alumniRepo->findByOriginalStudentId($studentIdStr);
        return $alumni ? $this->attachSecurityHistory($alumni) : null;
    }

    private function attachSecurityHistory(array $alumni): array {
        $depositStmt = $this->db->prepare('
            SELECT sd.*
            FROM security_deposits sd
            JOIN students s ON s.id = sd.student_id
            WHERE s.student_id_str = ?
            ORDER BY sd.id DESC
            LIMIT 1
        ');
        $depositStmt->execute([$alumni['original_student_id']]);
        $deposit = $depositStmt->fetch() ?: null;
        $transactions = [];
        $lifecycleStmt = $this->db->prepare("
            SELECT h.event_type, h.description, h.created_at, a.username AS admin_username
            FROM student_history h
            JOIN students s ON s.id = h.student_id
            LEFT JOIN admins a ON a.id = h.performed_by_admin
            WHERE s.student_id_str = ?
              AND h.event_type IN ('STUDENT_MARKED_ALUMNI', 'SECURITY_KEPT_FOR_LATER_RETURN', 'ALUMNI_SECURITY_SETTLED')
            ORDER BY h.id ASC
        ");
        $lifecycleStmt->execute([$alumni['original_student_id']]);
        $lifecycleHistory = $lifecycleStmt->fetchAll();
        if ($deposit) {
            $transactionStmt = $this->db->prepare("
                SELECT sdt.*, COALESCE(sdt.processed_at, sdt.created_at) AS payment_date, a.username AS admin_username
                FROM security_deposit_transactions sdt
                LEFT JOIN admins a ON a.id = sdt.created_by_admin
                WHERE sdt.security_deposit_id = ?
                ORDER BY sdt.id ASC
            ");
            $transactionStmt->execute([(int)$deposit['id']]);
            $transactions = $transactionStmt->fetchAll();
        }
        $alumni['security_deposit'] = $deposit;
        $alumni['security_transactions'] = $transactions;
        $alumni['security_lifecycle_history'] = $lifecycleHistory;
        return $alumni;
    }
}
