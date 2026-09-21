<?php $config = require APP_ROOT . '/config/app.php'; $student = $student ?? []; $invoices = $invoices ?? []; $payments = $payments ?? []; $summary = $summary ?? []; ?>
<div style="max-width:1100px;margin:0 auto;">

    <!-- Breadcrumb & Header -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.85rem;color:var(--text-muted);margin-bottom:0.25rem;">
                <a href="<?php echo $config['base_url']; ?>/dashboard" style="color:var(--text-muted);text-decoration:none;"><i class="fas fa-home"></i> Dashboard</a>
                <span>/</span>
                <a href="<?php echo $config['base_url']; ?>/fees/pending" style="color:var(--text-muted);text-decoration:none;">Pending Fee</a>
                <span>/</span>
                <span style="color:var(--text);"><?php echo htmlspecialchars($student['full_name'] ?? 'Student'); ?></span>
            </div>
            <h2 style="margin:0;display:flex;align-items:center;gap:0.6rem;flex-wrap:wrap;">
                <span><?php echo htmlspecialchars($student['full_name'] ?? ''); ?></span>
                <span class="badge" style="background:#334155;font-size:0.85rem;font-family:monospace;">
                    <?php echo htmlspecialchars($student['student_id_str'] ?? ''); ?>
                </span>
                <?php
                    $overallStatus = $summary['overall_status'] ?? 'Pending';
                    $statusBg = 'rgba(245,158,11,0.15)';
                    $statusColor = '#fcd34d';
                    $statusBorder = 'rgba(245,158,11,0.3)';
                    if ($overallStatus === 'Overdue') {
                        $statusBg = 'rgba(239,68,68,0.15)';
                        $statusColor = '#fca5a5';
                        $statusBorder = 'rgba(239,68,68,0.3)';
                    } elseif ($overallStatus === 'Partial') {
                        $statusBg = 'rgba(59,130,246,0.15)';
                        $statusColor = '#93c5fd';
                        $statusBorder = 'rgba(59,130,246,0.3)';
                    }
                ?>
                <span style="background:<?php echo $statusBg; ?>;color:<?php echo $statusColor; ?>;border:1px solid <?php echo $statusBorder; ?>;border-radius:999px;padding:0.2rem 0.65rem;font-size:0.75rem;font-weight:700;">
                    <?php echo htmlspecialchars($overallStatus); ?>
                </span>
            </h2>
        </div>
        <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
            <a href="<?php echo $config['base_url']; ?>/students/view/<?php echo (int)($student['id'] ?? 0); ?>" class="btn" style="background:#1e3a5f;color:#93c5fd;border:1px solid rgba(59,130,246,0.3);">
                <i class="fas fa-user"></i> Student Profile
            </a>
            <a href="<?php echo $config['base_url']; ?>/fees/pending" class="btn" style="background:#334155;color:white;">
                <i class="fas fa-arrow-left"></i> Back to Pending Fees
            </a>
        </div>
    </div>

    <!-- Summary Metrics Row -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem;">
        <div class="card" style="margin:0;background:linear-gradient(135deg,#f59e0b,#d97706);color:white;border:none;">
            <div style="font-size:0.8rem;opacity:0.85;text-transform:uppercase;letter-spacing:0.05em;">Total Pending Balance</div>
            <div style="font-size:1.8rem;font-weight:800;margin-top:0.25rem;">
                Rs. <?php echo number_format((float)($summary['total_pending_balance'] ?? 0), 2); ?>
            </div>
            <div style="font-size:0.8rem;opacity:0.8;margin-top:0.25rem;">Across <?php echo (int)($summary['pending_months_count'] ?? 0); ?> billing period(s)</div>
        </div>
        <div class="card" style="margin:0;background:#1e293b;border:1px solid var(--border);">
            <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Monthly Fee</div>
            <div style="font-size:1.6rem;font-weight:700;margin-top:0.25rem;color:var(--primary);">
                Rs. <?php echo number_format((float)($summary['monthly_fee'] ?? 0), 0); ?>
            </div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.25rem;">Profile fixed charge</div>
        </div>
        <div class="card" style="margin:0;background:#1e293b;border:1px solid var(--border);">
            <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Oldest Pending Month</div>
            <div style="font-size:1.3rem;font-weight:700;margin-top:0.25rem;color:#fcd34d;">
                <?php echo htmlspecialchars($summary['oldest_pending_month'] ?? '—'); ?>
            </div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.25rem;">
                Due: <?php echo !empty($summary['earliest_due_date']) ? date('M d, Y', strtotime($summary['earliest_due_date'])) : '—'; ?>
            </div>
        </div>
        <div class="card" style="margin:0;background:#1e293b;border:1px solid var(--border);">
            <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Total Paid on Pending Dues</div>
            <div style="font-size:1.6rem;font-weight:700;margin-top:0.25rem;color:#6ee7b7;">
                Rs. <?php echo number_format((float)($summary['total_amount_paid'] ?? 0), 2); ?>
            </div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.25rem;">Partial settlements</div>
        </div>
    </div>

    <!-- Student & Accommodation Information Card -->
    <div class="card" style="margin-bottom:1.5rem;">
        <h4 style="color:var(--primary);margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem;">
            <i class="fas fa-id-card"></i> Student & Accommodation Details
        </h4>
        <div class="row">
            <div class="col-md-3">
                <div style="font-size:0.78rem;color:var(--text-muted);text-transform:uppercase;">CNIC</div>
                <div style="font-weight:600;font-family:monospace;font-size:0.9rem;"><?php echo htmlspecialchars(\App\Services\StudentService::formatCnic($student['cnic'] ?? '')); ?></div>
            </div>
            <div class="col-md-3">
                <div style="font-size:0.78rem;color:var(--text-muted);text-transform:uppercase;">Phone</div>
                <div style="font-weight:600;"><?php echo htmlspecialchars($student['phone'] ?? '—'); ?></div>
            </div>
            <div class="col-md-3">
                <div style="font-size:0.78rem;color:var(--text-muted);text-transform:uppercase;">Room & Bed</div>
                <div style="font-weight:600;color:var(--primary);">
                    <?php if (!empty($student['room_number'])): ?>
                        Room <?php echo htmlspecialchars($student['room_number']); ?>
                        <?php if (!empty($student['bed_number'])): ?>
                            (Bed <?php echo (int)$student['bed_number']; ?>)
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color:var(--text-muted);">Not Allocated</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-3">
                <div style="font-size:0.78rem;color:var(--text-muted);text-transform:uppercase;">Student Status</div>
                <div><span class="badge badge-success"><?php echo htmlspecialchars($student['status'] ?? 'Active'); ?></span></div>
            </div>
        </div>
    </div>

    <!-- Pending Fee Breakdown -->
    <div class="card" style="margin-bottom:1.5rem;">
        <h4 style="color:#f59e0b;margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem;">
            <i class="fas fa-clock"></i> Pending Fee Breakdown (<?php echo count($invoices); ?>)
        </h4>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Billing Month</th>
                        <th>Invoice #</th>
                        <th>Invoice Amount</th>
                        <th>Amount Paid</th>
                        <th>Pending Balance</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center;padding:2rem;color:var(--text-muted);">
                                No pending fees found for this student.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($invoices as $inv): ?>
                        <?php
                            $iStatus = $inv['status'];
                            $ibg = 'rgba(245,158,11,0.12)';
                            $iclr = '#fcd34d';
                            $ibdr = 'rgba(245,158,11,0.3)';
                            if ($iStatus === 'Overdue') {
                                $ibg = 'rgba(239,68,68,0.12)';
                                $iclr = '#fca5a5';
                                $ibdr = 'rgba(239,68,68,0.3)';
                            } elseif ($iStatus === 'Partial') {
                                $ibg = 'rgba(59,130,246,0.12)';
                                $iclr = '#93c5fd';
                                $ibdr = 'rgba(59,130,246,0.3)';
                            }
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($inv['billing_period']); ?></strong>
                            </td>
                            <td>
                                <span class="badge" style="background:#334155;font-family:monospace;font-size:0.78rem;">
                                    <?php echo htmlspecialchars($inv['invoice_number']); ?>
                                </span>
                            </td>
                            <td>Rs. <?php echo number_format($inv['invoice_total'], 2); ?></td>
                            <td>
                                <?php if ($inv['paid_amount'] > 0): ?>
                                    <span style="color:#6ee7b7;font-weight:600;">Rs. <?php echo number_format($inv['paid_amount'], 2); ?></span>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">Rs. 0.00</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color:#f59e0b;font-size:0.95rem;">
                                    Rs. <?php echo number_format($inv['pending_amount'], 2); ?>
                                </strong>
                            </td>
                            <td style="white-space:nowrap;font-size:0.85rem;color:var(--text-muted);">
                                <?php echo $inv['due_date'] ? date('M d, Y', strtotime($inv['due_date'])) : '—'; ?>
                                <?php if ($inv['is_overdue']): ?>
                                    <br><small style="color:#ef4444;font-weight:600;">Past Due</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="background:<?php echo $ibg; ?>;color:<?php echo $iclr; ?>;border:1px solid <?php echo $ibdr; ?>;border-radius:999px;padding:0.2rem 0.55rem;font-size:0.75rem;font-weight:700;white-space:nowrap;">
                                    <?php echo htmlspecialchars($iStatus); ?>
                                </span>
                            </td>
                            <td style="white-space:nowrap;">
                                <a href="<?php echo $config['base_url']; ?>/fees/pay/<?php echo (int)$inv['id']; ?>"
                                   class="btn btn-sm btn-primary" title="Record payment for this invoice">
                                    <i class="fas fa-hand-holding-usd"></i> Receive Payment
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment History Section -->
    <div class="card">
        <h4 style="color:var(--primary);margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem;">
            <i class="fas fa-history"></i> Payment History (<?php echo count($payments); ?>)
        </h4>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Payment Date</th>
                        <th>Invoice #</th>
                        <th>Billing Period</th>
                        <th>Amount Received</th>
                        <th>Payment Method</th>
                        <th>Received By</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="9" style="text-align:center;padding:2rem;color:var(--text-muted);">
                                No payments recorded yet for this student.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td>
                                <span class="badge" style="background:#334155;font-family:monospace;font-size:0.78rem;">
                                    <?php echo htmlspecialchars($p['receipt_number'] ?? '—'); ?>
                                </span>
                            </td>
                            <td style="white-space:nowrap;"><?php echo !empty($p['payment_date']) ? date('M d, Y', strtotime($p['payment_date'])) : '—'; ?></td>
                            <td><?php echo htmlspecialchars($p['invoice_number'] ?? '—'); ?></td>
                            <td>
                                <?php
                                    $pPeriod = '';
                                    if (!empty($p['billing_month']) && !empty($p['billing_year'])) {
                                        $pPeriod = date('M Y', mktime(0, 0, 0, (int)$p['billing_month'], 1, (int)$p['billing_year']));
                                    }
                                    echo htmlspecialchars($pPeriod ?: '—');
                                ?>
                            </td>
                            <td>
                                <strong style="color:#6ee7b7;">Rs. <?php echo number_format((float)($p['amount'] ?? 0), 2); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($p['payment_method'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($p['received_by_admin_username'] ?? 'Admin'); ?></td>
                            <td>
                                <span class="badge badge-success"><?php echo htmlspecialchars($p['status'] ?? 'Success'); ?></span>
                            </td>
                            <td>
                                <a href="<?php echo $config['base_url']; ?>/fees/receipt/<?php echo (int)$p['id']; ?>"
                                   class="btn btn-sm" style="background:#1e3a5f;color:#93c5fd;border:1px solid rgba(59,130,246,0.3);"
                                   title="View Receipt">
                                    <i class="fas fa-receipt"></i> Receipt
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>