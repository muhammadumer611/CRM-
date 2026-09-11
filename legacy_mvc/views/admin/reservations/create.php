<?php $config = require APP_ROOT . '/config/app.php'; ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-calendar-plus"></i> Create Reservation</h3>
        <a href="<?php echo $config['base_url']; ?>/reservations" class="btn" style="background:#334155;color:white;">Back</a>
    </div>

    <form method="POST" action="<?php echo $config['base_url']; ?>/reservations/store">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <div class="form-group">
            <label class="form-label">Reserved By *</label>
            <input type="text" name="reserved_by_name" class="form-control" required>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">CNIC / ID Card Number *</label>
                    <input type="text" name="cnic" class="form-control" maxlength="15" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="text" name="phone" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">District *</label>
                    <input type="text" name="district" class="form-control" required>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Room *</label>
                    <select name="room_id" id="reservationRoom" class="form-control" required>
                        <option value="">Select Room</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo (int)$room['id']; ?>" data-total="<?php echo (int)$room['total_beds']; ?>">Room <?php echo htmlspecialchars($room['room_number']); ?> (<?php echo htmlspecialchars($room['room_type']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Bed *</label>
                    <select name="bed_number" id="reservationBed" class="form-control" required>
                        <option value="">Select available bed</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Booking / Reservation Amount *</label>
                    <input type="number" name="reservation_amount" class="form-control" min="0" step="0.01" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Reservation Date</label>
                    <input type="date" name="reservation_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Expected Arrival Date</label>
                    <input type="date" name="expected_arrival_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="PENDING">PENDING</option>
                        <option value="CONFIRMED">CONFIRMED</option>
                    </select>
                </div>
            </div>
            <div class="col-12">
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control"></textarea>
                </div>
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:0.75rem;">
            <a href="<?php echo $config['base_url']; ?>/reservations" class="btn" style="background:#334155;color:white;">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Reservation</button>
        </div>
    </form>
</div>

<script>
const roomSelect = document.getElementById('reservationRoom');
const bedSelect = document.getElementById('reservationBed');
const roomOptions = <?php echo json_encode(array_map(function($room) { return [ 'id' => (int)$room['id'], 'available_beds' => $room['available_beds'] ?? [], 'room_number' => $room['room_number'] ]; }, $rooms)); ?>;

function loadAvailableBeds(roomId) {
    const room = roomOptions.find(r => String(r.id) === String(roomId));
    bedSelect.innerHTML = '<option value="">Select available bed</option>';
    if (!room) return;

    room.available_beds.forEach(function(bed) {
        const option = document.createElement('option');
        option.value = bed;
        option.textContent = 'Bed ' + bed;
        bedSelect.appendChild(option);
    });
}

roomSelect.addEventListener('change', function () {
    loadAvailableBeds(this.value);
});
</script>
