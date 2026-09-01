<?php $config = require APP_ROOT . '/config/app.php'; ?>
<?php
$roomRepository = new \App\Repositories\RoomRepository();
$roomCatalog = [];
foreach ($roomRepository->findAllWithAvailability() as $room) {
    $occupied = (int)($room['occupied_beds'] ?? 0);
    $available = max(0, (int)$room['total_beds'] - $occupied);
    $roomCatalog[] = [
        'id' => (int)$room['id'],
        'room_number' => $room['room_number'],
        'block' => $room['block'],
        'floor' => $room['floor'],
        'room_type' => $room['room_type'],
        'total_beds' => (int)$room['total_beds'],
        'occupied_beds' => $occupied,
        'available_beds' => $available,
        'available_capacity' => $available,
        'status' => $room['status'] ?? 'Available'
    ];
}
$singlePersonRooms = array_values(array_filter($roomCatalog, fn($room) => (int)$room['available_beds'] > 0));
$fullRoomRooms = array_values(array_filter($roomCatalog, fn($room) => (int)$room['available_beds'] > 0));
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-plus"></i> Add New Student</h3>
        <a href="<?php echo $config['base_url']; ?>/students" class="btn" style="background:#334155;color:white;">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <form action="<?php echo $config['base_url']; ?>/students/store" method="POST" id="studentOnboardForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

        <div style="background:#0f172a;border:1px solid var(--border);border-radius:8px;padding:1.5rem;margin-bottom:1.5rem;">
            <h4 style="color:var(--primary);margin-bottom:1.25rem;"><i class="fas fa-house-user"></i> Accommodation Type</h4>
            <div class="row">
                <div class="col-md-6">
                    <label class="choice-card">
                        <input type="radio" name="accommodation_type" value="single" class="accommodation-option">
                        <span>Single Person</span>
                    </label>
                </div>
                <div class="col-md-6">
                    <label class="choice-card">
                        <input type="radio" name="accommodation_type" value="full_room" class="accommodation-option">
                        <span>Full Room</span>
                    </label>
                </div>
            </div>
        </div>

        <div id="singleRoomSection" style="display:none;">
            <div style="background:#0f172a;border:1px solid var(--border);border-radius:8px;padding:1.5rem;margin-bottom:1.5rem;">
                <h4 style="color:var(--primary);margin-bottom:1.25rem;"><i class="fas fa-bed"></i> Room & Bed Allocation</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Select Room *</label>
                            <select id="singleRoomSelect" name="room_id" class="form-control">
                                <option value="">Select available room</option>
                                <?php foreach ($singlePersonRooms as $room): ?>
                                    <option value="<?php echo (int)$room['id']; ?>" data-room-number="<?php echo htmlspecialchars($room['room_number']); ?>" data-block="<?php echo htmlspecialchars($room['block']); ?>" data-floor="<?php echo htmlspecialchars($room['floor']); ?>" data-type="<?php echo htmlspecialchars($room['room_type']); ?>" data-total="<?php echo (int)$room['total_beds']; ?>" data-occupied="<?php echo (int)$room['occupied_beds']; ?>" data-available="<?php echo (int)$room['available_beds']; ?>">
                                        <?php echo htmlspecialchars($room['block'] . ' - ' . $room['room_number']); ?> | <?php echo htmlspecialchars($room['room_type']); ?> | <?php echo (int)$room['available_beds']; ?> Available
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Joining Date *</label>
                            <input type="date" name="joining_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                </div>

                <div id="singleRoomSummary" style="display:none;background:#1e293b;border:1px solid var(--border);border-radius:6px;padding:1rem;margin-bottom:1rem;">
                    <div class="row">
                        <div class="col-md-2"><span class="small-label">Room</span><strong id="singleRoomNumber">—</strong></div>
                        <div class="col-md-2"><span class="small-label">Block</span><strong id="singleRoomBlock">—</strong></div>
                        <div class="col-md-2"><span class="small-label">Floor</span><strong id="singleRoomFloor">—</strong></div>
                        <div class="col-md-2"><span class="small-label">Type</span><strong id="singleRoomType">—</strong></div>
                        <div class="col-md-2"><span class="small-label">Occupied</span><strong id="singleRoomOccupied">—</strong></div>
                        <div class="col-md-2"><span class="small-label">Available</span><strong id="singleRoomAvailable">—</strong></div>
                    </div>
                </div>

                <div id="singleBedSection" style="display:none;">
                    <label class="form-label">Select Bed *</label>
                    <select id="singleBedSelect" name="bed_number" class="form-control">
                        <option value="">Select available bed</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="fullRoomSection" style="display:none;">
            <div style="background:#0f172a;border:1px solid var(--border);border-radius:8px;padding:1.5rem;margin-bottom:1.5rem;">
                <h4 style="color:var(--primary);margin-bottom:1.25rem;"><i class="fas fa-users"></i> Room Selection</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Select Room *</label>
                            <select id="fullRoomSelect" name="room_id" class="form-control">
                                <option value="">Select room</option>
                                <?php foreach ($fullRoomRooms as $room): ?>
                                    <option value="<?php echo (int)$room['id']; ?>" data-room-number="<?php echo htmlspecialchars($room['room_number']); ?>" data-block="<?php echo htmlspecialchars($room['block']); ?>" data-floor="<?php echo htmlspecialchars($room['floor']); ?>" data-type="<?php echo htmlspecialchars($room['room_type']); ?>" data-total="<?php echo (int)$room['total_beds']; ?>" data-occupied="<?php echo (int)$room['occupied_beds']; ?>" data-available="<?php echo (int)$room['available_beds']; ?>">
                                        <?php echo htmlspecialchars($room['block'] . ' - ' . $room['room_number']); ?> | <?php echo htmlspecialchars($room['room_type']); ?> | <?php echo (int)$room['available_beds']; ?> Available
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Joining Date *</label>
                            <input type="date" name="joining_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                </div>
                <div id="fullRoomSummary" style="display:none;background:#1e293b;border:1px solid var(--border);border-radius:6px;padding:1rem;margin-bottom:1rem;">
                    <div class="row">
                        <div class="col-md-3"><span class="small-label">Room</span><strong id="fullRoomNumber">—</strong></div>
                        <div class="col-md-3"><span class="small-label">Block</span><strong id="fullRoomBlock">—</strong></div>
                        <div class="col-md-3"><span class="small-label">Floor</span><strong id="fullRoomFloor">—</strong></div>
                        <div class="col-md-3"><span class="small-label">Type</span><strong id="fullRoomType">—</strong></div>
                    </div>
                    <div class="row" style="margin-top:0.75rem;">
                        <div class="col-md-6"><span class="small-label">Total Beds</span><strong id="fullRoomTotalBeds">—</strong></div>
                        <div class="col-md-6"><span class="small-label">Available Capacity</span><strong id="fullRoomAvailable">—</strong></div>
                    </div>
                </div>

                <div id="fullRoomOccupants" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center" style="margin-bottom:1rem;">
                        <h5 style="color:var(--primary);margin:0;">Room Occupants</h5>
                        <button type="button" id="addOccupantBtn" class="btn btn-sm btn-primary">+ Add Another Person</button>
                    </div>
                    <div id="occupantList"></div>
                </div>
            </div>
        </div>

        <div id="studentInfoSection" style="display:none;background:#0f172a;border:1px solid var(--border);border-radius:8px;padding:1.5rem;margin-bottom:1.5rem;">
            <h4 style="color:var(--primary);margin-bottom:1.25rem;"><i class="fas fa-user"></i> Student Information</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. Muhammad Ali Khan">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">CNIC *</label>
                        <input type="text" name="cnic" class="form-control" maxlength="15" placeholder="e.g. 12345-1234567-1">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Phone Number *</label>
                        <input type="text" name="phone" class="form-control" placeholder="e.g. 03001234567">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="Optional">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Blood Group</label>
                        <select name="blood_group" class="form-control">
                            <option value="">Select...</option>
                            <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                                <option value="<?php echo $bg; ?>"><?php echo $bg; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Address *</label>
                        <input type="text" name="address" class="form-control" placeholder="Full address">
                    </div>
                </div>
            </div>

            <div style="margin-top:1.5rem;">
                <h5 style="color:var(--primary);margin-bottom:1rem;"><i class="fas fa-users"></i> Guardian Information</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Guardian Name *</label>
                            <input type="text" name="guardian_name" class="form-control" placeholder="Guardian name">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Relation *</label>
                            <select name="relation" class="form-control">
                                <option value="">Select...</option>
                                <?php foreach(['Father','Mother','Brother','Sister','Uncle','Aunt','Spouse','Other'] as $rel): ?>
                                    <option value="<?php echo $rel; ?>"><?php echo $rel; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Guardian Phone *</label>
                            <input type="text" name="guardian_phone" class="form-control" placeholder="e.g. 03001234567">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Guardian CNIC *</label>
                            <input type="text" name="guardian_cnic" class="form-control" maxlength="15" placeholder="e.g. 12345-1234567-1">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Guardian Address</label>
                            <input type="text" name="guardian_address" class="form-control" placeholder="Optional">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="financialSection" style="display:none;background:#0f172a;border:1px solid var(--border);border-radius:8px;padding:1.5rem;margin-bottom:1.5rem;">
            <h4 style="color:var(--primary);margin-bottom:1.25rem;"><i class="fas fa-money-bill-wave"></i> Financial Details</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" id="monthlyFeeLabel">Monthly Fee (Rs.) *</label>
                        <input type="number" id="monthlyFeeInput" name="monthly_fee" class="form-control" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Security Deposit (Rs.)</label>
                        <input type="number" name="security_deposit" class="form-control" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:1rem;justify-content:flex-end;">
            <a href="<?php echo $config['base_url']; ?>/students" class="btn" style="background:#334155;color:white;">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Onboard Student</button>
        </div>
    </form>
