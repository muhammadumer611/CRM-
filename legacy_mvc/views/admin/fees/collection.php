<?php $config = require APP_ROOT . '/config/app.php'; $filters = $filters ?? []; $payments = $payments ?? []; $summary = $summary ?? ['total_collection' => 0, 'payment_count' => 0, 'latest_payment_date' => null]; ?>
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title">Total Collection</h3>
            <small style="color: var(--text-muted);">Actual cash received from recorded payments only</small>
        </div>
        <a href="<?php echo $config['base_url']; ?>/fees" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Fees</a>
    </div>

    <div class="row" style="margin-bottom: 1.5rem;">
        <div class="col-md-4">
            <div class="card" style="background: linear-gradient(135deg, #10b981, #059669); color: white; margin: 0; min-height: 130px;">
                <div style="font-size: 0.8rem; opacity: 0.9;">Collected</div>
                <div style="font-size: 2rem; font-weight: 800; margin-top: 0.5rem;">Rs. <?php echo number_format((float)$summary['total_collection'], 2); ?></div>
                <div style="font-size: 0.8rem; opacity: 0.9; margin-top: 0.5rem;"><?php echo (int)$summary['payment_count']; ?> active payments</div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card" style="margin: 0; min-height: 130px;">
                <div class="card-title" style="margin-bottom: 1rem;">Filters</div>
                <form method="GET" action="<?php echo $config['base_url']; ?>/fees/collection" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0; min-width: 130px;">
                        <label class="form-label">Range</label>
                        <select name="date_filter" class="form-control">
                            <option value="today" <?php echo (($filters['date_filter'] ?? 'this_month') === 'today') ? 'selected' : ''; ?>>Today</option>
                            <option value="this_month" <?php echo (($filters['date_filter'] ?? 'this_month') === 'this_month') ? 'selected' : ''; ?>>This month</option>
                            <option value="this_year" <?php echo (($filters['date_filter'] ?? 'this_month') === 'this_year') ? 'selected' : ''; ?>>This year</option>
                            <option value="custom" <?php echo (($filters['date_filter'] ?? 'this_month') === 'custom') ? 'selected' : ''; ?>>Custom date range</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0; min-width: 160px;">
                        <label class="form-label">From</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($filters['start_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0; min-width: 160px;">
                        <label class="form-label">To</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($filters['end_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0; min-width: 150px;">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-control">
                            <option value="">All methods</option>
                            <option value="Cash" <?php echo (($filters['payment_method'] ?? '') === 'Cash') ? 'selected' : ''; ?>>Cash</option>
                            <option value="Bank Transfer" <?php echo (($filters['payment_method'] ?? '') === 'Bank Transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                            <option value="Online" <?php echo (($filters['payment_method'] ?? '') === 'Online') ? 'selected' : ''; ?>>Online</option>
                            <option value="Other" <?php echo (($filters['payment_method'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
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
                    <th>Amount Received</th>
                    <th>Payment Date</th>
                    <th>Payment Method</th>
                    <th>Receipt Number</th>
                    <th>Billing Month/Year</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            No active collection records found for this date range.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($payment['student_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($payment['student_id'] ?? 'N/A'); ?></td>
                        <td>Rs. <?php echo number_format((float)($payment['amount_received'] ?? 0), 2); ?></td>
                        <td><?php echo htmlspecialchars($payment['payment_date'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($payment['payment_method'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($payment['receipt_number'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($payment['billing_period'] ?? (($payment['billing_month'] ?? '') . '/' . ($payment['billing_year'] ?? ''))); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
