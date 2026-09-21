<?php $config = require APP_ROOT . '/config/app.php'; ?>
<?php
$roomRepository = new \App\Repositories\RoomRepository();
$roomCatalog = [];
foreach ($roomRepository->findAllWithAvailability() as $room) {
    $occupied = (int)($room['active_occupied_beds'] ?? 0);
    $reserved = (int)($room['active_reserved_beds'] ?? 0);
    $available = max(0, (int)($room['available_beds'] ?? ((int)$room['total_beds'] - $occupied - $reserved)));
    $roomCatalog[] = [
        'id' => (int)$room['id'],
        'room_number' => $room['room_number'],
        'block' => $room['block'],
        'floor' => $room['floor'],
        'room_type' => $room['room_type'],
        'total_beds' => (int)$room['total_beds'],
        'occupied_beds' => $occupied,
        'reserved_beds' => $reserved,
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

        <div class="form-group">
            <label class="form-label">Added By *</label>
            <input type="text" name="added_by_name" class="form-control" required>
        </div>

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
                                    <option value="<?php echo (int)$room['id']; ?>" data-room-number="<?php echo htmlspecialchars($room['room_number']); ?>" data-floor="<?php echo htmlspecialchars($room['floor']); ?>" data-type="<?php echo htmlspecialchars($room['room_type']); ?>" data-total="<?php echo (int)$room['total_beds']; ?>" data-occupied="<?php echo (int)$room['occupied_beds']; ?>" data-available="<?php echo (int)$room['available_beds']; ?>">
                                        Room <?php echo htmlspecialchars($room['room_number']); ?> | <?php echo htmlspecialchars($room['room_type']); ?> | <?php echo (int)$room['available_beds']; ?> Available
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
                        <div class="col-md-3"><span class="small-label">Room</span><strong id="singleRoomNumber">—</strong></div>
                        <div class="col-md-3"><span class="small-label">Floor</span><strong id="singleRoomFloor">—</strong></div>
                        <div class="col-md-3"><span class="small-label">Type</span><strong id="singleRoomType">—</strong></div>
                        <div class="col-md-3"><span class="small-label">Available Beds</span><strong id="singleRoomAvailable">—</strong></div>
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
                                    <option value="<?php echo (int)$room['id']; ?>" data-room-number="<?php echo htmlspecialchars($room['room_number']); ?>" data-floor="<?php echo htmlspecialchars($room['floor']); ?>" data-type="<?php echo htmlspecialchars($room['room_type']); ?>" data-total="<?php echo (int)$room['total_beds']; ?>" data-occupied="<?php echo (int)$room['occupied_beds']; ?>" data-available="<?php echo (int)$room['available_beds']; ?>">
                                        Room <?php echo htmlspecialchars($room['room_number']); ?> | <?php echo htmlspecialchars($room['room_type']); ?> | <?php echo (int)$room['available_beds']; ?> Available
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
                        <div class="col-md-4"><span class="small-label">Room</span><strong id="fullRoomNumber">—</strong></div>
                        <div class="col-md-4"><span class="small-label">Floor</span><strong id="fullRoomFloor">—</strong></div>
                        <div class="col-md-4"><span class="small-label">Type</span><strong id="fullRoomType">—</strong></div>
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
                </div>
            </div>
        </div>

        <div id="residentInfoSection" style="display:none;background:#0f172a;border:1px solid var(--border);border-radius:8px;padding:1.5rem;margin-bottom:1.5rem;">
            <h4 style="color:var(--primary);margin-bottom:1.25rem;"><i class="fas fa-user-tie"></i> Resident Information</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Resident Type *</label>
                        <select name="resident_type" id="residentType" class="form-control">
                            <option value="Student">Student</option>
                            <option value="Job / Working">Job / Working</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6" id="collegeUniversityWrapper">
                    <div class="form-group">
                        <label class="form-label">College / University Name</label>
                        <input type="text" name="college_university" id="collegeUniversity" class="form-control" placeholder="e.g. University of Lahore">
                    </div>
                </div>
                <div class="col-md-6" id="jobWorkplaceWrapper" style="display:none;">
                    <div class="form-group">
                        <label class="form-label">Job / Workplace</label>
                        <input type="text" name="job_workplace" id="jobWorkplace" class="form-control" placeholder="e.g. XYZ Company">
                    </div>
                </div>
            </div>
        </div>

        <div id="vehicleInfoSection" style="display:none;background:#0f172a;border:1px solid var(--border);border-radius:8px;padding:1.5rem;margin-bottom:1.5rem;">
            <h4 style="color:var(--primary);margin-bottom:1.25rem;"><i class="fas fa-motorcycle"></i> Vehicle Information</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Vehicle Number</label>
                        <input type="text" name="vehicle_number" class="form-control" placeholder="e.g. ABC-123">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Vehicle Type</label>
                        <select name="vehicle_type" id="vehicleType" class="form-control">
                            <option value="">Select...</option>
                            <option value="Motorcycle / Bike">Motorcycle / Bike</option>
                            <option value="Car">Car</option>
                            <option value="Nill">Nill</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6" id="vehicleTypeOtherWrapper" style="display:none;">
                    <div class="form-group">
                        <label class="form-label">Vehicle Type (Other)</label>
                        <input type="text" name="vehicle_type_other" class="form-control" placeholder="Describe vehicle type">
                    </div>
                </div>
            </div>
        </div>

        <div id="noteSection" style="display:none;background:#0f172a;border:1px solid var(--border);border-radius:8px;padding:1.5rem;margin-bottom:1.5rem;">
            <h4 style="color:var(--primary);margin-bottom:1.25rem;"><i class="fas fa-sticky-note"></i> Note</h4>
            <div class="form-group">
                <label class="form-label">Note</label>
                <textarea name="note" class="form-control" rows="4" placeholder="Optional additional information for the resident"></textarea>
            </div>
        </div>

        <div id="financialSection" style="display:none;background:#0f172a;border:1px solid var(--border);border-radius:8px;padding:1.5rem;margin-bottom:1.5rem;">
            <h4 style="color:var(--primary);margin-bottom:1.25rem;"><i class="fas fa-money-bill-wave"></i> Fee &amp; Billing</h4>
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
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">First Month Billing</label>
                        <select name="first_month_billing_mode" id="firstMonthBillingMode" class="form-control">
                            <option value="automatic">Automatic policy</option>
                            <option value="full">Full monthly fee</option>
                            <option value="proration">Daily proration</option>
                            <option value="manual">Manual adjustment</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6" id="manualAdjustmentFields" style="display:none;">
                    <div class="form-group"><label class="form-label">First Month Adjustment (Rs.)</label><input type="number" name="first_month_discount" id="firstMonthDiscount" class="form-control" min="0" step="0.01" value="0"></div>
                    <div class="form-group"><label class="form-label">Reason</label><input type="text" name="first_month_discount_reason" id="firstMonthDiscountReason" class="form-control" placeholder="e.g. Late joining discount"></div>
                </div>
            </div>
            <div id="firstMonthPreview" style="margin-top:1rem;padding:1rem;border:1px solid var(--border);border-radius:6px;background:#1e293b;display:none;"></div>
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
const firstMonthMode = document.getElementById('firstMonthBillingMode');
const firstMonthDiscount = document.getElementById('firstMonthDiscount');
const firstMonthPreview = document.getElementById('firstMonthPreview');

function updateFirstMonthPreview() {
    const fee = parseFloat(document.getElementById('monthlyFeeInput').value || '0');
    const dateInput = Array.from(document.querySelectorAll('input[name="joining_date"]')).find((field) => !field.disabled);
    const dateValue = dateInput ? dateInput.value : '';
    const mode = firstMonthMode ? firstMonthMode.value : 'automatic';
    if (!fee || !dateValue || !firstMonthPreview) { if (firstMonthPreview) firstMonthPreview.style.display = 'none'; return; }
    const date = new Date(dateValue + 'T00:00:00');
    const days = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
    const payableDays = days - date.getDate() + 1;
    let discount = 0;
    if (mode === 'proration') discount = fee - (fee / days * payableDays);
    if (mode === 'manual') discount = Math.min(fee, Math.max(0, parseFloat(firstMonthDiscount.value || '0')));
    const net = Math.max(0, fee - discount);
    firstMonthPreview.style.display = 'block';
    firstMonthPreview.innerHTML = '<strong>First Month Preview</strong><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.75rem;margin-top:.75rem;">' +
        '<span>Actual fee<br><b>Rs. ' + fee.toLocaleString() + '</b></span><span>Billing period<br><b>' + date.toLocaleString(undefined, {month:'long', year:'numeric'}) + '</b></span><span>Payable days<br><b>' + (mode === 'full' ? days : payableDays) + '</b></span><span>Adjustment<br><b>Rs. ' + Math.round(discount).toLocaleString() + '</b></span><span>Final payable<br><b>Rs. ' + Math.round(net).toLocaleString() + '</b></span></div>';
}

function setAccomodationMode(mode) {
    const singleVisible = mode === 'single';
    const fullVisible = mode === 'full_room';
    document.getElementById('singleRoomSection').style.display = singleVisible ? 'block' : 'none';
    document.getElementById('fullRoomSection').style.display = fullVisible ? 'block' : 'none';
    document.querySelectorAll('#singleRoomSection select, #singleRoomSection input').forEach((field) => {
        field.disabled = !singleVisible;
    });
    document.querySelectorAll('#fullRoomSection select, #fullRoomSection input').forEach((field) => {
        field.disabled = !fullVisible;
    });
    document.getElementById('studentInfoSection').style.display = 'block';
    document.getElementById('residentInfoSection').style.display = 'block';
    document.getElementById('vehicleInfoSection').style.display = 'block';
    document.getElementById('noteSection').style.display = 'block';
    document.getElementById('financialSection').style.display = 'block';
    financialSection.style.display = 'block';
    updateResidentFields();
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
    document.getElementById('singleRoomFloor').textContent = selected.dataset.floor || '—';
    document.getElementById('singleRoomType').textContent = selected.dataset.type || '—';
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
            <div class="col-md-6"><div class="form-group"><label class="form-label">Phone *</label><input type="text" name="occupants[${index - 1}][phone]" class="form-control" maxlength="12" required></div></div>
            <div class="col-md-6"><div class="form-group"><label class="form-label">Guardian Phone *</label><input type="text" name="occupants[${index - 1}][guardian_phone]" class="form-control" maxlength="12" required></div></div>
        </div>
    `;

    occupantList.appendChild(occupantCard);
    if (window.applyIdentityMasks) window.applyIdentityMasks(occupantCard);
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

['input', 'change'].forEach((eventName) => {
    document.getElementById('monthlyFeeInput').addEventListener(eventName, updateFirstMonthPreview);
    firstMonthMode.addEventListener(eventName, function() {
        document.getElementById('manualAdjustmentFields').style.display = firstMonthMode.value === 'manual' ? 'block' : 'none';
        updateFirstMonthPreview();
    });
    firstMonthDiscount.addEventListener(eventName, updateFirstMonthPreview);
    document.querySelectorAll('input[name="joining_date"]').forEach((field) => field.addEventListener(eventName, updateFirstMonthPreview));
});

function updateResidentFields() {
    const residentType = document.getElementById('residentType');
    const collegeWrap = document.getElementById('collegeUniversityWrapper');
    const jobWrap = document.getElementById('jobWorkplaceWrapper');
    const vehicleType = document.getElementById('vehicleType');
    const vehicleOtherWrap = document.getElementById('vehicleTypeOtherWrapper');
    const residentInfoSection = document.getElementById('residentInfoSection');
    const vehicleInfoSection = document.getElementById('vehicleInfoSection');
    const noteSection = document.getElementById('noteSection');

    if (residentType && residentInfoSection) {
        residentInfoSection.style.display = 'block';
    }
    if (vehicleType && vehicleInfoSection) {
        vehicleInfoSection.style.display = 'block';
    }
    if (noteSection) {
        noteSection.style.display = 'block';
    }

    if (collegeWrap && jobWrap) {
        const type = residentType ? residentType.value : 'Student';
        collegeWrap.style.display = type === 'Student' ? 'block' : 'none';
        jobWrap.style.display = type === 'Job / Working' ? 'block' : 'none';
    }

    if (vehicleType && vehicleOtherWrap) {
        vehicleOtherWrap.style.display = vehicleType.value === 'Other' ? 'block' : 'none';
    }
}

function bindResidentAndVehicleToggles() {
    const residentType = document.getElementById('residentType');
    const vehicleType = document.getElementById('vehicleType');
    if (residentType) {
        residentType.addEventListener('change', updateResidentFields);
    }
    if (vehicleType) {
        vehicleType.addEventListener('change', updateResidentFields);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = '<?php echo $config['base_url']; ?>';
    document.body.dataset.baseUrl = baseUrl;
    document.querySelectorAll('.choice-card').forEach((card) => {
        const radio = card.querySelector('input');
        if (radio && radio.checked) {
            card.classList.add('selected');
        }
    });
    bindResidentAndVehicleToggles();
    fullRoomSelect.value = '';
    singleRoomSelect.value = '';
    setAccomodationMode('');
    updateResidentFields();

    const urlParams = new URLSearchParams(window.location.search);
    const prefillRoomId = urlParams.get('room_id');
    const prefillBedNum = urlParams.get('bed_number');

    if (prefillRoomId) {
        const singleRadio = document.querySelector('input[name="accommodation_type"][value="single"]');
        if (singleRadio) {
            singleRadio.checked = true;
            setAccomodationMode('single');
            singleRoomSelect.value = prefillRoomId;
            if (singleRoomSelect.value === prefillRoomId) {
                populateSingleRoomSummary(singleRoomSelect);
                if (prefillBedNum) {
                    const checkBeds = setInterval(() => {
                        const bedSelect = document.getElementById('singleBedSelect');
                        if (bedSelect && bedSelect.querySelector(`option[value="${prefillBedNum}"]`)) {
                            bedSelect.value = prefillBedNum;
                            clearInterval(checkBeds);
                        }
                    }, 50);
                    setTimeout(() => clearInterval(checkBeds), 3000);
                }
            }
        }
    }
});
</script>
