<?php $config = require APP_ROOT . '/config/app.php'; $filters = $filters ?? []; $students = $students ?? []; $summary = $summary ?? ['total_paid_amount' => 0, 'paid_student_count' => 0, 'total_invoices_count' => 0]; $paidUrl = $config['base_url'] . '/fees/paid' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''); ?>
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
                <i class="fas fa-check-circle" style="color:#10b981;"></i>
                Paid Fee
            </h3>
            <span style="background:rgba(16,185,129,0.15);color:#6ee7b7;border:1px solid rgba(16,185,129,0.3);
                         border-radius:999px;padding:0.2rem 0.75rem;font-size:0.8rem;font-weight:700;">
                <?php echo (int)($summary['paid_student_count'] ?? 0); ?> student<?php echo (int)($summary['paid_student_count'] ?? 0) === 1 ? '' : 's'; ?> fully paid
            </span>
        </div>
        <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
            <a href="<?php echo $config['base_url']; ?>/fees/pending" class="btn" style="background:#f59e0b;color:white;">
                <i class="fas fa-clock"></i> Pending Fee
            </a>
            <a href="<?php echo $config['base_url']; ?>/fees" class="btn" style="background:#334155;color:white;">
                <i class="fas fa-list"></i> All Fees
            </a>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem;">
        <div class="card" style="margin:0;background:linear-gradient(135deg,#059669,#047857);color:white;border:none;">
            <div style="font-size:0.8rem;opacity:0.85;text-transform:uppercase;letter-spacing:0.05em;">Total Paid Amount</div>
            <div style="font-size:1.8rem;font-weight:800;margin-top:0.25rem;">Rs. <?php echo number_format((float)($summary['total_paid_amount'] ?? 0), 2); ?></div>
            <div style="font-size:0.8rem;opacity:0.85;margin-top:0.25rem;"><?php echo (int)($summary['total_invoices_count'] ?? 0); ?> paid invoice<?php echo (int)($summary['total_invoices_count'] ?? 0) === 1 ? '' : 's'; ?></div>
        </div>
        <div class="card" style="margin:0;background:#1e293b;border:1px solid var(--border);">
            <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Fully Paid Students</div>
            <div style="font-size:1.8rem;font-weight:700;margin-top:0.25rem;color:#6ee7b7;"><?php echo (int)($summary['paid_student_count'] ?? 0); ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.25rem;">Active students with zero dues</div>
        </div>
        <div class="card" style="margin:0;background:#1e293b;border:1px solid var(--border);">
            <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Payment Status</div>
            <div style="font-size:1.8rem;font-weight:700;margin-top:0.25rem;color:#93c5fd;">100% Cleared</div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.25rem;">No remaining balance</div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div style="margin-bottom:1.5rem;">
        <form method="GET" action="<?php echo $config['base_url']; ?>/fees/paid"
              style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="margin-bottom:0;flex:1;min-width:180px;">
                <label class="form-label">Search Student</label>
                <input type="text" name="search" class="form-control"
                       value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>"
                       placeholder="Name, Student ID, CNIC, Phone...">
            </div>
            <div class="form-group" style="margin-bottom:0;flex:0 0 140px;">
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
            <button type="submit" class="btn btn-primary" style="height:42px;">
                <i class="fas fa-filter"></i> Filter
            </button>
            <?php if (!empty($filters['search']) || !empty($filters['room']) || !empty($filters['month']) || !empty($filters['year'])): ?>
                <a href="<?php echo $config['base_url']; ?>/fees/paid"
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
                    <th>Paid Month(s)</th>
                    <th>Paid Amount</th>
                    <th>Remaining Balance</th>
                    <th>Payment Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="12" style="text-align:center;padding:3rem;color:var(--text-muted);">
                            <i class="fas fa-receipt" style="font-size:2.5rem;color:#94a3b8;margin-bottom:0.75rem;display:block;opacity:0.5;"></i>
                            <div style="font-size:1.1rem;font-weight:600;color:var(--text);margin-bottom:0.25rem;">No paid fee students found.</div>
                            <?php if (!empty($filters['search']) || !empty($filters['room']) || !empty($filters['month']) || !empty($filters['year'])): ?>
                                <div style="font-size:0.85rem;">
                                    No records match your filter criteria.
                                    <a href="<?php echo $config['base_url']; ?>/fees/paid" style="color:var(--primary);">Clear filters</a>
                                </div>
                            <?php else: ?>
                                <div style="font-size:0.85rem;">There are no active students with completely paid fees yet.</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $stu): ?>
                    <tr>
                        <!-- Name -->
                        <td>
                            <a href="<?php echo $config['base_url']; ?>/students/view/<?php echo (int)$stu['student_id']; ?>?from=<?php echo urlencode($paidUrl); ?>"
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

                        <!-- Paid Months -->
                        <td>
                            <div style="display:flex;flex-wrap:wrap;gap:0.3rem;">
                                <?php foreach ($stu['invoices'] as $inv): ?>
                                    <span style="background:rgba(16,185,129,0.12);color:#6ee7b7;border:1px solid rgba(16,185,129,0.25);border-radius:4px;padding:0.15rem 0.45rem;font-size:0.75rem;font-weight:600;white-space:nowrap;">
                                        <?php echo htmlspecialchars($inv['billing_period']); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </td>

                        <!-- Paid Amount -->
                        <td>
                            <strong style="color:#10b981;font-size:0.95rem;">
                                Rs. <?php echo number_format($stu['total_paid'], 0); ?>
                            </strong>
                        </td>

                        <!-- Remaining Balance -->
                        <td>
                            <span class="badge badge-success" style="font-size:0.75rem;">
                                Rs. 0
                            </span>
                        </td>

                        <!-- Payment Date -->
                        <td style="white-space:nowrap;font-size:0.85rem;color:var(--text-muted);">
                            <?php echo $stu['latest_payment_date'] ? date('M d, Y', strtotime($stu['latest_payment_date'])) : '—'; ?>
                        </td>

                        <!-- Status -->
                        <td>
                            <span style="background:rgba(16,185,129,0.15);color:#6ee7b7;border:1px solid rgba(16,185,129,0.3);border-radius:999px;padding:0.2rem 0.55rem;font-size:0.75rem;font-weight:700;white-space:nowrap;">
                                Fully Paid
                            </span>
                        </td>

                        <!-- Actions -->
                        <td style="white-space:nowrap;">
                            <a href="<?php echo $config['base_url']; ?>/students/view/<?php echo (int)$stu['student_id']; ?>?from=<?php echo urlencode($paidUrl); ?>"
                               class="btn btn-sm" style="background:#1e3a5f;color:#93c5fd;border:1px solid rgba(59,130,246,0.3);"
                               title="View Student Profile">
                                <i class="fas fa-eye"></i> View Profile
                            </a>
                            <a href="<?php echo $config['base_url']; ?>/students/account/<?php echo (int)$stu['student_id']; ?>"
                               class="btn btn-sm" style="background:#334155;color:white;"
                               title="View Account Statement">
                                <i class="fas fa-file-invoice"></i> Statement
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
