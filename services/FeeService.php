<?php
namespace Services;

use Repositories\FeeRepository;
use Utils\TransactionHelper;
use Core\Logger;
use Services\StudentHistoryService;
use Exception;
use PDO;

class FeeService {
    private $repository;

    public function __construct() {
        $this->repository = new FeeRepository();
    }

    public function createFee($data) {
        $existing = $this->repository->findByStudentAndBillingPeriod(
            $data['student_id'], 
            $data['billing_month'], 
            $data['billing_year']
        );
        
        if ($existing) {
            throw new Exception("Fee record already exists for this student for the selected month and year.");
        }

        $data['status'] = $this->calculateStatus($data['amount'], 0, $data['due_date']);
        
        $id = $this->repository->create($data);
        Logger::info("Created fee record #{$id} for student #{$data['student_id']}");

        StudentHistoryService::record(
            $data['student_id'],
            'FEE_CREATED',
            "Generated fee for " . $data['billing_month'] . "/" . $data['billing_year'],
            null,
            $data
        );

        return $id;
    }

    public function getFee($id) {
        $fee = $this->repository->findById($id);
        if (!$fee) {
            throw new Exception("Fee record not found");
        }
        
        if ($fee['status'] === 'Pending' && date('Y-m-d') > $fee['due_date']) {
            $fee['status'] = 'Overdue';
        }
        
        return $fee;
    }

    public function searchFees($filters) {
        $fees = $this->repository->search($filters);
        $today = date('Y-m-d');
        foreach ($fees as &$fee) {
            if ($fee['status'] === 'Pending' && $today > $fee['due_date']) {
                $fee['status'] = 'Overdue';
            }
        }
        return $fees;
    }

    public function recordPayment($feeId, $paymentAmount, $method, $ref, $remarks) {
        return TransactionHelper::execute(function(PDO $db) use ($feeId, $paymentAmount, $method, $ref, $remarks) {
            $stmt = $db->prepare("SELECT amount, paid_amount, due_date, student_id FROM fee_records WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $feeId]);
            $fee = $stmt->fetch();

            if (!$fee) {
                throw new Exception("Fee record not found.");
            }

            if ($paymentAmount <= 0) {
                throw new Exception("Payment amount must be greater than zero.");
            }

            $newPaidAmount = $fee['paid_amount'] + $paymentAmount;
            
            if ($newPaidAmount > $fee['amount']) {
                $outstanding = $fee['amount'] - $fee['paid_amount'];
                throw new Exception("Payment cannot exceed outstanding amount ({$outstanding}).");
            }

            $newStatus = $this->calculateStatus($fee['amount'], $newPaidAmount, $fee['due_date']);

            $updateStmt = $db->prepare("
                UPDATE fee_records 
                SET paid_amount = :paid_amount, payment_date = CURRENT_DATE, 
                    status = :status, payment_method = :method, 
                    transaction_ref = :ref, remarks = :remarks 
                WHERE id = :id
            ");
            
            $updateStmt->execute([
                'paid_amount' => $newPaidAmount,
                'status' => $newStatus,
                'method' => $method,
                'ref' => $ref,
                'remarks' => $remarks,
                'id' => $feeId
            ]);

            StudentHistoryService::record(
                $fee['student_id'],
                'FEE_PAYMENT',
                "Processed payment of {$paymentAmount}",
                ['paid_amount' => $fee['paid_amount'], 'status' => $fee['status']],
                ['paid_amount' => $newPaidAmount, 'status' => $newStatus, 'payment_method' => $method],
                null,
                $db
            );

            Logger::info("Payment recorded for fee #{$feeId}. Amount: {$paymentAmount}. Method: {$method}");
            return true;
        });
    }

    public function getStatistics() {
        return $this->repository->getStatistics();
    }

    public function getStudentFeeSummary($studentId) {
        return $this->repository->getStudentFeeSummary($studentId);
    }

    private function calculateStatus($totalAmount, $paidAmount, $dueDate) {
        if ($paidAmount >= $totalAmount) {
            return 'Paid';
        }
        
        if ($paidAmount > 0 && $paidAmount < $totalAmount) {
            return 'Partial';
        }
        
        if ($paidAmount == 0 && date('Y-m-d') <= $dueDate) {
            return 'Pending';
        }
        
        if ($paidAmount == 0 && date('Y-m-d') > $dueDate) {
            return 'Overdue';
        }
        
        return 'Pending';
    }
}
