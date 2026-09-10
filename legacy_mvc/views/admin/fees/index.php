<?php $config = require APP_ROOT . '/config/app.php'; ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-money-bill-wave"></i> Fee Management</h3>
    </div>

    <!-- Financial Summary -->
    <?php if ($summary): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;">
        <div style="background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.2);border-radius:8px;padding:1rem;text-align:center;">
            <div style="font-size:0.8rem;color:var(--text-muted);">TOTAL BILLED</div>
            <div style="font-size:1.3rem;font-weight:700;color:#93c5fd;margin-top:0.25rem;">Rs. <?php echo number_format($summary['total_billed'], 0); ?></div>
        </div>
        <div style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);border-radius:8px;padding:1rem;text-align:center;">
            <div style="font-size:0.8rem;color:var(--text-muted);">COLLECTED</div>
            <div style="font-size:1.3rem;font-weight:700;color:#6ee7b7;margin-top:0.25rem;">Rs. <?php echo number_format($summary['total_collected'], 0); ?></div>
        </div>
        <div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);border-radius:8px;padding:1rem;text-align:center;">
            <div style="font-size:0.8rem;color:var(--text-muted);">OUTSTANDING</div>
            <div style="font-size:1.3rem;font-weight:700;color:#fcd34d;margin-top:0.25rem;">Rs. <?php echo number_format($summary['total_outstanding'], 0); ?></div>
        </div>
        <div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:1rem;text-align:center;">
            <div style="font-size:0.8rem;color:var(--text-muted);">OVERDUE</div>
            <div style="font-size:1.3rem;font-weight:700;color:#fca5a5;margin-top:0.25rem;">Rs. <?php echo number_format($summary['total_overdue'], 0); ?></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div style="margin-bottom:1.5rem;">
        <form action="<?php echo $config['base_url']; ?>/fees" method="GET" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group" style="margin-bottom:0;flex:1;min-width:200px;">
                <label class="form-label">Search Student</label>
                <input type="text" name="search" class="form-control" placeholder="Name or ID..." value="<?php echo htmlspecialchars($filters['search']); ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;flex:0 0 130px;">
                <label class="form-label">Month</label>
                <select name="month" class="form-control">
                    <option value="">All</option>
                    <?php for($m=1; $m<=12; ++$m): ?>
                        <option value="<?php echo $m; ?>" <?php echo $filters['month'] == $m ? 'selected' : ''; ?>><?php echo date('M', mktime(0,0,0,$m,1)); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;flex:0 0 100px;">
                <label class="form-label">Year</label>
                <select name="year" class="form-control">
                    <option value="">All</option>
                    <?php $y = date('Y'); for($i=$y-2; $i<=$y+1; ++$i): ?>
                        <option value="<?php echo $i; ?>" <?php echo $filters['year'] == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;flex:0 0 130px;">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">All</option>
                    <option value="Paid"    <?php echo $filters['status']==='Paid'    ? 'selected' : ''; ?>>Paid</option>
                    <option value="Partial" <?php echo $filters['status']==='Partial' ? 'selected' : ''; ?>>Partial</option>
                    <option value="Pending" <?php echo $filters['status']==='Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Overdue" <?php echo $filters['status']==='Overdue' ? 'selected' : ''; ?>>Overdue</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height:42px;"><i class="fas fa-filter"></i> Filter</button>
            <?php if ($filters['search'] || $filters['status'] || $filters['month'] || $filters['year']): ?>
                <a href="<?php echo $config['base_url']; ?>/fees" class="btn" style="height:42px;background:#334155;color:white;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Student</th>
                    <th>Period</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Remaining</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($fees)): ?>
                    <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--text-muted);">No fee records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($fees as $fee): ?>
                    <?php
                        $remaining = (float)$fee['amount'] - (float)$fee['paid_amount'];
                        $statusColors = [
                            'Paid'    => '#10b981',
                            'Partial' => '#f59e0b',
                            'Pending' => '#ef4444',
                            'Overdue' => '#7f1d1d'
                        ];
                        $statusColor = $statusColors[$fee['status']] ?? '#94a3b8';
                    ?>
                    <tr>
                        <td><code style="font-size:0.75rem;color:var(--text-muted);"><?php echo htmlspecialchars($fee['invoice_number'] ?? '#'.$fee['id']); ?></code></td>
                        <td>
                            <strong><?php echo htmlspecialchars($fee['full_name']); ?></strong><br>
                            <small class="badge" style="background:#334155;"><?php echo htmlspecialchars($fee['student_id_str']); ?></small>
                        </td>
                        <td><?php echo date('M Y', mktime(0,0,0,$fee['billing_month'],1,$fee['billing_year'])); ?></td>
                        <td>Rs. <?php echo number_format($fee['amount'], 0); ?></td>
                        <td style="color:var(--success);">Rs. <?php echo number_format($fee['paid_amount'], 0); ?></td>
                        <td style="color:<?php echo $remaining > 0 ? 'var(--danger)' : 'var(--text-muted)'; ?>;">
                            Rs. <?php echo number_format($remaining, 0); ?>
                        </td>
                        <td>
                            <span style="<?php echo (strtotime($fee['due_date']) < time() && $fee['status'] !== 'Paid') ? 'color:var(--danger);font-weight:600;' : ''; ?>">
                                <?php echo date('M d, Y', strtotime($fee['due_date'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge" style="background:<?php echo $statusColor; ?>22;color:<?php echo $statusColor; ?>;border:1px solid <?php echo $statusColor; ?>44;border-radius:999px;padding:0.25rem 0.6rem;font-size:0.75rem;font-weight:700;">
                                <?php echo htmlspecialchars($fee['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($fee['status'] !== 'Paid'): ?>
                                <a href="<?php echo $config['base_url']; ?>/fees/pay/<?php echo $fee['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-money-check-alt"></i> Pay
                                </a>
                            <?php else: ?>
                                <a href="<?php echo $config['base_url']; ?>/fees/pay/<?php echo $fee['id']; ?>" class="btn btn-sm" style="background:#1e293b;color:#6ee7b7;border:1px solid rgba(16,185,129,0.2);">
                                    <i class="fas fa-receipt"></i> View
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php $totalPages = ceil($total / $perPage); ?>
    <?php if ($totalPages > 1): ?>
    <div style="margin-top:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div style="color:var(--text-muted);font-size:0.9rem;">
            Showing <?php echo (($page-1)*$perPage)+1; ?>–<?php echo min($page*$perPage,$total); ?> of <?php echo $total; ?> records
        </div>
        <div style="display:flex;gap:0.5rem;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($filters['search']); ?>&status=<?php echo urlencode($filters['status']); ?>&month=<?php echo urlencode($filters['month']); ?>&year=<?php echo urlencode($filters['year']); ?>" class="btn btn-sm" style="background:#334155;color:white;"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
            <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
                <a href="?page=<?php echo $p; ?>&search=<?php echo urlencode($filters['search']); ?>&status=<?php echo urlencode($filters['status']); ?>&month=<?php echo urlencode($filters['month']); ?>&year=<?php echo urlencode($filters['year']); ?>" class="btn btn-sm" style="<?php echo $p===$page ? 'background:var(--primary-dark);color:white;' : 'background:#334155;color:white;'; ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($filters['search']); ?>&status=<?php echo urlencode($filters['status']); ?>&month=<?php echo urlencode($filters['month']); ?>&year=<?php echo urlencode($filters['year']); ?>" class="btn btn-sm" style="background:#334155;color:white;"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
