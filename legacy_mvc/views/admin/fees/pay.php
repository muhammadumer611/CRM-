<?php $config = require APP_ROOT . '/config/app.php'; ?>
<?php
$remaining = (float)$fee['amount'] - (float)$fee['paid_amount'];
$isPaid = $fee['status'] === 'Paid';
$statusColors = ['Paid' => '#10b981', 'Partial' => '#f59e0b', 'Pending' => '#ef4444', 'Overdue' => '#991b1b'];
$statusColor = $statusColors[$fee['status']] ?? '#94a3b8';
?>
<div style="max-width:720px;margin:0 auto;">

    <!-- Invoice Header -->
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-header">
            <div>
                <h3 class="card-title"><i class="fas fa-file-invoice-dollar"></i> Invoice Details</h3>
                <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.25rem;"><?php echo htmlspecialchars($fee['invoice_number'] ?? '#'.$fee['id']); ?></div>
            </div>
            <div style="display:flex;gap:0.75rem;align-items:center;">
                <span class="badge" style="background:<?php echo $statusColor; ?>22;color:<?php echo $statusColor; ?>;border:1px solid <?php echo $statusColor; ?>44;border-radius:999px;padding:0.35rem 0.8rem;font-size:0.85rem;font-weight:700;">
                    <?php echo htmlspecialchars($fee['status']); ?>
                </span>
                <a href="<?php echo $config['base_url']; ?>/fees" class="btn" style="background:#334155;color:white;">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;">
            <div>
                <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Student</div>
                <div style="font-weight:600;margin-top:0.25rem;"><?php echo htmlspecialchars($fee['full_name']); ?></div>
                <div style="font-size:0.8rem;color:var(--text-muted);"><?php echo htmlspecialchars($fee['student_id_str']); ?></div>
            </div>
            <div>
                <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Period</div>
                <div style="font-weight:600;margin-top:0.25rem;"><?php echo date('F Y', mktime(0,0,0,$fee['billing_month'],1,$fee['billing_year'])); ?></div>
            </div>
            <div>
                <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Due Date</div>
                <div style="font-weight:600;margin-top:0.25rem;<?php echo (strtotime($fee['due_date']) < time() && !$isPaid) ? 'color:var(--danger);' : ''; ?>">
                    <?php echo date('M d, Y', strtotime($fee['due_date'])); ?>
                </div>
            </div>
            <div>
                <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Total Amount</div>
                <div style="font-weight:700;font-size:1.1rem;margin-top:0.25rem;">Rs. <?php echo number_format($fee['amount'], 2); ?></div>
            </div>
            <div>
                <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Total Paid</div>
                <div style="font-weight:700;font-size:1.1rem;color:#10b981;margin-top:0.25rem;">Rs. <?php echo number_format($fee['paid_amount'], 2); ?></div>
            </div>
            <div>
                <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Remaining</div>
                <div style="font-weight:700;font-size:1.1rem;color:<?php echo $remaining > 0 ? 'var(--danger)' : 'var(--success)'; ?>;margin-top:0.25rem;">
                    Rs. <?php echo number_format($remaining, 2); ?>
                </div>
            </div>
        </div>

        <!-- Progress bar -->
        <?php $pct = $fee['amount'] > 0 ? min(100, round(($fee['paid_amount']/$fee['amount'])*100)) : 0; ?>
        <div style="background:#1e293b;border-radius:999px;height:8px;overflow:hidden;">
            <div style="width:<?php echo $pct; ?>%;height:100%;background:<?php echo $isPaid ? '#10b981' : '#f59e0b'; ?>;border-radius:999px;transition:width 0.5s;"></div>
        </div>
        <div style="text-align:right;font-size:0.8rem;color:var(--text-muted);margin-top:0.25rem;"><?php echo $pct; ?>% paid</div>
    </div>

    <!-- Payment Form (hidden if already paid) -->
    <?php if (!$isPaid): ?>
    <div class="card" style="margin-bottom:1.5rem;">
        <h4 style="margin-bottom:1.25rem;color:var(--primary);"><i class="fas fa-money-check-alt"></i> Record Payment</h4>
        <form action="<?php echo $config['base_url']; ?>/fees/storePayment/<?php echo $fee['id']; ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Payment Amount (Rs.) *</label>
                        <input type="number" name="paid_amount" class="form-control" step="0.01" min="0.01"
                               max="<?php echo $remaining; ?>"
                               value="<?php echo number_format($remaining, 2, '.', ''); ?>"
                               required>
                        <small style="color:var(--text-muted);">Max: Rs. <?php echo number_format($remaining, 2); ?></small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Payment Method *</label>
                        <select name="payment_method" class="form-control" required>
                            <?php foreach(['Cash','Bank Transfer','Online','Card','JazzCash','EasyPaisa','Other'] as $pm): ?>
                                <option value="<?php echo $pm; ?>"><?php echo $pm; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Transaction Reference (Optional)</label>
                        <input type="text" name="transaction_ref" class="form-control" placeholder="Bank ref / JazzCash ID...">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Remarks (Optional)</label>
                        <input type="text" name="remarks" class="form-control" placeholder="Any notes...">
                    </div>
                </div>
            </div>

            <div style="margin-top:1rem;">
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:0.75rem;font-size:1.05rem;">
                    <i class="fas fa-check-circle"></i> Confirm Payment
                </button>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.25);border-radius:8px;padding:1.25rem;margin-bottom:1.5rem;text-align:center;">
        <i class="fas fa-check-circle" style="color:#10b981;font-size:1.5rem;"></i>
        <div style="font-weight:700;color:#10b981;margin-top:0.5rem;">Invoice fully paid</div>
    </div>
    <?php endif; ?>

    <!-- Payment History -->
    <?php if (!empty($payments)): ?>
    <div class="card">
        <h4 style="margin-bottom:1.25rem;"><i class="fas fa-history"></i> Payment History</h4>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Received By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><code style="color:var(--primary);font-size:0.8rem;"><?php echo htmlspecialchars($p['receipt_number']); ?></code></td>
                        <td><?php echo date('M d, Y', strtotime($p['payment_date'])); ?></td>
                        <td style="color:var(--success);font-weight:600;">Rs. <?php echo number_format($p['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($p['payment_method']); ?></td>
                        <td><?php echo htmlspecialchars($p['transaction_ref'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($p['received_by_name'] ?? '—'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
