<?php $config = require __DIR__ . '/../../../config/app.php'; ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Room Allocations</h3>
        <a href="<?php echo $config['base_url']; ?>/allocations/create" class="btn btn-primary"><i class="fas fa-plus"></i> Allocate Room</a>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Room</th>
                    <th>Bed</th>
                    <th>Joining Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allocations)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 2rem;">No active allocations found.</td></tr>
                <?php else: ?>
                    <?php foreach ($allocations as $alloc): ?>
                    <tr>
                        <td><span class="badge" style="background-color: #334155;"><?php echo htmlspecialchars($alloc['student_id_str']); ?></span></td>
                        <td><strong><?php echo htmlspecialchars($alloc['full_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($alloc['block'] . ' - ' . $alloc['room_number']); ?></td>
                        <td>Bed <?php echo htmlspecialchars($alloc['bed_number']); ?></td>
                        <td><?php echo htmlspecialchars($alloc['joining_date']); ?></td>
                        <td>
                            <form action="<?php echo $config['base_url']; ?>/allocations/remove/<?php echo $alloc['id']; ?>" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to remove this student from the room?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-user-minus"></i> Remove</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
