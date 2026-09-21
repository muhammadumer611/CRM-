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

    public function convertToAlumni($studentId, $leavingDate, $leavingReason, $remarks = '', $securityDeduction = 0.0, $securityRefundRemarks = '', $checkedOutByName = '', $processedByName = '') {
        $checkedOutByName = trim((string)$checkedOutByName);
        $processedByName = trim((string)$processedByName);
        $securityDeduction = (float)$securityDeduction;
        if ($securityDeduction < 0) {
            return ['success' => false, 'error' => 'Security deduction cannot be negative.'];
        }
        if ($securityDeduction > 0 && trim((string)$securityRefundRemarks) === '') {
            return ['success' => false, 'error' => 'Security deduction remarks are required when a cut is entered.'];
        }
        if ($checkedOutByName === '' || $processedByName === '') {
            return ['success' => false, 'error' => 'Checked Out By and Processed By are required.'];
        }

        $this->db->beginTransaction();

        try {
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

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM room_allocations WHERE room_id = ? AND status = 'Active'");
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

            $stmtSD = $this->db->prepare("SELECT * FROM security_deposits WHERE student_id = ? AND remaining_amount > 0 AND status IN ('HELD', 'ADJUSTED', 'PARTIALLY_REFUNDED') ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $stmtSD->execute([$studentId]);
            $securityDeposit = $stmtSD->fetch();

            $depositSettlement = null;
            if ($securityDeposit) {
                $rem = (float)$securityDeposit['remaining_amount'];
                if ($securityDeduction > $rem) {
                    throw new Exception('Security deduction cannot exceed the current security balance.');
                }
                $deduction = $securityDeduction;
                $refund = $rem - $deduction;
                $adminId = Session::get('admin_id');
                $processedStaffName = trim((string)($processedByName ?: $checkedOutByName));

                if ($deduction > 0) {
                    $stmtTx1 = $this->db->prepare("INSERT INTO security_deposit_transactions (security_deposit_id, transaction_type, amount, reason, created_by_admin, processed_by_name, processed_at) VALUES (?, 'ADJUSTMENT', ?, ?, ?, ?, NOW())");
                    $stmtTx1->execute([$securityDeposit['id'], $deduction, $securityRefundRemarks ?: 'Deduction upon checkout', $adminId, $processedStaffName]);
                }

                if ($refund > 0) {
                    $stmtTx2 = $this->db->prepare("INSERT INTO security_deposit_transactions (security_deposit_id, transaction_type, amount, reason, created_by_admin, processed_by_name, processed_at) VALUES (?, 'REFUND', ?, ?, ?, ?, NOW())");
                    $stmtTx2->execute([$securityDeposit['id'], $refund, $securityRefundRemarks ?: 'Refunded upon checkout', $adminId, $processedStaffName]);
                }

                $newDepositStatus = ($refund > 0) ? 'REFUNDED' : 'FORFEITED';
                $stmtUpdateSD = $this->db->prepare("UPDATE security_deposits SET remaining_amount = 0.00, status = ?, processed_by_name = ?, processed_at = NOW() WHERE id = ?");
                $stmtUpdateSD->execute([$newDepositStatus, $processedStaffName, $securityDeposit['id']]);

                $depositSettlement = [
                    'original_amount' => (float)$securityDeposit['original_amount'],
                    'deducted' => $deduction,
                    'refunded' => $refund,
                    'status' => $newDepositStatus,
                    'remarks' => $securityRefundRemarks
                ];
            } elseif ($securityDeduction > 0) {
                throw new Exception('There is no current security balance available for deduction.');
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
                'security_deposit' => $securityDeposit ? ['status' => $securityDeposit['status'], 'remaining' => $securityDeposit['remaining_amount']] : null
            ];
            $newValue = [
                'status' => 'Inactive',
                'alumni_id' => $alumniId,
                'leaving_date' => $leavingDate,
                'security_settlement' => $depositSettlement
            ];

            StudentHistoryService::record(
                $studentId,
                'STUDENT_MARKED_ALUMNI',
                "Student {$student['student_id_str']} was marked as alumni. Reason: {$leavingReason}." . ($depositSettlement ? " Security Deposit Refund: Rs. " . number_format($depositSettlement['refunded'], 2) . " (Deducted: Rs. " . number_format($depositSettlement['deducted'], 2) . ")" : ""),
                $oldValue,
                $newValue,
                Session::get('admin_id'),
                $this->db
            );

            $this->db->commit();
            return ['success' => true, 'alumni_id' => $alumniId, 'deposit_settlement' => $depositSettlement];
        } catch (Exception $e) {
            $this->db->rollBack();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $this->adminRepo->logAction(Session::get('admin_id'), 'Error', 'Alumni conversion failed: ' . $e->getMessage(), $ip);
            return ['success' => false, 'error' => $e->getMessage()];
        }
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
        return $this->alumniRepo->findById($id);
    }
    
    public function getAlumniByOriginalStudentId($studentIdStr) {
        return $this->alumniRepo->findByOriginalStudentId($studentIdStr);
    }
}
