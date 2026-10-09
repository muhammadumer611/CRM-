<?php $config = require APP_ROOT . '/config/app.php'; $filters = $filters ?? []; $students = $students ?? []; $summary = $summary ?? ['total_pending_amount' => 0, 'pending_student_count' => 0, 'overdue_student_count' => 0, 'total_invoices_count' => 0]; ?>
<div class="card">
    <!-- Page Header -->
    <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
            <a href="<?php echo $config['base_url']; ?>/dashboard"
               style="color:var(--text-muted);font-size:0.85rem;display:inline-flex;align-items:center;gap:0.35rem;">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <span style="color:var(--border);">/</span>
            <h3 class="card-title" style="margin:0;">
                <i class="fas fa-file-invoice-dollar" style="color:#f59e0b;"></i>
                Pending Fee
            </h3>
            <span style="background:rgba(245,158,11,0.15);color:#fcd34d;border:1px solid rgba(245,158,11,0.3);
                         border-radius:999px;padding:0.2rem 0.75rem;font-size:0.8rem;font-weight:700;">
                <?php echo (int)($summary['pending_student_count'] ?? 0); ?> student<?php echo (int)($summary['pending_student_count'] ?? 0) === 1 ? '' : 's'; ?>
            </span>
        </div>
        <div style="display:flex;gap:0.75rem;align-items:center;">
            <a href="<?php echo $config['base_url']; ?>/fees" class="btn" style="background:#334155;color:white;">
                <i class="fas fa-list"></i> All Fees
            </a>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem;">
        <div class="card" style="margin:0;background:linear-gradient(135deg,#f59e0b,#d97706);color:white;border:none;">
            <div style="font-size:0.8rem;opacity:0.85;text-transform:uppercase;letter-spacing:0.05em;">Total Pending Balance</div>
            <div style="font-size:1.8rem;font-weight:800;margin-top:0.25rem;">Rs. <?php echo number_format((float)($summary['total_pending_amount'] ?? 0), 2); ?></div>
            <div style="font-size:0.8rem;opacity:0.8;margin-top:0.25rem;"><?php echo (int)($summary['total_invoices_count'] ?? 0); ?> pending fee record<?php echo (int)($summary['total_invoices_count'] ?? 0) === 1 ? '' : 's'; ?></div>
        </div>
        <div class="card" style="margin:0;background:#1e293b;border:1px solid var(--border);">
            <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Pending Students</div>
            <div style="font-size:1.8rem;font-weight:700;margin-top:0.25rem;color:#fcd34d;"><?php echo (int)($summary['pending_student_count'] ?? 0); ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.25rem;">Active students with dues</div>
        </div>
        <div class="card" style="margin:0;background:#1e293b;border:1px solid var(--border);">
            <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Overdue Students</div>
            <div style="font-size:1.8rem;font-weight:700;margin-top:0.25rem;color:#fca5a5;"><?php echo (int)($summary['overdue_student_count'] ?? 0); ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.25rem;">Past due date</div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div style="margin-bottom:1.5rem;">
        <form method="GET" action="<?php echo $config['base_url']; ?>/fees/pending"
              style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="margin-bottom:0;flex:1;min-width:180px;">
                <label class="form-label">Search Student</label>
                <input type="text" name="search" class="form-control"
                       value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>"
                       placeholder="Name, Student ID, CNIC, Phone...">
            </div>
            <div class="form-group" style="margin-bottom:0;flex:0 0 130px;">
                <label class="form-label">Room</label>
                <input type="text" name="room" class="form-control"
                       value="<?php echo htmlspecialchars($filters['room'] ?? ''); ?>"
                       placeholder="Room Number">
            </div>
            <div class="form-group" style="margin-bottom:0;flex:0 0 110px;">
                <label class="form-label">Month</label>
                <input type="number" name="month" min="1" max="12" class="form-control"
                       value="<?php echo htmlspecialchars($filters['month'] ?? ''); ?>"
                       placeholder="1-12">
            </div>
            <div class="form-group" style="margin-bottom:0;flex:0 0 110px;">
                <label class="form-label">Year</label>
                <input type="number" name="year" min="2020" max="2035" class="form-control"
                       value="<?php echo htmlspecialchars($filters['year'] ?? ''); ?>"
                       placeholder="YYYY">
            </div>
            <div class="form-group" style="margin-bottom:0;flex:0 0 140px;">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?php echo (($filters['status'] ?? '') === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="Partial" <?php echo (($filters['status'] ?? '') === 'Partial') ? 'selected' : ''; ?>>Partially Paid</option>
                    <option value="Overdue" <?php echo (($filters['status'] ?? '') === 'Overdue') ? 'selected' : ''; ?>>Overdue</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height:42px;">
                <i class="fas fa-filter"></i> Filter
            </button>
            <?php if (!empty($filters['search']) || !empty($filters['room']) || !empty($filters['month']) || !empty($filters['year']) || !empty($filters['status'])): ?>
                <a href="<?php echo $config['base_url']; ?>/fees/pending"
                   class="btn" style="height:42px;background:#334155;color:white;">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Students Table -->
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>CNIC</th>
                    <th>Phone</th>
                    <th>Room / Bed</th>
                    <th>Monthly Fee</th>
                    <th>Pending Month(s)</th>
                    <th>Total Fee</th>
                    <th>Paid</th>
                    <th>Pending Amount</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="13" style="text-align:center;padding:3rem;color:var(--text-muted);">
                            <i class="fas fa-check-circle" style="font-size:2.5rem;color:#10b981;margin-bottom:0.75rem;display:block;opacity:0.7;"></i>
                            <div style="font-size:1.1rem;font-weight:600;color:var(--text);margin-bottom:0.25rem;">No pending fees found.</div>
                            <?php if (!empty($filters['search']) || !empty($filters['room']) || !empty($filters['month']) || !empty($filters['year']) || !empty($filters['status'])): ?>
                                <div style="font-size:0.85rem;">
                                    No records match your filter criteria.
                                    <a href="<?php echo $config['base_url']; ?>/fees/pending" style="color:var(--primary);">Clear filters</a>
                                </div>
                            <?php else: ?>
                                <div style="font-size:0.85rem;">All active students have cleared their monthly dues!</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $stu): ?>
                    <?php
                        $st = $stu['overall_status'];
                        $badgeBg = 'rgba(245,158,11,0.12)';
                        $badgeColor = '#fcd34d';
                        $badgeBorder = 'rgba(245,158,11,0.3)';
                        if ($st === 'Overdue') {
                            $badgeBg = 'rgba(239,68,68,0.12)';
                            $badgeColor = '#fca5a5';
                            $badgeBorder = 'rgba(239,68,68,0.3)';
                        } elseif ($st === 'Partial') {
                            $badgeBg = 'rgba(59,130,246,0.12)';
                            $badgeColor = '#93c5fd';
                            $badgeBorder = 'rgba(59,130,246,0.3)';
                        }
                    ?>
                    <tr>
                        <!-- Name -->
                        <td>
                            <a href="<?php echo $config['base_url']; ?>/fees/pending/<?php echo (int)$stu['student_id']; ?>"
                               style="color:var(--text);font-weight:700;text-decoration:none;"
                               onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text)'">
                                <?php echo htmlspecialchars($stu['student_name']); ?>
                            </a>
                        </td>

                        <!-- Student ID -->
                        <td>
                            <span class="badge" style="background:#334155;font-family:monospace;font-size:0.78rem;">
                                <?php echo htmlspecialchars($stu['student_id_str']); ?>
                            </span>
                        </td>

                        <!-- CNIC -->
                        <td style="font-family:monospace;font-size:0.85rem;">
                            <?php echo htmlspecialchars(\App\Services\StudentService::formatCnic($stu['cnic'])); ?>
                        </td>

                        <!-- Phone -->
                        <td><?php echo htmlspecialchars($stu['phone']); ?></td>

                        <!-- Room / Bed -->
                        <td>
                            <?php if (!empty($stu['room_number'])): ?>
                                <span style="color:var(--primary);font-weight:600;">
                                    <?php
                                        $rm = '';
                                        if (!empty($stu['block'])) $rm .= htmlspecialchars($stu['block']) . '-';
                                        $rm .= htmlspecialchars($stu['room_number']);
                                        echo $rm;
                                    ?>
                                </span>
                                <?php if (!empty($stu['bed_number'])): ?>
                                    <br><small style="color:var(--text-muted);">Bed <?php echo (int)$stu['bed_number']; ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Monthly Fee -->
                        <td>
                            <?php if ($stu['monthly_fee'] !== null && $stu['monthly_fee'] > 0): ?>
                                Rs. <?php echo number_format($stu['monthly_fee'], 0); ?>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Pending Months -->
                        <td>
                            <div style="display:flex;flex-wrap:wrap;gap:0.3rem;">
                                <?php foreach ($stu['invoices'] as $inv): ?>
                                    <span style="background:rgba(245,158,11,0.12);color:#fcd34d;border:1px solid rgba(245,158,11,0.25);border-radius:4px;padding:0.15rem 0.45rem;font-size:0.75rem;font-weight:600;white-space:nowrap;">
                                        <?php echo htmlspecialchars($inv['billing_period']); ?>
                                        <?php if ($inv['is_overdue']): ?>
                                            <i class="fas fa-exclamation-triangle" style="color:#ef4444;font-size:0.7rem;margin-left:0.2rem;" title="Overdue"></i>
                                        <?php endif; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </td>

                        <!-- Total Fee -->
                        <td>Rs. <?php echo number_format($stu['total_due'], 0); ?></td>

                        <!-- Paid -->
                        <td>
                            <?php if ($stu['total_paid'] > 0): ?>
                                <span style="color:#6ee7b7;font-weight:600;">Rs. <?php echo number_format($stu['total_paid'], 0); ?></span>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">Rs. 0</span>
                            <?php endif; ?>
                        </td>

                        <!-- Pending Amount -->
                        <td>
                            <strong style="color:#f59e0b;font-size:0.95rem;">
                                Rs. <?php echo number_format($stu['total_pending'], 0); ?>
                            </strong>
                        </td>

                        <!-- Earliest Due Date -->
                        <td style="white-space:nowrap;font-size:0.85rem;color:var(--text-muted);">
                            <?php echo $stu['earliest_due_date'] ? date('M d, Y', strtotime($stu['earliest_due_date'])) : '—'; ?>
                        </td>

                        <!-- Status -->
                        <td>
                            <span style="background:<?php echo $badgeBg; ?>;color:<?php echo $badgeColor; ?>;border:1px solid <?php echo $badgeBorder; ?>;border-radius:999px;padding:0.2rem 0.55rem;font-size:0.75rem;font-weight:700;white-space:nowrap;">
                                <?php echo htmlspecialchars($st); ?>
                            </span>
                        </td>

                        <!-- Action -->
                        <td style="white-space:nowrap;">
                            <a href="<?php echo $config['base_url']; ?>/fees/pending/<?php echo (int)$stu['student_id']; ?>"
                               class="btn btn-sm" style="background:#1e3a5f;color:#93c5fd;border:1px solid rgba(59,130,246,0.3);"
                               title="View Complete Pending Details">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>