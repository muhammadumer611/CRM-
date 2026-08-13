<?php
namespace App\Services;

use App\Repositories\StudentRepository;
use App\Repositories\AdminRepository;
use App\Core\Session;
use App\Services\AuditLogger;

class StudentService {
    private $studentRepo;
    private $adminRepo;

    public function __construct() {
        $this->studentRepo = new StudentRepository();
        $this->adminRepo = new AdminRepository();
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
        // Validate CNIC format (13 digits)
        if (!preg_match('/^[0-9]{13}$/', $data['cnic'])) {
            return ['success' => false, 'error' => 'CNIC must be 13 digits without dashes.'];
        }
        
        // Check duplicate CNIC
        if ($this->studentRepo->findByCnic($data['cnic'])) {
            return ['success' => false, 'error' => 'A student with this CNIC already exists.'];
        }

        $data['student_id_str'] = $this->studentRepo->generateStudentId();
        
        // Filter keys for DB
        $dbData = [
            'student_id_str' => $data['student_id_str'],
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

        $id = $this->studentRepo->create($dbData);
        
        if ($id) {
            AuditLogger::logAdminAction(
                'STUDENT_CREATED',
                'student',
                $id,
                'Student profile created: ' . $dbData['student_id_str'],
                null,
                $dbData
            );

            $this->adminRepo->logAction(Session::get('admin_id'), 'Create Student', "Created student ID: {$dbData['student_id_str']}", $_SERVER['REMOTE_ADDR']);
            
            \App\Services\StudentHistoryService::record(
                $id,
                'STUDENT_CREATED',
                'Student profile created.',
                null,
                $dbData
            );
            
            return ['success' => true, 'id' => $id];
        }
        
        return ['success' => false, 'error' => 'Failed to create student.'];
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
