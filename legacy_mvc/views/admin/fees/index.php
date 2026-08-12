<?php $config = require __DIR__ . '/../../../config/app.php'; ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Fee Management</h3>
        <a href="<?php echo $config['base_url']; ?>/fees/create" class="btn btn-primary"><i class="fas fa-plus"></i> Add Fee Record</a>
    </div>

    <div style="margin-bottom: 1.5rem;">
        <form action="<?php echo $config['base_url']; ?>/fees" method="GET" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                <label class="form-label">Search Student</label>
                <input type="text" name="search" class="form-control" placeholder="Name or ID..." value="<?php echo htmlspecialchars($filters['search']); ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0; flex: 0 0 150px;">
                <label class="form-label">Month</label>
                <select name="month" class="form-control">
                    <option value="">All</option>
                    <?php for($m=1; $m<=12; ++$m): ?>
                        <option value="<?php echo $m; ?>" <?php echo $filters['month'] == $m ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0; flex: 0 0 150px;">
                <label class="form-label">Year</label>
                <select name="year" class="form-control">
                    <option value="">All</option>
                    <?php $y = date('Y'); for($i=$y-2; $i<=$y+1; ++$i): ?>
                        <option value="<?php echo $i; ?>" <?php echo $filters['year'] == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0; flex: 0 0 150px;">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">All</option>
                    <option value="Paid" <?php echo $filters['status'] === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="Pending" <?php echo $filters['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Partial" <?php echo $filters['status'] === 'Partial' ? 'selected' : ''; ?>>Partial</option>
                    <option value="Overdue" <?php echo $filters['status'] === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height: 42px;"><i class="fas fa-filter"></i> Filter</button>
        </form>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Billing Period</th>
                    <th>Total/Paid</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fees)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 2rem;">No fee records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($fees as $fee): ?>
                    <tr>
                        <td>#<?php echo $fee['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($fee['full_name']); ?></strong><br>
                            <small class="badge" style="background-color: #334155; margin-top: 5px; display: inline-block;"><?php echo htmlspecialchars($fee['student_id_str']); ?></small>
                        </td>
                        <td>
                            <?php echo date('F', mktime(0, 0, 0, $fee['billing_month'], 1)) . ' ' . $fee['billing_year']; ?><br>
                            <small style="color: var(--danger);">Due: <?php echo htmlspecialchars($fee['due_date']); ?></small>
                        </td>
                        <td>
                            Rs. <?php echo number_format($fee['amount'], 0); ?><br>
                            <small style="color: var(--success);">Paid: Rs. <?php echo number_format($fee['paid_amount'], 0); ?></small>
                        </td>
                        <td>
                            <?php if ($fee['status'] === 'Paid'): ?>
                                <span class="badge badge-success">Paid</span>
                            <?php elseif ($fee['status'] === 'Pending'): ?>
                                <span class="badge badge-danger">Pending</span>
                            <?php elseif ($fee['status'] === 'Partial'): ?>
                                <span class="badge badge-warning">Partial</span>
                            <?php else: ?>
                                <span class="badge badge-danger" style="background-color: #7f1d1d;">Overdue</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($fee['status'] !== 'Paid'): ?>
                            <a href="<?php echo $config['base_url']; ?>/fees/pay/<?php echo $fee['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-money-check-alt"></i> Pay</a>
                            <?php else: ?>
                            <button class="btn btn-sm" style="background: #334155; color: #94a3b8; cursor: not-allowed;" disabled><i class="fas fa-check"></i> Paid</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
