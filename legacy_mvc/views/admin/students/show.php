<?php $config = require APP_ROOT . '/config/app.php'; ?>
<?php $resolvedBackUrl = !empty($backUrl) ? $backUrl : $config['base_url'] . '/students'; ?>
<div style="max-width:900px;margin:0 auto;">

    <!-- Header -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h2 style="margin:0;"><?php echo htmlspecialchars($student['full_name']); ?></h2>
            <span class="badge" style="background:#334155;font-size:0.85rem;margin-top:0.25rem;display:inline-block;">
                <?php echo htmlspecialchars($student['student_id_str']); ?>
            </span>
            <?php if ($student['status'] === 'Active'): ?>
                <span class="badge badge-success" style="margin-left:0.5rem;">Active</span>
            <?php else: ?>
                <span class="badge badge-danger" style="margin-left:0.5rem;">Inactive / Alumni</span>
            <?php endif; ?>
        </div>
        <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
            <?php if ($student['status'] === 'Active'): ?>
                <button type="button" class="btn btn-danger" onclick="openCheckoutModal()">
                    <i class="fas fa-sign-out-alt"></i> Checkout / Mark Alumni
                </button>
            <?php endif; ?>
            <a href="<?php echo $config['base_url']; ?>/students/edit/<?php echo (int)$student['id']; ?>?from=<?php echo urlencode($resolvedBackUrl); ?>"
               class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Student
            </a>
            <a href="<?php echo $resolvedBackUrl; ?>" class="btn" style="background:#334155;color:white;">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Personal Info -->
        <div class="col-md-6">
            <div class="card" style="margin-bottom:1rem;">
                <h4 style="color:var(--primary);margin-bottom:1rem;"><i class="fas fa-user"></i> Personal Info</h4>
                <table style="width:100%;border-collapse:collapse;">
                    <?php $rows = [
                        'Phone' => $student['phone'],
                        'Email' => $student['email'] ?? '—',
                        'Blood Group' => $student['blood_group'] ?? '—',
                        'Address' => $student['address'],
                        'CNIC' => \App\Services\StudentService::formatCnic($student['cnic']),
                        'Joined' => date('M d, Y', strtotime($student['created_at']))
                    ]; ?>
                    <?php foreach ($rows as $label => $val): ?>
                    <tr>
                        <td style="padding:0.5rem 0;color:var(--text-muted);font-size:0.85rem;width:35%;vertical-align:top;"><?php echo $label; ?></td>
                        <td style="padding:0.5rem 0;font-weight:500;font-size:0.9rem;"><?php echo htmlspecialchars($val); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <!-- Right Column: Guardian + Financial -->
        <div class="col-md-6">
            <div class="card" style="margin-bottom:1rem;">
                <h4 style="color:var(--primary);margin-bottom:1rem;"><i class="fas fa-users"></i> Guardian</h4>
                <table style="width:100%;border-collapse:collapse;">
                    <?php $rows2 = [
                        'Name' => $student['guardian_name'],
                        'Relation' => $student['relation'],
                        'Phone' => $student['guardian_phone'],
                        'CNIC' => \App\Services\StudentService::formatCnic($student['guardian_cnic']),
                        'Address' => $student['guardian_address'] ?? '—'
                    ]; ?>
                    <?php foreach ($rows2 as $label => $val): ?>
                    <tr>
                        <td style="padding:0.5rem 0;color:var(--text-muted);font-size:0.85rem;width:35%;vertical-align:top;"><?php echo $label; ?></td>
                        <td style="padding:0.5rem 0;font-weight:500;font-size:0.9rem;"><?php echo htmlspecialchars($val); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Financial Summary -->
            <div class="card">
                <h4 style="color:var(--primary);margin-bottom:1rem;"><i class="fas fa-rupee-sign"></i> Financial</h4>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div>
                        <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;">Monthly Fee</div>
                        <div style="font-weight:700;color:var(--primary);margin-top:0.25rem;">
                            <?php echo $student['monthly_fee'] !== null ? 'Rs. ' . number_format($student['monthly_fee'], 0) : '—'; ?>
                        </div>
                    </div>
                    <?php if ($securityDeposit): ?>
                    <div>
                        <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;">Security Deposit</div>
                        <div style="font-weight:700;color:#c4b5fd;margin-top:0.25rem;">Rs. <?php echo number_format($securityDeposit['remaining_amount'], 0); ?></div>
                        <div style="font-size:0.75rem;color:var(--text-muted);"><?php echo htmlspecialchars($securityDeposit['status']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($feeHistory)): ?>
                <?php
                    $totalBilled = array_sum(array_column($feeHistory, 'amount'));
                    $totalPaid   = array_sum(array_column($feeHistory, 'paid_amount'));
                    $outstanding = $totalBilled - $totalPaid;
                ?>
                <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);">
                    <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;">
                        <span style="color:var(--text-muted);font-size:0.85rem;">Total Billed</span>
                        <span style="font-weight:600;">Rs. <?php echo number_format($totalBilled, 0); ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;">
                        <span style="color:var(--text-muted);font-size:0.85rem;">Total Paid</span>
                        <span style="font-weight:600;color:var(--success);">Rs. <?php echo number_format($totalPaid, 0); ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:var(--text-muted);font-size:0.85rem;">Outstanding</span>
                        <span style="font-weight:700;color:<?php echo $outstanding > 0 ? 'var(--danger)' : 'var(--success)'; ?>;">
                            Rs. <?php echo number_format($outstanding, 0); ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Room Allocation -->
    <?php if (!empty($student['room_number'])): ?>
    <div class="card" style="margin-top:1rem;">
        <h4 style="color:var(--primary);margin-bottom:1rem;"><i class="fas fa-bed"></i> Current Room Allocation</h4>
        <div style="display:flex;gap:2rem;flex-wrap:wrap;">
            <div><span style="color:var(--text-muted);font-size:0.85rem;">Room</span><br><strong><?php echo htmlspecialchars($student['block'] . '-' . $student['room_number']); ?></strong></div>
            <div><span style="color:var(--text-muted);font-size:0.85rem;">Bed</span><br><strong><?php echo (int)$student['bed_number']; ?></strong></div>
            <div><span style="color:var(--text-muted);font-size:0.85rem;">Type</span><br><strong><?php echo htmlspecialchars($student['room_type'] ?? '—'); ?></strong></div>
            <div><span style="color:var(--text-muted);font-size:0.85rem;">Floor</span><br><strong><?php echo htmlspecialchars($student['floor'] ?? '—'); ?></strong></div>
            <div><span style="color:var(--text-muted);font-size:0.85rem;">Since</span><br><strong><?php echo $student['allocation_date'] ? date('M d, Y', strtotime($student['allocation_date'])) : '—'; ?></strong></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Fee History -->
    <?php if (!empty($feeHistory)): ?>
    <div class="card" style="margin-top:1rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h4 style="color:var(--primary);margin:0;"><i class="fas fa-file-invoice-dollar"></i> Fee History</h4>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th><th>Period</th><th>Amount</th><th>Paid</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feeHistory as $f): ?>
                    <?php $sc = ['Paid'=>'#10b981','Partial'=>'#f59e0b','Pending'=>'#ef4444','Overdue'=>'#991b1b'][$f['status']] ?? '#94a3b8'; ?>
                    <tr>
                        <td><code style="font-size:0.75rem;color:var(--text-muted);"><?php echo htmlspecialchars($f['invoice_number'] ?? '#'.$f['id']); ?></code></td>
                        <td><?php echo date('M Y', mktime(0,0,0,$f['billing_month'],1,$f['billing_year'])); ?></td>
                        <td>Rs. <?php echo number_format($f['amount'], 0); ?></td>
                        <td style="color:var(--success);">Rs. <?php echo number_format($f['paid_amount'], 0); ?></td>
                        <td><span class="badge" style="background:<?php echo $sc; ?>22;color:<?php echo $sc; ?>;border:1px solid <?php echo $sc; ?>44;border-radius:999px;padding:0.2rem 0.5rem;font-size:0.73rem;font-weight:700;"><?php echo $f['status']; ?></span></td>
                        <td><a href="<?php echo $config['base_url']; ?>/fees/pay/<?php echo $f['id']; ?>" class="btn btn-sm" style="background:#1e293b;color:var(--primary);"><i class="fas fa-receipt"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Checkout Modal -->
