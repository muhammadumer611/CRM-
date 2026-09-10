<?php
namespace App\Services;

use App\Repositories\StudentRepository;
use App\Repositories\AdminRepository;
use App\Repositories\FeeRepository;
use App\Core\Session;
use App\Core\Database;
use App\Services\StudentHistoryService;
use Exception;
use PDO;

class StudentService {
    private $studentRepo;
    private $adminRepo;
    private $feeRepo;
    private $db;

    public function __construct() {
        $this->studentRepo = new StudentRepository();
        $this->adminRepo = new AdminRepository();
        $this->feeRepo = new FeeRepository();
        $this->db = Database::getInstance()->getConnection();
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
        $cleanCnic = self::sanitizeCnic($data['cnic'] ?? '');
        if (!self::validateCnic($cleanCnic)) {
            return ['success' => false, 'error' => 'Please enter a valid CNIC.'];
        }

        $cleanGuardianCnic = self::sanitizeCnic($data['guardian_cnic'] ?? '');
        if (!empty($data['guardian_cnic']) && !self::validateCnic($cleanGuardianCnic)) {
            return ['success' => false, 'error' => 'Please enter a valid guardian CNIC.'];
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

        $this->db->beginTransaction();

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

            $stmtBed = $this->db->prepare("SELECT id FROM room_allocations WHERE room_id = ? AND bed_number = ? AND status = 'Active' FOR UPDATE");
            $stmtBed->execute([$roomId, $bedNumber]);
            if ($stmtBed->fetch()) {
                throw new Exception('Selected bed is already occupied.');
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
                'phone' => trim($data['phone']),
                'email' => empty($data['email']) ? null : trim($data['email']),
                'blood_group' => empty($data['blood_group']) ? null : trim($data['blood_group']),
                'address' => trim($data['address']),
                'guardian_name' => trim($data['guardian_name']),
                'guardian_phone' => trim($data['guardian_phone']),
                'guardian_cnic' => $cleanGuardianCnic ?: $cleanCnic,
                'guardian_address' => empty($data['guardian_address']) ? null : trim($data['guardian_address']),
                'relation' => trim($data['relation']),
                'status' => 'Active',
                'monthly_fee' => $monthlyFee,
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

            $billingMonth = (int)date('n', strtotime($joiningDate));
            $billingYear = (int)date('Y', strtotime($joiningDate));
            $dueDate = date('Y-m-d', strtotime($joiningDate . ' +7 days'));
            $invoiceNum = 'INV-' . strtoupper(uniqid());

            $stmtInv = $this->db->prepare("INSERT INTO fee_records (invoice_number, student_id, billing_month, billing_year, invoice_date, amount, due_date, status, charge_type) VALUES (?, ?, ?, ?, CURDATE(), ?, ?, 'Pending', 'MONTHLY_FEE')");
            $stmtInv->execute([$invoiceNum, $studentId, $billingMonth, $billingYear, $monthlyFee, $dueDate]);
            $invoiceId = $this->db->lastInsertId();

            StudentHistoryService::record($studentId, 'STUDENT_CREATED', 'Single-person onboarding completed.', null, array_merge($dbData, ['allocation_id' => $allocationId, 'invoice_id' => $invoiceId]), Session::get('admin_id'), $this->db);
            $this->db->commit();

            return ['success' => true, 'id' => $studentId, 'student_id_str' => $studentIdStr];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function onboardFullRoom($data) {
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

        $this->db->beginTransaction();

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
                $phone = trim((string)($occupant['phone'] ?? '')) ?: '00000000000';
                $guardianName = trim((string)($occupant['guardian_name'] ?? '')) ?: ($fullName . ' Guardian');
                $guardianPhone = trim((string)($occupant['guardian_phone'] ?? '')) ?: '00000000000';
                $guardianCnic = self::sanitizeCnic($occupant['guardian_cnic'] ?? '') ?: $cnic;

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
                    'guardian_cnic' => $guardianCnic,
                    'guardian_address' => empty($occupant['guardian_address'] ?? '') ? null : trim((string)$occupant['guardian_address']),
                    'relation' => trim((string)($occupant['relation'] ?? '')) ?: 'Other',
                    'status' => 'Active',
                    'monthly_fee' => $monthlyRoomFee,
                ];

                $studentId = $this->studentRepo->create($dbData, $this->db);
                if (!$studentId) {
                    throw new Exception('Failed to create one or more student records.');
                }

                $bedNumber = array_shift($availableBeds);
                $stmtAlloc = $this->db->prepare("INSERT INTO room_allocations (student_id, room_id, bed_number, joining_date, status) VALUES (?, ?, ?, ?, 'Active')");
                $stmtAlloc->execute([$studentId, $roomId, $bedNumber, $joiningDate]);
                $createdStudentIds[] = ['id' => $studentId, 'student_id_str' => $studentIdStr, 'bed_number' => $bedNumber];

                if ($securityDeposit > 0) {
                    $stmtSD = $this->db->prepare("INSERT INTO security_deposits (student_id, original_amount, remaining_amount, status) VALUES (?, ?, ?, 'HELD')");
                    $stmtSD->execute([$studentId, $securityDeposit, $securityDeposit]);
                }

                $billingMonth = (int)date('n', strtotime($joiningDate));
                $billingYear = (int)date('Y', strtotime($joiningDate));
                $dueDate = date('Y-m-d', strtotime($joiningDate . ' +7 days'));
                $invoiceNum = 'INV-' . strtoupper(uniqid());
                $stmtInv = $this->db->prepare("INSERT INTO fee_records (invoice_number, student_id, billing_month, billing_year, invoice_date, amount, due_date, status, charge_type) VALUES (?, ?, ?, ?, CURDATE(), ?, ?, 'Pending', 'MONTHLY_FEE')");
                $stmtInv->execute([$invoiceNum, $studentId, $billingMonth, $billingYear, $monthlyRoomFee, $dueDate]);
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

        $cleanCnic = self::sanitizeCnic($data['cnic'] ?? '');
        if (!self::validateCnic($cleanCnic)) {
            return ['success' => false, 'error' => 'CNIC must be 13 digits (e.g. 12345-1234567-1 or 1234512345671).'];
        }

        $cleanGuardianCnic = self::sanitizeCnic($data['guardian_cnic'] ?? '');
        if (!empty($data['guardian_cnic']) && !self::validateCnic($cleanGuardianCnic)) {
            return ['success' => false, 'error' => 'Guardian CNIC must be 13 digits (e.g. 12345-1234567-1 or 1234512345671).'];
        }
        
        if ($this->studentRepo->findByCnic($cleanCnic, $id)) {
            return ['success' => false, 'error' => 'A student with this CNIC already exists.'];
        }

        $dbData = [
            'full_name'        => trim($data['full_name']),
            'cnic'             => $cleanCnic,
            'phone'            => trim($data['phone']),
            'email'            => empty($data['email'])          ? null : trim($data['email']),
            'blood_group'      => empty($data['blood_group'])    ? null : trim($data['blood_group']),
            'address'          => trim($data['address']),
            'guardian_name'    => trim($data['guardian_name']),
            'guardian_phone'   => trim($data['guardian_phone']),
            'guardian_cnic'    => $cleanGuardianCnic,
            'guardian_address' => empty($data['guardian_address']) ? null : trim($data['guardian_address']),
            'relation'         => trim($data['relation']),
            'status'           => $data['status'] ?? 'Active'
        ];

        // Update monthly_fee if provided explicitly
        if (isset($data['monthly_fee']) && $data['monthly_fee'] !== '') {
            $dbData['monthly_fee'] = (float)$data['monthly_fee'];
        }

        if ($this->studentRepo->update($id, $dbData)) {
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

                StudentHistoryService::record($id, $eventType, $desc, $oldValues, $changes);
            }

            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Failed to update student.'];
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
