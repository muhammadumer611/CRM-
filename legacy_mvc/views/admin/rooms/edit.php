<?php $config = require __DIR__ . '/../../../config/app.php'; ?>
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
                <label class="form-label">Block *</label>
                <input type="text" name="block" class="form-control" value="<?php echo htmlspecialchars($room['block']); ?>" required>
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
                <input type="number" name="total_beds" class="form-control" min="<?php echo $room['occupied_beds'] > 0 ? $room['occupied_beds'] : 1; ?>" value="<?php echo $room['total_beds']; ?>" required>
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

        <div style="margin-top: 2rem;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Room</button>
        </div>
    </form>
</div>
