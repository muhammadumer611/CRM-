<?php
namespace App\Services;

use App\Repositories\StudentRepository;
use App\Repositories\AdminRepository;
use App\Repositories\FeeRepository;
use App\Repositories\RoomRepository;
use App\Core\Session;
use App\Core\Database;
use App\Services\StudentHistoryService;
use App\Services\BillingService;
use Exception;
use PDO;

class StudentService {
    private $studentRepo;
    private $adminRepo;
    private $feeRepo;
    private $roomRepo;
    private $db;
    private $billingService;

    public function __construct() {
        $this->studentRepo = new StudentRepository();
        $this->adminRepo = new AdminRepository();
        $this->feeRepo = new FeeRepository();
        $this->roomRepo = new RoomRepository();
        $this->db = Database::getInstance()->getConnection();
        $this->billingService = new BillingService($this->db);
    }

    public function getAllStudents($filters, $page, $perPage) {
        $offset = ($page - 1) * $perPage;
        return [
            'data'  => $this->studentRepo->findAll($filters, $perPage, $offset),
            'total' => $this->studentRepo->count($filters)
        ];
    }

    public function getStudent($id) {
        return $this->studentRepo->findById($id);
    }

    public static function sanitizeCnic($cnic) {
        return preg_replace('/[^0-9]/', '', (string)$cnic);
    }

    public static function validateCnic($cnic) {
        $clean = self::sanitizeCnic($cnic);
        return strlen($clean) === 13;
    }

    public static function formatCnic($cnic) {
        $digits = self::sanitizeCnic($cnic);
        if (strlen($digits) === 13) {
            return substr($digits, 0, 5) . '-' . substr($digits, 5, 7) . '-' . substr($digits, 12, 1);
        }
        return (string)$cnic;
    }

    public static function sanitizePhone($phone) {
        return preg_replace('/[^0-9]/', '', (string)$phone);
    }

    public static function validatePhone($phone) {
        return preg_match('/^03[0-9]{9}$/', self::sanitizePhone($phone)) === 1;
    }

    public static function formatPhone($phone) {
        $digits = self::sanitizePhone($phone);
        return strlen($digits) === 11 ? substr($digits, 0, 4) . '-' . substr($digits, 4) : (string)$phone;
    }

    private static function normalizeResidentType($value) {
        $type = trim((string)($value ?? ''));
        if (in_array($type, ['Job / Working', 'Job', 'Working'], true)) {
            return 'Job / Working';
        }
        return 'Student';
    }

    private static function normalizeVehicleType($selectedValue, $otherValue = '') {
        $selected = trim((string)($selectedValue ?? ''));
        if ($selected === '') {
            return null;
        }
        if ($selected === 'Other') {
            $custom = trim((string)($otherValue ?? ''));
            return $custom !== '' ? $custom : null;
        }
        $valid = ['Motorcycle / Bike', 'Car', 'Nill', 'Other'];
        return in_array($selected, $valid, true) ? $selected : null;
    }

    // ============================================================
    // ATOMIC STUDENT ONBOARDING (Phase 13)
    // Single transaction: student + allocation + fee + security deposit + history + audit
    // ============================================================
    private function hasSupportedFirstMonthBillingMode($data) {
        $billingMode = strtolower(trim((string)($data['first_month_billing_mode'] ?? 'full')));
        return in_array($billingMode, ['full', 'manual'], true);
    }

    private function beginOnboardingTransaction(): bool {
        if ($this->db->inTransaction()) {
            $this->db->exec('SAVEPOINT student_onboarding');
            return false;
        }

        $this->db->beginTransaction();
        return true;
    }

    private function commitOnboardingTransaction(bool $ownsTransaction): void {
        if ($ownsTransaction) {
            $this->db->commit();
            return;
        }

        $this->db->exec('RELEASE SAVEPOINT student_onboarding');
    }

    private function rollbackOnboardingTransaction(bool $ownsTransaction): void {
        if ($ownsTransaction) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return;
        }

