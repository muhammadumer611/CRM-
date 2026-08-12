<?php $config = require __DIR__ . '/../../../config/app.php'; ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">New Room Allocation</h3>
        <a href="<?php echo $config['base_url']; ?>/allocations" class="btn" style="background: #334155; color: white;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <form action="<?php echo $config['base_url']; ?>/allocations/store" method="POST">
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
                <small style="color: var(--text-muted);">Only active students are listed.</small>
            </div>
            
            <div class="col-md-6 form-group">
                <label class="form-label">Room *</label>
                <select name="room_id" class="form-control" id="room_select" required>
                    <option value="" data-beds="0">Select Room</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?php echo $room['id']; ?>" data-beds="<?php echo $room['total_beds']; ?>">
                            <?php echo htmlspecialchars($room['block'] . ' - ' . $room['room_number'] . ' (Available Beds: ' . ($room['total_beds'] - $room['occupied_beds']) . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-6 form-group">
                <label class="form-label">Bed Number *</label>
                <input type="number" name="bed_number" id="bed_number" class="form-control" min="1" required>
                <small id="bed_help" style="color: var(--text-muted);">Select a room first to see total beds.</small>
            </div>
            
            <div class="col-md-6 form-group">
                <label class="form-label">Joining Date *</label>
                <input type="date" name="joining_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>

        <div style="margin-top: 2rem;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Allocate Room</button>
        </div>
    </form>
</div>

<script>
document.getElementById('room_select').addEventListener('change', function() {
    var selectedOption = this.options[this.selectedIndex];
    var maxBeds = selectedOption.getAttribute('data-beds');
    var bedInput = document.getElementById('bed_number');
    var bedHelp = document.getElementById('bed_help');
    
    if (maxBeds > 0) {
        bedInput.max = maxBeds;
        bedHelp.textContent = 'Valid bed numbers: 1 to ' + maxBeds;
    } else {
        bedInput.removeAttribute('max');
        bedHelp.textContent = 'Select a room first to see total beds.';
    }
});
</script>
