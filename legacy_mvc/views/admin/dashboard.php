<?php $config = require APP_ROOT . '/config/app.php'; ?>

<style>
    .dashboard-shell { animation: dashboard-enter 0.35s ease-out both; }
    .dashboard-card { transition: transform 0.18s ease, box-shadow 0.18s ease; }
    .dashboard-card:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(15,23,42,0.22); }
    .student-search-panel { border: 1px solid var(--border); background: linear-gradient(135deg, rgba(14,165,233,0.12), rgba(15,23,42,0.35)); }
    .student-search-results { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 0.75rem; margin-top: 1rem; }
    .student-search-result { animation: result-enter 0.25s ease-out both; border: 1px solid var(--border); transition: transform 0.18s ease, border-color 0.18s ease; }
    .student-search-result:hover { transform: translateY(-2px); border-color: var(--primary); }
    .student-search-panel form { flex-wrap: wrap; }
    .student-search-panel form input { flex: 1 1 240px; }
    .student-search-panel .search-submit.is-loading { opacity: 0.75; cursor: wait; }
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

<!-- ===== ROW 1: KEY STATS ===== -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;" class="dashboard-card">
    <a href="<?php echo $config['base_url']; ?>/students/active"
       style="display:block;text-decoration:none;color:inherit;border-radius:8px;transition:transform 0.15s,box-shadow 0.15s;"
       onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px rgba(59,130,246,0.35)';"
       onmouseout="this.style.transform='';this.style.boxShadow='';"
       title="Click to view all active students">
    <div class="card" style="margin:0;background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;border:none;cursor:pointer;height:100%;">
        <div style="font-size:0.8rem;opacity:0.85;text-transform:uppercase;letter-spacing:0.05em;display:flex;align-items:center;gap:0.4rem;">
            Active Students <i class="fas fa-arrow-right" style="font-size:0.7rem;opacity:0.8;"></i>
        </div>
        <div style="font-size:2rem;font-weight:700;margin-top:0.25rem;"><?php echo $stats['active_students']; ?></div>
        <div style="font-size:0.8rem;opacity:0.7;margin-top:0.25rem;"><?php echo $stats['total_students']; ?> total (<?php echo $stats['alumni_count']; ?> alumni)</div>
    </div>
    </a>
    <a href="<?php echo $config['base_url']; ?>/rooms/available-beds"
       style="display:block;text-decoration:none;color:inherit;border-radius:8px;transition:transform 0.15s,box-shadow 0.15s;"
       onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px rgba(16,185,129,0.35)';"
       onmouseout="this.style.transform='';this.style.boxShadow='';"
       title="Click to view all available beds">
    <div class="card" style="margin:0;background:linear-gradient(135deg,#10b981,#059669);color:white;border:none;cursor:pointer;height:100%;">
        <div style="font-size:0.8rem;opacity:0.85;text-transform:uppercase;letter-spacing:0.05em;display:flex;align-items:center;gap:0.4rem;">
            Available Beds <i class="fas fa-arrow-right" style="font-size:0.7rem;opacity:0.8;"></i>
        </div>
        <div style="font-size:2rem;font-weight:700;margin-top:0.25rem;"><?php echo $stats['available_beds']; ?></div>
        <div style="font-size:0.8rem;opacity:0.7;margin-top:0.25rem;"><?php echo $stats['occupied_beds']; ?> occupied of <?php echo $stats['total_beds']; ?> total</div>
    </div>
    </a>
    <a href="<?php echo $config['base_url']; ?>/fees/pending"
       style="display:block;text-decoration:none;color:inherit;border-radius:8px;transition:transform 0.15s,box-shadow 0.15s;"
       onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px rgba(245,158,11,0.35)';"
       onmouseout="this.style.transform='';this.style.boxShadow='';"
       title="Click to view all pending fees">
    <div class="card" style="margin:0;background:linear-gradient(135deg,#f59e0b,#d97706);color:white;border:none;cursor:pointer;height:100%;">
        <div style="font-size:0.8rem;opacity:0.85;text-transform:uppercase;letter-spacing:0.05em;display:flex;align-items:center;gap:0.4rem;">
            Pending Fee <i class="fas fa-arrow-right" style="font-size:0.7rem;opacity:0.8;"></i>
        </div>
        <div style="font-size:2rem;font-weight:700;margin-top:0.25rem;"><?php echo $stats['pending_fees']; ?></div>
        <div style="font-size:0.8rem;opacity:0.7;margin-top:0.25rem;"><?php echo $stats['overdue_fees']; ?> overdue</div>
    </div>
    </a>
    <a href="<?php echo $config['base_url']; ?>/fees/paid"
       style="display:block;text-decoration:none;color:inherit;border-radius:8px;transition:transform 0.15s,box-shadow 0.15s;"
       onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px rgba(16,185,129,0.35)';"
       onmouseout="this.style.transform='';this.style.boxShadow='';"
       title="Click to view all paid fees">
    <div class="card" style="margin:0;background:linear-gradient(135deg,#059669,#047857);color:white;border:none;cursor:pointer;height:100%;">
        <div style="font-size:0.8rem;opacity:0.85;text-transform:uppercase;letter-spacing:0.05em;display:flex;align-items:center;gap:0.4rem;">
            Paid Fee <i class="fas fa-arrow-right" style="font-size:0.7rem;opacity:0.8;"></i>
        </div>
        <div style="font-size:2rem;font-weight:700;margin-top:0.25rem;"><?php echo (int)($stats['paid_fees'] ?? 0); ?></div>
        <div style="font-size:0.8rem;opacity:0.7;margin-top:0.25rem;">Fully paid students</div>
    </div>
    </a>
