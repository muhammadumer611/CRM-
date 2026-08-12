<?php $config = require APP_ROOT . '/config/app.php'; ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Manage Rooms</h3>
        <a href="<?php echo $config['base_url']; ?>/rooms/create" class="btn btn-primary"><i class="fas fa-plus"></i> Add Room</a>
    </div>
    
    <div style="margin-bottom: 1.5rem;">
        <form action="<?php echo $config['base_url']; ?>/rooms" method="GET" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Room No, Block, Type..." value="<?php echo htmlspecialchars($filters['search']); ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0; flex: 0 0 200px;">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="">All</option>
                    <option value="Available" <?php echo $filters['status'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                    <option value="Partially Occupied" <?php echo $filters['status'] === 'Partially Occupied' ? 'selected' : ''; ?>>Partially Occupied</option>
                    <option value="Occupied" <?php echo $filters['status'] === 'Occupied' ? 'selected' : ''; ?>>Occupied</option>
                    <option value="Disabled" <?php echo $filters['status'] === 'Disabled' ? 'selected' : ''; ?>>Disabled</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height: 42px;"><i class="fas fa-filter"></i> Filter</button>
        </form>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Room No</th>
                    <th>Block/Floor</th>
                    <th>Type</th>
                    <th>Beds (Occ/Total)</th>
                    <th>Fees/Deposit</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rooms)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 2rem;">No rooms found.</td></tr>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($room['room_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($room['block']); ?> / <?php echo htmlspecialchars($room['floor']); ?></td>
                        <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                        <td>
                            <?php echo $room['occupied_beds']; ?> / <?php echo $room['total_beds']; ?>
                            <div style="width: 100%; height: 4px; background: #334155; margin-top: 4px; border-radius: 2px;">
                                <?php $percent = $room['total_beds'] > 0 ? ($room['occupied_beds'] / $room['total_beds']) * 100 : 0; ?>
                                <div style="height: 100%; width: <?php echo $percent; ?>%; background: <?php echo $percent == 100 ? 'var(--danger)' : 'var(--primary)'; ?>; border-radius: 2px;"></div>
                            </div>
                        </td>
                        <td>
                            Rs. <?php echo number_format($room['monthly_fee'], 0); ?><br>
                            <small style="color: var(--text-muted);">Dep: Rs. <?php echo number_format($room['security_deposit'], 0); ?></small>
                        </td>
                        <td>
                            <?php if ($room['status'] === 'Available'): ?>
                                <span class="badge badge-success">Available</span>
                            <?php elseif ($room['status'] === 'Partially Occupied'): ?>
                                <span class="badge badge-warning">Partial</span>
                            <?php elseif ($room['status'] === 'Occupied'): ?>
                                <span class="badge badge-danger">Occupied</span>
                            <?php else: ?>
                                <span class="badge" style="background-color: #475569;">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo $config['base_url']; ?>/rooms/edit/<?php echo $room['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
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
