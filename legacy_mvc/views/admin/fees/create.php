<?php $config = require __DIR__ . '/../../../config/app.php'; ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Create Fee Record</h3>
        <a href="<?php echo $config['base_url']; ?>/fees" class="btn" style="background: #334155; color: white;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <form action="<?php echo $config['base_url']; ?>/fees/store" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        
        <div class="row">
            <div class="col-md-6 form-group">
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
            
            <div class="col-md-3 form-group">
                <label class="form-label">Billing Month *</label>
                <select name="billing_month" class="form-control" required>
                    <?php $curMonth = date('n'); for($m=1; $m<=12; ++$m): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m == $curMonth ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $m, 1)); ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="col-md-3 form-group">
                <label class="form-label">Billing Year *</label>
                <select name="billing_year" class="form-control" required>
                    <?php $y = date('Y'); for($i=$y-1; $i<=$y+1; ++$i): ?>
                        <option value="<?php echo $i; ?>" <?php echo $i == $y ? 'selected' : ''; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="col-md-6 form-group">
                <label class="form-label">Total Amount (Rs.) *</label>
                <input type="number" name="amount" class="form-control" step="0.01" min="1" required>
            </div>
            
            <div class="col-md-6 form-group">
                <label class="form-label">Due Date *</label>
                <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+10 days')); ?>" required>
            </div>

            <div class="col-12 form-group">
                <label class="form-label">Remarks (Optional)</label>
                <textarea name="remarks" class="form-control" rows="2"></textarea>
            </div>
        </div>

        <div style="margin-top: 2rem;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Record</button>
        </div>
    </form>
</div>
