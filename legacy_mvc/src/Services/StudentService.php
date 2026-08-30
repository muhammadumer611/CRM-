<?php
namespace App\Services;

use App\Repositories\StudentRepository;
use App\Repositories\AdminRepository;
use App\Core\Session;
use App\Services\AuditLogger;

class StudentService {
    private $studentRepo;
    private $adminRepo;
    private $db;

    public function __construct() {
        $this->studentRepo = new StudentRepository();
        $this->adminRepo = new AdminRepository();
        $this->db = \App\Core\Database::getInstance()->getConnection();
    }

    public function getAllStudents($filters, $page, $perPage) {
        $offset = ($page - 1) * $perPage;
        return [
            'data' => $this->studentRepo->findAll($filters, $perPage, $offset),
            'total' => $this->studentRepo->count($filters)
        ];
    }

    public function getStudent($id) {
        return $this->studentRepo->findById($id);
    }

    public function createStudent($data) {
        if (!preg_match('/^[0-9]{13}$/', trim((string)($data['cnic'] ?? '')))) {
            return ['success' => false, 'error' => 'CNIC must be 13 digits without dashes.'];
        }

        if ($this->studentRepo->findByCnic(trim((string)($data['cnic'] ?? '')))) {
            return ['success' => false, 'error' => 'A student with this CNIC already exists.'];
        }

        $this->db->beginTransaction();

        try {
            $roomEnabled = !empty($data['room_allocation_enabled']) && $data['room_allocation_enabled'] == '1';
            $roomId = isset($data['room_id']) ? (int)$data['room_id'] : 0;
            $allocationType = strtoupper(trim((string)($data['allocation_type'] ?? 'BED')));
            if (!in_array($allocationType, ['BED', 'FULL_ROOM'], true)) {
                $allocationType = 'BED';
            }
            $bedNumber = isset($data['bed_number']) ? (int)$data['bed_number'] : 0;
            $joiningDate = !empty($data['joining_date']) ? $data['joining_date'] : date('Y-m-d');
            $allocationRemarks = trim((string)($data['allocation_remarks'] ?? ''));

            $studentIdStr = $this->studentRepo->generateStudentId();
            $dbData = [
                'student_id_str' => $studentIdStr,
                'full_name' => trim((string)($data['full_name'] ?? '')),
                'cnic' => trim((string)($data['cnic'] ?? '')),
                'phone' => trim((string)($data['phone'] ?? '')),
                'email' => empty($data['email']) ? null : trim((string)$data['email']),
                'blood_group' => empty($data['blood_group']) ? null : trim((string)$data['blood_group']),
                'address' => trim((string)($data['address'] ?? '')),
                'guardian_name' => trim((string)($data['guardian_name'] ?? '')),
                'guardian_phone' => trim((string)($data['guardian_phone'] ?? '')),
                'guardian_cnic' => trim((string)($data['guardian_cnic'] ?? '')),
                'relation' => trim((string)($data['relation'] ?? '')),
                'status' => $data['status'] ?? 'Active'
            ];

            $studentId = $this->studentRepo->create($dbData, $this->db);
            if (!$studentId) {
                throw new \Exception('Failed to create student record.');
            }

            $roomAllocationId = null;
            if ($roomEnabled) {
                if ($roomId <= 0) {
                    throw new \Exception('Please select a valid room for allocation.');
                }

                $stmtRoom = $this->db->prepare("SELECT * FROM rooms WHERE id = ? FOR UPDATE");
                $stmtRoom->execute([$roomId]);
                $room = $stmtRoom->fetch();

                if (!$room) {
                    throw new \Exception('Selected room does not exist.');
                }

                if ($room['status'] === 'Disabled') {
                    throw new \Exception('Room allocation is not allowed for a disabled room.');
                }

                $allocStmt = $this->db->prepare("SELECT id FROM room_allocations WHERE student_id = ? AND status = 'Active' LIMIT 1");
                $allocStmt->execute([$studentId]);
                if ($allocStmt->fetch()) {
                    throw new \Exception('This student already has an active room allocation.');
                }

                $summaryStmt = $this->db->prepare("
                    SELECT
                        COUNT(*) AS active_allocations,
                        COALESCE(SUM(CASE
                            WHEN bed_number = 0 THEN ?
                            WHEN bed_number > 0 THEN 1
                            ELSE 0
                        END), 0) AS effective_occupied_beds
                    FROM room_allocations
                    WHERE room_id = ? AND status = 'Active'
                ");
                $summaryStmt->execute([(int)$room['total_beds'], $roomId]);
                $occupancy = $summaryStmt->fetch();
                $effectiveOccupied = (int)($occupancy['effective_occupied_beds'] ?? 0);

                if ($allocationType === 'FULL_ROOM') {
                    if ($effectiveOccupied > 0 || (int)($occupancy['active_allocations'] ?? 0) > 0) {
                        throw new \Exception('This room is not completely available for a full-room allocation.');
                    }

                    $allocInsert = $this->db->prepare("INSERT INTO room_allocations (student_id, room_id, bed_number, joining_date, status, remarks) VALUES (?, ?, 0, ?, 'Active', ?)");
                    $allocInsert->execute([$studentId, $roomId, $joiningDate, ($allocationRemarks ?: 'FULL_ROOM allocation')]);
                    $roomAllocationId = (int)$this->db->lastInsertId();

                    $this->db->prepare("UPDATE rooms SET occupied_beds = total_beds, status = 'Occupied' WHERE id = ?")->execute([$roomId]);

                    \App\Services\StudentHistoryService::record(
                        $studentId,
                        'ROOM_ALLOCATED',
                        'Allocated to room ' . $room['block'] . ' - ' . $room['room_number'] . ' as FULL_ROOM.',
                        null,
                        ['room_id' => $roomId, 'allocation_type' => 'FULL_ROOM', 'joining_date' => $joiningDate],
                        $this->db
                    );
                } else {
                    if ($bedNumber <= 0) {
                        throw new \Exception('Please select a valid room and bed for allocation.');
                    }

                    if ($bedNumber < 1 || $bedNumber > (int)$room['total_beds']) {
                        throw new \Exception('Selected bed number is invalid for this room.');
                    }

                    if ($effectiveOccupied >= (int)$room['total_beds']) {
                        throw new \Exception('The selected room is fully occupied.');
                    }

                    $bedStmt = $this->db->prepare("SELECT id FROM room_allocations WHERE room_id = ? AND bed_number = ? AND status = 'Active' LIMIT 1");
                    $bedStmt->execute([$roomId, $bedNumber]);
                    if ($bedStmt->fetch()) {
                        throw new \Exception('The selected bed is no longer available.');
                    }

                    $allocInsert = $this->db->prepare("INSERT INTO room_allocations (student_id, room_id, bed_number, joining_date, status, remarks) VALUES (?, ?, ?, ?, 'Active', ?)");
                    $allocInsert->execute([$studentId, $roomId, $bedNumber, $joiningDate, $allocationRemarks]);
                    $roomAllocationId = (int)$this->db->lastInsertId();

                    $newOccupied = $effectiveOccupied + 1;
                    $newStatus = $newOccupied >= (int)$room['total_beds'] ? 'Occupied' : 'Partially Occupied';
                    $this->db->prepare("UPDATE rooms SET occupied_beds = ?, status = ? WHERE id = ?")->execute([$newOccupied, $newStatus, $roomId]);

                    \App\Services\StudentHistoryService::record(
                        $studentId,
                        'ROOM_ALLOCATED',
                        'Allocated to room ' . $room['block'] . ' - ' . $room['room_number'] . ', bed ' . $bedNumber . '.',
                        null,
                        ['room_id' => $roomId, 'bed_number' => $bedNumber, 'joining_date' => $joiningDate],
                        $this->db
                    );
                }
            }

            if ($allocationType === 'FULL_ROOM') {
                $rawOccupants = $data['room_occupants'] ?? [];
                if (!is_array($rawOccupants)) {
                    $rawOccupants = [];
                }

                $normalizedOccupants = [];
                $seenCnics = [];
                foreach ($rawOccupants as $index => $occupant) {
                    if (!is_array($occupant)) {
                        continue;
                    }

                    $fullName = trim((string)($occupant['full_name'] ?? ''));
                    $cnic = trim((string)($occupant['cnic'] ?? ''));
                    $phone = trim((string)($occupant['phone'] ?? ''));
                    $relation = trim((string)($occupant['relation'] ?? ''));

                    if ($fullName === '' && $cnic === '' && $phone === '' && $relation === '') {
                        continue;
                    }

                    if (!preg_match('/^[0-9]{13}$/', $cnic)) {
                        throw new \Exception('Each full-room occupant must have a valid 13-digit CNIC.');
                    }

                    if (isset($seenCnics[$cnic])) {
                        throw new \Exception('Duplicate occupant CNIC detected in the same full-room allocation.');
                    }

                    $existingOccupant = $this->db->prepare("SELECT id FROM room_occupants WHERE cnic = ? LIMIT 1");
                    $existingOccupant->execute([$cnic]);
                    if ($existingOccupant->fetch()) {
                        throw new \Exception('Occupant CNIC already exists in the system for a different room allocation.');
                    }

                    $normalizedOccupants[] = [
                        'full_name' => $fullName,
                        'cnic' => $cnic,
                        'phone' => $phone,
                        'relation' => $relation,
                    ];
                    $seenCnics[$cnic] = true;
                }

                if (count($normalizedOccupants) === 0) {
                    throw new \Exception('A full-room allocation requires at least one occupant record.');
                }

                $occupantInsert = $this->db->prepare("INSERT INTO room_occupants (room_allocation_id, full_name, cnic, phone, relation) VALUES (?, ?, ?, ?, ?)");
                foreach ($normalizedOccupants as $occupant) {
                    $occupantInsert->execute([
                        $roomAllocationId,
                        $occupant['full_name'],
                        $occupant['cnic'],
                        $occupant['phone'],
                        $occupant['relation'],
                    ]);
                }
            }

            $monthlyFee = isset($data['monthly_fee']) ? (float)$data['monthly_fee'] : 0.0;
            $securityDeposit = isset($data['security_deposit']) ? (float)$data['security_deposit'] : 0.0;
            $discount = isset($data['discount']) ? (float)$data['discount'] : 0.0;
            $initialPayment = isset($data['initial_payment']) ? (float)$data['initial_payment'] : 0.0;
            $paymentMethod = trim((string)($data['payment_method'] ?? 'Cash'));
            $transactionRef = trim((string)($data['transaction_ref'] ?? '')) ?: null;
            $firstBillingMonth = isset($data['first_billing_month']) ? (int)$data['first_billing_month'] : (int)date('n');
            $firstBillingYear = isset($data['first_billing_year']) ? (int)$data['first_billing_year'] : (int)date('Y');
            $firstDueDate = !empty($data['due_date']) ? $data['due_date'] : date('Y-m-d', strtotime('+15 days'));

            if ($monthlyFee < 0 || $securityDeposit < 0 || $discount < 0 || $initialPayment < 0) {
                throw new \Exception('Financial values cannot be negative.');
            }

            if ($discount > 0 && $monthlyFee > 0) {
                $monthlyTotal = max(0, $monthlyFee - $discount);
            } else {
                $monthlyTotal = $monthlyFee;
            }

            $firstMonthFee = $monthlyTotal;
            $totalAdmissionCharges = $firstMonthFee + $securityDeposit;
            $remainingAfterInitialPayment = max(0, $totalAdmissionCharges - $initialPayment);

            $feeInvoiceId = null;
            if ($monthlyFee > 0 || $securityDeposit > 0 || !empty($data['financial_setup_enabled']) || !empty($data['monthly_fee'])) {
                $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                $invoiceTotal = $firstMonthFee + $securityDeposit - $discount;
                $appliedInitialPayment = min($initialPayment, $invoiceTotal);
                $invoiceStatus = $appliedInitialPayment > 0 ? ($appliedInitialPayment >= $invoiceTotal ? 'Paid' : 'Partial') : 'Pending';

                $firstInvoice = $this->db->prepare("INSERT INTO fee_records (invoice_number, student_id, billing_month, billing_year, invoice_date, amount, additional_charges, discount, paid_amount, due_date, status, payment_method, transaction_ref, remarks, charge_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'MONTHLY_FEE')");
                $firstInvoice->execute([
                    $invoiceNumber,
                    $studentId,
                    $firstBillingMonth,
                    $firstBillingYear,
                    date('Y-m-d'),
                    $firstMonthFee,
                    $securityDeposit,
                    $discount,
                    $appliedInitialPayment,
                    $firstDueDate,
                    $invoiceStatus,
                    $appliedInitialPayment > 0 ? $paymentMethod : null,
                    $appliedInitialPayment > 0 ? $transactionRef : null,
                    'Admission setup invoice; security deposit included as additional charge.'
                ]);
                $feeInvoiceId = (int)$this->db->lastInsertId();

                if ($appliedInitialPayment > 0) {
                    $paymentStmt = $this->db->prepare("INSERT INTO fee_payments (invoice_id, amount, payment_date, payment_method, transaction_ref, remarks, received_by_admin) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $paymentStmt->execute([
                        $feeInvoiceId,
                        $appliedInitialPayment,
                        date('Y-m-d'),
                        $paymentMethod,
                        $transactionRef,
                        'Initial admission payment applied to opening invoice.',
                        Session::get('admin_id')
                    ]);
                }
            }

            AuditLogger::logAdminAction(
                'ADMISSION_COMPLETED',
                'student',
                $studentId,
                'Student admission completed for ' . $dbData['full_name'],
                null,
                ['student_id' => $studentId, 'room_allocation_enabled' => $roomEnabled, 'room_id' => $roomId, 'bed_number' => $bedNumber, 'initial_payment' => $initialPayment, 'total_charges' => $totalAdmissionCharges]
            );

            \App\Services\StudentHistoryService::record(
                $studentId,
                'ADMISSION_COMPLETED',
                'Admission completed and initial setup created.',
                null,
                ['room_allocation_enabled' => $roomEnabled, 'room_id' => $roomId, 'bed_number' => $bedNumber, 'monthly_fee' => $monthlyFee, 'security_deposit' => $securityDeposit, 'initial_payment' => $initialPayment],
                $this->db
            );

            $this->db->commit();

            return ['success' => true, 'id' => $studentId, 'allocation_id' => $roomAllocationId, 'invoice_id' => $feeInvoiceId, 'remaining_balance' => $remainingAfterInitialPayment];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateStudent($id, $data) {
        $student = $this->studentRepo->findById($id);
        if (!$student) return ['success' => false, 'error' => 'Student not found.'];

        if (!preg_match('/^[0-9]{13}$/', $data['cnic'])) {
            return ['success' => false, 'error' => 'CNIC must be 13 digits without dashes.'];
        }
        
        if ($this->studentRepo->findByCnic($data['cnic'], $id)) {
            return ['success' => false, 'error' => 'A student with this CNIC already exists.'];
        }

        $dbData = [
            'full_name' => trim($data['full_name']),
            'cnic' => trim($data['cnic']),
            'phone' => trim($data['phone']),
            'email' => empty($data['email']) ? null : trim($data['email']),
            'blood_group' => empty($data['blood_group']) ? null : trim($data['blood_group']),
            'address' => trim($data['address']),
            'guardian_name' => trim($data['guardian_name']),
            'guardian_phone' => trim($data['guardian_phone']),
            'guardian_cnic' => trim($data['guardian_cnic']),
            'relation' => trim($data['relation']),
            'status' => $data['status'] ?? 'Active'
        ];

        if ($this->studentRepo->update($id, $dbData)) {
            $this->adminRepo->logAction(Session::get('admin_id'), 'Update Student', "Updated student ID: {$student['student_id_str']}", $_SERVER['REMOTE_ADDR']);

            $changes = [];
            $oldValues = [];
            foreach ($dbData as $key => $value) {
                if (isset($student[$key]) && $student[$key] !== $value) {
                    $changes[$key] = $value;
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
                    } else if ($changes['status'] === 'Active') {
                        $eventType = 'STUDENT_ENABLED';
                        $desc = 'Student enabled by administrator.';
                    }
                }

                AuditLogger::logAdminAction(
                    $eventType,
                    'student',
                    $id,
                    $desc,
                    $oldValues,
                    $changes
                );
                
                \App\Services\StudentHistoryService::record(
                    $id,
                    $eventType,
                    $desc,
                    $oldValues,
                    $changes
                );
            }

            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Failed to update student.'];
    }
}
