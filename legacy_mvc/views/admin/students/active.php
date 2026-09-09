<?php $config = require APP_ROOT . '/config/app.php'; ?>
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
                <i class="fas fa-user-check" style="color:#3b82f6;"></i>
                Active Students
            </h3>
            <span style="background:rgba(59,130,246,0.15);color:#93c5fd;border:1px solid rgba(59,130,246,0.3);
                         border-radius:999px;padding:0.2rem 0.75rem;font-size:0.8rem;font-weight:700;">
                <?php echo (int)$total; ?> active
            </span>
        </div>
        <a href="<?php echo $config['base_url']; ?>/students/create" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> New Student
        </a>
    </div>

    <!-- Search Form -->
    <div style="margin-bottom:1.5rem;">
        <form action="<?php echo $config['base_url']; ?>/students/active" method="GET"
              style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group" style="margin-bottom:0;flex:1;min-width:220px;">
                <label class="form-label">Search Active Students</label>
                <input type="text" name="search" class="form-control"
                       placeholder="Name, CNIC, Phone..."
                       value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn btn-primary" style="height:42px;">
                <i class="fas fa-search"></i> Search
            </button>
            <?php if (!empty($filters['search'])): ?>
                <a href="<?php echo $config['base_url']; ?>/students/active"
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
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>CNIC</th>
                    <th>Phone</th>
                    <th>Room / Bed</th>
                    <th>Monthly Fee</th>
                    <th>Fee Status</th>
                    <th>Joined</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="10" style="text-align:center;padding:3rem;color:var(--text-muted);">
                            <i class="fas fa-user-slash" style="font-size:2rem;margin-bottom:0.75rem;display:block;opacity:0.4;"></i>
                            <div style="font-size:1rem;font-weight:600;margin-bottom:0.25rem;">No active students found.</div>
                            <?php if (!empty($filters['search'])): ?>
                                <div style="font-size:0.85rem;">
                                    No results for "<strong><?php echo htmlspecialchars($filters['search']); ?></strong>".
                                    <a href="<?php echo $config['base_url']; ?>/students/active" style="color:var(--primary);">Clear search</a>
                                </div>
                            <?php else: ?>
                                <div style="font-size:0.85rem;">
                                    <a href="<?php echo $config['base_url']; ?>/students/create" style="color:var(--primary);">Onboard a student</a> to get started.
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $s): ?>
                    <?php
                        $feeStatus = $s['fee_status'] ?? null;
                        $feeStatusColors = [
                            'Paid'    => ['bg'=>'rgba(16,185,129,0.12)', 'color'=>'#6ee7b7',  'border'=>'rgba(16,185,129,0.3)'],
                            'Partial' => ['bg'=>'rgba(245,158,11,0.12)', 'color'=>'#fcd34d',  'border'=>'rgba(245,158,11,0.3)'],
                            'Pending' => ['bg'=>'rgba(239,68,68,0.12)',  'color'=>'#fca5a5',  'border'=>'rgba(239,68,68,0.3)'],
                            'Overdue' => ['bg'=>'rgba(153,27,27,0.15)',  'color'=>'#fca5a5',  'border'=>'rgba(153,27,27,0.4)'],
                        ];
                        $sc = $feeStatusColors[$feeStatus] ?? ['bg'=>'rgba(148,163,184,0.12)','color'=>'var(--text-muted)','border'=>'rgba(148,163,184,0.2)'];
                        $activeUrl = $config['base_url'] . '/students/active' . (!empty($filters['search']) ? '?search=' . urlencode($filters['search']) : '');
                    ?>
                    <tr>
                        <td>
                            <span class="badge" style="background:#334155;font-family:monospace;font-size:0.78rem;">
                                <?php echo htmlspecialchars($s['student_id_str']); ?>
                            </span>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($s['full_name']); ?></strong>
                            <?php if (!empty($s['email'])): ?>
                                <br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($s['email']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="font-family:monospace;font-size:0.85rem;">
                            <?php echo htmlspecialchars(\App\Services\StudentService::formatCnic($s['cnic'])); ?>
                        </td>
                        <td><?php echo htmlspecialchars($s['phone']); ?></td>
                        <td>
                            <?php if (!empty($s['room_number'])): ?>
                                <span style="color:var(--primary);font-weight:600;">
                                    <?php
                                        $roomLabel = '';
                                        if (!empty($s['block'])) $roomLabel = htmlspecialchars($s['block']) . '-';
                                        $roomLabel .= htmlspecialchars($s['room_number']);
                                        echo $roomLabel;
                                    ?>
                                </span><br>
                                <small style="color:var(--text-muted);">Bed <?php echo (int)$s['bed_number']; ?></small>
                            <?php else: ?>
                                <span style="color:var(--text-muted);font-size:0.85rem;">
                                    <i class="fas fa-exclamation-circle" style="color:#fbbf24;"></i> Not allocated
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($s['monthly_fee'] !== null): ?>
                                <span style="color:var(--primary);font-weight:600;">Rs. <?php echo number_format($s['monthly_fee'], 0); ?></span>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($feeStatus): ?>
                                <span style="background:<?php echo $sc['bg']; ?>;color:<?php echo $sc['color']; ?>;
                                             border:1px solid <?php echo $sc['border']; ?>;border-radius:999px;
                                             padding:0.2rem 0.55rem;font-size:0.73rem;font-weight:700;white-space:nowrap;">
                                    <?php echo htmlspecialchars($feeStatus); ?>
                                </span>
                            <?php else: ?>
                                <span style="color:var(--text-muted);font-size:0.85rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space:nowrap;font-size:0.85rem;color:var(--text-muted);">
                            <?php
                                $joinedDate = $s['allocation_date'] ?? ($s['created_at'] ?? null);
                                echo $joinedDate ? date('M d, Y', strtotime($joinedDate)) : '—';
                            ?>
                        </td>
                        <td><span class="badge badge-success">Active</span></td>
                        <td style="white-space:nowrap;">
                            <a href="<?php echo $config['base_url']; ?>/students/view/<?php echo (int)$s['id']; ?>?from=<?php echo urlencode($activeUrl); ?>"
                               class="btn btn-sm" style="background:#1e3a5f;color:#93c5fd;border:1px solid rgba(59,130,246,0.25);"
                               title="View Profile">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="<?php echo $config['base_url']; ?>/students/edit/<?php echo (int)$s['id']; ?>?from=<?php echo urlencode($activeUrl); ?>"
                               class="btn btn-sm btn-primary" title="Edit Student">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php $totalPages = (int)ceil($total / max(1, $perPage)); ?>
    <?php if ($totalPages > 1): ?>
    <div style="margin-top:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div style="color:var(--text-muted);font-size:0.9rem;">
            Showing <?php echo (($page - 1) * $perPage) + 1; ?>&#8211;<?php echo min($page * $perPage, $total); ?> of <?php echo $total; ?> active students
        </div>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>"
                   class="btn btn-sm" style="background:#334155;color:white;">
                    <i class="fas fa-chevron-left"></i> Prev
                </a>
            <?php endif; ?>
            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                <a href="?page=<?php echo $p; ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>"
                   class="btn btn-sm"
                   style="<?php echo $p === $page ? 'background:var(--primary-dark);color:white;' : 'background:#334155;color:white;'; ?>">
                    <?php echo $p; ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>"
                   class="btn btn-sm" style="background:#334155;color:white;">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>