</div>

<style>
.choice-card {
    display:flex; align-items:center; justify-content:center; min-height: 70px; border:1px solid var(--border); background:#0f172a; border-radius:10px; padding:1rem; cursor:pointer; transition:0.2s ease; font-weight:600;
}
.choice-card input { margin-right:10px; }
.choice-card:hover { border-color: var(--primary); background: rgba(56,189,248,0.08); }
.choice-card.selected { border-color: var(--primary); background: rgba(56,189,248,0.12); }
.small-label { display:block; color:var(--text-muted); font-size:0.75rem; margin-bottom:0.25rem; }
.occupant-card { border:1px solid var(--border); border-radius:8px; padding:1rem; background:#1e293b; margin-bottom:1rem; }
.occupant-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; }
.remove-occupant-btn { background: rgba(239,68,68,0.1); color: var(--danger); border:1px solid rgba(239,68,68,0.25); border-radius:6px; padding:0.35rem 0.7rem; cursor:pointer; }
</style>

<script>
const singleRoomSelect = document.getElementById('singleRoomSelect');
const fullRoomSelect = document.getElementById('fullRoomSelect');
const singleRoomSummary = document.getElementById('singleRoomSummary');
const singleBedSection = document.getElementById('singleBedSection');
const fullRoomSummary = document.getElementById('fullRoomSummary');
const fullRoomOccupants = document.getElementById('fullRoomOccupants');
const addOccupantBtn = document.getElementById('addOccupantBtn');
const occupantList = document.getElementById('occupantList');
const financialSection = document.getElementById('financialSection');
const monthlyFeeLabel = document.getElementById('monthlyFeeLabel');

