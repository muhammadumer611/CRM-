<?php $config = require APP_ROOT . '/config/app.php'; $student = $student ?? []; $account = $account ?? []; $invoices = $account['invoices'] ?? []; $payments = $account['payments'] ?? []; ?>
<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 class="card-title">Student Account Statement</h3>
            <div style="margin-top: 0.5rem; color: var(--text-muted);">
                <?php echo htmlspecialchars($student['student_id_str'] ?? ''); ?> • <?php echo htmlspecialchars($student['full_name'] ?? ''); ?>
            </div>
        </div>
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <a href="<?php echo $config['base_url']; ?>/students" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card" style="margin-bottom: 0;">
                <h4 style="margin-bottom: 1rem;">Balance Summary</h4>
                <div style="display: grid; gap: 0.9rem;">
                    <div><div style="color: var(--text-muted); font-size: 0.8rem;">Total Charges</div><div style="font-size: 1.35rem; font-weight: 700;">Rs. <?php echo number_format((float)($account['total_charges'] ?? 0), 2); ?></div></div>
                    <div><div style="color: var(--text-muted); font-size: 0.8rem;">Total Paid</div><div style="font-size: 1.2rem; font-weight: 700; color: var(--success);">Rs. <?php echo number_format((float)($account['total_paid'] ?? 0), 2); ?></div></div>
                    <div><div style="color: var(--text-muted); font-size: 0.8rem;">Outstanding</div><div style="font-size: 1.2rem; font-weight: 700; color: var(--danger);">Rs. <?php echo number_format((float)($account['total_outstanding'] ?? 0), 2); ?></div></div>
                    <div><div style="color: var(--text-muted); font-size: 0.8rem;">Overdue</div><div style="font-size: 1.2rem; font-weight: 700; color: #fbbf24;">Rs. <?php echo number_format((float)($account['total_overdue'] ?? 0), 2); ?></div></div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card" style="margin-bottom: 0;">
                <h4 style="margin-bottom: 1rem;">Student Details</h4>
                <div class="row">
                    <div class="col-md-6"><div class="form-label">Phone</div><div class="detail-value"><?php echo htmlspecialchars($student['phone'] ?? ''); ?></div></div>
                    <div class="col-md-6"><div class="form-label">CNIC</div><div class="detail-value"><?php echo htmlspecialchars($student['cnic'] ?? ''); ?></div></div>
                    <div class="col-md-6"><div class="form-label">Guardian</div><div class="detail-value"><?php echo htmlspecialchars($student['guardian_name'] ?? ''); ?></div></div>
                    <div class="col-md-6"><div class="form-label">Guardian Phone</div><div class="detail-value"><?php echo htmlspecialchars($student['guardian_phone'] ?? ''); ?></div></div>
                    <div class="col-md-12"><div class="form-label">Address</div><div class="detail-value"><?php echo htmlspecialchars($student['address'] ?? ''); ?></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Invoice Ledger</h3>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Period</th>
                    <th>Due Date</th>
                    <th>Charge</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 1.5rem;">No invoices found.</td></tr>
                <?php else: foreach ($invoices as $invoice): $invoiceTotal = (float)($invoice['amount'] ?? 0) + (float)($invoice['additional_charges'] ?? 0) - (float)($invoice['discount'] ?? 0); $invoiceBalance = max(0, $invoiceTotal - (float)($invoice['paid_amount'] ?? 0)); ?>
                    <tr>
                        <td><?php echo htmlspecialchars($invoice['invoice_number'] ?? '#'.$invoice['id']); ?></td>
                        <td><?php echo date('F Y', mktime(0,0,0,(int)($invoice['billing_month'] ?? 1),1,(int)($invoice['billing_year'] ?? date('Y')))); ?></td>
                        <td><?php echo htmlspecialchars($invoice['due_date'] ?? 'N/A'); ?></td>
                        <td>Rs. <?php echo number_format($invoiceTotal, 2); ?></td>
                        <td>Rs. <?php echo number_format((float)($invoice['paid_amount'] ?? 0), 2); ?></td>
                        <td>Rs. <?php echo number_format($invoiceBalance, 2); ?></td>
                        <td><?php echo htmlspecialchars($invoice['status'] ?? 'Pending'); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Payment Ledger</h3>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Invoice</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 1.5rem;">No payments recorded yet.</td></tr>
                <?php else: foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($payment['payment_date'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($payment['invoice_number'] ?? ''); ?></td>
                        <td>Rs. <?php echo number_format((float)($payment['amount'] ?? 0), 2); ?></td>
                        <td><?php echo htmlspecialchars($payment['payment_method'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($payment['transaction_ref'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($payment['remarks'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
