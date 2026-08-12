<?php $config = require __DIR__ . '/../../../config/app.php'; ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Manage Complaints</h3>
    </div>

    <div style="margin-bottom: 1.5rem;">
        <form action="<?php echo $config['base_url']; ?>/complaints" method="GET" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Subject, Student Name, ID..." value="<?php echo htmlspecialchars($filters['search']); ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0; flex: 0 0 150px;">
                <label class="form-label">Priority</label>
                <select name="priority" class="form-control">
                    <option value="">All</option>
                    <option value="Low" <?php echo $filters['priority'] === 'Low' ? 'selected' : ''; ?>>Low</option>
                    <option value="Medium" <?php echo $filters['priority'] === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="High" <?php echo $filters['priority'] === 'High' ? 'selected' : ''; ?>>High</option>
                    <option value="Urgent" <?php echo $filters['priority'] === 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0; flex: 0 0 150px;">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">All</option>
                    <option value="Open" <?php echo $filters['status'] === 'Open' ? 'selected' : ''; ?>>Open</option>
                    <option value="In Progress" <?php echo $filters['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="Resolved" <?php echo $filters['status'] === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                    <option value="Closed" <?php echo $filters['status'] === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height: 42px;"><i class="fas fa-filter"></i> Filter</button>
        </form>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student</th>
                    <th>Subject</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($complaints)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 2rem;">No complaints found.</td></tr>
                <?php else: ?>
                    <?php foreach ($complaints as $complaint): ?>
                    <tr>
                        <td><small><?php echo date('M d, Y H:i', strtotime($complaint['created_at'])); ?></small></td>
                        <td>
                            <strong><?php echo htmlspecialchars($complaint['full_name']); ?></strong><br>
                            <small class="badge" style="background-color: #334155; margin-top: 5px; display: inline-block;"><?php echo htmlspecialchars($complaint['student_id_str']); ?></small>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($complaint['subject']); ?></strong>
                        </td>
                        <td>
                            <?php
                            $colors = ['Low' => '#3b82f6', 'Medium' => '#f59e0b', 'High' => '#ef4444', 'Urgent' => '#7f1d1d'];
                            $pColor = $colors[$complaint['priority']] ?? '#94a3b8';
                            ?>
                            <span class="badge" style="background-color: <?php echo $pColor; ?>; color: white;"><?php echo $complaint['priority']; ?></span>
                        </td>
                        <td>
                            <?php if ($complaint['status'] === 'Open'): ?>
                                <span class="badge badge-danger">Open</span>
                            <?php elseif ($complaint['status'] === 'In Progress'): ?>
                                <span class="badge badge-warning">In Progress</span>
                            <?php elseif ($complaint['status'] === 'Resolved'): ?>
                                <span class="badge badge-success">Resolved</span>
                            <?php else: ?>
                                <span class="badge" style="background-color: #475569; color: white;">Closed</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo $config['base_url']; ?>/complaints/edit/<?php echo $complaint['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> View/Update</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
