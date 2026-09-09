<?php $config = require APP_ROOT . '/config/app.php'; ?>

<!-- ===== ROW 1: KEY STATS ===== -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;">
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
    <div class="card" style="margin:0;background:linear-gradient(135deg,#ef4444,#dc2626);color:white;border:none;">
        <div style="font-size:0.8rem;opacity:0.85;text-transform:uppercase;letter-spacing:0.05em;">Outstanding Fees</div>
        <div style="font-size:1.5rem;font-weight:700;margin-top:0.25rem;">Rs. <?php echo number_format($stats['total_outstanding'], 0); ?></div>
        <div style="font-size:0.8rem;opacity:0.7;margin-top:0.25rem;">Unpaid dues</div>
    </div>
</div>

<!-- ===== ROW 2: FINANCIAL DETAILS ===== -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem;">
    <div class="card" style="margin:0;">
        <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">This Month — Expected</div>
        <div style="font-size:1.4rem;font-weight:700;color:#93c5fd;margin-top:0.25rem;">Rs. <?php echo number_format($stats['this_month_expected'], 0); ?></div>
    </div>
    <div class="card" style="margin:0;">
        <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">This Month — Collected</div>
        <div style="font-size:1.4rem;font-weight:700;color:#6ee7b7;margin-top:0.25rem;">Rs. <?php echo number_format($stats['this_month_collected'], 0); ?></div>
    </div>
    <div class="card" style="margin:0;">
        <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Security Deposits Held</div>
        <div style="font-size:1.4rem;font-weight:700;color:#c4b5fd;margin-top:0.25rem;">Rs. <?php echo number_format($stats['security_held'], 0); ?></div>
        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:0.25rem;">Separate from fees</div>
    </div>
    <div class="card" style="margin:0;">
        <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Security Deposits Returned</div>
        <div style="font-size:1.4rem;font-weight:700;color:#fcd34d;margin-top:0.25rem;">Rs. <?php echo number_format($stats['security_returned'], 0); ?></div>
    </div>
</div>

<!-- ===== ROW 3: ALERTS + ACTIVITY ===== -->
<div class="row">
    <div class="col-md-8">
        <!-- Recent Activity -->
        <div class="card">
            <h3 class="card-title" style="margin-bottom:1rem;"><i class="fas fa-stream" style="color:var(--primary);"></i> Recent Activity</h3>
            <?php if (empty($recentActivity)): ?>
                <p style="color:var(--text-muted);">No recent activity.</p>
            <?php else: ?>
                <ul style="list-style:none;padding:0;">
                    <?php foreach($recentActivity as $log): ?>
                    <li style="padding:0.75rem 0;border-bottom:1px solid var(--border);">
                        <div style="font-weight:600;color:var(--primary);font-size:0.9rem;"><?php echo htmlspecialchars($log['action']); ?></div>
                        <?php if (!empty($log['description'])): ?>
                        <div style="color:var(--text-muted);font-size:0.85rem;margin-top:0.2rem;"><?php echo htmlspecialchars($log['description']); ?></div>
                        <?php endif; ?>
                        <div style="font-size:0.75rem;margin-top:0.2rem;opacity:0.65;">
                            By <?php echo htmlspecialchars($log['username'] ?? 'System'); ?> &bull; <?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <!-- Alerts -->
        <div class="card">
            <h3 class="card-title" style="margin-bottom:1rem;"><i class="fas fa-exclamation-triangle" style="color:#fbbf24;"></i> Alerts</h3>
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
        </div>

        <!-- Quick Links -->
        <div class="card" style="margin-top:1rem;">
            <h3 class="card-title" style="margin-bottom:1rem;"><i class="fas fa-bolt" style="color:var(--primary);"></i> Quick Actions</h3>
            <div style="display:flex;flex-direction:column;gap:0.5rem;">
                <a href="<?php echo $config['base_url']; ?>/students/create" class="btn btn-primary"><i class="fas fa-user-plus"></i> Onboard Student</a>
                <a href="<?php echo $config['base_url']; ?>/fees/create" class="btn" style="background:#1e293b;color:white;"><i class="fas fa-file-invoice-dollar"></i> Create Invoice</a>
                <a href="<?php echo $config['base_url']; ?>/reports" class="btn" style="background:#0ea5e9;color:white;"><i class="fas fa-chart-line"></i> View Reports</a>
            </div>
        </div>
    </div>
</div>
