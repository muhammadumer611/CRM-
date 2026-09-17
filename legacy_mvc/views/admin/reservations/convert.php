<?php $config = require APP_ROOT . '/config/app.php'; ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-plus"></i> Convert Reservation to Student</h3>
        <a href="<?php echo $config['base_url']; ?>/reservations/view/<?php echo (int)$reservation['id']; ?>" class="btn" style="background:#334155;color:white;">Back</a>
    </div>

    <form method="POST" action="<?php echo $config['base_url']; ?>/reservations/convert/<?php echo (int)$reservation['id']; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <div class="form-group">
            <label class="form-label">Converted By *</label>
            <input type="text" name="converted_by_name" class="form-control" required>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($reservation['full_name']); ?>" required></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label class="form-label">CNIC</label><input type="text" name="cnic" class="form-control" value="<?php echo htmlspecialchars($reservation['cnic']); ?>" required></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($reservation['phone']); ?>" required></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label class="form-label">District</label><input type="text" name="district" class="form-control" value="<?php echo htmlspecialchars($reservation['district']); ?>" required></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label class="form-label">Room</label><input type="text" class="form-control" value="Room <?php echo htmlspecialchars($reservation['room_number']); ?>" disabled></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label class="form-label">Reserved Bed</label><input type="text" class="form-control" value="Bed <?php echo (int)$reservation['bed_number']; ?>" disabled></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label class="form-label">Monthly Fee (Rs.)</label><input type="number" name="monthly_fee" class="form-control" step="0.01" min="0" value="0"></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label class="form-label">Security Deposit (Rs.)</label><input type="number" name="security_deposit" class="form-control" step="0.01" min="0" value="0"></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label class="form-label">Address</label><input type="text" name="address" class="form-control" required></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label class="form-label">Joining Date</label><input type="date" name="joining_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label class="form-label">Guardian Name</label><input type="text" name="guardian_name" class="form-control" required></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label class="form-label">Guardian Phone</label><input type="text" name="guardian_phone" class="form-control" required></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label class="form-label">Relation</label><select name="relation" class="form-control"><option value="Father">Father</option><option value="Mother">Mother</option><option value="Brother">Brother</option><option value="Sister">Sister</option><option value="Other">Other</option></select></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label class="form-label">Resident Type</label><select name="resident_type" class="form-control"><option value="Student">Student</option><option value="Job / Working">Job / Working</option></select></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label class="form-label">College / University Name</label><input type="text" name="college_university" class="form-control"></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label class="form-label">Job / Workplace</label><input type="text" name="job_workplace" class="form-control"></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label class="form-label">Vehicle Number</label><input type="text" name="vehicle_number" class="form-control"></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label class="form-label">Vehicle Type</label><select name="vehicle_type" class="form-control"><option value="">Select...</option><option value="Motorcycle / Bike">Motorcycle / Bike</option><option value="Car">Car</option><option value="Nill">Nill</option><option value="Other">Other</option></select></div>
            </div>
            <div class="col-12">
                <div class="form-group"><label class="form-label">Note</label><textarea name="note" class="form-control" rows="3"></textarea></div>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:0.75rem;">
            <a href="<?php echo $config['base_url']; ?>/reservations/view/<?php echo (int)$reservation['id']; ?>" class="btn" style="background:#334155;color:white;">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-user-check"></i> Convert to Active Student</button>
        </div>
    </form>
</div>
