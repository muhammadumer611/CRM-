<?php $config = require __DIR__ . '/../../../config/app.php'; ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Add New Student</h3>
        <a href="<?php echo $config['base_url']; ?>/students" class="btn" style="background: #334155; color: white;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <form action="<?php echo $config['base_url']; ?>/students/store" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        
        <h4 style="margin-bottom: 1rem; color: var(--primary); border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">Personal Information</h4>
        <div class="row">
            <div class="col-md-6 form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">CNIC (13 digits without dashes) *</label>
                <input type="text" name="cnic" class="form-control" pattern="[0-9]{13}" title="13 digit numeric CNIC" required>
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">Phone Number *</label>
                <input type="text" name="phone" class="form-control" required>
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control">
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">Blood Group</label>
                <select name="blood_group" class="form-control">
                    <option value="">Select</option>
                    <option value="A+">A+</option><option value="A-">A-</option>
                    <option value="B+">B+</option><option value="B-">B-</option>
                    <option value="O+">O+</option><option value="O-">O-</option>
                    <option value="AB+">AB+</option><option value="AB-">AB-</option>
                </select>
            </div>
            <div class="col-12 form-group">
                <label class="form-label">Permanent Address *</label>
                <textarea name="address" class="form-control" rows="3" required></textarea>
            </div>
        </div>
        
        <h4 style="margin-top: 1rem; margin-bottom: 1rem; color: var(--primary); border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">Guardian Information</h4>
        <div class="row">
            <div class="col-md-6 form-group">
                <label class="form-label">Guardian Name *</label>
                <input type="text" name="guardian_name" class="form-control" required>
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">Relation *</label>
                <input type="text" name="relation" class="form-control" placeholder="e.g. Father, Brother" required>
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">Guardian Phone *</label>
                <input type="text" name="guardian_phone" class="form-control" required>
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">Guardian CNIC *</label>
                <input type="text" name="guardian_cnic" class="form-control" required>
            </div>
        </div>

        <div style="margin-top: 2rem;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Student</button>
        </div>
    </form>
</div>