function setAccomodationMode(mode) {
    const singleVisible = mode === 'single';
    const fullVisible = mode === 'full_room';
    document.getElementById('singleRoomSection').style.display = singleVisible ? 'block' : 'none';
    document.getElementById('fullRoomSection').style.display = fullVisible ? 'block' : 'none';
    document.getElementById('studentInfoSection').style.display = 'block';
    document.getElementById('financialSection').style.display = 'block';
    financialSection.style.display = 'block';
    if (singleVisible) {
        monthlyFeeLabel.textContent = 'Monthly Fee (Rs.) *';
        document.getElementById('monthlyFeeInput').name = 'monthly_fee';
        document.querySelectorAll('.accommodation-option').forEach(r => {
            const card = r.closest('.choice-card');
            if (card) card.classList.toggle('selected', r.checked);
        });
    } else if (fullVisible) {
        monthlyFeeLabel.textContent = 'Monthly Room Fee (Rs.) *';
        document.getElementById('monthlyFeeInput').name = 'monthly_room_fee';
        document.querySelectorAll('.accommodation-option').forEach(r => {
            const card = r.closest('.choice-card');
            if (card) card.classList.toggle('selected', r.checked);
        });
    }
}

document.querySelectorAll('.accommodation-option').forEach((radio) => {
    radio.addEventListener('change', function() {
        setAccomodationMode(this.value);
    });
});

