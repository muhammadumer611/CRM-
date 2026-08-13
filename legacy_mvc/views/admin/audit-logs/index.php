<?php $config = require APP_ROOT . '/config/app.php'; ?>
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title">Audit Logs</h3>
            <small style="color: var(--text-muted);">Administrative activity and system changes</small>
        </div>
    </div>

    <form method="GET" action="<?php echo $config['base_url']; ?>/audit-logs" style="margin-bottom: 1.25rem;">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label">Action</label>
                    <input type="text" name="action" class="form-control" value="<?php echo htmlspecialchars($filters['action'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label">Entity Type</label>
                    <input type="text" name="entity_type" class="form-control" value="<?php echo htmlspecialchars($filters['entity_type'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label">Admin</label>
                    <input type="text" name="admin" class="form-control" value="<?php echo htmlspecialchars($filters['admin'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group" style="margin-top: 1.7rem;">
                    <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-filter"></i> Filter</button>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group" style="margin-top: 1.7rem;">
                    <a href="<?php echo $config['base_url']; ?>/audit-logs" class="btn btn-secondary" style="width: 100%;"><i class="fas fa-undo"></i> Reset</a>
                </div>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>
                        <a href="<?php echo $config['base_url']; ?>/audit-logs?<?php echo http_build_query(array_merge($filters, ['sort' => 'created_at', 'direction' => $direction === 'DESC' ? 'ASC' : 'DESC' ])); ?>">
                            Date/Time
                        </a>
                    </th>
                    <th>Admin</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Description</th>
                    <th>IP Address</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding: 1.5rem;">No audit records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(date('d M Y H:i:s', strtotime($log['created_at']))); ?></td>
                            <td><?php echo htmlspecialchars($log['admin_username'] ?? 'System'); ?></td>
                            <td>
                                <span class="badge badge-primary" style="background: rgba(56,189,248,0.1); color: #7dd3fc; border: 1px solid rgba(56,189,248,0.3);">
                                    <?php echo htmlspecialchars($log['action'] ?? 'SYSTEM'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['entity_type'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars(substr($log['description'] ?? '', 0, 120)); ?><?php echo strlen((string)($log['description'] ?? '')) > 120 ? '...' : ''; ?></td>
                            <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                            <td>
                                <a href="<?php echo $config['base_url']; ?>/audit-logs/<?php echo (int)$log['id']; ?>" class="btn btn-sm btn-primary">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($logs)): ?>
        <div class="card" style="margin-top: 1rem;">
            <div class="card-header" style="margin-bottom:0;">
                <div>
                    <small style="color: var(--text-muted);">Page <?php echo (int)$pagination['current_page']; ?> of <?php echo (int)$pagination['total_pages']; ?></small>
                </div>
                <div>
                    <?php $prevPage = max(1, (int)$pagination['current_page'] - 1); ?>
                    <?php $nextPage = min((int)$pagination['total_pages'], (int)$pagination['current_page'] + 1); ?>
                    <?php $queryString = http_build_query(array_merge($filters, ['page' => $prevPage, 'sort' => $sort ?? 'created_at', 'direction' => $direction ?? 'DESC'])); ?>
                    <a href="<?php echo $config['base_url']; ?>/audit-logs?<?php echo htmlspecialchars($queryString); ?>" class="btn btn-sm <?php echo ($pagination['current_page'] <= 1) ? 'disabled' : ''; ?>">Previous</a>
                    <?php $queryStringNext = http_build_query(array_merge($filters, ['page' => $nextPage, 'sort' => $sort ?? 'created_at', 'direction' => $direction ?? 'DESC'])); ?>
                    <a href="<?php echo $config['base_url']; ?>/audit-logs?<?php echo htmlspecialchars($queryStringNext); ?>" class="btn btn-sm <?php echo ($pagination['current_page'] >= $pagination['total_pages']) ? 'disabled' : ''; ?>">Next</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
