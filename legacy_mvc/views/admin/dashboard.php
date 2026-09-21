<?php $config = require APP_ROOT . '/config/app.php'; ?>

<style>
    .dashboard-shell { animation: dashboard-enter 0.35s ease-out both; display:flex; flex-direction:column; }
    .dashboard-shell .student-search-panel { order:1; }
    .dashboard-shell .dashboard-summary { order:2; }
    .dashboard-shell .dashboard-alerts { order:3; }
    .dashboard-shell .dashboard-quick-actions { order:4; }
    .dashboard-card { transition: transform 0.18s ease, box-shadow 0.18s ease; }
    .dashboard-card:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(15,23,42,0.22); }
    .student-search-panel { border: 1px solid var(--border); background: linear-gradient(135deg, rgba(14,165,233,0.12), rgba(15,23,42,0.35)); }
    .student-search-results { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 0.75rem; margin-top: 1rem; }
    .student-search-result { animation: result-enter 0.25s ease-out both; border: 1px solid var(--border); transition: transform 0.18s ease, border-color 0.18s ease; }
    .student-search-result:hover { transform: translateY(-2px); border-color: var(--primary); }
    .student-search-panel form { flex-wrap: wrap; }
    .student-search-panel form input { flex: 1 1 240px; }
    .student-search-panel .search-submit.is-loading { opacity: 0.75; cursor: wait; }
    .dashboard-summary { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)) !important; grid-auto-rows:172px; gap:1rem; margin-bottom:1.5rem; }
    .dashboard-summary > a { display:flex !important; min-width:0; }
    .dashboard-summary .dashboard-stat-card { width:100%; min-height:172px; height:100% !important; margin:0 !important; padding:1.2rem !important; border:1px solid rgba(148,163,184,.28) !important; border-radius:12px !important; background:var(--card) !important; color:var(--text) !important; box-shadow:0 8px 20px rgba(15,23,42,.08) !important; display:flex; flex-direction:column; justify-content:space-between; gap:1rem; }
    .dashboard-summary .dashboard-stat-card:hover { border-color:var(--primary) !important; box-shadow:0 12px 26px rgba(15,23,42,.14) !important; }
    .dashboard-stat-heading { display:flex; align-items:center; justify-content:space-between; gap:.75rem; color:var(--text-muted); font-size:.78rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }
    .dashboard-stat-icon { width:2.25rem; height:2.25rem; display:inline-flex; align-items:center; justify-content:center; flex:0 0 2.25rem; border-radius:9px; color:var(--primary); background:rgba(2,132,199,.12); font-size:1rem; }
    .dashboard-stat-value { color:var(--text); font-size:clamp(1.55rem,2.4vw,2rem); line-height:1.1; font-weight:800; overflow-wrap:anywhere; }
    .dashboard-stat-support { color:var(--text-muted); font-size:.78rem; line-height:1.4; }
    .dashboard-summary .dashboard-stat-card:nth-child(2) .dashboard-stat-icon { color:var(--success); background:rgba(16,185,129,.14); }
    .dashboard-summary .dashboard-stat-card:nth-child(3) .dashboard-stat-icon { color:#6366f1; background:rgba(99,102,241,.14); }
    .dashboard-summary .dashboard-stat-card:nth-child(4) .dashboard-stat-icon { color:#d97706; background:rgba(217,119,6,.14); }
    .dashboard-summary .dashboard-stat-card:nth-child(5) .dashboard-stat-icon { color:var(--success); background:rgba(16,185,129,.14); }
    .dashboard-summary .dashboard-stat-card:nth-child(6) .dashboard-stat-icon { color:#7c3aed; background:rgba(124,58,237,.14); }
    @media (max-width:1100px) { .dashboard-summary { grid-template-columns:repeat(2,minmax(0,1fr)) !important; } }
    @media (max-width:600px) { .dashboard-summary { grid-template-columns:1fr !important; grid-auto-rows:148px; } .dashboard-summary .dashboard-stat-card { min-height:0; } }
    @keyframes dashboard-enter { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes result-enter { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
    @media (prefers-reduced-motion: reduce) {
        .dashboard-shell, .student-search-result { animation: none; }
        .dashboard-card, .student-search-result { transition: none; }
    }
</style>

<div class="dashboard-shell">
    <div class="card student-search-panel" style="margin-bottom:1.5rem;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
            <div>
                <h2 style="margin:0 0 0.35rem;"><i class="fas fa-search" style="color:var(--primary);"></i> Find a Student</h2>
                <div style="color:var(--text-muted);font-size:0.9rem;">Search by name, CNIC, phone, district, room, bed, or student ID.</div>
            </div>
        </div>
        <form id="studentSearchForm" method="GET" action="<?php echo $config['base_url']; ?>/dashboard" style="display:flex;gap:0.75rem;margin-top:1rem;align-items:center;">
            <label for="studentSearch" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);">Search students</label>
            <input id="studentSearch" name="student_search" class="form-control" type="search" value="<?php echo htmlspecialchars($studentSearch ?? ''); ?>" placeholder="e.g. Muhammad, 35202, 0300, Room 101" autocomplete="off">
            <button type="submit" class="btn btn-primary search-submit" style="white-space:nowrap;"><i class="fas fa-search"></i> Search</button>
            <?php if (!empty($studentSearch)): ?><a href="<?php echo $config['base_url']; ?>/dashboard" class="btn" style="background:#334155;color:white;">Clear</a><?php endif; ?>
        </form>
        <?php if (!empty($studentSearch)): ?>
            <?php if (empty($studentResults)): ?>
                <div style="margin-top:1rem;color:var(--text-muted);">No students found for “<?php echo htmlspecialchars($studentSearch); ?>”.</div>
            <?php else: ?>
                <div class="student-search-results" aria-live="polite">
                    <?php foreach ($studentResults as $index => $result): ?>
                        <div class="student-search-result card" style="margin:0;animation-delay:<?php echo min($index, 5) * 45; ?>ms;">
                            <div style="display:flex;justify-content:space-between;gap:0.75rem;align-items:flex-start;">
                                <div><strong><?php echo htmlspecialchars($result['full_name']); ?></strong><div style="font-size:0.8rem;color:var(--text-muted);"><?php echo htmlspecialchars($result['student_id_str']); ?></div></div>
                                <span class="badge <?php echo $result['status'] === 'Active' ? 'badge-success' : 'badge-danger'; ?>"><?php echo htmlspecialchars($result['status']); ?></span>
                            </div>
                            <div style="font-size:0.82rem;color:var(--text-muted);margin-top:0.75rem;line-height:1.6;">
                                    <div><?php echo htmlspecialchars($result['phone']); ?> | <?php echo htmlspecialchars(\App\Services\StudentService::formatCnic($result['cnic'])); ?></div>
                                <div>Fee: <?php echo htmlspecialchars($result['fee_status'] ?? 'Not billed'); ?><?php if ((float)($result['pending_amount'] ?? 0) > 0): ?> · Pending Rs. <?php echo number_format((float)$result['pending_amount'], 0); ?><?php endif; ?></div>
                            </div>
                            <a href="<?php echo $config['base_url']; ?>/students/account/<?php echo (int)$result['id']; ?>" class="btn btn-sm btn-primary" style="margin-top:0.9rem;width:100%;justify-content:center;"><i class="fas fa-user-circle"></i> View Account</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

<script>
document.getElementById('studentSearchForm')?.addEventListener('submit', function () {
    const button = this.querySelector('.search-submit');
    if (!button) return;
    button.classList.add('is-loading');
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
});
</script>

<?php $alerts = $alerts ?? ['rooms' => [], 'fees' => [], 'fee_alerts_active' => false]; ?>
<div class="card dashboard-alerts" style="margin-bottom:1.5rem;border:1px solid rgba(245,158,11,0.35);box-shadow:0 10px 24px rgba(15,23,42,0.18);">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
        <div>
            <h3 class="card-title" style="margin:0;"><i class="fas fa-exclamation-triangle" style="color:#fbbf24;"></i> Attention Required</h3>
            <div style="color:var(--text-muted);font-size:0.85rem;margin-top:0.25rem;">Room capacity and fee items needing attention.</div>
        </div>
        <span class="badge" style="background:rgba(245,158,11,0.12);color:#fbbf24;border:1px solid rgba(245,158,11,0.25);">Live</span>
    </div>
    <?php if (empty($alerts['rooms']) && empty($alerts['fees'])): ?>
        <div style="color:var(--text-muted);">No room capacity or monthly fee alerts right now.</div>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:0.75rem;">
            <?php foreach ($alerts['rooms'] as $roomAlert): ?>
                <a href="<?php echo $config['base_url']; ?>/rooms/available-beds?search=<?php echo urlencode($roomAlert['room_number']); ?>" class="card" style="margin:0;text-decoration:none;border-color:rgba(245,158,11,0.35);">
                    <strong><i class="fas fa-door-open" style="color:#fbbf24;"></i> Room <?php echo htmlspecialchars($roomAlert['room_number']); ?> almost full</strong>
                    <div style="color:var(--text-muted);margin-top:0.4rem;">Occupied <?php echo (int)$roomAlert['occupied_beds']; ?> of <?php echo (int)$roomAlert['total_beds']; ?> beds · <?php echo (int)$roomAlert['available_beds']; ?> bed remaining.</div>
                    <div style="color:var(--primary);font-size:0.82rem;margin-top:0.7rem;">View room <i class="fas fa-arrow-right"></i></div>
                </a>
            <?php endforeach; ?>
            <?php foreach ($alerts['fees'] as $feeAlert): ?>
                <a href="<?php echo $config['base_url']; ?>/students/account/<?php echo (int)$feeAlert['student_id']; ?>" class="card" style="margin:0;text-decoration:none;border-color:rgba(239,68,68,0.35);">
                    <strong><i class="fas fa-file-invoice-dollar" style="color:#f87171;"></i> Fee pending: <?php echo htmlspecialchars($feeAlert['student_name']); ?></strong>
                    <div style="color:var(--text-muted);margin-top:0.4rem;"><?php echo htmlspecialchars($feeAlert['student_id_str']); ?> · <?php echo date('F Y', mktime(0, 0, 0, (int)$feeAlert['billing_month'], 1, (int)$feeAlert['billing_year'])); ?> · Outstanding Rs. <?php echo number_format((float)$feeAlert['pending_amount'], 2); ?></div>
                    <div style="color:var(--primary);font-size:0.82rem;margin-top:0.7rem;">View student account <i class="fas fa-arrow-right"></i></div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ===== ROW 1: KEY STATS ===== -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;" class="dashboard-card dashboard-summary">
    <a href="<?php echo $config['base_url']; ?>/students/active"
       style="display:block;text-decoration:none;color:inherit;border-radius:8px;transition:transform 0.15s,box-shadow 0.15s;"
       onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px rgba(59,130,246,0.35)';"
       onmouseout="this.style.transform='';this.style.boxShadow='';"
       title="Click to view all active students">
    <div class="card dashboard-stat-card" style="margin:0;background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;border:none;cursor:pointer;height:100%;">
        <div class="dashboard-stat-heading"><span class="dashboard-stat-icon"><i class="fas fa-user-graduate"></i></span><span>Active Students</span><i class="fas fa-arrow-right" style="font-size:0.7rem;opacity:0.8;"></i>
        </div>
        <div class="dashboard-stat-value"><?php echo $stats['active_students']; ?></div>
        <div class="dashboard-stat-support"><?php echo $stats['total_students']; ?> total (<?php echo $stats['alumni_count']; ?> alumni)</div>
    </div>
    </a>
    <a href="<?php echo $config['base_url']; ?>/rooms/available-beds"
       style="display:block;text-decoration:none;color:inherit;border-radius:8px;transition:transform 0.15s,box-shadow 0.15s;"
       onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px rgba(16,185,129,0.35)';"
       onmouseout="this.style.transform='';this.style.boxShadow='';"
       title="Click to view all available beds">
    <div class="card dashboard-stat-card" style="margin:0;background:linear-gradient(135deg,#10b981,#059669);color:white;border:none;cursor:pointer;height:100%;">
        <div class="dashboard-stat-heading"><span class="dashboard-stat-icon"><i class="fas fa-bed"></i></span><span>Available Beds</span><i class="fas fa-arrow-right" style="font-size:0.7rem;opacity:0.8;"></i>
        </div>
        <div class="dashboard-stat-value"><?php echo $stats['available_beds']; ?></div>
        <div class="dashboard-stat-support"><?php echo $stats['occupied_beds']; ?> occupied of <?php echo $stats['total_beds']; ?> total</div>
    </div>
    </a>
    <a href="<?php echo $config['base_url']; ?>/reservations"
       style="display:block;text-decoration:none;color:inherit;border-radius:8px;transition:transform 0.15s,box-shadow 0.15s;"
       onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px rgba(99,102,241,0.35)';"
       onmouseout="this.style.transform='';this.style.boxShadow='';"
       title="Click to view active reservations">
    <div class="card dashboard-stat-card" style="margin:0;background:linear-gradient(135deg,#6366f1,#4f46e5);color:white;border:none;cursor:pointer;height:100%;">
        <div class="dashboard-stat-heading"><span class="dashboard-stat-icon"><i class="fas fa-calendar-check"></i></span><span>Reservations</span><i class="fas fa-arrow-right" style="font-size:0.7rem;opacity:0.8;"></i>
        </div>
        <div class="dashboard-stat-value">
            <?php
                $reservationSummary = (new \App\Services\ReservationService())->getReservationSummary();
                $pendingReservationTotal = 0;
                foreach ($reservationSummary as $item) {
                    if (in_array($item['status'], ['PENDING', 'CONFIRMED'], true)) {
                        $pendingReservationTotal += (int)$item['total'];
                    }
                }
                echo (int)$pendingReservationTotal;
            ?>
        </div>
        <div class="dashboard-stat-support">active reservations</div>
    </div>
    </a>
    <a href="<?php echo $config['base_url']; ?>/fees/pending"
       style="display:block;text-decoration:none;color:inherit;border-radius:8px;transition:transform 0.15s,box-shadow 0.15s;"
       onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px rgba(245,158,11,0.35)';"
       onmouseout="this.style.transform='';this.style.boxShadow='';"
       title="Click to view all pending fees">
    <div class="card dashboard-stat-card" style="margin:0;background:linear-gradient(135deg,#f59e0b,#d97706);color:white;border:none;cursor:pointer;height:100%;">
        <div class="dashboard-stat-heading"><span class="dashboard-stat-icon"><i class="fas fa-file-invoice-dollar"></i></span><span>Pending Fee</span><i class="fas fa-arrow-right" style="font-size:0.7rem;opacity:0.8;"></i>
        </div>
        <div class="dashboard-stat-value"><?php echo $stats['pending_fees']; ?></div>
        <div class="dashboard-stat-support"><?php echo $stats['overdue_fees']; ?> overdue</div>
    </div>
    </a>
    <a href="<?php echo $config['base_url']; ?>/fees/paid"
       style="display:block;text-decoration:none;color:inherit;border-radius:8px;transition:transform 0.15s,box-shadow 0.15s;"
       onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px rgba(16,185,129,0.35)';"
       onmouseout="this.style.transform='';this.style.boxShadow='';"
       title="Click to view all paid fees">
    <div class="card dashboard-stat-card" style="margin:0;background:linear-gradient(135deg,#059669,#047857);color:white;border:none;cursor:pointer;height:100%;">
        <div class="dashboard-stat-heading"><span class="dashboard-stat-icon"><i class="fas fa-circle-check"></i></span><span>Paid Fee</span><i class="fas fa-arrow-right" style="font-size:0.7rem;opacity:0.8;"></i>
        </div>
        <div class="dashboard-stat-value"><?php echo (int)($stats['paid_fees'] ?? 0); ?></div>
        <div class="dashboard-stat-support">Fully paid students</div>
    </div>
    </a>
    <a href="<?php echo $config['base_url']; ?>/fees/security-deposits"
       style="display:block;text-decoration:none;color:inherit;border-radius:8px;transition:transform 0.15s,box-shadow 0.15s;"
       onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px rgba(168,85,247,0.35)';"
       onmouseout="this.style.transform='';this.style.boxShadow='';"
       title="Click to view security deposits">
    <div class="card dashboard-stat-card" style="margin:0;background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:white;border:none;cursor:pointer;height:100%;">
        <div class="dashboard-stat-heading"><span class="dashboard-stat-icon"><i class="fas fa-shield-halved"></i></span><span>Security Held</span><i class="fas fa-arrow-right" style="font-size:0.7rem;opacity:0.8;"></i>
        </div>
        <div class="dashboard-stat-value">PKR <?php echo number_format((float)($stats['security_held'] ?? 0), 0); ?></div>
        <div class="dashboard-stat-support">Current active resident balance</div>
    </div>
    </a>
</div>

<!-- ===== ROW 2: QUICK ACTIONS ===== -->
<div class="row dashboard-quick-actions">
    <div class="col-md-12">
        <!-- Quick Links -->
        <div class="card" style="height: 100%; margin-bottom: 0;">
            <h3 class="card-title" style="margin-bottom:1rem;"><i class="fas fa-bolt" style="color:var(--primary);"></i> Quick Actions</h3>
            <div style="display:flex;flex-direction:column;gap:0.75rem;">
                <a href="<?php echo $config['base_url']; ?>/students/create" class="btn btn-primary" style="padding:0.75rem 1rem;"><i class="fas fa-user-plus"></i> Onboard Student</a>
                <a href="<?php echo $config['base_url']; ?>/rooms/create" class="btn" style="background:#0f172a;color:#cbd5e1;border:1px solid var(--border);padding:0.75rem 1rem;"><i class="fas fa-door-open"></i> Add Room</a>
                <a href="<?php echo $config['base_url']; ?>/reports" class="btn" style="background:#0ea5e9;color:white;padding:0.75rem 1rem;"><i class="fas fa-chart-line"></i> View Reports</a>
            </div>
        </div>
    </div>
</div>
