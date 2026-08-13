<?php
namespace Controllers;

use Core\Response;
use Core\Validator;
use Services\FeeService;
use Exception;

class FeeController {
    private $service;

    public function __construct() {
        $this->service = new FeeService();
    }

    private function requireAdmin() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['admin_id'])) {
            Response::error('Unauthorized. Admin access required.', 401);
        }
    }

    public function create() {
        $this->requireAdmin();
        global $requestBody;

        $validator = new Validator($requestBody);
        $rules = [
            'student_id' => 'required|integer',
            'billing_month' => 'required|integer',
            'billing_year' => 'required|integer',
            'amount' => 'required|numeric',
            'due_date' => 'required|date'
        ];

        if (!$validator->validate($rules)) {
            Response::error('Validation failed.', 422, $validator->getErrors());
        }

        if ($requestBody['billing_month'] < 1 || $requestBody['billing_month'] > 12) {
            Response::error('Validation failed.', 422, ['billing_month' => ['Must be between 1 and 12.']]);
        }

        try {
            $id = $this->service->createFee($requestBody);
            Response::json(true, 'Invoice created successfully.', ['id' => $id], null, 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 409);
        }
    }

    public function index() {
        $this->requireAdmin();
        $filters = $_GET;
        try {
            $fees = $this->service->searchFees($filters);
            Response::success('Invoices retrieved successfully.', ['fees' => $fees]);
        } catch (Exception $e) {
            Response::error('Failed to retrieve invoices.', 500);
        }
    }

    public function dashboard() {
        $this->requireAdmin();
        try {
            $data = $this->service->getDashboardData();
            Response::success('Fee dashboard retrieved successfully.', $data);
        } catch (Exception $e) {
            Response::error('Failed to retrieve dashboard.', 500);
        }
    }

    public function show($params) {
        $this->requireAdmin();
        try {
            $fee = $this->service->getFee($params['id']);
            Response::success('Invoice retrieved successfully.', ['fee' => $fee]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    public function paymentHistory($params) {
        $this->requireAdmin();
        try {
            $history = $this->service->getPaymentHistory($params['id']);
            Response::success('Payment history retrieved successfully.', ['payments' => $history]);
        } catch (Exception $e) {
            Response::error('Failed to retrieve payment history.', 500);
        }
    }

    public function receipt($params) {
        $this->requireAdmin();
        try {
            $receipt = $this->service->getReceipt($params['id']);
            Response::success('Receipt retrieved successfully.', ['receipt' => $receipt]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    public function studentFees($params) {
        $this->requireAdmin();
        $filters = ['student_id' => $params['student_id']];
        try {
            $fees = $this->service->searchFees($filters);
            Response::success('Student fee invoices retrieved.', ['fees' => $fees]);
        } catch (Exception $e) {
            Response::error('Failed to retrieve student invoices.', 500);
        }
    }

    public function studentSummary($params) {
        $this->requireAdmin();
        try {
            $summary = $this->service->getStudentFeeSummary($params['student_id']);
            if (!$summary || !$summary['student_id']) {
                Response::error('Student not found', 404);
            }
            Response::success('Student summary retrieved.', ['summary' => $summary]);
        } catch (Exception $e) {
            Response::error('Failed to retrieve summary.', 500);
        }
    }

    public function statistics() {
        $this->requireAdmin();
        try {
            $stats = $this->service->getStatistics();
            Response::success('Statistics retrieved successfully.', ['statistics' => $stats]);
        } catch (Exception $e) {
            Response::error('Failed to retrieve statistics.', 500);
        }
    }

    public function recordPayment($params) {
        $this->requireAdmin();
        global $requestBody;

        if (empty($requestBody['payment_amount']) || !is_numeric($requestBody['payment_amount']) || (float)$requestBody['payment_amount'] <= 0) {
            Response::error('Payment amount is required and must be greater than zero.', 422);
        }

        $validator = new Validator($requestBody);
        $rules = [
            'payment_amount' => 'required|numeric',
            'payment_method' => 'required|string|max:50',
            'transaction_ref' => 'string|max:100',
            'remarks' => 'string'
        ];

        if (!$validator->validate($rules)) {
            Response::error('Validation failed.', 422, $validator->getErrors());
        }

        $method = trim((string)($requestBody['payment_method'] ?? ''));
        if (!in_array($method, ['Cash', 'Bank Transfer', 'Online', 'Other'], true)) {
            Response::error('Unsupported payment method.', 422);
        }

        try {
            $this->service->recordPayment(
                $params['id'],
                $requestBody['payment_amount'],
                $method,
                $requestBody['transaction_ref'] ?? null,
                $requestBody['remarks'] ?? null,
                $_SESSION['admin_id']
            );
            Response::success('Payment recorded successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
