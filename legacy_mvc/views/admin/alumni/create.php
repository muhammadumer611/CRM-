<?php $config = require APP_ROOT . '/config/app.php'; ?>
<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Transfer Student to Alumni</h3>
        <a href="<?php echo $config['base_url']; ?>/alumni" class="btn" style="background: #334155; color: white;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    
    <div class="alert alert-warning" style="margin-bottom: 2rem;">
        <i class="fas fa-exclamation-triangle"></i> This action will:
        <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
            <li>Close their active room allocation (if any)</li>
            <li>Free up their bed for new students</li>
            <li>Change their status to 'Inactive'</li>
            <li>Create an immutable alumni record</li>
        </ul>
    </div>

    <form action="<?php echo $config['base_url']; ?>/alumni/store" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        
        <div class="form-group">
            <label class="form-label">Student *</label>
            <select name="student_id" class="form-control" required>
                <option value="">Select Student</option>
                <?php foreach ($students as $student): ?>
                    <option value="<?php echo $student['id']; ?>">
                        <?php echo htmlspecialchars($student['student_id_str'] . ' - ' . $student['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">Reason for Leaving *</label>
            <select name="leaving_reason" class="form-control" required>
                <option value="Completed Degree">Completed Degree</option>
                <option value="Graduated">Graduated</option>
                <option value="Personal Reasons">Personal Reasons</option>
                <option value="Expelled">Expelled</option>
                <option value="Moved to another hostel">Moved to another hostel</option>
                <option value="Other">Other</option>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">Additional Remarks</label>
            <textarea name="remarks" class="form-control" rows="3"></textarea>
        </div>

        <div style="margin-top: 2rem;">
            <button type="submit" class="btn btn-danger" style="width: 100%; justify-content: center; font-size: 1.1rem; padding: 0.75rem;"><i class="fas fa-sign-out-alt"></i> Transfer to Alumni</button>
        </div>
    </form>
</div>
