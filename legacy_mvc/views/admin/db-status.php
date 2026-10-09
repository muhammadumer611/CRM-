<?php $config = require APP_ROOT . '/config/app.php'; ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-database"></i> Database Status</h3>
        <a href="<?php echo $config['base_url']; ?>/dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="alert <?php echo $dbStatus['connected'] ? 'alert-success' : 'alert-error'; ?>">
        <?php echo $dbStatus['connected'] ? 'Database connected successfully.' : 'Database connection failed.'; ?>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card" style="margin: 0; background: rgba(15,23,42,0.6);">
                <div class="form-group">
                    <label class="form-label">MySQL Server</label>
                    <div class="form-control" style="background:#0f172a; border-color:#334155; min-height: 46px; display:flex; align-items:center;">
                        <?php echo htmlspecialchars($dbStatus['server'] ?? 'localhost'); ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Database</label>
                    <div class="form-control" style="background:#0f172a; border-color:#334155; min-height: 46px; display:flex; align-items:center;">
                        <?php echo htmlspecialchars($dbStatus['database'] ?? 'unknown'); ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <div class="form-control" style="background:#0f172a; border-color:#334155; min-height: 46px; display:flex; align-items:center;">
                        <?php echo $dbStatus['connected'] ? '<span class="badge badge-success">Connected</span>' : '<span class="badge badge-danger">Disconnected</span>'; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card" style="margin: 0; background: rgba(15,23,42,0.6);">
                <h4 style="margin-bottom: 1rem;">Required Tables</h4>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Table</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dbStatus['tables'] as $table): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($table['name']); ?></td>
                                    <td>
                                        <?php if ($table['exists']): ?>
                                            <span class="badge badge-success">✓</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Missing</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($dbStatus['error'])): ?>
        <div class="card" style="margin-top: 1rem; background: rgba(239,68,68,0.08); border-color: rgba(239,68,68,0.2);">
            <h4 style="margin-bottom: 0.75rem; color: #fca5a5;">Connection Error</h4>
            <pre style="white-space: pre-wrap; color: #f8fafc; font-family: Consolas, monospace;"><?php echo htmlspecialchars($dbStatus['error']); ?></pre>
        </div>
    <?php endif; ?>
</div>
