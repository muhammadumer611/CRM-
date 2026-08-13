<?php $config = require APP_ROOT . '/config/app.php'; $allowedTypes = ['fee', 'payment', 'room', 'allocation', 'student', 'system']; $allowedPriorities = ['low', 'medium', 'high', 'critical']; ?>
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title">Notification Center</h3>
            <small style="color: var(--text-muted);">Operational alerts and reminders</small>
        </div>
        <form method="POST" action="<?php echo $config['base_url']; ?>/notifications/mark-all-read">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-check-double"></i> Mark all as read</button>
        </form>
    </div>

    <form method="GET" action="<?php echo $config['base_url']; ?>/notifications" style="margin-bottom: 1.5rem;">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-control">
                        <option value="">All</option>
                        <?php foreach ($allowedTypes as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($filters['type'] ?? '') === $type ? 'selected' : ''; ?>><?php echo ucfirst($type); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-control">
                        <option value="">All</option>
                        <?php foreach ($allowedPriorities as $priority): ?>
                            <option value="<?php echo htmlspecialchars($priority); ?>" <?php echo ($filters['priority'] ?? '') === $priority ? 'selected' : ''; ?>><?php echo ucfirst($priority); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="form-label">Read Status</label>
                    <select name="read_status" class="form-control">
                        <option value="">All</option>
                        <option value="unread" <?php echo ($filters['read_status'] ?? '') === 'unread' ? 'selected' : ''; ?>>Unread</option>
                        <option value="read" <?php echo ($filters['read_status'] ?? '') === 'read' ? 'selected' : ''; ?>>Read</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="Title, message or key">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group" style="margin-top: 1.75rem;">
                    <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fas fa-filter"></i> Apply</button>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group" style="margin-top: 1.75rem;">
                    <a href="<?php echo $config['base_url']; ?>/notifications" class="btn btn-secondary" style="background: #334155; color: white; width:100%;"><i class="fas fa-undo"></i> Reset</a>
                </div>
            </div>
        </div>
    </form>

    <?php if (empty($notifications)): ?>
        <div class="card" style="border-style: dashed; background: rgba(15,23,42,0.6);">
            <h4 style="margin-bottom: 0.5rem;">No notifications found</h4>
            <p style="color: var(--text-muted);">You're all caught up for the selected period.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach ($notifications as $notification): ?>
                <?php $isUnread = (int)($notification['is_read'] ?? 0) === 0; ?>
                <div class="card" style="margin-bottom: 0; background: <?php echo $isUnread ? 'rgba(14,165,233,0.06)' : 'rgba(15,23,42,0.8)'; ?>; border-color: <?php echo $isUnread ? '#38bdf8' : '#334155'; ?>;">
                    <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; flex-wrap: wrap;">
                        <div>
                            <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.5rem; flex-wrap:wrap;">
                                <strong style="font-size: 1.05rem;<?php echo $isUnread ? '; color: #dbeafe;' : ''; ?>"><?php echo htmlspecialchars($notification['title'] ?? 'Notification'); ?></strong>
                                <span class="badge" style="background: rgba(59,130,246,0.13); color: #93c5fd; border: 1px solid rgba(59,130,246,0.2); text-transform: uppercase;">
                                    <?php echo htmlspecialchars($notification['type'] ?? 'system'); ?>
                                </span>
                                <span class="badge" style="text-transform: uppercase; background: rgba(245,158,11,0.12); color: #fbbf24; border: 1px solid rgba(245,158,11,0.2);">
                                    <?php echo htmlspecialchars($notification['priority'] ?? 'medium'); ?>
                                </span>
                                <?php if ($isUnread): ?>
                                    <span class="badge" style="background: rgba(16,185,129,0.12); color: #34d399; border: 1px solid rgba(16,185,129,0.2);">Unread</span>
                                <?php else: ?>
                                    <span class="badge" style="background: rgba(148,163,184,0.12); color: #cbd5e1; border: 1px solid rgba(148,163,184,0.2);">Read</span>
                                <?php endif; ?>
                            </div>
                            <p style="color: var(--text-muted); margin-bottom: 0.75rem; line-height: 1.6;">
                                <?php echo htmlspecialchars($notification['message'] ?? ''); ?>
                            </p>
                            <div style="font-size:0.8rem; color: var(--text-muted);">
                                <?php echo htmlspecialchars(date('d M Y H:i', strtotime($notification['created_at']))); ?>
                            </div>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:0.5rem; min-width:160px;">
                            <?php if ($isUnread): ?>
                                <form method="POST" action="<?php echo $config['base_url']; ?>/notifications/mark-read/<?php echo (int)($notification['id'] ?? 0); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-check"></i> Mark read</button>
                                </form>
                            <?php endif; ?>
                            <?php if (!empty($notification['entity_type']) && !empty($notification['entity_id'])): ?>
                                <?php
                                    $entity = strtolower((string)$notification['entity_type']);
                                    $link = '#';
                                    if ($entity === 'fee') { $link = $config['base_url'] . '/fees'; }
                                    elseif ($entity === 'student') { $link = $config['base_url'] . '/students'; }
                                    elseif ($entity === 'room') { $link = $config['base_url'] . '/rooms'; }
                                    elseif ($entity === 'allocation') { $link = $config['base_url'] . '/allocations'; }
                                ?>
                                <a href="<?php echo htmlspecialchars($link); ?>" class="btn btn-sm" style="background: rgba(16,185,129,0.12); color: #6ee7b7; border: 1px solid rgba(16,185,129,0.2);">View related</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card" style="margin-top: 1rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
                <span style="color: var(--text-muted);">Page <?php echo (int)$pagination['current_page']; ?> of <?php echo (int)$pagination['total_pages']; ?></span>
                <div style="display:flex; gap:0.5rem;">
                    <?php $prevPage = max(1, (int)$pagination['current_page'] - 1); ?>
                    <?php $nextPage = min((int)$pagination['total_pages'], (int)$pagination['current_page'] + 1); ?>
                    <?php $paramsPrev = array_merge($filters, ['page' => $prevPage]); ?>
                    <?php $paramsNext = array_merge($filters, ['page' => $nextPage]); ?>
                    <a href="<?php echo $config['base_url']; ?>/notifications?<?php echo http_build_query($paramsPrev); ?>" class="btn btn-sm <?php echo $pagination['current_page'] <= 1 ? 'disabled' : ''; ?>" style="background: #334155; color: white;">Previous</a>
                    <a href="<?php echo $config['base_url']; ?>/notifications?<?php echo http_build_query($paramsNext); ?>" class="btn btn-sm <?php echo $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : ''; ?>" style="background: #334155; color: white;">Next</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
