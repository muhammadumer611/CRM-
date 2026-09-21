<?php $config = require APP_ROOT . '/config/app.php'; ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Room</h3>
        <a href="<?php echo $config['base_url']; ?>/rooms" class="btn" style="background: #334155; color: white;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <form action="<?php echo $config['base_url']; ?>/rooms/update/<?php echo $room['id']; ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        
        <div class="row">
            <div class="col-md-6 form-group">
                <label class="form-label">Room Number *</label>
                <input type="text" name="room_number" class="form-control" value="<?php echo htmlspecialchars($room['room_number']); ?>" required>
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">Floor *</label>
                <input type="text" name="floor" class="form-control" value="<?php echo htmlspecialchars($room['floor']); ?>" required>
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">Room Type *</label>
                <select name="room_type" class="form-control" required>
                    <option value="Single" <?php echo $room['room_type'] == 'Single' ? 'selected' : ''; ?>>Single</option>
                    <option value="Double" <?php echo $room['room_type'] == 'Double' ? 'selected' : ''; ?>>Double</option>
                    <option value="Triple" <?php echo $room['room_type'] == 'Triple' ? 'selected' : ''; ?>>Triple</option>
                    <option value="Dormitory" <?php echo $room['room_type'] == 'Dormitory' ? 'selected' : ''; ?>>Dormitory</option>
                </select>
            </div>
            <div class="col-md-4 form-group">
                <label class="form-label">Total Beds * (Occupied: <?php echo $room['occupied_beds']; ?>)</label>
                <input type="number" name="total_beds" class="form-control" min="<?php echo $room['occupied_beds'] > 0 ? $room['occupied_beds'] : 1; ?>" max="10" value="<?php echo $room['total_beds']; ?>" required>
            </div>
            <div class="col-md-4 form-group">
                <label class="form-label">Monthly Fee (Rs.) *</label>
                <input type="number" name="monthly_fee" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($room['monthly_fee']); ?>" required>
            </div>
            <div class="col-md-4 form-group">
                <label class="form-label">Security Deposit (Rs.) *</label>
                <input type="number" name="security_deposit" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($room['security_deposit']); ?>" required>
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="Available" <?php echo $room['status'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                    <option value="Partially Occupied" <?php echo $room['status'] == 'Partially Occupied' ? 'selected' : ''; ?>>Partially Occupied</option>
                    <option value="Occupied" <?php echo $room['status'] == 'Occupied' ? 'selected' : ''; ?>>Occupied</option>
                    <option value="Disabled" <?php echo $room['status'] == 'Disabled' ? 'selected' : ''; ?>>Disabled</option>
                </select>
                <small style="color: var(--text-muted);">Status will auto-adjust based on occupancy unless set to Disabled.</small>
            </div>
        </div>

        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Room</button>
            <a href="<?php echo $config['base_url']; ?>/rooms" class="btn" style="background: #334155; color: white;">Cancel</a>
        </div>
    </form>

    <?php if (!empty($bedDetails) && !empty($bedDetails['beds'])): ?>
        <hr style="border-color: var(--border); margin: 2rem 0 1.5rem 0;">
        <h4 style="color: var(--primary); margin-bottom: 1rem;"><i class="fas fa-bed"></i> Current Bed Occupancy Layout</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem;">
            <?php foreach ($bedDetails['beds'] as $bed): ?>
                <div style="background: #0f172a; border: 1px solid <?php echo $bed['is_occupied'] ? 'rgba(239,68,68,0.4)' : 'rgba(16,185,129,0.4)'; ?>; border-radius: 8px; padding: 1rem; text-align: center;">
                    <div style="font-size: 1.5rem; color: <?php echo $bed['is_occupied'] ? '#f87171' : '#34d399'; ?>; margin-bottom: 0.25rem;">
                        <i class="fas fa-bed"></i>
                    </div>
                    <strong>Bed <?php echo $bed['bed_number']; ?></strong>
                    <div style="margin-top: 0.35rem;">
                        <?php if ($bed['is_occupied']): ?>
                            <span class="badge badge-danger">Occupied</span>
                            <div style="font-size: 0.8rem; color: #cbd5e1; margin-top: 0.5rem; line-height: 1.3;">
                                <?php echo htmlspecialchars($bed['occupant']['student_name']); ?><br>
                                <small style="color: var(--text-muted);"><?php echo htmlspecialchars($bed['occupant']['student_id_str']); ?></small>
                            </div>
                        <?php else: ?>
                            <span class="badge badge-success">Available</span>
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;">
                                Ready for allocation
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

