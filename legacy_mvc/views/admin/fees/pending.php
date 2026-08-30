<?php $config = require APP_ROOT . '/config/app.php'; $filters = $filters ?? []; $rows = $rows ?? []; $summary = $summary ?? ['total_pending_amount' => 0, 'pending_student_count' => 0, 'invoice_count' => 0]; ?>
<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 class="card-title">Pending Fees</h3>
            <small style="color: var(--text-muted);">Calculated from actual invoices and payments only</small>
        </div>
        <a href="<?php echo $config['base_url']; ?>/fees" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Fees</a>
    </div>

    <div class="row" style="margin-bottom: 1.5rem;">
        <div class="col-md-4">
            <div class="card" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; margin: 0; min-height: 130px;">
                <div style="font-size: 0.8rem; opacity: 0.9;">Total Pending</div>
                <div style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem;">Rs. <?php echo number_format((float)$summary['total_pending_amount'], 2); ?></div>
                <div style="font-size: 0.8rem; opacity: 0.9; margin-top: 0.5rem;"><?php echo (int)$summary['invoice_count']; ?> pending invoices</div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card" style="margin: 0; min-height: 130px;">
                <div class="card-title" style="margin-bottom: 1rem;">Filters</div>
                <form method="GET" action="<?php echo $config['base_url']; ?>/fees/pending" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0; min-width: 130px;">
                        <label class="form-label">Period</label>
                        <select name="period" class="form-control">
                            <option value="current_month" <?php echo (($filters['period'] ?? 'current_month') === 'current_month') ? 'selected' : ''; ?>>Current month</option>
                            <option value="previous_months" <?php echo (($filters['period'] ?? 'current_month') === 'previous_months') ? 'selected' : ''; ?>>Previous months</option>
                            <option value="all" <?php echo (($filters['period'] ?? 'current_month') === 'all') ? 'selected' : ''; ?>>All</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0; min-width: 120px;">
                        <label class="form-label">Month</label>
                        <input type="number" name="month" min="1" max="12" class="form-control" value="<?php echo htmlspecialchars($filters['month'] ?? ''); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0; min-width: 120px;">
                        <label class="form-label">Year</label>
                        <input type="number" name="year" min="2024" class="form-control" value="<?php echo htmlspecialchars($filters['year'] ?? ''); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0; min-width: 140px;">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="">All</option>
                            <option value="Pending" <?php echo (($filters['status'] ?? '') === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Partially Paid" <?php echo (($filters['status'] ?? '') === 'Partially Paid') ? 'selected' : ''; ?>>Partially Paid</option>
                            <option value="Overdue" <?php echo (($filters['status'] ?? '') === 'Overdue') ? 'selected' : ''; ?>>Overdue</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0; min-width: 180px;">
                        <label class="form-label">Student</label>
                        <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="Name or ID">
                    </div>
                    <button type="submit" class="btn btn-primary" style="height: 42px;"><i class="fas fa-filter"></i> Apply</button>
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
                    <th>Monthly Fee</th>
                    <th>Billing Month</th>
                    <th>Amount Paid</th>
                    <th>Pending Amount</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            No pending or partially paid fee invoices found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['student_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['student_id'] ?? 'N/A'); ?></td>
                        <td>Rs. <?php echo number_format((float)($row['monthly_fee'] ?? 0), 2); ?></td>
                        <td><?php echo htmlspecialchars($row['billing_period'] ?? (($row['billing_month'] ?? '') . '/' . ($row['billing_year'] ?? ''))); ?></td>
                        <td>Rs. <?php echo number_format((float)($row['amount_paid'] ?? 0), 2); ?></td>
                        <td>Rs. <?php echo number_format((float)($row['pending_amount'] ?? 0), 2); ?></td>
                        <td><?php echo htmlspecialchars($row['due_date'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['display_status'] ?? ($row['status'] ?? 'Pending')); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
