<?php $config = require APP_ROOT . '/config/app.php'; ?>
<div style="max-width:900px;margin:0 auto;">
    <div class="card" style="margin-bottom:1.5rem;">
        <h3 style="margin:0 0 1rem 0; color:var(--primary);"><i class="fas fa-user-cog"></i> Account Settings</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
            <div>
                <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.08em;">Current Username</div>
                <div style="font-size:1.3rem; font-weight:700; margin-top:0.35rem;"><?php echo htmlspecialchars($admin['username'] ?? ''); ?></div>
            </div>
            <div>
                <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.08em;">Account Status</div>
                <div style="font-size:1.1rem; font-weight:700; margin-top:0.35rem; color:var(--success);">Active</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card" style="height:100%;">
                <h4 style="color:var(--primary);margin-bottom:1rem;"><i class="fas fa-user"></i> Update Username</h4>
                <form action="<?php echo $config['base_url']; ?>/account-settings/username" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                    <div class="form-group">
                        <label class="form-label">New Username</label>
                        <input type="text" name="new_username" class="form-control" placeholder="Enter new username" required pattern="[A-Za-z0-9_.-]{3,30}" title="3-30 characters, letters/numbers/dots/underscores/hyphens only">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Username</label>
                        <input type="text" name="confirm_username" class="form-control" placeholder="Confirm new username" required pattern="[A-Za-z0-9_.-]{3,30}">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
                        <i class="fas fa-save"></i> Update Username
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card" style="height:100%;">
                <h4 style="color:var(--primary);margin-bottom:1rem;"><i class="fas fa-lock"></i> Change Password</h4>
                <form action="<?php echo $config['base_url']; ?>/account-settings/password" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" placeholder="Current password" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="New password" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_new_password" class="form-control" placeholder="Confirm new password" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