</div>

<!-- ===== ROW 2: ALERTS & QUICK ACTIONS ===== -->
<div class="row">
    <div class="col-md-7">
        <!-- Alerts -->
        <div class="card" style="height: 100%; margin-bottom: 0;">
            <h3 class="card-title" style="margin-bottom:1rem;"><i class="fas fa-exclamation-triangle" style="color:#fbbf24;"></i> Alerts & Operational Summary</h3>
            <?php $alertSummary = \App\Services\NotificationService::newInstance()->getDashboardAlertSummary(); ?>
            <div style="display:flex;flex-direction:column;gap:0.75rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:0.75rem;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:6px;">
                    <span style="font-size:0.9rem;">Overdue Fees</span>
                    <span style="font-weight:700;color:#fca5a5;font-size:1.1rem;"><?php echo (int)$alertSummary['overdue_fees']; ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:0.75rem;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);border-radius:6px;">
                    <span style="font-size:0.9rem;">Due Soon</span>
                    <span style="font-weight:700;color:#fcd34d;font-size:1.1rem;"><?php echo (int)$alertSummary['due_soon']; ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:0.75rem;background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.2);border-radius:6px;">
                    <span style="font-size:0.9rem;">Rooms Nearly Full</span>
                    <span style="font-weight:700;color:#93c5fd;font-size:1.1rem;"><?php echo (int)$alertSummary['rooms_nearly_full']; ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:0.75rem;background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);border-radius:6px;">
                    <span style="font-size:0.9rem;">Without Room</span>
                    <span style="font-weight:700;color:#6ee7b7;font-size:1.1rem;"><?php echo (int)$alertSummary['students_without_allocation']; ?></span>
                </div>
            </div>
            <div style="margin-top: 1.25rem;">
                <a href="<?php echo $config['base_url']; ?>/notifications" class="btn btn-sm" style="background: rgba(56,189,248,0.12); color: #38bdf8; border: 1px solid rgba(56,189,248,0.25); width: 100%; justify-content: center;">
                    <i class="fas fa-bell"></i> View All Notifications & Alerts
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-5">
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
