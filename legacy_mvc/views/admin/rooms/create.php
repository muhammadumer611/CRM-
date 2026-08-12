<?php $config = require __DIR__ . '/../../../config/app.php'; ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Add New Room</h3>
        <a href="<?php echo $config['base_url']; ?>/rooms" class="btn" style="background: #334155; color: white;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <form action="<?php echo $config['base_url']; ?>/rooms/store" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        
        <div class="row">
            <div class="col-md-6 form-group">
                <label class="form-label">Room Number *</label>
                <input type="text" name="room_number" class="form-control" required autofocus>
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">Block *</label>
                <input type="text" name="block" class="form-control" placeholder="e.g. Block A" required>
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">Floor *</label>
                <input type="text" name="floor" class="form-control" placeholder="e.g. Ground, 1st" required>
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">Room Type *</label>
                <select name="room_type" class="form-control" required>
                    <option value="">Select</option>
                    <option value="Single">Single</option>
                    <option value="Double">Double</option>
                    <option value="Triple">Triple</option>
                    <option value="Dormitory">Dormitory</option>
                </select>
            </div>
            <div class="col-md-4 form-group">
                <label class="form-label">Total Beds *</label>
                <input type="number" name="total_beds" class="form-control" min="1" required>
            </div>
            <div class="col-md-4 form-group">
                <label class="form-label">Monthly Fee (Rs.) *</label>
                <input type="number" name="monthly_fee" class="form-control" step="0.01" min="0" required>
            </div>
            <div class="col-md-4 form-group">
                <label class="form-label">Security Deposit (Rs.) *</label>
                <input type="number" name="security_deposit" class="form-control" step="0.01" min="0" required>
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="Available">Available</option>
                    <option value="Disabled">Disabled</option>
                </select>
            </div>
        </div>

        <div style="margin-top: 2rem;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Room</button>
        </div>
    </form>
</div>
