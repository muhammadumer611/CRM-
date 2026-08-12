<?php $config = require __DIR__ . '/../../../config/app.php'; ?>
<div class="row">
    <div class="col-md-3">
        <div class="card" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;">
            <h3>Active Students</h3>
            <h2><?php echo $stats['active_students'] ?? 0; ?> <small style="font-size: 1rem; opacity: 0.8;">/ <?php echo $stats['total_students'] ?? 0; ?></small></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
            <h3>Available Beds</h3>
            <h2><?php echo $stats['available_beds'] ?? 0; ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
            <h3>Pending Fees</h3>
            <h2><?php echo $stats['pending_fees'] ?? 0; ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white;">
            <h3>Active Complaints</h3>
            <h2><?php echo $stats['open_complaints'] ?? 0; ?></h2>
        </div>
    </div>
</div>

<div class="row" style="margin-top: 2rem;">
    <div class="col-md-8">
        <div class="card">
            <h3 class="card-title">Recent Activity</h3>
            <div style="margin-top: 1rem;">
                <?php if (empty($recentActivity)): ?>
                    <p style="color: var(--text-muted);">No recent activity.</p>
                <?php else: ?>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach($recentActivity as $log): ?>
                        <li style="padding: 1rem 0; border-bottom: 1px solid var(--border);">
                            <div style="font-weight: bold; color: var(--primary);"><?php echo htmlspecialchars($log['action']); ?></div>
                            <div style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;"><?php echo htmlspecialchars($log['details']); ?></div>
                            <div style="font-size: 0.8rem; margin-top: 0.25rem; opacity: 0.7;">
                                By <?php echo htmlspecialchars($log['username']); ?> on <?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <h3 class="card-title">Quick Links</h3>
            <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem;">
                <a href="<?php echo $config['base_url']; ?>/students/create" class="btn" style="background: #1e293b; color: white;"><i class="fas fa-user-plus"></i> Add Student</a>
                <a href="<?php echo $config['base_url']; ?>/allocations/create" class="btn" style="background: #1e293b; color: white;"><i class="fas fa-bed"></i> Allocate Room</a>
                <a href="<?php echo $config['base_url']; ?>/fees/create" class="btn" style="background: #1e293b; color: white;"><i class="fas fa-file-invoice-dollar"></i> Add Fee Record</a>
            </div>
        </div>
    </div>
</div>