        if ($this->db->inTransaction()) {
            $this->db->exec('ROLLBACK TO SAVEPOINT student_onboarding');
            $this->db->exec('RELEASE SAVEPOINT student_onboarding');
        }
    }

    private function normalizeOnboardingSecurity(array $data) {
        if (!isset($data['security_option']) && (int)($data['exclude_reservation_id'] ?? 0) > 0) {
            $legacyAmount = $data['security_deposit'] ?? '';
            if ($legacyAmount === '' || (is_numeric($legacyAmount) && (float)$legacyAmount === 0.0)) {
                return ['success' => true, 'record' => ['status' => null]];
            }
            return ['success' => false, 'error' => 'Select an explicit security option before recording a reservation security amount.'];
        }
        if (!is_scalar($data['security_option'] ?? null)) {
            return ['success' => false, 'error' => 'Please select a valid security option.'];
        }
        $option = strtoupper(trim((string)$data['security_option']));
        $amountValue = $data['security_amount'] ?? $data['security_deposit'] ?? '';
        $amount = 0.0;

        if (!in_array($option, ['NO_SECURITY', 'SECURITY_LATER', 'PAY_NOW'], true)) {
            return ['success' => false, 'error' => 'Please select a valid security option.'];
        }
        $noteField = [
            'NO_SECURITY' => 'security_waiver_reason',
            'SECURITY_LATER' => 'security_later_note',
            'PAY_NOW' => 'security_payment_note',
        ][$option];
        $noteValue = $data[$noteField] ?? $data['security_note'] ?? '';
        if (!is_scalar($noteValue)) {
            return ['success' => false, 'error' => 'Security note or reason must be plain text.'];
        }
        $note = trim((string)$noteValue);
        if ($option === 'NO_SECURITY') {
            if ($note === '') {
                return ['success' => false, 'error' => 'A reason is required when security is waived.'];
            }
            return ['success' => true, 'record' => [
                'status' => 'WAIVED',
                'amount' => 0.0,
                'expected_payment_date' => null,
                'note' => $note,
                'payment_method' => null,
                'reference_number' => null,
            ]];
        }

        if ($amountValue !== '') {
            if (!is_numeric($amountValue) || !is_finite((float)$amountValue)) {
                return ['success' => false, 'error' => 'Security amount must be a valid number.'];
            }
            $amount = (float)$amountValue;
            if ($amount < 0 || abs($amount - round($amount, 2)) > 0.000001) {
                return ['success' => false, 'error' => 'Security amount must be non-negative and have no more than two decimal places.'];
            }
        }
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Security amount must be greater than zero.'];
        }
        if ($option === 'SECURITY_LATER') {
            if (!is_string($data['security_expected_payment_date'] ?? null)) {
                return ['success' => false, 'error' => 'A valid expected security payment date is required.'];
            }
            $expectedDate = trim($data['security_expected_payment_date']);
            $parsedDate = \DateTime::createFromFormat('!Y-m-d', $expectedDate);
            if (!$parsedDate || $parsedDate->format('Y-m-d') !== $expectedDate) {
                return ['success' => false, 'error' => 'A valid expected security payment date is required.'];
            }
            if ($note === '') {
                return ['success' => false, 'error' => 'A note or reason is required for pending security.'];
            }
            return ['success' => true, 'record' => [
                'status' => 'PENDING',
                'amount' => $amount,
                'expected_payment_date' => $expectedDate,
                'note' => $note,
                'payment_method' => null,
                'reference_number' => null,
            ]];
        }

        if (!is_string($data['security_payment_method'] ?? null)) {
            return ['success' => false, 'error' => 'A valid security payment method is required.'];
        }
        $paymentMethod = trim($data['security_payment_method']);
        $allowedMethods = ['Cash', 'Bank Transfer', 'Online', 'Card', 'JazzCash', 'EasyPaisa', 'Other'];
        if (!in_array($paymentMethod, $allowedMethods, true)) {
            return ['success' => false, 'error' => 'A valid security payment method is required.'];
        }
        if (!is_scalar($data['security_reference_number'] ?? '')) {
            return ['success' => false, 'error' => 'Security payment reference must be plain text.'];
        }
        $reference = trim((string)($data['security_reference_number'] ?? ''));
        if (strlen($reference) > 100) {
            return ['success' => false, 'error' => 'Security payment reference cannot exceed 100 characters.'];
        }
        return ['success' => true, 'record' => [
            'status' => 'HELD',
            'amount' => $amount,
            'expected_payment_date' => null,
            'note' => $note !== '' ? $note : 'Security deposit received during onboarding.',
            'payment_method' => $paymentMethod,
            'reference_number' => $reference !== '' ? $reference : null,
        ]];
    }

    private function recordOnboardingSecurityDeposit(int $studentId, array $security, bool $includeAttribution = false): void {
        if ($security['status'] === null) {
            return;
        }
        if (!$this->db->inTransaction()) {
            throw new Exception('Security deposit onboarding requires an active database transaction.');
        }
        $amount = (float)$security['amount'];
        $remaining = $security['status'] === 'HELD' ? $amount : 0.0;
        $depositSql = $includeAttribution
            ? 'INSERT INTO security_deposits (student_id, original_amount, remaining_amount, status, expected_payment_date, note, processed_by_name, processed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            : 'INSERT INTO security_deposits (student_id, original_amount, remaining_amount, status, expected_payment_date, note) VALUES (?, ?, ?, ?, ?, ?)';
        $stmt = $this->db->prepare($depositSql);
        $depositValues = [
            $studentId,
            $amount,
            $remaining,
            $security['status'],
            $security['expected_payment_date'],
            $security['note'],
        ];
        if ($includeAttribution) {
            $depositValues[] = $security['processed_by_name'] ?? null;
            $depositValues[] = $security['processed_at'] ?? null;
        }
        $stmt->execute($depositValues);

        if ($security['status'] === 'HELD') {
            $depositId = (int)$this->db->lastInsertId();
            if ($includeAttribution) {
                $stmtTransaction = $this->db->prepare(
                    "INSERT INTO security_deposit_transactions (security_deposit_id, transaction_type, amount, reason, reference_number, payment_method, created_by_admin, processed_by_name, processed_at) VALUES (?, 'HOLD', ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmtTransaction->execute([
                    $depositId,
                    $amount,
                    $security['note'],
                    $security['reference_number'],
                    $security['payment_method'],
                    Session::get('admin_id') ?: null,
                    $security['processed_by_name'] ?? null,
                    $security['processed_at'] ?? date('Y-m-d H:i:s'),
                ]);
            } else {
                $stmtTransaction = $this->db->prepare(
                    "INSERT INTO security_deposit_transactions (security_deposit_id, transaction_type, amount, reason, reference_number, payment_method, created_by_admin, processed_at) VALUES (?, 'HOLD', ?, ?, ?, ?, ?, NOW())"
                );
                $stmtTransaction->execute([
                    $depositId,
                    $amount,
                    $security['note'],
                    $security['reference_number'],
                    $security['payment_method'],
                    Session::get('admin_id') ?: null,
                ]);
            }
        }
    }

    public function recordReadmissionSecurityDeposit(int $studentId, array $data): ?array {
        if (!isset($data['security_option']) || !is_scalar($data['security_option'])
            || trim((string)$data['security_option']) === '') {
            return null;
        }
        if (!$this->db->inTransaction()) {
            throw new Exception('Re-admission security deposits require an active database transaction.');
        }

        $result = $this->normalizeOnboardingSecurity($data);
        if (!$result['success']) {
            throw new Exception($result['error']);
        }
        $security = $result['record'];
        if (!is_scalar($data['processed_by_name'] ?? null) || trim((string)$data['processed_by_name']) === '') {
            throw new Exception('Processed By is required for the new security record.');
        }
        $security['processed_by_name'] = trim((string)$data['processed_by_name']);

        if ($security['status'] === 'HELD') {
            $paymentDate = $data['security_payment_date'] ?? date('Y-m-d');
            if (!is_string($paymentDate)) {
                throw new Exception('Enter a valid security payment date.');
            }
            $parsedDate = \DateTimeImmutable::createFromFormat('!Y-m-d', trim($paymentDate));
            $dateErrors = \DateTimeImmutable::getLastErrors();
            if (!$parsedDate
                || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
                || $parsedDate->format('Y-m-d') !== trim($paymentDate)
                || trim($paymentDate) > date('Y-m-d')) {
                throw new Exception('Enter a valid security payment date that is not in the future.');
            }
            $security['processed_at'] = trim($paymentDate) . ' 12:00:00';
        }

        $this->recordOnboardingSecurityDeposit($studentId, $security, true);
        return [
            'status' => $security['status'],
            'amount' => (float)$security['amount'],
            'recorded_as_received' => $security['status'] === 'HELD',
        ];
    }

    public function onboardStudent($data) {
        $accommodationType = strtolower(trim((string)($data['accommodation_type'] ?? '')));

        if ($accommodationType === 'single' || $accommodationType === 'single_person') {
            return $this->onboardSinglePerson($data);
        }

        if ($accommodationType === 'full_room' || $accommodationType === 'full') {
            return $this->onboardFullRoom($data);
        }

        return ['success' => false, 'error' => 'Please select accommodation type.'];
    }

    public function onboardSinglePerson($data) {
        if (!$this->hasSupportedFirstMonthBillingMode($data)) {
            return ['success' => false, 'error' => 'Please select Full Monthly Fee or Manual Adjustment.'];
        }
        $securityResult = $this->normalizeOnboardingSecurity($data);
        if (!$securityResult['success']) {
            return $securityResult;
        }
        $security = $securityResult['record'];
        $addedByName = trim((string)($data['added_by_name'] ?? ''));

        $residentType = self::normalizeResidentType($data['resident_type'] ?? 'Student');
        $collegeUniversity = trim((string)($data['college_university'] ?? ''));
        $jobWorkplace = trim((string)($data['job_workplace'] ?? ''));
        $vehicleNumber = trim((string)($data['vehicle_number'] ?? ''));
        $vehicleType = self::normalizeVehicleType($data['vehicle_type'] ?? '', $data['vehicle_type_other'] ?? '');
        $note = trim((string)($data['note'] ?? ''));
        $bloodGroup = trim((string)($data['blood_group'] ?? ''));

        if ($bloodGroup !== '' && !in_array($bloodGroup, ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], true)) {
            return ['success' => false, 'error' => 'Please select a valid blood group.'];
        }

        if ($residentType === 'Student' && $collegeUniversity === '') {
            return ['success' => false, 'error' => 'College / University is required for Student residents.'];
        }
        if ($residentType === 'Job / Working' && $jobWorkplace === '') {
            return ['success' => false, 'error' => 'Job / Workplace is required for Job / Working residents.'];
        }

        $cleanCnic = self::sanitizeCnic($data['cnic'] ?? '');
        if (!self::validateCnic($cleanCnic)) {
            return ['success' => false, 'error' => 'Please enter a valid CNIC.'];
        }

        $phone = self::sanitizePhone($data['phone'] ?? '');
        $guardianPhone = self::sanitizePhone($data['guardian_phone'] ?? '');
        if (!self::validatePhone($phone) || !self::validatePhone($guardianPhone)) {
            return ['success' => false, 'error' => 'Student and guardian phone numbers must be valid 11-digit Pakistani mobile numbers.'];
        }

        $monthlyFee = isset($data['monthly_fee']) && $data['monthly_fee'] !== '' ? (float)$data['monthly_fee'] : 0.0;
        if ($monthlyFee <= 0) {
            return ['success' => false, 'error' => 'Please enter a valid monthly fee.'];
        }

        $roomId = !empty($data['room_id']) ? (int)$data['room_id'] : 0;
        $bedNumber = !empty($data['bed_number']) ? (int)$data['bed_number'] : 0;
        $joiningDate = !empty($data['joining_date']) ? $data['joining_date'] : date('Y-m-d');

        if (!$roomId) {
            return ['success' => false, 'error' => 'Please select a room.'];
        }
        if (!$bedNumber) {
            return ['success' => false, 'error' => 'Please select an available bed.'];
        }

        $ownsTransaction = $this->beginOnboardingTransaction();

        try {
            $stmtCnic = $this->db->prepare("SELECT id FROM students WHERE cnic = ? FOR UPDATE");
            $stmtCnic->execute([$cleanCnic]);
            if ($stmtCnic->fetch()) {
                throw new Exception('CNIC already exists. Please check the existing student record.');
            }

            $stmtPhone = $this->db->prepare("SELECT id FROM students WHERE phone = ? OR REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', '') = ? FOR UPDATE");
            $stmtPhone->execute([$phone, $phone]);
            if ($stmtPhone->fetch()) {
                throw new Exception('A student with this phone number already exists. Please check the existing student record.');
            }

            $stmtRoom = $this->db->prepare("SELECT * FROM rooms WHERE id = ? FOR UPDATE");
            $stmtRoom->execute([$roomId]);
            $room = $stmtRoom->fetch();
            if (!$room) {
                throw new Exception('Selected room not found.');
            }
            if ($room['status'] === 'Disabled') {
                throw new Exception('This room is disabled.');
            }

            $totalBeds = (int)$room['total_beds'];
            if ($bedNumber < 1 || $bedNumber > $totalBeds) {
                throw new Exception('Selected bed is invalid for this room.');
            }

            $stmtBed = $this->db->prepare("SELECT id FROM room_allocations WHERE room_id = ? AND (bed_number = ? OR bed_number = 0) AND status = 'Active' FOR UPDATE");
            $stmtBed->execute([$roomId, $bedNumber]);
            if ($stmtBed->fetch()) {
                throw new Exception('Selected bed is already occupied.');
            }

            $reservationId = (int)($data['exclude_reservation_id'] ?? 0);
            $reservationSql = "SELECT id FROM reservations WHERE room_id = ? AND bed_number = ? AND status IN ('PENDING', 'CONFIRMED')";
            $reservationParams = [$roomId, $bedNumber];
            if ($reservationId > 0) {
                $reservationSql .= " AND id != ?";
                $reservationParams[] = $reservationId;
            }
            $stmtReservation = $this->db->prepare($reservationSql . ' FOR UPDATE');
            $stmtReservation->execute($reservationParams);
            if ($stmtReservation->fetch()) {
                throw new Exception('Bed is no longer available. It is reserved.');
            }

            $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM room_allocations WHERE room_id = ? AND status = 'Active'");
            $stmtCount->execute([$roomId]);
            $currentOccupied = (int)$stmtCount->fetchColumn();

            if ($currentOccupied >= $totalBeds) {
                throw new Exception('This room has no available capacity.');
            }

            $studentIdStr = $this->studentRepo->generateStudentId($this->db);
            $dbData = [
                'student_id_str' => $studentIdStr,
                'full_name' => trim($data['full_name']),
                'cnic' => $cleanCnic,
                'phone' => $phone,
                'email' => empty($data['email']) ? null : trim($data['email']),
                'blood_group' => $bloodGroup !== '' ? $bloodGroup : null,
                'address' => trim($data['address']),
                'guardian_name' => trim($data['guardian_name']),
                'guardian_phone' => $guardianPhone,
                'guardian_address' => empty($data['guardian_address']) ? null : trim($data['guardian_address']),
                'relation' => trim($data['relation']),
                'resident_type' => $residentType,
                'college_university' => $residentType === 'Student' && $collegeUniversity !== '' ? $collegeUniversity : null,
                'job_workplace' => $residentType === 'Job / Working' && $jobWorkplace !== '' ? $jobWorkplace : null,
                'vehicle_number' => $vehicleNumber !== '' ? $vehicleNumber : null,
                'vehicle_type' => $vehicleType !== '' ? $vehicleType : null,
                'note' => $note !== '' ? $note : null,
                'status' => 'Active',
                'monthly_fee' => $monthlyFee,
                'added_by_name' => $addedByName !== '' ? $addedByName : null,
                'added_at' => date('Y-m-d H:i:s'),
            ];

            $studentId = $this->studentRepo->create($dbData, $this->db);
            if (!$studentId) {
                throw new Exception('Failed to create student record.');
            }

            $stmtAlloc = $this->db->prepare("INSERT INTO room_allocations (student_id, room_id, bed_number, joining_date, status) VALUES (?, ?, ?, ?, 'Active')");
            $stmtAlloc->execute([$studentId, $roomId, $bedNumber, $joiningDate]);
            $allocationId = $this->db->lastInsertId();

            $newOccupied = $currentOccupied + 1;
            $newStatus = ($newOccupied >= $totalBeds) ? 'Occupied' : 'Partially Occupied';
            $this->db->prepare("UPDATE rooms SET occupied_beds = ?, status = ? WHERE id = ?")->execute([$newOccupied, $newStatus, $roomId]);

            $this->recordOnboardingSecurityDeposit((int)$studentId, $security);

            $invoice = $this->billingService->createFirstMonthInvoice($studentId, $monthlyFee, $joiningDate, [
                'first_month_billing_mode' => $data['first_month_billing_mode'] ?? 'full',
                'first_month_discount' => $data['first_month_discount'] ?? 0,
                'first_month_discount_reason' => $data['first_month_discount_reason'] ?? '',
                'created_by_admin' => Session::get('admin_id'),
            ]);
            $invoiceId = $invoice['id'];

            StudentHistoryService::record($studentId, 'STUDENT_CREATED', 'Single-person onboarding completed.', null, array_merge($dbData, ['allocation_id' => $allocationId, 'invoice_id' => $invoiceId]), Session::get('admin_id'), $this->db);
            $this->commitOnboardingTransaction($ownsTransaction);

            return ['success' => true, 'id' => $studentId, 'student_id_str' => $studentIdStr];
        } catch (\Throwable $e) {
            $this->rollbackOnboardingTransaction($ownsTransaction);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function onboardFullRoom($data) {
        if (!$this->hasSupportedFirstMonthBillingMode($data)) {
            return ['success' => false, 'error' => 'Please select Full Monthly Fee or Manual Adjustment.'];
        }
        $securityResult = $this->normalizeOnboardingSecurity($data);
        if (!$securityResult['success']) {
            return $securityResult;
        }
        $security = $securityResult['record'];
        $addedByName = trim((string)($data['added_by_name'] ?? ''));

        $roomId = !empty($data['room_id']) ? (int)$data['room_id'] : 0;
        $monthlyRoomFee = isset($data['monthly_room_fee']) && $data['monthly_room_fee'] !== '' ? (float)$data['monthly_room_fee'] : 0.0;

        if (!$roomId) {
            return ['success' => false, 'error' => 'Please select a room.'];
        }
        if ($monthlyRoomFee <= 0) {
            return ['success' => false, 'error' => 'Please enter a valid monthly room fee.'];
        }
        $occupants = $data['occupants'] ?? [];
        if (!is_array($occupants) || count($occupants) === 0) {
            return ['success' => false, 'error' => 'Please add at least one occupant.'];
        }

        $ownsTransaction = $this->beginOnboardingTransaction();

        try {
            $stmtRoom = $this->db->prepare("SELECT * FROM rooms WHERE id = ? FOR UPDATE");
            $stmtRoom->execute([$roomId]);
            $room = $stmtRoom->fetch();
            if (!$room) {
                throw new Exception('Selected room not found.');
            }
            if ($room['status'] === 'Disabled') {
                throw new Exception('Selected room is disabled.');
            }

            $fullRoomStmt = $this->db->prepare("SELECT id FROM room_allocations WHERE room_id = ? AND bed_number = 0 AND status = 'Active' FOR UPDATE");
            $fullRoomStmt->execute([$roomId]);
            if ($fullRoomStmt->fetch()) {
                throw new Exception('This room is already assigned as a full room.');
            }

            $reservationStmt = $this->db->prepare("SELECT id FROM reservations WHERE room_id = ? AND status IN ('PENDING', 'CONFIRMED') LIMIT 1 FOR UPDATE");
            $reservationStmt->execute([$roomId]);
            if ($reservationStmt->fetch()) {
                throw new Exception('This room contains an active reservation and cannot be assigned as a full room.');
            }

            $totalBeds = (int)$room['total_beds'];
            $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM room_allocations WHERE room_id = ? AND status = 'Active'");
            $stmtCount->execute([$roomId]);
            $currentOccupied = (int)$stmtCount->fetchColumn();
            $requestedCount = count($occupants);

            if ($requestedCount > $totalBeds || ($currentOccupied + $requestedCount) > $totalBeds) {
                throw new Exception('This room has no available capacity for the selected occupants.');
            }

            $availableBeds = [];
            for ($bed = 1; $bed <= $totalBeds; $bed++) {
                $stmtBed = $this->db->prepare("SELECT id FROM room_allocations WHERE room_id = ? AND bed_number = ? AND status = 'Active' FOR UPDATE");
                $stmtBed->execute([$roomId, $bed]);
                if (!$stmtBed->fetch()) {
                    $availableBeds[] = $bed;
                }
            }
            if (count($availableBeds) < $requestedCount) {
                throw new Exception('Room capacity reached.');
            }

            $createdStudentIds = [];
            $seenPhoneNumbers = [];
            $joiningDate = !empty($data['joining_date']) ? $data['joining_date'] : date('Y-m-d');

            foreach (array_values($occupants) as $index => $occupant) {
                $fullName = trim((string)($occupant['full_name'] ?? ''));
                $cnic = self::sanitizeCnic($occupant['cnic'] ?? '');
                if ($fullName === '' || !self::validateCnic($cnic)) {
                    throw new Exception('Please enter a valid CNIC for each person.');
                }

                $stmtCnic = $this->db->prepare("SELECT id FROM students WHERE cnic = ? FOR UPDATE");
                $stmtCnic->execute([$cnic]);
                if ($stmtCnic->fetch()) {
                    throw new Exception('CNIC already exists for person ' . ((int)$index + 1) . '. Please check the existing student record.');
                }

                $studentIdStr = $this->studentRepo->generateStudentId($this->db);
                $phone = self::sanitizePhone($occupant['phone'] ?? '');
                $guardianName = trim((string)($occupant['guardian_name'] ?? '')) ?: ($fullName . ' Guardian');
                $guardianPhone = self::sanitizePhone($occupant['guardian_phone'] ?? '');
                if (!self::validatePhone($phone) || !self::validatePhone($guardianPhone)) {
                    throw new Exception('Each full-room occupant needs valid student and guardian phone numbers.');
                }
                $stmtPhone = $this->db->prepare("SELECT id FROM students WHERE phone = ? OR REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', '') = ? FOR UPDATE");
                $stmtPhone->execute([$phone, $phone]);
                if ($stmtPhone->fetch() || isset($seenPhoneNumbers[$phone])) {
                    throw new Exception('A student phone number already exists for person ' . ((int)$index + 1) . '. Please check the existing student record.');
                }
                $seenPhoneNumbers[$phone] = true;
                $residentType = self::normalizeResidentType($occupant['resident_type'] ?? 'Student');
                $collegeUniversity = trim((string)($occupant['college_university'] ?? ''));
                $jobWorkplace = trim((string)($occupant['job_workplace'] ?? ''));
                $vehicleNumber = trim((string)($occupant['vehicle_number'] ?? ''));
                $vehicleType = self::normalizeVehicleType($occupant['vehicle_type'] ?? '', $occupant['vehicle_type_other'] ?? '');
                $note = trim((string)($occupant['note'] ?? ''));
                $bloodGroup = trim((string)($occupant['blood_group'] ?? ''));
                if ($bloodGroup !== '' && !in_array($bloodGroup, ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], true)) {
                    throw new Exception('Please select a valid blood group for each person.');
                }

                $dbData = [
                    'student_id_str' => $studentIdStr,
                    'full_name' => $fullName,
                    'cnic' => $cnic,
                    'phone' => $phone,
                    'email' => empty($occupant['email'] ?? '') ? null : trim((string)$occupant['email']),
                    'blood_group' => $bloodGroup !== '' ? $bloodGroup : null,
                    'address' => trim((string)($occupant['address'] ?? '')) ?: 'Full room occupant',
                    'guardian_name' => $guardianName,
                    'guardian_phone' => $guardianPhone,
                    'guardian_address' => empty($occupant['guardian_address'] ?? '') ? null : trim((string)$occupant['guardian_address']),
                    'relation' => trim((string)($occupant['relation'] ?? '')) ?: 'Other',
                    'resident_type' => $residentType,
                    'college_university' => $residentType === 'Student' && $collegeUniversity !== '' ? $collegeUniversity : null,
                    'job_workplace' => $residentType === 'Job / Working' && $jobWorkplace !== '' ? $jobWorkplace : null,
                    'vehicle_number' => $vehicleNumber !== '' ? $vehicleNumber : null,
                    'vehicle_type' => $vehicleType !== '' ? $vehicleType : null,
                    'note' => $note !== '' ? $note : null,
                    'status' => 'Active',
                    'monthly_fee' => $monthlyRoomFee,
                    'added_by_name' => $addedByName !== '' ? $addedByName : null,
                    'added_at' => date('Y-m-d H:i:s'),
                ];

                $studentId = $this->studentRepo->create($dbData, $this->db);
                if (!$studentId) {
                    throw new Exception('Failed to create one or more student records.');
                }

                $bedNumber = array_shift($availableBeds);
                $reservationStmt = $this->db->prepare("SELECT id FROM reservations WHERE room_id = ? AND bed_number = ? AND status IN ('PENDING', 'CONFIRMED') FOR UPDATE");
                $reservationStmt->execute([$roomId, $bedNumber]);
                if ($reservationStmt->fetch()) {
                    throw new Exception('Bed is no longer available. It is reserved.');
                }
                $stmtAlloc = $this->db->prepare("INSERT INTO room_allocations (student_id, room_id, bed_number, joining_date, status) VALUES (?, ?, ?, ?, 'Active')");
                $stmtAlloc->execute([$studentId, $roomId, $bedNumber, $joiningDate]);
                $createdStudentIds[] = ['id' => $studentId, 'student_id_str' => $studentIdStr, 'bed_number' => $bedNumber];

                $this->recordOnboardingSecurityDeposit((int)$studentId, $security);

                $invoice = $this->billingService->createFirstMonthInvoice($studentId, $monthlyRoomFee, $joiningDate, [
                    'first_month_billing_mode' => $data['first_month_billing_mode'] ?? 'full',
                    'first_month_discount' => $data['first_month_discount'] ?? 0,
                    'first_month_discount_reason' => $data['first_month_discount_reason'] ?? '',
                    'created_by_admin' => Session::get('admin_id'),
                ]);
                StudentHistoryService::record($studentId, 'STUDENT_CREATED', 'Full-room onboarding completed.', null, $dbData, Session::get('admin_id'), $this->db);
            }

            $newOccupied = $currentOccupied + $requestedCount;
            $newStatus = ($newOccupied >= $totalBeds) ? 'Occupied' : 'Partially Occupied';
            $this->db->prepare("UPDATE rooms SET occupied_beds = ?, status = ? WHERE id = ?")->execute([$newOccupied, $newStatus, $roomId]);

            $this->commitOnboardingTransaction($ownsTransaction);
            return ['success' => true, 'count' => count($createdStudentIds), 'student_id_str' => $createdStudentIds[0]['student_id_str'] ?? null];
        } catch (\Throwable $e) {
            $this->rollbackOnboardingTransaction($ownsTransaction);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }


    // ============================================================
    // Simple create (kept for backward compat, redirects to onboard)
    // ============================================================
    public function createStudent($data) {
        return $this->onboardStudent($data);
    }

    public function updateStudent($id, $data) {
        $student = $this->studentRepo->findById($id);
        if (!$student) return ['success' => false, 'error' => 'Student not found.'];

        $requestedJoiningDate = null;
        $joiningDateChanged = false;
        if (array_key_exists('joining_date', $data)) {
            if (!is_scalar($data['joining_date']) || trim((string)$data['joining_date']) === '') {
                return ['success' => false, 'error' => 'Joining date is required and must be a valid date.'];
            }
            $requestedJoiningDate = trim((string)$data['joining_date']);
            $parsedJoiningDate = \DateTime::createFromFormat('!Y-m-d', $requestedJoiningDate);
            if (!$parsedJoiningDate || $parsedJoiningDate->format('Y-m-d') !== $requestedJoiningDate) {
                return ['success' => false, 'error' => 'Joining date must be a valid date in YYYY-MM-DD format.'];
            }
            if (empty($student['allocation_id'])) {
                return ['success' => false, 'error' => 'There is no active room allocation whose joining date can be changed.'];
            }
            $joiningDateChanged = ($student['allocation_date'] ?? null) !== $requestedJoiningDate;
        }

        $securityFields = ['security_amount', 'security_expected_payment_date', 'security_note'];
        $securityEditRequested = count(array_intersect($securityFields, array_keys($data))) > 0;
        $securityValues = null;
        if ($securityEditRequested) {
            if (!is_scalar($data['security_amount'] ?? null) || trim((string)$data['security_amount']) === ''
                || !is_scalar($data['security_expected_payment_date'] ?? null)
                || !is_scalar($data['security_note'] ?? null)) {
                return ['success' => false, 'error' => 'Enter a valid pending security amount, expected payment date, and note.'];
            }
            $securityAmountValue = trim((string)$data['security_amount']);
            if (!is_numeric($securityAmountValue) || !is_finite((float)$securityAmountValue)) {
                return ['success' => false, 'error' => 'Security amount must be a valid number.'];
            }
            $securityAmount = (float)$securityAmountValue;
            if ($securityAmount <= 0 || $securityAmount > 9999999999.99
                || abs($securityAmount - round($securityAmount, 2)) > 0.000001) {
                return ['success' => false, 'error' => 'Security amount must be greater than zero, no more than Rs. 9,999,999,999.99, and have no more than two decimal places.'];
            }
            $expectedPaymentDate = trim((string)$data['security_expected_payment_date']);
            $parsedExpectedDate = \DateTime::createFromFormat('!Y-m-d', $expectedPaymentDate);
            if (!$parsedExpectedDate || $parsedExpectedDate->format('Y-m-d') !== $expectedPaymentDate) {
                return ['success' => false, 'error' => 'Expected security payment date must be a valid date in YYYY-MM-DD format.'];
            }
            $securityNote = trim((string)$data['security_note']);
            if ($securityNote === '') {
                return ['success' => false, 'error' => 'A note or reason is required for pending security.'];
            }
            $securityValues = [
                'original_amount' => number_format($securityAmount, 2, '.', ''),
                'expected_payment_date' => $expectedPaymentDate,
                'note' => $securityNote,
            ];
        }

        $residentType = self::normalizeResidentType($data['resident_type'] ?? 'Student');
        $collegeUniversity = trim((string)($data['college_university'] ?? ''));
        $jobWorkplace = trim((string)($data['job_workplace'] ?? ''));
        $vehicleNumber = trim((string)($data['vehicle_number'] ?? ''));
        $vehicleType = self::normalizeVehicleType($data['vehicle_type'] ?? '', $data['vehicle_type_other'] ?? '');
        $note = trim((string)($data['note'] ?? ''));

        if ($residentType === 'Student' && $collegeUniversity === '') {
            return ['success' => false, 'error' => 'College / University is required for Student residents.'];
        }
        if ($residentType === 'Job / Working' && $jobWorkplace === '') {
            return ['success' => false, 'error' => 'Job / Workplace is required for Job / Working residents.'];
        }

        $cleanCnic = self::sanitizeCnic($data['cnic'] ?? '');
        if (!self::validateCnic($cleanCnic)) {
            return ['success' => false, 'error' => 'CNIC must be 13 digits (e.g. 12345-1234567-1 or 1234512345671).'];
        }

        $phone = self::sanitizePhone($data['phone'] ?? '');
        $guardianPhone = self::sanitizePhone($data['guardian_phone'] ?? '');
        if (!self::validatePhone($phone) || !self::validatePhone($guardianPhone)) {
            return ['success' => false, 'error' => 'Student and guardian phone numbers must be valid 11-digit Pakistani mobile numbers.'];
        }

        if ($this->studentRepo->findByCnic($cleanCnic, $id)) {
            return ['success' => false, 'error' => 'A student with this CNIC already exists.'];
        }

        $currentRoomId = !empty($student['room_id']) ? (int)$student['room_id'] : 0;
        $currentBedNumber = isset($student['bed_number']) ? (int)$student['bed_number'] : 0;
        $targetRoomId = array_key_exists('room_id', $data) && trim((string)$data['room_id']) !== ''
            ? (int)$data['room_id'] : $currentRoomId;
        $targetBedNumber = array_key_exists('bed_number', $data) && trim((string)$data['bed_number']) !== ''
            ? (int)$data['bed_number'] : $currentBedNumber;
        $allocationChanged = $targetRoomId !== $currentRoomId || $targetBedNumber !== $currentBedNumber;

        if ($allocationChanged && ($student['status'] ?? 'Active') !== 'Active') {
            return ['success' => false, 'error' => 'Only active students can be moved to another room or bed.'];
        }
        if ($allocationChanged && ($targetRoomId <= 0 || $targetBedNumber <= 0)) {
            return ['success' => false, 'error' => 'Please select a valid room and bed.'];
        }

        $dbData = [
            'full_name'          => trim($data['full_name']),
            'cnic'               => $cleanCnic,
            'phone'              => $phone,
            'email'              => empty($data['email'])          ? null : trim($data['email']),
            'blood_group'        => empty($data['blood_group'])    ? null : trim($data['blood_group']),
            'address'            => trim($data['address']),
            'guardian_name'      => trim($data['guardian_name']),
            'guardian_phone'     => $guardianPhone,
            'relation'           => trim($data['relation']),
            'resident_type'      => $residentType,
            'college_university' => $residentType === 'Student' ? ($collegeUniversity !== '' ? $collegeUniversity : null) : null,
            'job_workplace'      => $residentType === 'Job / Working' ? ($jobWorkplace !== '' ? $jobWorkplace : null) : null,
            'vehicle_number'     => $vehicleNumber !== '' ? $vehicleNumber : null,
            'vehicle_type'       => $vehicleType !== '' ? $vehicleType : null,
            'note'               => $note !== '' ? $note : null,
            'status'             => $data['status'] ?? 'Active'
        ];

        if (array_key_exists('guardian_address', $data)) {
            $dbData['guardian_address'] = empty($data['guardian_address']) ? null : trim($data['guardian_address']);
        }

        // Update monthly_fee if provided explicitly
        if (isset($data['monthly_fee']) && $data['monthly_fee'] !== '') {
            $dbData['monthly_fee'] = (float)$data['monthly_fee'];
        }

        $startedTransaction = !$this->db->inTransaction();
        if ($startedTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $studentLock = $this->db->prepare("SELECT * FROM students WHERE id = ? FOR UPDATE");
            $studentLock->execute([(int)$id]);
            if (!$studentLock->fetch()) {
                throw new Exception('Student not found.');
            }

            $additionalChanges = [];
            $additionalOldValues = [];
            if ($securityEditRequested) {
                $securityLock = $this->db->prepare('SELECT * FROM security_deposits WHERE student_id = ? ORDER BY id DESC LIMIT 1 FOR UPDATE');
                $securityLock->execute([(int)$id]);
                $deposit = $securityLock->fetch();
                if (!$deposit || $deposit['status'] !== 'PENDING' || (float)$deposit['remaining_amount'] !== 0.0) {
                    throw new Exception('Only an unpaid pending security deposit can be edited here. Paid, waived, or settled deposits must use the security settlement workflow.');
                }

                $transactionCount = $this->db->prepare('SELECT COUNT(*) FROM security_deposit_transactions WHERE security_deposit_id = ?');
                $transactionCount->execute([(int)$deposit['id']]);
                if ((int)$transactionCount->fetchColumn() > 0) {
                    throw new Exception('This pending security deposit has transaction history and cannot be edited. Use the security correction workflow.');
                }

                foreach ($securityValues as $field => $value) {
                    $isDifferent = $field === 'original_amount'
                        ? round((float)$deposit[$field], 2) !== round((float)$value, 2)
                        : (string)($deposit[$field] ?? '') !== (string)$value;
                    if ($isDifferent) {
                        $additionalChanges['security_' . $field] = $value;
                        $additionalOldValues['security_' . $field] = $deposit[$field];
                    }
                }
                if ($additionalChanges) {
                    $securityUpdate = $this->db->prepare('UPDATE security_deposits SET original_amount = ?, expected_payment_date = ?, note = ? WHERE id = ? AND status = \'PENDING\' AND remaining_amount = 0');
                    $securityUpdate->execute([
                        $securityValues['original_amount'],
                        $securityValues['expected_payment_date'],
                        $securityValues['note'],
                        (int)$deposit['id'],
                    ]);
                }
            }

            if ($allocationChanged) {
                $roomIds = array_values(array_unique(array_filter([$currentRoomId, $targetRoomId])));
                sort($roomIds, SORT_NUMERIC);
                $lockedRooms = [];
                foreach ($roomIds as $roomId) {
                    $roomStmt = $this->db->prepare('SELECT * FROM rooms WHERE id = ? FOR UPDATE');
                    $roomStmt->execute([$roomId]);
                    $lockedRooms[$roomId] = $roomStmt->fetch();
                }
                $targetRoom = $lockedRooms[$targetRoomId] ?? null;
                if (!$targetRoom || $targetRoom['status'] === 'Disabled' || $targetBedNumber > (int)$targetRoom['total_beds']) {
                    throw new Exception('Selected room or bed is invalid.');
                }

                $reservationStmt = $this->db->prepare("SELECT id FROM reservations WHERE room_id = ? AND bed_number = ? AND status IN ('PENDING', 'CONFIRMED') FOR UPDATE");
                $reservationStmt->execute([$targetRoomId, $targetBedNumber]);
                if ($reservationStmt->fetch()) {
                    throw new Exception('Selected bed is reserved and cannot be allocated.');
                }

                $allocationStmt = $this->db->prepare("SELECT id FROM room_allocations WHERE room_id = ? AND (bed_number = ? OR bed_number = 0) AND status = 'Active' AND student_id != ? FOR UPDATE");
                $allocationStmt->execute([$targetRoomId, $targetBedNumber, (int)$id]);
                if ($allocationStmt->fetch()) {
                    throw new Exception('Selected bed is already occupied.');
                }

                $currentAllocationStmt = $this->db->prepare("SELECT id FROM room_allocations WHERE student_id = ? AND status = 'Active' FOR UPDATE");
                $currentAllocationStmt->execute([(int)$id]);
                $currentAllocation = $currentAllocationStmt->fetch();
                if (!$currentAllocation) {
                    throw new Exception('Active room allocation not found.');
                }

                $closeStmt = $this->db->prepare("UPDATE room_allocations SET status = 'Closed', leaving_date = ? WHERE id = ?");
                $closeStmt->execute([date('Y-m-d'), (int)$currentAllocation['id']]);
                $newAllocationStmt = $this->db->prepare("INSERT INTO room_allocations (student_id, room_id, bed_number, joining_date, status) VALUES (?, ?, ?, ?, 'Active')");
                $newAllocationDate = $joiningDateChanged ? $requestedJoiningDate : date('Y-m-d');
                $newAllocationStmt->execute([(int)$id, $targetRoomId, $targetBedNumber, $newAllocationDate]);
                if ($joiningDateChanged) {
                    $additionalChanges['allocation_joining_date'] = $newAllocationDate;
                    $additionalOldValues['allocation_joining_date'] = $student['allocation_date'] ?? null;
                }
            } elseif ($joiningDateChanged) {
                $allocationLock = $this->db->prepare("SELECT id, joining_date FROM room_allocations WHERE student_id = ? AND status = 'Active' FOR UPDATE");
                $allocationLock->execute([(int)$id]);
                $activeAllocation = $allocationLock->fetch();
                if (!$activeAllocation) {
                    throw new Exception('There is no active room allocation whose joining date can be changed.');
                }
                $updateAllocationDate = $this->db->prepare("UPDATE room_allocations SET joining_date = ? WHERE id = ? AND status = 'Active'");
                $updateAllocationDate->execute([$requestedJoiningDate, (int)$activeAllocation['id']]);
                $additionalChanges['allocation_joining_date'] = $requestedJoiningDate;
                $additionalOldValues['allocation_joining_date'] = $activeAllocation['joining_date'];
            }

            if (!$this->studentRepo->update($id, $dbData, $this->db)) {
                throw new Exception('Failed to update student.');
            }

            if ($allocationChanged) {
                foreach (array_values(array_unique(array_filter([$currentRoomId, $targetRoomId]))) as $roomId) {
                    $countStmt = $this->db->prepare("SELECT COUNT(*) FROM room_allocations ra JOIN students s ON s.id = ra.student_id AND s.status = 'Active' WHERE ra.room_id = ? AND ra.status = 'Active'");
                    $countStmt->execute([$roomId]);
                    $occupied = (int)$countStmt->fetchColumn();
                    $room = $lockedRooms[$roomId];
                    $status = $room['status'];
                    if ($status !== 'Disabled') {
                        $status = $occupied === 0 ? 'Available' : ($occupied >= (int)$room['total_beds'] ? 'Occupied' : 'Partially Occupied');
                    }
                    $this->db->prepare('UPDATE rooms SET occupied_beds = ?, status = ? WHERE id = ?')->execute([$occupied, $status, $roomId]);
                }
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->adminRepo->logAction(Session::get('admin_id'), 'Update Student', "Updated student ID: {$student['student_id_str']}", $ip);

            $changes   = [];
            $oldValues = [];
            foreach ($dbData as $key => $value) {
                if (isset($student[$key]) && $student[$key] !== $value) {
                    $changes[$key]   = $value;
                    $oldValues[$key] = $student[$key];
                }
            }
            $changes = array_merge($changes, $additionalChanges);
            $oldValues = array_merge($oldValues, $additionalOldValues);

            if ($allocationChanged) {
                $changes['room_allocation'] = [
                    'room_id' => $targetRoomId,
                    'bed_number' => $targetBedNumber,
                    'joining_date' => $joiningDateChanged ? $requestedJoiningDate : date('Y-m-d'),
                ];
                $oldValues['room_allocation'] = [
                    'room_id' => $currentRoomId,
                    'bed_number' => $currentBedNumber,
                    'joining_date' => $student['allocation_date'] ?? null,
                ];
            }

            if (!empty($changes)) {
                $eventType = 'STUDENT_UPDATED';
                $desc = 'Student profile updated.';
                
                if (isset($changes['status'])) {
                    if ($changes['status'] === 'Inactive') {
                        $eventType = 'STUDENT_DISABLED';
                        $desc = 'Student disabled by administrator.';
                    } elseif ($changes['status'] === 'Active') {
                        $eventType = 'STUDENT_ENABLED';
                        $desc = 'Student enabled by administrator.';
                    }
                }

                if ($allocationChanged) {
                    $eventType = 'ROOM_TRANSFERRED';
                    $desc = 'Student room/bed allocation transferred.';
                }
                StudentHistoryService::record($id, $eventType, $desc, $oldValues, $changes, Session::get('admin_id'), $this->db);
            }

            if ($startedTransaction) {
                $this->db->commit();
            }
            return ['success' => true];
        } catch (Exception $e) {
            if ($startedTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getRoomOptionsForStudent($studentId) {
        $student = $this->studentRepo->findById((int)$studentId);
        if (!$student) {
            return [];
        }
        $currentRoomId = (int)($student['room_id'] ?? 0);
        $rooms = $this->roomRepo->findAllWithAvailability();
        $options = [];
        foreach ($rooms as $room) {
            $roomId = (int)$room['id'];
            if (($room['status'] ?? '') === 'Disabled') {
                continue;
            }
            $beds = $this->roomRepo->getAvailableBedsForRoom($roomId);
            if ($roomId === $currentRoomId && !empty($student['bed_number'])) {
                $beds[] = (int)$student['bed_number'];
                $beds = array_values(array_unique($beds));
                sort($beds);
            }
            if (!empty($beds) || $roomId === $currentRoomId) {
                $room['available_bed_numbers'] = $beds;
                $options[] = $room;
            }
        }
        return $options;
    }

    public function getStudentFeeHistory($studentId) {
        return $this->studentRepo->getFeeHistory($studentId);
    }

    public function getStudentSecurityDeposit($studentId) {
        $stmt = $this->db->prepare("
            SELECT sd.*, 
                   (SELECT COALESCE(SUM(sdt.amount), 0) FROM security_deposit_transactions sdt WHERE sdt.security_deposit_id = sd.id AND sdt.transaction_type IN ('ADJUSTMENT', 'FORFEIT')) AS total_deducted,
                   (SELECT COALESCE(SUM(sdt.amount), 0) FROM security_deposit_transactions sdt WHERE sdt.security_deposit_id = sd.id AND sdt.transaction_type = 'REFUND') AS total_refunded
            FROM security_deposits sd
            WHERE sd.student_id = ?
            ORDER BY sd.id DESC
            LIMIT 1
        ");
        $stmt->execute([$studentId]);
        $securityDeposit = $stmt->fetch();
        if (!$securityDeposit) {
            return false;
        }
        $transactionStmt = $this->db->prepare("
            SELECT sdt.*, COALESCE(sdt.processed_at, sdt.created_at) AS payment_date, a.username AS admin_username
            FROM security_deposit_transactions sdt
            LEFT JOIN admins a ON a.id = sdt.created_by_admin
            WHERE sdt.security_deposit_id = ?
            ORDER BY sdt.id ASC
        ");
        $transactionStmt->execute([(int)$securityDeposit['id']]);
        $securityDeposit['transactions'] = $transactionStmt->fetchAll();
        return $securityDeposit;
    }
}
