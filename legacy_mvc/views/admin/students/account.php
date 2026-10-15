<?php $config = require APP_ROOT . '/config/app.php'; $student = $student ?? []; $account = $account ?? []; $invoices = $account['invoices'] ?? []; $payments = $account['payments'] ?? []; $isActiveStudent = ($student['status'] ?? '') === 'Active'; ?>
<style>
    .student-account-page { width: 100%; max-width: 1200px; min-width: 0; margin: 0 auto; }
    .student-account-page .card { min-width: 0; }
    .student-account-page .card-header { align-items: flex-start; min-width: 0; }
    .student-account-page .account-heading,
    .student-account-page .detail-value { min-width: 0; overflow-wrap: anywhere; word-break: break-word; }
    .student-account-page .account-metric-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 150px), 1fr)); gap: 1rem; margin-bottom: 1rem; }
    .student-account-page .account-detail-card .row > div { min-width: 0; }
    .student-account-page .table-responsive { max-width: 100%; }
    @media (max-width: 480px) {
        .student-account-page .card-header { gap: 0.75rem; }
        .student-account-page .account-metric-grid { grid-template-columns: 1fr; }
    }
</style>
<div class="student-account-page">
<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 1rem;">
        <div class="account-heading">
            <h3 class="card-title">Student Account Statement</h3>
            <div style="margin-top: 0.5rem; color: var(--text-muted);">
                <?php echo htmlspecialchars($student['student_id_str'] ?? ''); ?> • <?php echo htmlspecialchars($student['full_name'] ?? ''); ?>
                <?php if (!$isActiveStudent): ?> · Alumni account (read-only)<?php endif; ?>
            </div>
        </div>
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <a href="<?php echo $config['base_url']; ?>/students" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

    <?php $currentCycleFee = $account['current_fee'] ?? null; $currentFee = $account['active_fee'] ?? $currentCycleFee; $nextPeriod = $account['next_billing_period'] ?? ['month' => date('n'), 'year' => date('Y')]; $currentTotal = $currentFee ? (float)$currentFee['amount'] + (float)$currentFee['additional_charges'] - (float)$currentFee['discount'] : 0; $currentPaid = $currentFee ? (float)$currentFee['paid_amount'] : 0; $currentPending = max(0, $currentTotal - $currentPaid); ?>
    <div class="card" style="margin: 0 0 1.5rem; border: 1px solid rgba(56,189,248,0.35);">
        <div class="card-header" style="margin-bottom: 0.5rem;">
            <div>
                <h3 class="card-title">Fees / Financial Account</h3>
                <div style="color:var(--text-muted);font-size:0.9rem;"><?php echo $currentFee ? date('F Y', mktime(0, 0, 0, (int)$currentFee['billing_month'], 1, (int)$currentFee['billing_year'])) : 'No billing period created'; ?></div>
            </div>
            <?php if ($currentFee): ?><span class="badge" style="background:<?php echo $currentFee['status'] === 'Paid' ? '#10b981' : '#f59e0b'; ?>22;color:<?php echo $currentFee['status'] === 'Paid' ? '#10b981' : '#f59e0b'; ?>;"><?php echo htmlspecialchars($currentFee['status']); ?></span><?php endif; ?>
        </div>
        <?php if ($currentFee): ?>
        <div class="account-metric-grid">
            <div><div class="form-label">Monthly Fee</div><strong>Rs. <?php echo number_format($currentTotal, 2); ?></strong></div>
            <div><div class="form-label">Paid</div><strong style="color:var(--success);">Rs. <?php echo number_format($currentPaid, 2); ?></strong></div>
            <div><div class="form-label">Pending</div><strong style="color:<?php echo $currentPending > 0 ? 'var(--danger)' : 'var(--success)'; ?>;">Rs. <?php echo number_format($currentPending, 2); ?></strong></div>
        </div>
        <?php if ($currentPending > 0 && $isActiveStudent): ?>
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
        <?php if (!$currentCycleFee && $isActiveStudent): ?>
        <form action="<?php echo $config['base_url']; ?>/students/account/invoice/<?php echo (int)$student['id']; ?>" method="POST" style="margin-top:1rem;">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Add Monthly Fee — <?php echo date('F Y', mktime(0, 0, 0, (int)$nextPeriod['month'], 1, (int)$nextPeriod['year'])); ?></button>
        </form>
        <?php endif; ?>
        <?php if (!$isActiveStudent): ?><div style="color:var(--text-muted);margin-top:1rem;">Monthly billing actions are disabled for inactive/alumni students. Existing fee history remains available below.</div><?php endif; ?>
    </div>

    <?php $securityDeposit = $account['security_deposit'] ?? null; $securityTransactions = $account['security_transactions'] ?? []; ?>
    <div class="card" style="margin-top:1.5rem;border:1px solid rgba(139,92,246,0.35);">
        <div class="card-header" style="flex-wrap:wrap;gap:1rem;margin-bottom:1rem;">
            <div>
                <h3 class="card-title"><i class="fas fa-shield-halved" style="color:#a78bfa;"></i> Security Deposit</h3>
                <div style="color:var(--text-muted);font-size:0.88rem;margin-top:0.35rem;">Separate from monthly fees and fee-payment history.</div>
            </div>
            <?php if ($securityDeposit): ?>
                <?php $securityStatus = strtoupper((string)$securityDeposit['status']); $securityBadgeColor = in_array($securityStatus, ['HELD', 'PAID'], true) ? 'var(--success)' : ($securityStatus === 'PENDING' ? 'var(--warning)' : ($securityStatus === 'WAIVED' ? 'var(--text-muted)' : 'var(--primary)')); ?>
                <span class="badge" style="background:color-mix(in srgb, <?php echo $securityBadgeColor; ?> 14%, transparent);color:<?php echo $securityBadgeColor; ?>;"><?php echo htmlspecialchars($securityStatus === 'PAID' || $securityStatus === 'HELD' ? 'PAID / HELD' : $securityStatus); ?></span>
            <?php else: ?>
                <span class="badge" style="background:var(--surface-muted);color:var(--text-muted);">NO RECORD</span>
            <?php endif; ?>
        </div>
        <?php if (!$securityDeposit): ?>
            <div style="color:var(--text-muted);">No security deposit record exists for this student.</div>
        <?php else: ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;">
                <div><div class="form-label"><?php echo $securityStatus === 'PENDING' ? 'Pending Security Amount' : ($securityStatus === 'WAIVED' ? 'Security Amount' : 'Original Security Amount'); ?></div><strong>Rs. <?php echo number_format((float)$securityDeposit['original_amount'], 2); ?></strong></div>
                <?php if ($securityStatus !== 'WAIVED' && $securityStatus !== 'PENDING'): ?>
                    <div><div class="form-label">Remaining Held Amount</div><strong>Rs. <?php echo number_format((float)$securityDeposit['remaining_amount'], 2); ?></strong></div>
                <?php endif; ?>
                <?php if ($securityStatus === 'PENDING' && !empty($securityDeposit['expected_payment_date'])): ?>
                    <div><div class="form-label">Expected Payment Date</div><strong><?php echo htmlspecialchars(date('M d, Y', strtotime($securityDeposit['expected_payment_date']))); ?></strong></div>
                <?php endif; ?>
                <?php if (!empty($securityDeposit['processed_at'])): ?>
                    <div><div class="form-label">Last Processed</div><strong><?php echo htmlspecialchars(date('M d, Y g:i A', strtotime($securityDeposit['processed_at']))); ?></strong></div>
                <?php endif; ?>
                <?php if ($securityStatus === 'WAIVED'): ?>
                    <div class="detail-item-full"><div class="form-label">Waiver Reason</div><div class="detail-note"><?php echo nl2br(htmlspecialchars($securityDeposit['note'] ?? '')); ?></div></div>
                <?php elseif (!empty($securityDeposit['note'])): ?>
                    <div class="detail-item-full"><div class="form-label"><?php echo $securityStatus === 'PENDING' ? 'Note / Reason' : 'Security Note'; ?></div><div class="detail-note"><?php echo nl2br(htmlspecialchars($securityDeposit['note'])); ?></div></div>
                <?php endif; ?>
            </div>

            <?php if ($securityStatus === 'PENDING'): ?>
                <button type="button" class="btn btn-primary" style="margin-top:1rem;" data-security-payment-open>
                    <i class="fas fa-money-bill-wave"></i> PAY SECURITY
                </button>
            <?php endif; ?>

            <div style="margin-top:1.5rem;">
                <h4 style="margin-bottom:0.75rem;">Security Payment / Financial History</h4>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Method</th><th>Reference</th><th>Processed By</th><th>Reason / Note</th></tr></thead>
                        <tbody>
                            <?php if (empty($securityTransactions)): ?>
                                <tr><td colspan="7" style="text-align:center;color:var(--text-muted);">No security transactions recorded.</td></tr>
                            <?php else: foreach ($securityTransactions as $securityTransaction): ?>
                                <tr>
                                    <td><?php echo !empty($securityTransaction['payment_date']) ? htmlspecialchars(date('M d, Y g:i A', strtotime($securityTransaction['payment_date']))) : '—'; ?></td>
                                    <td><?php echo htmlspecialchars(str_replace('_', ' ', $securityTransaction['transaction_type'])); ?></td>
                                    <td>Rs. <?php echo number_format((float)$securityTransaction['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($securityTransaction['payment_method'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars($securityTransaction['reference_number'] ?? '—'); ?></td>
                                    <td><?php echo htmlspecialchars(trim((string)($securityTransaction['processed_by_name'] ?? '')) ?: ($securityTransaction['admin_username'] ?? '—')); ?></td>
                                    <td><?php echo htmlspecialchars($securityTransaction['reason'] ?? '—'); ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
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
            <div class="card account-detail-card" style="margin-bottom: 0;">
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

<?php if ($securityDeposit && $securityStatus === 'PENDING'): ?>
<div class="modal" id="securityPaymentModal" aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="securityPaymentTitle" style="width:min(100%,560px);">
        <div class="modal-header">
            <h3 class="modal-title" id="securityPaymentTitle">Record Security Payment</h3>
            <button type="button" class="btn-close" aria-label="Close" data-security-payment-close>&times;</button>
        </div>
        <div class="modal-body">
            <p style="color:var(--text-muted);margin-bottom:1rem;">Record the full pending security amount. This payment is posted only to the security deposit ledger, never to monthly fees.</p>
            <div class="form-group">
                <label class="form-label">Amount to Receive</label>
                <input class="form-control" value="Rs. <?php echo number_format((float)$securityDeposit['original_amount'], 2); ?>" disabled>
            </div>
            <form id="securityPaymentForm" action="<?php echo $config['base_url']; ?>/students/account/security-payment/<?php echo (int)$student['id']; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                <input type="hidden" name="security_deposit_id" value="<?php echo (int)$securityDeposit['id']; ?>">
                <div class="form-group">
                    <label class="form-label" for="securityPaymentMethod">Payment Method *</label>
                    <select id="securityPaymentMethod" name="security_payment_method" class="form-control" required>
                        <option value="">Select payment method</option>
                        <?php foreach (['Cash','Bank Transfer','Online','Card','JazzCash','EasyPaisa','Other'] as $method): ?>
                            <option value="<?php echo htmlspecialchars($method); ?>"><?php echo htmlspecialchars($method); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="securityReferenceNumber">Reference (Optional)</label>
                    <input id="securityReferenceNumber" type="text" name="security_reference_number" class="form-control" maxlength="100">
                </div>
                <div style="display:flex;justify-content:flex-end;gap:0.75rem;flex-wrap:wrap;">
                    <button type="button" class="btn" style="background:var(--surface-muted);color:var(--text);" data-security-payment-close>Cancel</button>
                    <button type="submit" class="btn btn-primary" id="securityPaymentSubmit"><i class="fas fa-check-circle"></i> Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('securityPaymentModal');
    var form = document.getElementById('securityPaymentForm');
    var submit = document.getElementById('securityPaymentSubmit');
    if (!modal || !form || !submit) return;

    document.querySelector('[data-security-payment-open]')?.addEventListener('click', function () {
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.getElementById('securityPaymentMethod').focus();
    });
    modal.querySelectorAll('[data-security-payment-close]').forEach(function (button) {
        button.addEventListener('click', function () {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
        });
    });
    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
        }
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('show')) {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
        }
    });
    form.addEventListener('submit', function () {
        if (!form.reportValidity()) return;
        submit.disabled = true;
        submit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Recording Payment...';
    });
}());
</script>
<?php endif; ?>

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
                    <th>Actual Fee</th>
                    <th>Adjustment</th>
                    <th>Net Payable</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr><td colspan="9" style="text-align: center; padding: 1.5rem;">No invoices found.</td></tr>
                <?php else: foreach ($invoices as $invoice): $invoiceTotal = (float)($invoice['amount'] ?? 0) + (float)($invoice['additional_charges'] ?? 0) - (float)($invoice['discount'] ?? 0); $invoiceBalance = max(0, $invoiceTotal - (float)($invoice['paid_amount'] ?? 0)); ?>
                    <tr>
                        <td><?php echo htmlspecialchars($invoice['invoice_number'] ?? '#'.$invoice['id']); ?></td>
                        <td><?php echo date('F Y', mktime(0,0,0,(int)($invoice['billing_month'] ?? 1),1,(int)($invoice['billing_year'] ?? date('Y')))); ?></td>
                        <td><?php echo htmlspecialchars($invoice['due_date'] ?? 'N/A'); ?></td>
                        <td>Rs. <?php echo number_format((float)($invoice['amount'] ?? 0), 2); ?></td>
                        <td>Rs. <?php echo number_format((float)($invoice['discount'] ?? 0), 2); ?></td>
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
                    <th>Note</th>
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
                        <td><?php echo htmlspecialchars(trim((string)($payment['remarks'] ?? '')) ?: '—'); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