<?php if ($student['status'] === 'Active'): ?>
<?php $depAmount = $securityDeposit ? (float)$securityDeposit['remaining_amount'] : 0.0; ?>
<div class="modal" id="checkoutModal" style="display:none;align-items:center;justify-content:center;">
    <div class="modal-dialog" style="max-width:550px;width:100%;margin:auto;background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.5rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
            <h3 style="margin:0;color:var(--danger);"><i class="fas fa-sign-out-alt"></i> Student Checkout / Alumni</h3>
            <button type="button" class="btn-close" onclick="closeCheckoutModal()"></button>
        </div>

        <form action="<?php echo $config['base_url']; ?>/students/checkout/<?php echo $student['id']; ?>" method="POST" id="checkoutForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">

            <div class="form-group">
                <label class="form-label">Leaving Date *</label>
                <input type="date" name="leaving_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Leaving Reason *</label>
                <select name="leaving_reason" class="form-control" required>
                    <option value="Course Completed">Course Completed</option>
                    <option value="Shifted to Another City/Hostel">Shifted to Another City/Hostel</option>
                    <option value="Personal / Financial Reasons">Personal / Financial Reasons</option>
                    <option value="Graduated">Graduated</option>
                    <option value="Disciplinary Action">Disciplinary Action</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <?php if ($depAmount > 0): ?>
            <div style="background:rgba(56,189,248,0.08);border:1px solid rgba(56,189,248,0.25);border-radius:8px;padding:1rem;margin-bottom:1.25rem;">
                <div style="font-weight:600;color:var(--primary);margin-bottom:0.5rem;"><i class="fas fa-shield-alt"></i> Security Deposit Settlement</div>
                <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;font-size:0.9rem;">
                    <span>Held Security Deposit:</span>
                    <strong>Rs. <?php echo number_format($depAmount, 2); ?></strong>
                </div>

                <div class="form-group" style="margin-bottom:0.75rem;">
                    <label class="form-label">Deduction (Damage/Dues) Rs.</label>
                    <input type="number" name="security_deduction" id="secDeduction" class="form-control" step="0.01" min="0" max="<?php echo $depAmount; ?>" value="0" oninput="calcRefund(<?php echo $depAmount; ?>)">
                </div>

                <div style="display:flex;justify-content:space-between;padding-top:0.5rem;border-top:1px solid var(--border);font-size:0.95rem;">
                    <span>Refund to Student:</span>
                    <strong id="refundDisplay" style="color:var(--success);">Rs. <?php echo number_format($depAmount, 2); ?></strong>
                </div>

                <div class="form-group" style="margin-top:0.75rem;margin-bottom:0;">
                    <label class="form-label">Settlement Remarks</label>
                    <input type="text" name="security_refund_remarks" class="form-control" placeholder="e.g. Full refund / Rs. 1500 deducted for repairs">
                </div>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label">General Remarks (Optional)</label>
                <textarea name="remarks" class="form-control" rows="2" placeholder="Any final remarks..."></textarea>
            </div>

            <div style="display:flex;gap:1rem;justify-content:flex-end;margin-top:1.5rem;">
                <button type="button" class="btn" style="background:#334155;color:white;" onclick="closeCheckoutModal()">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-check"></i> Confirm Checkout</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCheckoutModal() {
    const m = document.getElementById('checkoutModal');
    m.style.display = 'flex';
}
function closeCheckoutModal() {
    const m = document.getElementById('checkoutModal');
    m.style.display = 'none';
}
function calcRefund(totalDeposit) {
    const ded = parseFloat(document.getElementById('secDeduction').value) || 0;
    const ref = Math.max(0, totalDeposit - ded);
    document.getElementById('refundDisplay').textContent = 'Rs. ' + ref.toFixed(2);
}
</script>
<?php endif; ?>
