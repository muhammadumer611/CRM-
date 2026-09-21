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

    <div class="card" style="margin-top:1.5rem;">
        <h4 style="color:var(--primary);margin-bottom:.4rem;"><i class="fas fa-file-invoice-dollar"></i> Fee &amp; Billing Settings</h4>
        <p style="color:var(--text-muted);margin-top:0;">Residents joining after the cutoff can receive a first-month adjustment. Their base monthly fee is never changed.</p>
        <form action="<?php echo $config['base_url']; ?>/account-settings/billing" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">
            <div class="row">
                <div class="col-md-4"><label class="form-label">Enable late-joining adjustment</label><input type="checkbox" name="late_joining_enabled" value="1" <?php echo ($billingSettings['late_joining_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>></div>
                <div class="col-md-4"><label class="form-label">Apply after day</label><input type="number" name="late_joining_cutoff_day" class="form-control" min="1" max="31" value="<?php echo htmlspecialchars($billingSettings['late_joining_cutoff_day'] ?? '10'); ?>"></div>
                <div class="col-md-4"><label class="form-label">Default due day</label><input type="number" name="default_due_day" class="form-control" min="1" max="31" value="<?php echo htmlspecialchars($billingSettings['default_due_day'] ?? '10'); ?>"></div>
                <div class="col-md-4"><label class="form-label">Proration method</label><select name="proration_method" class="form-control"><option value="calendar_days" <?php echo ($billingSettings['proration_method'] ?? '') === 'calendar_days' ? 'selected' : ''; ?>>Calendar days</option><option value="fixed_30_day" <?php echo ($billingSettings['proration_method'] ?? '') === 'fixed_30_day' ? 'selected' : ''; ?>>Fixed 30-day basis</option></select></div>
                <div class="col-md-4"><label class="form-label">Rounding</label><select name="rounding_method" class="form-control"><option value="nearest_rupee" <?php echo ($billingSettings['rounding_method'] ?? '') === 'nearest_rupee' ? 'selected' : ''; ?>>Nearest rupee</option><option value="exact" <?php echo ($billingSettings['rounding_method'] ?? '') === 'exact' ? 'selected' : ''; ?>>Exact</option></select></div>
                <div class="col-md-4"><label class="form-label">Maximum first-month adjustment (%)</label><input type="number" name="maximum_first_month_discount" class="form-control" min="0" max="100" step=".01" value="<?php echo htmlspecialchars($billingSettings['maximum_first_month_discount'] ?? '100'); ?>"></div>
            </div>
            <label style="display:block;margin-top:1rem;"><input type="checkbox" name="include_joining_day" value="1" <?php echo ($billingSettings['include_joining_day'] ?? '1') === '1' ? 'checked' : ''; ?>> Include joining date as a payable day</label>
            <label style="display:block;margin-top:.5rem;"><input type="checkbox" name="manual_first_month_discount_enabled" value="1" <?php echo ($billingSettings['manual_first_month_discount_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>> Allow manual first-month adjustments</label>
            <button type="submit" class="btn btn-primary" style="margin-top:1rem;"><i class="fas fa-save"></i> Save Billing Settings</button>
        </form>
    </div>

    <div class="card" style="margin-top:1.5rem;">
        <h4 style="color:var(--primary);margin-bottom:.4rem;"><i class="fas fa-palette"></i> Appearance / Theme</h4>
        <p style="color:var(--text-muted);margin-top:0;">Choose how the admin interface should appear on this browser.</p>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:1rem;">
            <?php foreach (['light' => 'Light', 'dark' => 'Dark', 'system' => 'System'] as $value => $label): ?>
                <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;color:var(--text);">
                    <input type="radio" name="theme_mode" value="<?php echo $value; ?>" onchange="setHmsTheme(this.value)">
                    <span><?php echo $label; ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<script>
    (function () {
        var mode = localStorage.getItem('hms-theme') || 'system';
        document.querySelectorAll('input[name="theme_mode"]').forEach(function (input) { input.checked = input.value === mode; });
    }());
</script>
