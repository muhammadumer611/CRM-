<?php $config = require APP_ROOT . '/config/app.php'; ?>
<?php $resolvedBackUrl = !empty($backUrl) ? $backUrl : $config['base_url'] . '/students'; ?>
<style>
    .student-profile-page { width: 100%; max-width: 1120px; min-width: 0; margin: 0 auto; }
    .student-profile-page .profile-page-header,
    .student-profile-page .profile-page-actions { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; min-width: 0; }
    .student-profile-page .profile-page-header { justify-content: space-between; margin-bottom: 1.5rem; }
    .student-profile-page .profile-page-header > div,
    .student-profile-page .row > div,
    .student-profile-page .card { min-width: 0; }
    .student-profile-page .profile-info-table { width: 100%; table-layout: fixed; border-collapse: collapse; }
    .student-profile-page .profile-info-table td { padding: 0.65rem 0.4rem; border-bottom: 1px solid var(--border); vertical-align: top; }
    .student-profile-page .profile-info-table tr:last-child td { border-bottom: 0; }
    .student-profile-page .profile-label { width: 35%; color: var(--text-muted); font-size: 0.85rem; }
    .student-profile-page .profile-value { font-weight: 500; font-size: 0.9rem; overflow-wrap: anywhere; word-break: break-word; }
    .student-profile-page .profile-allocation-strip { display: flex; gap: 1rem 2rem; flex-wrap: wrap; align-items: center; }
    .student-profile-page .profile-value,
    .student-profile-page .profile-allocation-strip > div { min-width: 0; }
    .student-profile-page .profile-history-table { min-width: 680px; }
    .student-profile-page .profile-financial-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 180px), 1fr)); gap: 1rem; }
    .student-profile-page .profile-financial-grid > div { min-width: 0; overflow-wrap: anywhere; }
    @media (max-width: 480px) {
        .student-profile-page .profile-page-header { align-items: flex-start; }
        .student-profile-page .profile-page-actions { width: 100%; }
        .student-profile-page .profile-page-actions .btn { flex: 1 1 100%; justify-content: center; }
        .student-profile-page .profile-label { width: 38%; }
        .student-profile-page .profile-info-table td { padding: 0.55rem 0.25rem; }
    }
