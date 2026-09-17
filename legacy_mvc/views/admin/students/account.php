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

    <?php $currentFee = $account['active_fee'] ?? $account['current_fee'] ?? null; $nextPeriod = $account['next_billing_period'] ?? ['month' => date('n'), 'year' => date('Y')]; $currentTotal = $currentFee ? (float)$currentFee['amount'] + (float)$currentFee['additional_charges'] - (float)$currentFee['discount'] : 0; $currentPaid = $currentFee ? (float)$currentFee['paid_amount'] : 0; $currentPending = max(0, $currentTotal - $currentPaid); ?>
    <div class="card" style="margin: 0 0 1.5rem; border: 1px solid rgba(56,189,248,0.35);">
        <div class="card-header" style="margin-bottom: 0.5rem;">
            <div>
                <h3 class="card-title">Fees / Financial Account</h3>
                <div style="color:var(--text-muted);font-size:0.9rem;"><?php echo $currentFee ? date('F Y', mktime(0, 0, 0, (int)$currentFee['billing_month'], 1, (int)$currentFee['billing_year'])) : 'No billing period created'; ?></div>
            </div>
            <?php if ($currentFee): ?><span class="badge" style="background:<?php echo $currentFee['status'] === 'Paid' ? '#10b981' : '#f59e0b'; ?>22;color:<?php echo $currentFee['status'] === 'Paid' ? '#10b981' : '#f59e0b'; ?>;"><?php echo htmlspecialchars($currentFee['status']); ?></span><?php endif; ?>
        </div>
        <?php if ($currentFee): ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:1rem;margin-bottom:1rem;">
            <div><div class="form-label">Monthly Fee</div><strong>Rs. <?php echo number_format($currentTotal, 2); ?></strong></div>
            <div><div class="form-label">Paid</div><strong style="color:var(--success);">Rs. <?php echo number_format($currentPaid, 2); ?></strong></div>
            <div><div class="form-label">Pending</div><strong style="color:<?php echo $currentPending > 0 ? 'var(--danger)' : 'var(--success)'; ?>;">Rs. <?php echo number_format($currentPending, 2); ?></strong></div>
        </div>
        <?php if ($currentPending > 0): ?>
        <form action="<?php echo $config['base_url']; ?>/students/account/payment/<?php echo (int)$student['id']; ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label class="form-label">Payment Amount (Rs.) *</label><input type="number" name="paid_amount" class="form-control" min="0.01" max="<?php echo number_format($currentPending, 2, '.', ''); ?>" step="0.01" value="<?php echo number_format($currentPending, 2, '.', ''); ?>" required><small style="color:var(--text-muted);">Maximum: Rs. <?php echo number_format($currentPending, 2); ?></small></div></div>
                <div class="col-md-4"><div class="form-group"><label class="form-label">Payment Date *</label><input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required></div></div>
                <div class="col-md-4"><div class="form-group"><label class="form-label">Payment Method *</label><select name="payment_method" class="form-control" required><?php foreach (['Cash','Bank Transfer','Online','Card','JazzCash','EasyPaisa','Other'] as $method): ?><option value="<?php echo $method; ?>"><?php echo $method; ?></option><?php endforeach; ?></select></div></div>
                <div class="col-md-4"><div class="form-group"><label class="form-label">Received By *</label><input type="text" name="received_by_name" class="form-control" placeholder="Staff member name" required></div></div>
                <div class="col-md-4"><div class="form-group"><label class="form-label">Reference</label><input type="text" name="transaction_ref" class="form-control" placeholder="Optional reference"></div></div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-check-circle"></i> Submit Payment</button>
        </form>
        <?php endif; ?>
        <?php else: ?>
            <div style="color:var(--text-muted);margin-bottom:1rem;">No fee has been created for the current billing cycle.</div>
        <?php endif; ?>
        <?php if (!$currentFee || $currentFee['status'] === 'Paid'): ?>
        <form action="<?php echo $config['base_url']; ?>/students/account/invoice/<?php echo (int)$student['id']; ?>" method="POST" style="margin-top:1rem;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Add Monthly Fee — <?php echo date('F Y', mktime(0, 0, 0, (int)$nextPeriod['month'], 1, (int)$nextPeriod['year'])); ?></button>
        </form>
        <?php endif; ?>
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
                        <th>Billing Month</th>
                    <th>Amount</th>
                        <th>Status</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 1.5rem;">No payments recorded yet.</td></tr>
                <?php else: foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($payment['payment_date'] ?? ''); ?></td>
                        <td><?php echo date('M Y', mktime(0, 0, 0, (int)$payment['billing_month'], 1, (int)$payment['billing_year'])); ?></td>
                        <td>Rs. <?php echo number_format((float)($payment['amount'] ?? 0), 2); ?></td>
                        <td><?php echo htmlspecialchars($payment['fee_status'] ?? 'Completed'); ?></td>
                        <td><?php echo htmlspecialchars($payment['payment_method'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($payment['transaction_ref'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($payment['remarks'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
