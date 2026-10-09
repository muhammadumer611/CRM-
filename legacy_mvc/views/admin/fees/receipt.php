<?php $config = require APP_ROOT . '/config/app.php'; $payment = $payment ?? []; $allocations = $allocations ?? []; $invoiceTotal = (float)($total_invoice ?? 0); $previousOutstanding = (float)($previous_outstanding ?? 0); $remainingOutstanding = (float)($remaining_outstanding ?? 0); ?>
<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div class="card-header" style="justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
        <div>
            <h3 class="card-title">Payment Receipt</h3>
            <div style="color: var(--text-muted); margin-top: 0.35rem;">Receipt #: <?php echo htmlspecialchars($payment['receipt_number'] ?? 'N/A'); ?></div>
        </div>
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <a href="<?php echo $config['base_url']; ?>/fees" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Fees</a>
            <button type="button" class="btn btn-success" onclick="window.print();"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>

    <div style="background: #0f172a; border: 1px solid var(--border); border-radius: 8px; padding: 2rem; margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; gap: 2rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
            <div>
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em;">Hostel Management</div>
                <div style="font-size: 1.5rem; font-weight: 700; margin-top: 0.25rem;">Official Payment Receipt</div>
            </div>
            <div style="text-align: right;">
                <div style="color: var(--text-muted);">Date</div>
                <div style="font-weight: 700; font-size: 1.2rem;"><?php echo htmlspecialchars($payment['payment_date'] ?? date('Y-m-d')); ?></div>
            </div>
        </div>

        <div class="row" style="margin-bottom: 1.5rem;">
            <div class="col-md-6">
                <div style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.35rem;">Student</div>
                <div style="font-size: 1.05rem; font-weight: 700;"><?php echo htmlspecialchars($payment['full_name'] ?? ''); ?></div>
                <div style="color: var(--text-muted);">Student ID: <?php echo htmlspecialchars($payment['student_id_str'] ?? $payment['student_id']); ?></div>
            </div>
            <div class="col-md-6" style="text-align: right;">
                <div style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.35rem;">Invoice</div>
                <div style="font-weight: 700; font-size: 1.05rem;"><?php echo htmlspecialchars($payment['invoice_number'] ?? ''); ?></div>
                <div style="color: var(--text-muted);">Period: <?php echo date('F Y', mktime(0,0,0,(int)($payment['billing_month'] ?? 1),1,(int)($payment['billing_year'] ?? date('Y')))); ?></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div style="background: rgba(15,23,42,0.4); border: 1px solid var(--border); border-radius: 6px; padding: 0.9rem;">
                <div style="color: var(--text-muted); font-size: 0.8rem;">Invoice Total</div>
                <div style="font-size: 1.3rem; font-weight: 700;">Rs. <?php echo number_format($invoiceTotal, 2); ?></div>
            </div>
            <div style="background: rgba(15,23,42,0.4); border: 1px solid var(--border); border-radius: 6px; padding: 0.9rem;">
                <div style="color: var(--text-muted); font-size: 0.8rem;">Received Amount</div>
                <div style="font-size: 1.3rem; font-weight: 700; color: var(--success);">Rs. <?php echo number_format((float)($payment['amount'] ?? 0), 2); ?></div>
            </div>
            <div style="background: rgba(15,23,42,0.4); border: 1px solid var(--border); border-radius: 6px; padding: 0.9rem;">
                <div style="color: var(--text-muted); font-size: 0.8rem;">Outstanding After Payment</div>
                <div style="font-size: 1.3rem; font-weight: 700; color: var(--danger);">Rs. <?php echo number_format($remainingOutstanding, 2); ?></div>
            </div>
        </div>

        <div style="border-top: 1px solid var(--border); padding-top: 1rem; margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: var(--text-muted);">Payment Method</span>
                <strong><?php echo htmlspecialchars($payment['payment_method'] ?? 'Cash'); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: var(--text-muted);">Reference</span>
                <strong><?php echo htmlspecialchars($payment['transaction_ref'] ?? 'N/A'); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: var(--text-muted);">Received By</span>
                <strong><?php echo htmlspecialchars($payment['admin_username'] ?? 'Admin'); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Remarks</span>
                <strong><?php echo htmlspecialchars($payment['remarks'] ?? 'General payment'); ?></strong>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h3 class="card-title">Allocation Details</h3>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Period</th>
                        <th>Allocated Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allocations)): ?>
                        <tr><td colspan="3" style="text-align: center; padding: 1.25rem;">No allocation records available.</td></tr>
                    <?php else: foreach ($allocations as $allocation): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($allocation['invoice_number'] ?? ''); ?></td>
                            <td><?php echo date('F Y', mktime(0,0,0,(int)($allocation['billing_month'] ?? 1),1,(int)($allocation['billing_year'] ?? date('Y')))); ?></td>
                            <td>Rs. <?php echo number_format((float)($allocation['amount'] ?? 0), 2); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