function populateSingleRoomSummary(roomSelect) {
    const selected = roomSelect.options[roomSelect.selectedIndex];
    if (!selected || !selected.value) {
        singleRoomSummary.style.display = 'none';
        singleBedSection.style.display = 'none';
        return;
    }

    document.getElementById('singleRoomNumber').textContent = selected.dataset.roomNumber || '—';
    document.getElementById('singleRoomBlock').textContent = selected.dataset.block || '—';
    document.getElementById('singleRoomFloor').textContent = selected.dataset.floor || '—';
    document.getElementById('singleRoomType').textContent = selected.dataset.type || '—';
    document.getElementById('singleRoomOccupied').textContent = (selected.dataset.occupied || '0');
    document.getElementById('singleRoomAvailable').textContent = (selected.dataset.available || '0');
    singleRoomSummary.style.display = 'block';
    loadAvailableBeds(selected.value);
}

function loadAvailableBeds(roomId) {
    const singleBedSelect = document.getElementById('singleBedSelect');
    if (!roomId) {
        singleBedSection.style.display = 'none';
        return;
    }

    singleBedSection.style.display = 'block';
    singleBedSelect.innerHTML = '<option value="">Loading available beds...</option>';

    fetch('<?php echo $config['base_url']; ?>/api/allocations/available-beds/' + roomId)
        .then((response) => {
            if (!response.ok) {
                throw new Error('Unable to load available beds');
            }
            return response.json();
        })
        .then((res) => {
            if (!res.success || !res.data || !Array.isArray(res.data.available_bed_numbers)) {
                singleBedSelect.innerHTML = '<option value="">No available beds</option>';
                return;
            }

            const beds = res.data.available_bed_numbers;
            singleBedSelect.innerHTML = '<option value="">Select available bed</option>';
            beds.forEach((bedNumber) => {
                const option = document.createElement('option');
                option.value = bedNumber;
                option.textContent = `Bed ${bedNumber} — Available`;
                singleBedSelect.appendChild(option);
            });

            if (beds.length === 0) {
                singleBedSelect.innerHTML = '<option value="">No available beds</option>';
            }
        })
        .catch(() => {
            singleBedSelect.innerHTML = '<option value="">No available beds</option>';
        });
}

function renderFullRoomSummary(roomSelect) {
    const selected = roomSelect.options[roomSelect.selectedIndex];
    if (!selected || !selected.value) {
        fullRoomSummary.style.display = 'none';
        fullRoomOccupants.style.display = 'none';
        return;
    }

    document.getElementById('fullRoomNumber').textContent = selected.dataset.roomNumber || '—';
    document.getElementById('fullRoomBlock').textContent = selected.dataset.block || '—';
    document.getElementById('fullRoomFloor').textContent = selected.dataset.floor || '—';
    document.getElementById('fullRoomType').textContent = selected.dataset.type || '—';
    document.getElementById('fullRoomTotalBeds').textContent = selected.dataset.total || '0';
    document.getElementById('fullRoomAvailable').textContent = selected.dataset.available || '0';
    fullRoomSummary.style.display = 'block';
    fullRoomOccupants.style.display = 'block';
    addOccupantRow();
}

