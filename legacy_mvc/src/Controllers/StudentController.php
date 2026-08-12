<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Session;
use App\Core\CSRF;
use App\Services\StudentService;

class StudentController {
    private $studentService;

    public function __construct() {
        Auth::check();
        $this->studentService = new StudentService();
    }

    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = $page > 0 ? $page : 1;
        $perPage = 10;
        
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];

        $result = $this->studentService->getAllStudents($filters, $page, $perPage);
        
        View::render('admin/students/index', [
            'title' => 'Students',
            'students' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => $perPage,
            'filters' => $filters
        ], 'admin');
    }

    public function create() {
        View::render('admin/students/create', [
            'title' => 'Add Student',
            'csrf_token' => CSRF::generateToken()
        ], 'admin');
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }
        
        CSRF::verifyToken($_POST['csrf_token'] ?? '');

        // Basic required field validation could be done here or in service
        $required = ['full_name', 'cnic', 'phone', 'address', 'guardian_name', 'guardian_phone', 'guardian_cnic', 'relation'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                Session::set('error', 'Please fill all required fields.');
                header('Location: ' . (require __DIR__ . '/../../config/app.php')['base_url'] . '/students/create');
                exit;
            }
        }

        $result = $this->studentService->createStudent($_POST);
        
        if ($result['success']) {
            Session::set('success', 'Student added successfully.');
            header('Location: ' . (require __DIR__ . '/../../config/app.php')['base_url'] . '/students');
        } else {
            Session::set('error', $result['error']);
            header('Location: ' . (require __DIR__ . '/../../config/app.php')['base_url'] . '/students/create');
        }
        exit;
    }

    public function edit($id) {
        $student = $this->studentService->getStudent($id);
        if (!$student) {
            Session::set('error', 'Student not found.');
            header('Location: ' . (require __DIR__ . '/../../config/app.php')['base_url'] . '/students');
            exit;
        }

        View::render('admin/students/edit', [
            'title' => 'Edit Student',
            'student' => $student,
            'csrf_token' => CSRF::generateToken()
        ], 'admin');
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }
        
        CSRF::verifyToken($_POST['csrf_token'] ?? '');

        $result = $this->studentService->updateStudent($id, $_POST);
        
        if ($result['success']) {
            Session::set('success', 'Student updated successfully.');
            header('Location: ' . (require __DIR__ . '/../../config/app.php')['base_url'] . '/students');
        } else {
            Session::set('error', $result['error']);
            header('Location: ' . (require __DIR__ . '/../../config/app.php')['base_url'] . '/students/edit/' . $id);
        }
        exit;
    }
}
