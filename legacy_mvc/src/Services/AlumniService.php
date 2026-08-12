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
    private $db;

    public function __construct() {
        $this->alumniRepo = new AlumniRepository();
        $this->studentRepo = new StudentRepository();
        $this->feeRepo = new FeeRepository();
        $this->db = Database::getInstance()->getConnection();
    }

    public function convertToAlumni($studentId, $leavingDate, $leavingReason, $remarks = '') {
        $this->db->beginTransaction();

        try {
            // Lock and fetch student
            $stmt = $this->db->prepare("SELECT * FROM students WHERE id = ? FOR UPDATE");
            $stmt->execute([$studentId]);
            $student = $stmt->fetch();

            if (!$student) {
                throw new Exception("Student not found.");
            }
            if ($student['status'] !== 'Active') {
                throw new Exception("Only active students can be converted to alumni.");
            }

            // Duplicate protection
            $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM alumni WHERE original_student_id = ? FOR UPDATE");
            $stmtCheck->execute([$student['student_id_str']]);
            if ($stmtCheck->fetchColumn() > 0) {
                throw new Exception("Alumni record already exists for this student.");
            }

            // Calculate outstanding fees
            $outstandingFee = $this->feeRepo->getOutstandingBalance($studentId, $this->db);
            $finalFeeStatus = ($outstandingFee > 0) ? "Has Pending Dues (Rs. " . number_format($outstandingFee, 2) . ")" : "Cleared";

            // Process Active Room Allocation
            $stmtAlloc = $this->db->prepare("SELECT * FROM room_allocations WHERE student_id = ? AND status = 'Active' FOR UPDATE");
            $stmtAlloc->execute([$studentId]);
            $alloc = $stmtAlloc->fetch();

            $prevRoomStr = null;
            $prevBed = null;
            $joiningDate = null;

            if ($alloc) {
                $prevRoomId = $alloc['room_id'];
                $prevBed = $alloc['bed_number'];
                $joiningDate = $alloc['joining_date'];

                // Get Room Info
                $stmtRoom = $this->db->prepare("SELECT * FROM rooms WHERE id = ? FOR UPDATE");
                $stmtRoom->execute([$prevRoomId]);
                $room = $stmtRoom->fetch();

                if (!$room) {
                    throw new Exception("Associated room not found.");
                }

                $prevRoomStr = $room['block'] . '-' . $room['room_number'];

                // Close Allocation
                $stmtCloseAlloc = $this->db->prepare("UPDATE room_allocations SET status = 'Closed', leaving_date = ? WHERE id = ?");
                $stmtCloseAlloc->execute([$leavingDate, $alloc['id']]);

                // Update Room Occupancy
                $newOccupied = max(0, $room['occupied_beds'] - 1);
                $newStatus = ($newOccupied == 0) ? 'Available' : 'Partially Occupied';
                
                // Do not override 'Disabled' status
                if ($room['status'] === 'Disabled') {
                    $newStatus = 'Disabled';
                }
                
                $stmtUpdateRoom = $this->db->prepare("UPDATE rooms SET occupied_beds = ?, status = ? WHERE id = ?");
                $stmtUpdateRoom->execute([$newOccupied, $newStatus, $prevRoomId]);
            }

            // Create Alumni Record
            $guardianInfo = json_encode([
                'name' => $student['guardian_name'],
                'phone' => $student['guardian_phone'],
                'cnic' => $student['guardian_cnic'],
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
                'remarks' => $remarks
            ], $this->db);

            // Set Student Status to Inactive
            $stmtUpdateStudent = $this->db->prepare("UPDATE students SET status = 'Inactive' WHERE id = ?");
            $stmtUpdateStudent->execute([$studentId]);

            // Create History Record
            $oldValue = [
                'status' => 'Active',
                'room_allocation' => $alloc ? ['room' => $prevRoomStr, 'bed' => $prevBed] : null
            ];
            $newValue = [
                'status' => 'Inactive',
                'alumni_id' => $alumniId,
                'leaving_date' => $leavingDate
            ];
            
            StudentHistoryService::record(
                $studentId,
                'STUDENT_MARKED_ALUMNI',
                "Student {$student['student_id_str']} was marked as alumni. Reason: {$leavingReason}.",
                $oldValue,
                $newValue,
                $this->db
            );

            $this->db->commit();
            return ['success' => true, 'alumni_id' => $alumniId];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            // Log technical error
            \App\Repositories\AdminRepository::logAction(Session::get('admin_id'), 'Error', 'Alumni conversion failed: ' . $e->getMessage(), $_SERVER['REMOTE_ADDR']);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAllAlumni($filters, $page, $perPage) {
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
