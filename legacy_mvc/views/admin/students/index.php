<?php $config = require APP_ROOT . '/config/app.php'; ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-graduate"></i> Students</h3>
        <a href="<?php echo $config['base_url']; ?>/students/create" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> New Student
        </a>
    </div>

    <!-- Filters -->
    <div style="margin-bottom:1.5rem;">
        <form action="<?php echo $config['base_url']; ?>/students" method="GET" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group" style="margin-bottom:0;flex:1;min-width:220px;">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name, CNIC, ID, phone, room or bed..." value="<?php echo htmlspecialchars($filters['search']); ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;flex:0 0 150px;">
                <label class="form-label">District / Address</label>
                <input type="text" name="district" class="form-control" placeholder="e.g. Sargodha" value="<?php echo htmlspecialchars($filters['district'] ?? ''); ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;flex:0 0 110px;">
                <label class="form-label">Room</label>
                <input type="text" name="room" class="form-control" placeholder="101" value="<?php echo htmlspecialchars($filters['room'] ?? ''); ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;flex:0 0 90px;">
                <label class="form-label">Bed</label>
                <input type="number" name="bed" class="form-control" min="1" value="<?php echo htmlspecialchars($filters['bed'] ?? ''); ?>">
            </div>
            <div class="form-group" style="margin-bottom:0;flex:0 0 150px;">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">All</option>
                    <option value="Active"   <?php echo $filters['status'] === 'Active'   ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo $filters['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height:42px;"><i class="fas fa-filter"></i> Filter</button>
            <?php if ($filters['search'] || $filters['status'] || ($filters['district'] ?? '') || ($filters['room'] ?? '') || ($filters['bed'] ?? '') !== ''): ?>
                <a href="<?php echo $config['base_url']; ?>/students" class="btn" style="height:42px;background:#334155;color:white;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>CNIC</th>
                    <th>Phone</th>
                    <th>Room / Bed</th>
                    <th>Monthly Fee</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-muted);">No students found.</td></tr>
                <?php else: ?>
                    <?php foreach ($students as $s): ?>
                    <tr>
                        <td><span class="badge" style="background:#334155;"><?php echo htmlspecialchars($s['student_id_str']); ?></span></td>
                        <td>
                            <strong><?php echo htmlspecialchars($s['full_name']); ?></strong>
                            <?php if ($s['email']): ?>
                                <br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($s['email']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars(\App\Services\StudentService::formatCnic($s['cnic'])); ?></td>
                        <td><?php echo htmlspecialchars($s['phone']); ?></td>
                        <td>
                            <?php if ($s['room_number']): ?>
                                <span style="color:var(--primary);font-weight:600;">
                                    Room <?php echo htmlspecialchars($s['room_number']); ?>
                                </span><br>
                                <small style="color:var(--text-muted);">Bed <?php echo (int)$s['bed_number']; ?></small>
                            <?php else: ?>
                                <span style="color:var(--text-muted);font-size:0.85rem;">Not allocated</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($s['monthly_fee'] !== null): ?>
                                <span style="color:var(--primary);">Rs. <?php echo number_format($s['monthly_fee'], 0); ?></span>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($s['status'] === 'Active'): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space:nowrap;">
                            <a href="<?php echo $config['base_url']; ?>/students/view/<?php echo $s['id']; ?>" class="btn btn-sm" style="background-color: #334155; color: white;" title="View Profile">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="<?php echo $config['base_url']; ?>/students/edit/<?php echo $s['id']; ?>" class="btn btn-sm btn-primary" title="Edit Student">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="<?php echo $config['base_url']; ?>/students/account/<?php echo $s['id']; ?>" class="btn btn-sm" style="background-color: #0284c7; color: white;" title="Account Statement">
                                <i class="fas fa-file-invoice-dollar"></i> Account
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php $totalPages = ceil($total / $perPage); ?>
    <?php if ($totalPages > 1): ?>
    <div style="margin-top:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div style="color:var(--text-muted);font-size:0.9rem;">
            Showing <?php echo (($page-1)*$perPage)+1; ?>–<?php echo min($page*$perPage, $total); ?> of <?php echo $total; ?> students
        </div>
        <div style="display:flex;gap:0.5rem;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($filters['search']); ?>&status=<?php echo urlencode($filters['status']); ?>" class="btn btn-sm" style="background:#334155;color:white;">
                    <i class="fas fa-chevron-left"></i> Prev
                </a>
            <?php endif; ?>
            <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
                <a href="?page=<?php echo $p; ?>&search=<?php echo urlencode($filters['search']); ?>&status=<?php echo urlencode($filters['status']); ?>"
                   class="btn btn-sm" style="<?php echo $p===$page ? 'background:var(--primary-dark);color:white;' : 'background:#334155;color:white;'; ?>">
                    <?php echo $p; ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($filters['search']); ?>&status=<?php echo urlencode($filters['status']); ?>" class="btn btn-sm" style="background:#334155;color:white;">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
