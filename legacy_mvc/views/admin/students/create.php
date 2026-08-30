<?php $config = require APP_ROOT . '/config/app.php'; ?>
<?php
$roomRepository = new \App\Repositories\RoomRepository();
$roomCatalog = [];
foreach ($roomRepository->findAllWithAvailability() as $room) {
    $roomId = (int)$room['id'];
    $available = max(0, (int)$room['available_beds']);
    $entries = [
        'id' => $roomId,
        'label' => $room['block'] . ' - ' . $room['room_number'],
        'room_type' => $room['room_type'],
        'floor' => $room['floor'],
        'total_beds' => (int)$room['total_beds'],
        'available_beds' => $available,
        'monthly_fee' => (float)$room['monthly_fee'],
        'security_deposit' => (float)$room['security_deposit'],
        'active_allocations' => (int)$room['active_allocations'],
        'effective_occupied_beds' => (int)$room['effective_occupied_beds'],
        'beds' => $roomRepository->getAvailableBedsForRoom($roomId),
    ];
    $roomCatalog[] = $entries;
}
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Add New Student</h3>
        <a href="<?php echo $config['base_url']; ?>/students" class="btn" style="background: #334155; color: white;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <form action="<?php echo $config['base_url']; ?>/students/store" method="POST" id="admissionForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <input type="hidden" id="room_allocation_enabled" name="room_allocation_enabled" value="0">
        <input type="hidden" name="allocation_type" id="allocationTypeHidden" value="BED">

        <div class="row">
            <div class="col-md-8">
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
            </div>

            <div class="col-md-4">
                <div class="card" style="border: 1px solid var(--border); background: #0f172a; color: #e2e8f0;">
                    <div class="card-header" style="background: #1e293b; color: white; border-bottom: 1px solid #334155;">
                        <h5 style="margin: 0;">Admission Summary</h5>
                    </div>
                    <div class="card-body" style="padding: 1rem;">
                        <div><strong>Student:</strong> <span id="summaryStudent">-</span></div>
                        <div><strong>Room:</strong> <span id="summaryRoom">Not selected</span></div>
                        <div><strong>Bed:</strong> <span id="summaryBed">-</span></div>
                        <hr>
                        <div><strong>Monthly Fee:</strong> Rs. <span id="summaryMonthlyFee">0</span></div>
                        <div><strong>Security Deposit:</strong> Rs. <span id="summarySecurityDeposit">0</span></div>
                        <div><strong>Discount:</strong> Rs. <span id="summaryDiscount">0</span></div>
                        <div><strong>First Month Fee:</strong> Rs. <span id="summaryFirstMonth">0</span></div>
                        <div><strong>Initial Payment:</strong> Rs. <span id="summaryInitialPayment">0</span></div>
                        <div><strong>Remaining:</strong> Rs. <span id="summaryRemaining">0</span></div>
                    </div>
                </div>
            </div>
        </div>

        <h4 style="margin-top: 2rem; margin-bottom: 1rem; color: var(--primary); border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">Room Allocation</h4>
        <div class="row">
            <div class="col-md-12 form-group">
                <label class="form-label">
                    <input type="checkbox" id="enableRoomAllocation" name="room_allocation_enabled" value="1"> Enable Room Allocation
                </label>
            </div>
            <div class="col-md-12 form-group room-field" style="display:none;">
                <label class="form-label">Allocation Type</label>
                <div class="row">
                    <div class="col-md-6">
                        <label style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0;">
                            <input type="radio" name="allocation_type" value="BED" checked> <span>Bed</span>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0;">
                            <input type="radio" name="allocation_type" value="FULL_ROOM"> <span>Full Room</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-md-6 form-group room-field" style="display:none;">
                <label class="form-label">Block / Room *</label>
                <select id="roomSelect" name="room_id" class="form-control">
                    <option value="">Select Room</option>
                </select>
            </div>
            <div class="col-md-6 form-group room-field bed-only" style="display:none;">
                <label class="form-label">Bed Number *</label>
                <input type="number" name="bed_number" id="bedNumberInput" class="form-control" min="1" value="1">
                <small id="bedHelp" class="text-muted">Select a room to view available beds.</small>
            </div>
            <div class="col-md-6 form-group room-field" style="display:none;">
                <label class="form-label">Joining Date *</label>
                <input type="date" name="joining_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-6 form-group room-field" style="display:none;">
                <label class="form-label">Allocation Remarks</label>
                <input type="text" name="allocation_remarks" class="form-control" placeholder="Optional remarks">
            </div>
        </div>

        <h4 style="margin-top: 2rem; margin-bottom: 1rem; color: var(--primary); border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">Financial Setup</h4>
        <div class="row">
            <div class="col-md-6 form-group">
                <label class="form-label">Monthly Fee</label>
                <input type="number" step="0.01" min="0" name="monthly_fee" id="monthlyFeeInput" class="form-control" value="0">
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">Security Deposit</label>
                <input type="number" step="0.01" min="0" name="security_deposit" id="securityDepositInput" class="form-control" value="0">
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">Discount</label>
                <input type="number" step="0.01" min="0" name="discount" id="discountInput" class="form-control" value="0">
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">Initial Payment</label>
                <input type="number" step="0.01" min="0" name="initial_payment" id="initialPaymentInput" class="form-control" value="0">
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">Payment Method</label>
                <select name="payment_method" class="form-control">
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Online">Online</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">Transaction Reference</label>
                <input type="text" name="transaction_ref" class="form-control" placeholder="Optional">
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">First Billing Month</label>
                <select name="first_billing_month" class="form-control">
                    <?php for ($i = 1; $i <= 12; $i++): ?><option value="<?php echo $i; ?>" <?php echo $i == date('n') ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $i, 1)); ?></option><?php endfor; ?>
                </select>
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">First Billing Year</label>
                <select name="first_billing_year" class="form-control">
                    <?php for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++): ?><option value="<?php echo $y; ?>" <?php echo $y == date('Y') ? 'selected' : ''; ?>><?php echo $y; ?></option><?php endfor; ?>
                </select>
            </div>
            <div class="col-md-6 form-group">
                <label class="form-label">Due Date</label>
                <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+15 days')); ?>">
            </div>
            <div class="col-md-12 form-group">
                <label class="form-label">Financial Remarks</label>
                <textarea name="financial_remarks" rows="2" class="form-control"></textarea>
            </div>
        </div>

        <div style="margin-top: 2rem;">
            <button type="submit" class="btn btn-primary" id="submitButton"><i class="fas fa-save"></i> Save Student & Setup</button>
        </div>
    </form>
