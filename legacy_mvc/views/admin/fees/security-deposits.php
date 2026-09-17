<?php $config = require APP_ROOT . '/config/app.php'; $filters = $filters ?? []; $summary = $summary ?? ['total_held' => 0, 'held_students' => 0, 'total_refunded' => 0, 'total_deducted' => 0]; $deposits = $deposits ?? []; ?>
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title">Security Deposits</h3>
            <small style="color: var(--text-muted);">Protected ledger: deposit, deduction, refund, and settlement history remain separate from monthly fee collection.</small>
        </div>
        <a href="<?php echo $config['base_url']; ?>/dashboard" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <div class="row" style="margin-bottom: 1.5rem;">
        <div class="col-md-3">
            <div class="card" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; margin: 0; min-height: 130px;">
                <div style="font-size: 0.8rem; opacity: 0.9;">Held</div>
                <div style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem;">Rs. <?php echo number_format((float)$summary['total_held'], 2); ?></div>
                <div style="font-size: 0.8rem; opacity: 0.9; margin-top: 0.5rem;"><?php echo (int)$summary['held_students']; ?> active deposits</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; margin: 0; min-height: 130px;">
                <div style="font-size: 0.8rem; opacity: 0.9;">Refunded</div>
                <div style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem;">Rs. <?php echo number_format((float)$summary['total_refunded'], 2); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; margin: 0; min-height: 130px;">
                <div style="font-size: 0.8rem; opacity: 0.9;">Deducted</div>
                <div style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem;">Rs. <?php echo number_format((float)$summary['total_deducted'], 2); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card" style="margin: 0; min-height: 130px;">
                <div class="card-title" style="margin-bottom: 0.75rem;">Filters</div>
                <form method="GET" action="<?php echo $config['base_url']; ?>/fees/security-deposits" style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Student ID</label>
                        <input type="text" name="student_id" class="form-control" value="<?php echo htmlspecialchars($filters['student_id'] ?? ''); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="">All</option>
                            <option value="Held" <?php echo (($filters['status'] ?? '') === 'Held') ? 'selected' : ''; ?>>Held</option>
                            <option value="Partially Deducted" <?php echo (($filters['status'] ?? '') === 'Partially Deducted') ? 'selected' : ''; ?>>Partially Deducted</option>
                            <option value="Refunded" <?php echo (($filters['status'] ?? '') === 'Refunded') ? 'selected' : ''; ?>>Refunded</option>
                            <option value="Settled" <?php echo (($filters['status'] ?? '') === 'Settled') ? 'selected' : ''; ?>>Settled / Forfeited</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Apply</button>
                </form>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>CNIC</th>
                    <th>Admission Date</th>
                    <th>Security Amount</th>
                    <th>Amount Deducted</th>
                    <th>Amount Refunded</th>
                    <th>Current Security Balance</th>
                    <th>Deposit Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($deposits)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 2rem; color: var(--text-muted);">No security deposit records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($deposits as $deposit): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($deposit['student_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($deposit['student_id_str'] ?? $deposit['student_id'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($deposit['cnic'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($deposit['admission_date'] ?? 'N/A'); ?></td>
                            <td>Rs. <?php echo number_format((float)($deposit['security_amount'] ?? 0), 2); ?></td>
                            <td>Rs. <?php echo number_format((float)($deposit['amount_deducted'] ?? 0), 2); ?></td>
                            <td>Rs. <?php echo number_format((float)($deposit['amount_refunded'] ?? 0), 2); ?></td>
                            <td>Rs. <?php echo number_format((float)($deposit['current_balance'] ?? 0), 2); ?></td>
                            <td>
                                <span class="badge" style="background: <?php echo ($deposit['deposit_status'] ?? 'Held') === 'Refunded' ? '#10b981' : (($deposit['deposit_status'] ?? 'Held') === 'Settled' ? '#6b7280' : (($deposit['deposit_status'] ?? 'Held') === 'Partially Deducted' ? '#f59e0b' : '#8b5cf6')); ?>; color: white; padding: 0.35rem 0.6rem; border-radius: 99px;">
                                    <?php echo htmlspecialchars($deposit['deposit_status'] ?? 'Held'); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
