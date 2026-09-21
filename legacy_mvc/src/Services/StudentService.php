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
        $addedByName = trim((string)($data['added_by_name'] ?? ''));
        if ($addedByName === '') {
            return ['success' => false, 'error' => 'Added By is required. Please enter the staff member name who added this student.'];
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
            return ['success' => false, 'error' => 'Please enter a valid CNIC.'];
        }

        $phone = self::sanitizePhone($data['phone'] ?? '');
        $guardianPhone = self::sanitizePhone($data['guardian_phone'] ?? '');
        if (!self::validatePhone($phone) || !self::validatePhone($guardianPhone)) {
            return ['success' => false, 'error' => 'Student and guardian phone numbers must be valid 11-digit Pakistani mobile numbers.'];
        }

        $monthlyFee = isset($data['monthly_fee']) && $data['monthly_fee'] !== '' ? (float)$data['monthly_fee'] : 0.0;
        $securityDeposit = isset($data['security_deposit']) && $data['security_deposit'] !== '' ? (float)$data['security_deposit'] : 0.0;

        if ($monthlyFee <= 0) {
            return ['success' => false, 'error' => 'Please enter a valid monthly fee.'];
        }
        if ($securityDeposit < 0) {
            return ['success' => false, 'error' => 'Security deposit cannot be negative.'];
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

        $shouldCommit = !$this->db->inTransaction();
        if ($shouldCommit) {
            $this->db->beginTransaction();
        }

        try {
            $stmtCnic = $this->db->prepare("SELECT id FROM students WHERE cnic = ? FOR UPDATE");
            $stmtCnic->execute([$cleanCnic]);
            if ($stmtCnic->fetch()) {
                throw new Exception('This CNIC is already associated with an active student.');
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
                'blood_group' => empty($data['blood_group']) ? null : trim($data['blood_group']),
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
                'added_by_name' => $addedByName,
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

            if ($securityDeposit > 0) {
                $stmtSD = $this->db->prepare("INSERT INTO security_deposits (student_id, original_amount, remaining_amount, status) VALUES (?, ?, ?, 'HELD')");
                $stmtSD->execute([$studentId, $securityDeposit, $securityDeposit]);
            }

            $invoice = $this->billingService->createFirstMonthInvoice($studentId, $monthlyFee, $joiningDate, [
                'first_month_billing_mode' => $data['first_month_billing_mode'] ?? 'automatic',
                'first_month_discount' => $data['first_month_discount'] ?? 0,
                'first_month_discount_reason' => $data['first_month_discount_reason'] ?? '',
                'created_by_admin' => Session::get('admin_id'),
            ]);
            $invoiceId = $invoice['id'];

            StudentHistoryService::record($studentId, 'STUDENT_CREATED', 'Single-person onboarding completed.', null, array_merge($dbData, ['allocation_id' => $allocationId, 'invoice_id' => $invoiceId]), Session::get('admin_id'), $this->db);
            if ($shouldCommit) {
                $this->db->commit();
            }

            return ['success' => true, 'id' => $studentId, 'student_id_str' => $studentIdStr];
        } catch (Exception $e) {
            if ($shouldCommit && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function onboardFullRoom($data) {
        $addedByName = trim((string)($data['added_by_name'] ?? ''));
        if ($addedByName === '') {
            return ['success' => false, 'error' => 'Added By is required. Please enter the staff member name who added this student.'];
        }

        $roomId = !empty($data['room_id']) ? (int)$data['room_id'] : 0;
        $monthlyRoomFee = isset($data['monthly_room_fee']) && $data['monthly_room_fee'] !== '' ? (float)$data['monthly_room_fee'] : 0.0;
        $securityDeposit = isset($data['security_deposit']) && $data['security_deposit'] !== '' ? (float)$data['security_deposit'] : 0.0;

        if (!$roomId) {
            return ['success' => false, 'error' => 'Please select a room.'];
        }
        if ($monthlyRoomFee <= 0) {
            return ['success' => false, 'error' => 'Please enter a valid monthly room fee.'];
        }
        if ($securityDeposit < 0) {
            return ['success' => false, 'error' => 'Security deposit cannot be negative.'];
        }

        $occupants = $data['occupants'] ?? [];
        if (!is_array($occupants) || count($occupants) === 0) {
            return ['success' => false, 'error' => 'Please add at least one occupant.'];
        }

        $shouldCommit = !$this->db->inTransaction();
        if ($shouldCommit) {
            $this->db->beginTransaction();
        }

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
            $joiningDate = !empty($data['joining_date']) ? $data['joining_date'] : date('Y-m-d');

            foreach ($occupants as $index => $occupant) {
                $fullName = trim((string)($occupant['full_name'] ?? ''));
                $cnic = self::sanitizeCnic($occupant['cnic'] ?? '');
                if ($fullName === '' || !self::validateCnic($cnic)) {
                    throw new Exception('Please enter a valid CNIC for each person.');
                }

                $stmtCnic = $this->db->prepare("SELECT id FROM students WHERE cnic = ? FOR UPDATE");
                $stmtCnic->execute([$cnic]);
                if ($stmtCnic->fetch()) {
                    throw new Exception('This CNIC is already associated with an active student.');
                }

                $studentIdStr = $this->studentRepo->generateStudentId($this->db);
                $phone = self::sanitizePhone($occupant['phone'] ?? '');
                $guardianName = trim((string)($occupant['guardian_name'] ?? '')) ?: ($fullName . ' Guardian');
                $guardianPhone = self::sanitizePhone($occupant['guardian_phone'] ?? '');
                if (!self::validatePhone($phone) || !self::validatePhone($guardianPhone)) {
                    throw new Exception('Each full-room occupant needs valid student and guardian phone numbers.');
                }
                $residentType = self::normalizeResidentType($occupant['resident_type'] ?? 'Student');
                $collegeUniversity = trim((string)($occupant['college_university'] ?? ''));
                $jobWorkplace = trim((string)($occupant['job_workplace'] ?? ''));
                $vehicleNumber = trim((string)($occupant['vehicle_number'] ?? ''));
                $vehicleType = self::normalizeVehicleType($occupant['vehicle_type'] ?? '', $occupant['vehicle_type_other'] ?? '');
                $note = trim((string)($occupant['note'] ?? ''));

                $dbData = [
                    'student_id_str' => $studentIdStr,
                    'full_name' => $fullName,
                    'cnic' => $cnic,
                    'phone' => $phone,
                    'email' => empty($occupant['email'] ?? '') ? null : trim((string)$occupant['email']),
                    'blood_group' => empty($occupant['blood_group'] ?? '') ? null : trim((string)$occupant['blood_group']),
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
                    'added_by_name' => $addedByName,
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

                if ($securityDeposit > 0) {
                    $stmtSD = $this->db->prepare("INSERT INTO security_deposits (student_id, original_amount, remaining_amount, status) VALUES (?, ?, ?, 'HELD')");
                    $stmtSD->execute([$studentId, $securityDeposit, $securityDeposit]);
                }

                $invoice = $this->billingService->createFirstMonthInvoice($studentId, $monthlyRoomFee, $joiningDate, [
                    'first_month_billing_mode' => $data['first_month_billing_mode'] ?? 'automatic',
                    'first_month_discount' => $data['first_month_discount'] ?? 0,
                    'first_month_discount_reason' => $data['first_month_discount_reason'] ?? '',
                    'created_by_admin' => Session::get('admin_id'),
                ]);
                StudentHistoryService::record($studentId, 'STUDENT_CREATED', 'Full-room onboarding completed.', null, $dbData, Session::get('admin_id'), $this->db);
            }

            $newOccupied = $currentOccupied + $requestedCount;
            $newStatus = ($newOccupied >= $totalBeds) ? 'Occupied' : 'Partially Occupied';
            $this->db->prepare("UPDATE rooms SET occupied_beds = ?, status = ? WHERE id = ?")->execute([$newOccupied, $newStatus, $roomId]);

            $this->db->commit();
            return ['success' => true, 'count' => count($createdStudentIds), 'student_id_str' => $createdStudentIds[0]['student_id_str'] ?? null];
        } catch (Exception $e) {
            $this->db->rollBack();
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
            'guardian_address'   => empty($data['guardian_address']) ? null : trim($data['guardian_address']),
            'relation'           => trim($data['relation']),
            'resident_type'      => $residentType,
            'college_university' => $residentType === 'Student' ? ($collegeUniversity !== '' ? $collegeUniversity : null) : null,
            'job_workplace'      => $residentType === 'Job / Working' ? ($jobWorkplace !== '' ? $jobWorkplace : null) : null,
            'vehicle_number'     => $vehicleNumber !== '' ? $vehicleNumber : null,
            'vehicle_type'       => $vehicleType !== '' ? $vehicleType : null,
            'note'               => $note !== '' ? $note : null,
            'status'             => $data['status'] ?? 'Active'
        ];

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
                $newAllocationStmt->execute([(int)$id, $targetRoomId, $targetBedNumber, date('Y-m-d')]);
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

            if ($allocationChanged) {
                $changes['room_allocation'] = ['room_id' => $targetRoomId, 'bed_number' => $targetBedNumber];
                $oldValues['room_allocation'] = ['room_id' => $currentRoomId, 'bed_number' => $currentBedNumber];
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
        return $stmt->fetch();
    }
}