function getRoomCapacity(roomSelect) {
    const selected = roomSelect.options[roomSelect.selectedIndex];
    if (!selected || !selected.value) return 0;
    return parseInt(selected.dataset.available || '0', 10);
}

function addOccupantRow() {
    const roomSelect = document.getElementById('fullRoomSelect');
    const capacity = getRoomCapacity(roomSelect);
    const currentCount = occupantList.querySelectorAll('.occupant-card').length;

    if (capacity <= 0 || currentCount >= capacity) {
        addOccupantBtn.disabled = true;
        addOccupantBtn.textContent = 'Room capacity reached.';
        return;
    }

    addOccupantBtn.disabled = false;
    addOccupantBtn.textContent = '+ Add Another Person';

    if (currentCount >= capacity) {
        return;
    }

    const index = currentCount + 1;
    const occupantCard = document.createElement('div');
    occupantCard.className = 'occupant-card';
    occupantCard.innerHTML = `
        <div class="occupant-header">
            <strong>Person ${index}</strong>
            <button type="button" class="remove-occupant-btn" data-index="${index - 1}">Remove</button>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="occupants[${index - 1}][full_name]" class="form-control" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">CNIC *</label>
                    <input type="text" name="occupants[${index - 1}][cnic]" class="form-control" maxlength="15" required>
                </div>
            </div>
        </div>
    `;

    occupantList.appendChild(occupantCard);
    occupantCard.querySelector('.remove-occupant-btn').addEventListener('click', function() {
        occupantCard.remove();
        updateOccupantLabels();
    });
    updateOccupantLabels();
}

function updateOccupantLabels() {
    const roomSelect = document.getElementById('fullRoomSelect');
    const capacity = getRoomCapacity(roomSelect);
    const cards = occupantList.querySelectorAll('.occupant-card');
    cards.forEach((card, idx) => {
        const label = card.querySelector('.occupant-header strong');
        if (label) label.textContent = `Person ${idx + 1}`;
    });

    if (cards.length >= capacity) {
        addOccupantBtn.disabled = true;
        addOccupantBtn.textContent = 'Room capacity reached.';
    } else {
        addOccupantBtn.disabled = false;
        addOccupantBtn.textContent = '+ Add Another Person';
    }
}

addOccupantBtn.addEventListener('click', function() {
    const roomSelect = document.getElementById('fullRoomSelect');
    const capacity = getRoomCapacity(roomSelect);
    const currentCount = occupantList.querySelectorAll('.occupant-card').length;
    if (currentCount >= capacity) {
        addOccupantBtn.disabled = true;
        addOccupantBtn.textContent = 'Room capacity reached.';
        return;
    }
    addOccupantRow();
});

singleRoomSelect.addEventListener('change', function() {
    populateSingleRoomSummary(this);
});
fullRoomSelect.addEventListener('change', function() {
    renderFullRoomSummary(this);
});

document.getElementById('studentOnboardForm').addEventListener('submit', function(event) {
    const mode = document.querySelector('input[name="accommodation_type"]:checked');
    if (!mode) {
        event.preventDefault();
        alert('Please select accommodation type.');
        return;
    }

    if (mode.value === 'single') {
        if (!singleRoomSelect.value) {
            event.preventDefault();
            alert('Please select a room.');
            return;
        }
        if (!document.getElementById('singleBedSelect').value) {
            event.preventDefault();
            alert('Please select an available bed.');
            return;
        }
    }

    if (mode.value === 'full_room') {
        const selectedRoom = fullRoomSelect.value;
        if (!selectedRoom) {
            event.preventDefault();
            alert('Please select a room.');
            return;
        }
        const cards = occupantList.querySelectorAll('.occupant-card');
        if (!cards.length) {
            event.preventDefault();
            alert('Please add at least one occupant.');
            return;
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = '<?php echo $config['base_url']; ?>';
    document.body.dataset.baseUrl = baseUrl;
    document.querySelectorAll('.choice-card').forEach((card) => {
        const radio = card.querySelector('input');
        if (radio && radio.checked) {
            card.classList.add('selected');
        }
    });
    fullRoomSelect.value = '';
    singleRoomSelect.value = '';
});
</script>