</div>

<script>
(function() {
    const roomCatalog = <?php echo json_encode($roomCatalog, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const roomCheckbox = document.getElementById('enableRoomAllocation');
    const roomFields = document.querySelectorAll('.room-field');
    const bedOnlyFields = document.querySelectorAll('.bed-only');
    const roomSelect = document.getElementById('roomSelect');
    const bedNumberInput = document.getElementById('bedNumberInput');
    const bedHelp = document.getElementById('bedHelp');
    const allocationTypeHidden = document.getElementById('allocationTypeHidden');
    const monthlyFeeInput = document.getElementById('monthlyFeeInput');
    const securityDepositInput = document.getElementById('securityDepositInput');
    const discountInput = document.getElementById('discountInput');
    const initialPaymentInput = document.getElementById('initialPaymentInput');
    const summaryStudent = document.getElementById('summaryStudent');
    const summaryRoom = document.getElementById('summaryRoom');
    const summaryBed = document.getElementById('summaryBed');
    const summaryMonthlyFee = document.getElementById('summaryMonthlyFee');
    const summarySecurityDeposit = document.getElementById('summarySecurityDeposit');
    const summaryDiscount = document.getElementById('summaryDiscount');
    const summaryFirstMonth = document.getElementById('summaryFirstMonth');
    const summaryInitialPayment = document.getElementById('summaryInitialPayment');
    const summaryRemaining = document.getElementById('summaryRemaining');

    function formatMoney(value) {
        return Number(value || 0).toLocaleString('en-PK', {minimumFractionDigits: 0, maximumFractionDigits: 0});
    }

    function refreshSummary() {
        const studentName = document.querySelector('input[name="full_name"]').value.trim();
        const roomOption = roomSelect.selectedOptions[0];
        const monthly = Number(monthlyFeeInput.value || 0);
        const security = Number(securityDepositInput.value || 0);
        const discount = Number(discountInput.value || 0);
        const firstMonth = Math.max(0, monthly - discount);
        const initial = Number(initialPaymentInput.value || 0);
        const remaining = Math.max(0, (firstMonth + security) - initial);

        summaryStudent.textContent = studentName || '-';
        summaryRoom.textContent = roomOption && roomOption.value ? roomOption.text : 'Not selected';
        summaryBed.textContent = roomCheckbox.checked ? (bedNumberInput.value || '-') : '-';
        summaryMonthlyFee.textContent = formatMoney(monthly);
        summarySecurityDeposit.textContent = formatMoney(security);
        summaryDiscount.textContent = formatMoney(discount);
        summaryFirstMonth.textContent = formatMoney(firstMonth);
        summaryInitialPayment.textContent = formatMoney(initial);
        summaryRemaining.textContent = formatMoney(remaining);
    }

    function getEligibleRooms(type) {
        return roomCatalog.filter(room => {
            const availableBeds = Number(room.available_beds || 0);
            if (type === 'FULL_ROOM') {
                return availableBeds >= room.total_beds;
            }
            return availableBeds > 0;
        });
    }

    function renderRoomOptions(type) {
        const options = getEligibleRooms(type);
        roomSelect.innerHTML = '<option value="">Select Room</option>';

        if (!options.length) {
            const empty = document.createElement('option');
            empty.value = '';
            empty.textContent = type === 'FULL_ROOM' ? 'No completely available rooms.' : 'No rooms with available beds.';
            empty.disabled = true;
            empty.selected = true;
            roomSelect.appendChild(empty);
            roomSelect.setAttribute('disabled', 'disabled');
            return;
        }

        roomSelect.removeAttribute('disabled');
        options.forEach(room => {
            const option = document.createElement('option');
            option.value = room.id;
            option.textContent = type === 'FULL_ROOM'
                ? room.label + ' - Completely Available'
                : room.label + ' - ' + room.available_beds + ' / ' + room.total_beds + ' beds available';
            option.dataset.monthlyFee = String(room.monthly_fee || 0);
            option.dataset.securityDeposit = String(room.security_deposit || 0);
            option.dataset.totalBeds = String(room.total_beds || 0);
            option.dataset.availableBeds = String(room.available_beds || 0);
            option.dataset.beds = JSON.stringify(room.beds || []);
            roomSelect.appendChild(option);
        });
    }

    function renderBedOptions(roomId) {
        const option = roomSelect.selectedOptions[0];
        const beds = option && option.dataset.beds ? JSON.parse(option.dataset.beds) : [];

        if (!option || !option.value) {
            bedHelp.textContent = 'Select a room to view available beds.';
            return;
        }

        const monthlyFee = Number(option.dataset.monthlyFee || 0);
        const security = Number(option.dataset.securityDeposit || 0);
        monthlyFeeInput.value = monthlyFee;
        securityDepositInput.value = security;

        if (!beds.length) {
            bedHelp.textContent = 'No available beds in this room.';
            bedNumberInput.value = 0;
            return;
        }

        bedHelp.textContent = 'Available beds: ' + beds.join(', ');
        bedNumberInput.max = String(Number(option.dataset.totalBeds || 0));
        bedNumberInput.value = beds[0];
        if (bedNumberInput.value === '0') {
            bedNumberInput.value = 1;
        }
    }

    function syncAllocationType() {
        const type = document.querySelector('input[name="allocation_type"]:checked')?.value || 'BED';
        allocationTypeHidden.value = type;
        const isBed = type === 'BED';
        bedOnlyFields.forEach(field => {
            field.style.display = isBed ? 'block' : 'none';
        });
        if (!isBed) {
            bedNumberInput.value = 0;
            bedHelp.textContent = 'Full room allocation does not require a bed number.';
            roomSelect.value = '';
        }
        renderRoomOptions(type);
        refreshSummary();
    }

    roomCheckbox.addEventListener('change', function() {
        const enabled = this.checked;
        roomFields.forEach(field => field.style.display = enabled ? 'block' : 'none');
        document.getElementById('room_allocation_enabled').value = enabled ? '1' : '0';
        if (!enabled) {
            roomSelect.value = '';
            bedNumberInput.value = 1;
            bedHelp.textContent = 'Select a room to view available beds.';
        }
        if (enabled) {
            syncAllocationType();
        }
        refreshSummary();
    });

    document.querySelectorAll('input[name="allocation_type"]').forEach(input => {
        input.addEventListener('change', syncAllocationType);
    });

    roomSelect.addEventListener('change', function() {
        const option = this.selectedOptions[0];
        if (!option || !option.value) {
            if (document.querySelector('input[name="allocation_type"]:checked')?.value === 'BED') {
                bedHelp.textContent = 'Select a room to view available beds.';
            }
            return;
        }

        const type = document.querySelector('input[name="allocation_type"]:checked')?.value || 'BED';
        if (type === 'BED') {
            renderBedOptions(Number(option.value));
        } else {
            bedNumberInput.value = 0;
            bedHelp.textContent = 'Full room allocation selected.';
            monthlyFeeInput.value = Number(option.dataset.monthlyFee || 0);
            securityDepositInput.value = Number(option.dataset.securityDeposit || 0);
        }
        refreshSummary();
    });

    [
        'input',
        'change'
    ].forEach(evt => {
        document.querySelector('input[name="full_name"]').addEventListener(evt, refreshSummary);
        monthlyFeeInput.addEventListener(evt, refreshSummary);
        securityDepositInput.addEventListener(evt, refreshSummary);
        discountInput.addEventListener(evt, refreshSummary);
        initialPaymentInput.addEventListener(evt, refreshSummary);
        bedNumberInput.addEventListener(evt, refreshSummary);
    });

    refreshSummary();
})();
</script>