</style>
<div class="student-profile-page">

    <!-- Header -->
    <div class="profile-page-header">
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
        <div class="profile-page-actions">
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
                <table class="profile-info-table">
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
                        <td class="profile-label"><?php echo $label; ?></td>
                        <td class="profile-value"><?php echo htmlspecialchars($val); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <!-- Right Column: Guardian + Financial -->
        <div class="col-md-6">
            <div class="card" style="margin-bottom:1rem;">
                <h4 style="color:var(--primary);margin-bottom:1rem;"><i class="fas fa-users"></i> Guardian</h4>
                <table class="profile-info-table">
                    <?php $rows2 = [
                        'Name' => $student['guardian_name'],
                        'Relation' => $student['relation'],
                        'Phone' => $student['guardian_phone'],
                        'Address' => $student['guardian_address'] ?? '—'
                    ]; ?>
                    <?php foreach ($rows2 as $label => $val): ?>
                    <tr>
                        <td class="profile-label"><?php echo $label; ?></td>
                        <td class="profile-value"><?php echo htmlspecialchars($val); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div class="card" style="margin-bottom:1rem;">
                <h4 style="color:var(--primary);margin-bottom:1rem;"><i class="fas fa-user-tie"></i> Resident Details</h4>
                <table class="profile-info-table">
                    <?php
                        $vehicleNumber = trim((string)($student['vehicle_number'] ?? ''));
                        $vehicleType = trim((string)($student['vehicle_type'] ?? ''));
                        $vehicleDisplay = ($vehicleNumber !== '' || $vehicleType !== '')
                            ? trim(($vehicleNumber !== '' ? $vehicleNumber : '') . ($vehicleNumber !== '' && $vehicleType !== '' ? ' / ' : '') . ($vehicleType !== '' ? $vehicleType : ''))
                            : 'None / No Vehicle';
                        $residentRows = [
                        'Resident Type' => $student['resident_type'] ?? 'Student',
                        'College / University' => $student['college_university'] ?? '—',
                        'Job / Workplace' => $student['job_workplace'] ?? '—',
                        'Vehicle' => $vehicleDisplay,
                        'Note' => $student['note'] ?? '—'
                    ]; ?>
                    <?php foreach ($residentRows as $label => $val): ?>
                    <tr>
                        <td class="profile-label"><?php echo $label; ?></td>
                        <td class="profile-value" style="white-space:pre-wrap;"><?php echo htmlspecialchars($val); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Financial Summary -->
            <div class="card">
                <h4 style="color:var(--primary);margin-bottom:1rem;"><i class="fas fa-rupee-sign"></i> Financial</h4>
                <div class="profile-financial-grid">
                    <div>
                        <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;">Monthly Fee</div>
                        <div style="font-weight:700;color:var(--primary);margin-top:0.25rem;">
                            <?php echo $student['monthly_fee'] !== null ? 'Rs. ' . number_format($student['monthly_fee'], 0) : '—'; ?>
                        </div>
                    </div>
                    <?php if ($securityDeposit): ?>
                    <div>
                        <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;">Security Deposit</div>
                        <div style="font-weight:700;color:#c4b5fd;margin-top:0.25rem;">Original: Rs. <?php echo number_format((float)$securityDeposit['original_amount'], 2); ?> · Remaining: Rs. <?php echo number_format((float)$securityDeposit['remaining_amount'], 2); ?></div>
                        <div style="font-size:0.75rem;color:var(--text-muted);"><?php echo htmlspecialchars($securityDeposit['status']); ?></div>
                        <?php if (!empty($securityDeposit['expected_payment_date'])): ?>
                            <div style="font-size:0.75rem;color:var(--text-muted);">Expected: <?php echo htmlspecialchars(date('M d, Y', strtotime($securityDeposit['expected_payment_date']))); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($securityDeposit['note'])): ?>
                            <div style="font-size:0.85rem;margin-top:0.5rem;white-space:pre-wrap;"><?php echo htmlspecialchars($securityDeposit['note']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($securityDeposit['transactions'])): ?>
                    <div style="grid-column:1 / -1;margin-top:0.75rem;">
                        <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;margin-bottom:0.5rem;">Security Transaction History</div>
                        <div class="table-responsive">
                            <table>
                                <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Method</th><th>Reference</th><th>Processed By</th><th>Reason</th></tr></thead>
                                <tbody>
                                    <?php foreach ($securityDeposit['transactions'] as $securityTransaction): ?>
                                    <tr>
                                        <td><?php echo !empty($securityTransaction['payment_date']) ? htmlspecialchars(date('M d, Y g:i A', strtotime($securityTransaction['payment_date']))) : '—'; ?></td>
                                        <td><?php echo htmlspecialchars(str_replace('_', ' ', $securityTransaction['transaction_type'])); ?></td>
                                        <td>Rs. <?php echo number_format((float)$securityTransaction['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($securityTransaction['payment_method'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($securityTransaction['reference_number'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars(trim((string)($securityTransaction['processed_by_name'] ?? '')) ?: ($securityTransaction['admin_username'] ?? '—')); ?></td>
                                        <td><?php echo htmlspecialchars($securityTransaction['reason'] ?? '—'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
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
        <div class="profile-allocation-strip">
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
            <table class="profile-history-table">
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
<div class="modal" id="checkoutModal" style="display:none;align-items:center;justify-content:center;">
    <div class="modal-dialog checkout-dialog" style="max-width:550px;width:100%;margin:auto;background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.5rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;flex:0 0 auto;">
            <h3 style="margin:0;color:var(--danger);"><i class="fas fa-sign-out-alt"></i> Student Checkout / Alumni</h3>
            <button type="button" class="btn-close" onclick="closeCheckoutModal()"></button>
        </div>

        <form action="<?php echo $config['base_url']; ?>/students/checkout/<?php echo $student['id']; ?>" method="POST" id="checkoutForm" class="checkout-form">
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

            <?php
                $currentSecurityBalance = (float)($securityDeposit['remaining_amount'] ?? 0);
                $securityState = strtoupper((string)($securityDeposit['status'] ?? ''));
                $securityCanSettle = in_array($securityState, ['PAID', 'HELD', 'ADJUSTED', 'PARTIALLY_REFUNDED'], true) && $currentSecurityBalance > 0;
            ?>
            <?php if ($securityCanSettle): ?>
            <div class="checkout-security-grid" style="display:block;">
                <div class="form-group" style="padding:0.9rem;border:1px solid rgba(59,130,246,0.3);border-radius:8px;background:rgba(59,130,246,0.06);">
                    <strong>Security held: Rs. <?php echo number_format($currentSecurityBalance, 2); ?></strong>
                    <div>Choose whether to settle the paid security at checkout or keep it available for later return from the Alumni record.</div>
                    <label style="display:flex;align-items:center;gap:0.5rem;margin-top:0.75rem;">
                        <input type="radio" name="security_option" value="KEEP_FOR_LATER" checked>
                        Keep security for later return
                    </label>
                    <label style="display:flex;align-items:center;gap:0.5rem;margin-top:0.5rem;">
                        <input type="radio" name="security_option" value="RETURN_NOW">
                        Return / settle security now
                    </label>
                    <div id="checkoutSecuritySettlement" style="display:none;margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid var(--border);">
                        <div class="form-group">
                            <label class="form-label" for="checkoutSecurityHeld">Security Held</label>
                            <input id="checkoutSecurityHeld" class="form-control" value="Rs. <?php echo number_format($currentSecurityBalance, 2, '.', ''); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="checkoutSecurityDeduction">Deduction (Rs.)</label>
                            <input id="checkoutSecurityDeduction" type="number" name="security_deduction" class="form-control" min="0" max="<?php echo htmlspecialchars(number_format($currentSecurityBalance, 2, '.', '')); ?>" step="0.01" value="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="checkoutSecurityFinalReturned">Final Returned (Rs.)</label>
                            <input id="checkoutSecurityFinalReturned" class="form-control" value="<?php echo number_format($currentSecurityBalance, 2, '.', ''); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="checkoutSecurityRemarks">Reason / Remarks</label>
                            <textarea id="checkoutSecurityRemarks" name="security_remarks" class="form-control" rows="2" placeholder="Required when entering a deduction"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <?php elseif ($securityState === 'PENDING'): ?>
            <div class="checkout-security-grid" style="display:block;">
                <div class="form-group" style="padding:0.9rem;border:1px solid rgba(245,158,11,0.35);border-radius:8px;background:rgba(245,158,11,0.08);">
                    <strong>Pending security: Rs. <?php echo number_format((float)$securityDeposit['original_amount'], 2); ?></strong>
                    <?php if (!empty($securityDeposit['expected_payment_date'])): ?><div>Expected payment: <?php echo htmlspecialchars(date('M d, Y', strtotime($securityDeposit['expected_payment_date']))); ?></div><?php endif; ?>
                    <?php if (!empty($securityDeposit['note'])): ?><div style="white-space:pre-wrap;"><?php echo htmlspecialchars($securityDeposit['note']); ?></div><?php endif; ?>
                    <div style="margin-top:0.4rem;">Checkout releases the bed and creates the Alumni record. Pending security remains pending; no payment, waiver, or refund will be created.</div>
                </div>
            </div>
            <?php elseif ($securityState === 'WAIVED'): ?>
            <div class="checkout-security-grid" style="display:block;">
                <div class="form-group" style="padding:0.9rem;border:1px solid var(--border);border-radius:8px;">
                    <strong>Security waived.</strong>
                    <div style="white-space:pre-wrap;"><?php echo htmlspecialchars($securityDeposit['note'] ?? ''); ?></div>
                    <div>Checkout will not create a security refund.</div>
                </div>
            </div>
            <?php elseif (!$securityDeposit): ?>
            <div class="checkout-security-grid" style="display:block;">
                <div class="form-group" style="color:var(--text-muted);">No security deposit record exists; checkout will not create a security settlement.</div>
            </div>
            <?php endif; ?>

            <div class="checkout-security-grid">
                <div class="form-group">
                    <label class="form-label">Processed By *</label>
                    <input type="text" name="processed_by_name" class="form-control" value="<?php echo htmlspecialchars((string)(\App\Core\Auth::user() ?: 'Admin'), ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Checked Out By *</label>
                    <input type="text" name="checked_out_by_name" class="form-control" value="<?php echo htmlspecialchars((string)(\App\Core\Auth::user() ?: 'Admin'), ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
            </div>

            <div style="display:flex;gap:1rem;justify-content:flex-end;margin-top:1.5rem;">
                <button type="button" class="btn" style="background:#334155;color:white;" onclick="closeCheckoutModal()">Cancel</button>
                <button type="submit" class="btn btn-danger" id="checkoutSubmitButton"><i class="fas fa-check"></i> Confirm Checkout</button>
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
const checkoutForm = document.getElementById('checkoutForm');
if (checkoutForm) {
    const settlement = document.getElementById('checkoutSecuritySettlement');
    const settlementInputs = settlement ? Array.from(settlement.querySelectorAll('input, textarea')) : [];
    const syncSecurityChoice = () => {
        const shouldSettle = checkoutForm.querySelector('[name="security_option"]:checked')?.value === 'RETURN_NOW';
        if (!settlement) return;
        settlement.style.display = shouldSettle ? 'block' : 'none';
        settlementInputs.forEach((input) => { input.disabled = !shouldSettle; });
    };
    checkoutForm.querySelectorAll('[name="security_option"]').forEach((radio) => {
        radio.addEventListener('change', syncSecurityChoice);
    });
    const deduction = document.getElementById('checkoutSecurityDeduction');
    if (deduction) {
        deduction.addEventListener('input', () => {
            const held = <?php echo json_encode($currentSecurityBalance ?? 0); ?>;
            const value = Number(deduction.value);
            document.getElementById('checkoutSecurityFinalReturned').value =
                Number.isFinite(value) ? Math.max(0, held - value).toFixed(2) : held.toFixed(2);
        });
    }
    checkoutForm.addEventListener('submit', (event) => {
        const shouldSettle = checkoutForm.querySelector('[name="security_option"]:checked')?.value === 'RETURN_NOW';
        if (shouldSettle && deduction && Number(deduction.value) > 0
            && !document.getElementById('checkoutSecurityRemarks').value.trim()) {
            event.preventDefault();
            document.getElementById('checkoutSecurityRemarks').focus();
            return;
        }
        const button = document.getElementById('checkoutSubmitButton');
        if (button) {
            button.disabled = true;
            button.textContent = 'Processing checkout...';
        }
    });
    syncSecurityChoice();
}
</script>
<?php endif; ?>
