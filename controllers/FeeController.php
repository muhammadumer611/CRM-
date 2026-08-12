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

    public function create() {
        global $requestBody;
        
        $validator = new Validator($requestBody);
        $rules = [
            'student_id' => 'required|integer',
            'billing_month' => 'required|integer',
            'billing_year' => 'required|integer',
            'amount' => 'required|numeric|positive_number',
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
            Response::json(true, 'Fee record created successfully.', ['id' => $id], null, 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 409);
        }
    }

    public function index() {
        $filters = $_GET;
        try {
            $fees = $this->service->searchFees($filters);
            Response::success('Fee records retrieved successfully.', ['fees' => $fees]);
        } catch (Exception $e) {
            Response::error('Failed to retrieve fees.', 500);
        }
    }

    public function show($params) {
        try {
            $fee = $this->service->getFee($params['id']);
            Response::success('Fee record retrieved successfully.', ['fee' => $fee]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 404);
        }
    }

    public function studentFees($params) {
        $filters = ['student_id' => $params['student_id']];
        try {
            $fees = $this->service->searchFees($filters);
            Response::success('Student fee records retrieved.', ['fees' => $fees]);
        } catch (Exception $e) {
            Response::error('Failed to retrieve student fees.', 500);
        }
    }

    public function studentSummary($params) {
        try {
            $summary = $this->service->getStudentFeeSummary($params['student_id']);
            if (!$summary['student_id']) {
                Response::error('Student not found', 404);
            }
            Response::success('Student summary retrieved.', ['summary' => $summary]);
        } catch (Exception $e) {
            Response::error('Failed to retrieve summary.', 500);
        }
    }

    public function statistics() {
        try {
            $stats = $this->service->getStatistics();
            Response::success('Statistics retrieved successfully.', ['statistics' => $stats]);
        } catch (Exception $e) {
            Response::error('Failed to retrieve statistics.', 500);
        }
    }

    public function recordPayment($params) {
        global $requestBody;

        $validator = new Validator($requestBody);
        $rules = [
            'payment_amount' => 'required|numeric|positive_number',
            'payment_method' => 'required|string|max:50',
            'transaction_ref' => 'string|max:100',
            'remarks' => 'string'
        ];

        if (!$validator->validate($rules)) {
            Response::error('Validation failed.', 422, $validator->getErrors());
        }

        if ($requestBody['payment_amount'] <= 0) {
            Response::error('Validation failed.', 422, ['payment_amount' => ['Payment amount must be greater than zero.']]);
        }

        try {
            $this->service->recordPayment(
                $params['id'],
                $requestBody['payment_amount'],
                $requestBody['payment_method'],
                $requestBody['transaction_ref'] ?? null,
                $requestBody['remarks'] ?? null
            );
            Response::success('Payment recorded successfully.');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
