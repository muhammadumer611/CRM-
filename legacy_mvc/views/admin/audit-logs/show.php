<?php $config = require APP_ROOT . '/config/app.php'; ?>
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title">Audit Log Details</h3>
            <small style="color: var(--text-muted);">Detailed activity record</small>
        </div>
        <div>
            <a href="<?php echo $config['base_url']; ?>/audit-logs" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Logs</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">Date / Time</label>
                <div class="form-control" style="background: #0f172a; height: auto; min-height: 45px;"><?php echo htmlspecialchars(date('d M Y H:i:s', strtotime($record['created_at']))); ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">Admin</label>
                <div class="form-control" style="background: #0f172a; height: auto; min-height: 45px;"><?php echo htmlspecialchars($record['admin_username'] ?? 'System'); ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">Action</label>
                <div class="form-control" style="background: #0f172a; height: auto; min-height: 45px;"><?php echo htmlspecialchars($record['action'] ?? 'SYSTEM'); ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">Entity</label>
                <div class="form-control" style="background: #0f172a; height: auto; min-height: 45px;"><?php echo htmlspecialchars($record['entity_type'] ?? 'N/A'); ?> / <?php echo htmlspecialchars((string)($record['entity_id'] ?? 'N/A')); ?></div>
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <label class="form-label">Description</label>
                <div class="form-control" style="background: #0f172a; min-height: 80px; white-space: pre-wrap;"><?php echo htmlspecialchars($record['description'] ?? ''); ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">IP Address</label>
                <div class="form-control" style="background: #0f172a; height: auto; min-height: 45px;"><?php echo htmlspecialchars($record['ip_address'] ?? 'N/A'); ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">User Agent</label>
                <div class="form-control" style="background: #0f172a; min-height: 45px; height: auto;"><?php echo htmlspecialchars($record['user_agent'] ?? 'N/A'); ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">Old Values</label>
                <pre class="form-control" style="background: #0f172a; min-height: 180px; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; font-size: 0.82rem;">
<?php echo htmlspecialchars(json_encode($record['old_values'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'null'); ?>
                </pre>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label">New Values</label>
                <pre class="form-control" style="background: #0f172a; min-height: 180px; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; font-size: 0.82rem;">
<?php echo htmlspecialchars(json_encode($record['new_values'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'null'); ?>
                </pre>
            </div>
        </div>
    </div>
</div>
