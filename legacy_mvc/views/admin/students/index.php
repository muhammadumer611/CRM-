<?php $config = require __DIR__ . '/../../../config/app.php'; ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Manage Students</h3>
        <a href="<?php echo $config['base_url']; ?>/students/create" class="btn btn-primary"><i class="fas fa-plus"></i> Add Student</a>
    </div>
    
    <div style="margin-bottom: 1.5rem;">
        <form action="<?php echo $config['base_url']; ?>/students" method="GET" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name, CNIC, ID, Phone..." value="<?php echo htmlspecialchars($filters['search']); ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0; flex: 0 0 200px;">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">All</option>
                    <option value="Active" <?php echo $filters['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo $filters['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height: 42px;"><i class="fas fa-filter"></i> Filter</button>
        </form>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>CNIC</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 2rem;">No students found.</td></tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><span class="badge" style="background-color: #334155;"><?php echo htmlspecialchars($student['student_id_str']); ?></span></td>
                        <td>
                            <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($student['cnic']); ?></td>
                        <td><?php echo htmlspecialchars($student['phone']); ?></td>
                        <td>
                            <?php if ($student['status'] === 'Active'): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo $config['base_url']; ?>/students/edit/<?php echo $student['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                            <!-- Future: View student profile -->
                            <!-- <a href="<?php echo $config['base_url']; ?>/students/show/<?php echo $student['id']; ?>" class="btn btn-sm" style="background-color: #475569; color: white;"><i class="fas fa-eye"></i></a> -->
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Simple Pagination -->
    <?php if ($total > $perPage): ?>
    <div style="margin-top: 1.5rem; display: flex; justify-content: center; gap: 0.5rem;">
        <?php 
        $totalPages = ceil($total / $perPage);
        for ($i = 1; $i <= $totalPages; $i++): 
            $query = $_GET;
            $query['page'] = $i;
            $queryString = http_build_query($query);
        ?>
            <a href="?<?php echo $queryString; ?>" class="btn btn-sm <?php echo $page == $i ? 'btn-primary' : ''; ?>" style="<?php echo $page != $i ? 'background-color: #334155; color: white;' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
