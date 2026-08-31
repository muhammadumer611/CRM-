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
        <h3 class="card-title"><i class="fas fa-user-plus"></i> New Student Onboarding</h3>
        <a href="<?php echo $config['base_url']; ?>/students" class="btn" style="background:#334155;color:white;">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

<<<<<<< HEAD
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

            <div class="col-md-12 form-group room-field full-room-occupants-section" style="display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                    <label class="form-label" style="margin:0;">Full Room Occupants</label>
                    <button type="button" class="btn btn-sm" id="addOccupantBtn" style="background:#1e293b; color:white;">+ Add Occupant</button>
                </div>
                <small class="text-muted" style="display:block; margin-bottom:0.75rem;">The primary student is the fee-paying tenant. Add each room member here without generating extra monthly hostel invoices.</small>
                <div id="occupantList"></div>
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
=======
    <form action="<?php echo $config['base_url']; ?>/students/store" method="POST" id="onboardForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

        <!-- ===== SECTION 1: PERSONAL INFORMATION ===== -->
        <div style="background:#0f172a;border:1px solid var(--border);border-radius:8px;padding:1.5rem;margin-bottom:1.5rem;">
            <h4 style="color:var(--primary);margin-bottom:1.25rem;"><i class="fas fa-user"></i> Personal Information</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" class="form-control" required placeholder="e.g. Muhammad Ali Khan" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">CNIC * (13 digits)</label>
                        <input type="text" name="cnic" class="form-control" required placeholder="e.g. 12345-1234567-1" maxlength="15" value="<?php echo htmlspecialchars($_POST['cnic'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Phone Number *</label>
                        <input type="text" name="phone" class="form-control" required placeholder="e.g. 03001234567" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="Optional" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Blood Group</label>
                        <select name="blood_group" class="form-control">
                            <option value="">Select...</option>
                            <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                                <option value="<?php echo $bg; ?>" <?php echo (($_POST['blood_group'] ?? '') === $bg) ? 'selected' : ''; ?>><?php echo $bg; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Address *</label>
                        <input type="text" name="address" class="form-control" required placeholder="Full address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== SECTION 2: GUARDIAN INFORMATION ===== -->
        <div style="background:#0f172a;border:1px solid var(--border);border-radius:8px;padding:1.5rem;margin-bottom:1.5rem;">
            <h4 style="color:var(--primary);margin-bottom:1.25rem;"><i class="fas fa-users"></i> Guardian Information</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Guardian Name *</label>
                        <input type="text" name="guardian_name" class="form-control" required placeholder="Guardian's full name" value="<?php echo htmlspecialchars($_POST['guardian_name'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Relation *</label>
                        <select name="relation" class="form-control" required>
                            <option value="">Select...</option>
                            <?php foreach(['Father','Mother','Brother','Sister','Uncle','Aunt','Spouse','Other'] as $rel): ?>
                                <option value="<?php echo $rel; ?>" <?php echo (($_POST['relation'] ?? '') === $rel) ? 'selected' : ''; ?>><?php echo $rel; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Guardian Phone *</label>
                        <input type="text" name="guardian_phone" class="form-control" required placeholder="e.g. 03001234567" value="<?php echo htmlspecialchars($_POST['guardian_phone'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Guardian CNIC *</label>
                        <input type="text" name="guardian_cnic" class="form-control" required placeholder="e.g. 12345-1234567-1" maxlength="15" value="<?php echo htmlspecialchars($_POST['guardian_cnic'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label">Guardian Address</label>
                        <input type="text" name="guardian_address" class="form-control" placeholder="Optional" value="<?php echo htmlspecialchars($_POST['guardian_address'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== SECTION 3: ROOM ALLOCATION ===== -->
        <div style="background:#0f172a;border:1px solid var(--border);border-radius:8px;padding:1.5rem;margin-bottom:1.5rem;">
            <h4 style="color:var(--primary);margin-bottom:0.5rem;"><i class="fas fa-bed"></i> Room & Bed Allocation <span style="font-size:0.8rem;color:var(--text-muted);font-weight:400;">(Optional — can be done later)</span></h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Select Room</label>
                        <select name="room_id" id="roomSelect" class="form-control" onchange="loadBeds(this.value)">
                            <option value="">— No room (assign later) —</option>
                            <?php foreach($rooms as $room): ?>
                                <option value="<?php echo $room['id']; ?>"
                                    data-total="<?php echo $room['total_beds']; ?>"
                                    data-occupied="<?php echo $room['occupied_beds']; ?>"
                                    data-fee="<?php echo $room['monthly_fee']; ?>"
                                    data-deposit="<?php echo $room['security_deposit']; ?>"
                                    <?php echo (($_POST['room_id'] ?? '') == $room['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($room['block'] . '-' . $room['room_number']); ?>
                                    (<?php echo $room['room_type']; ?> | <?php echo $room['total_beds'] - $room['occupied_beds']; ?> beds free)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Joining Date</label>
                        <input type="date" name="joining_date" id="joiningDate" class="form-control" value="<?php echo htmlspecialchars($_POST['joining_date'] ?? date('Y-m-d')); ?>">
                    </div>
                </div>
            </div>

            <!-- Room Summary Box -->
            <div id="roomSummary" style="display:none;background:#1e293b;border:1px solid var(--border);border-radius:6px;padding:1rem;margin-bottom:1rem;">
                <div style="display:flex;gap:2rem;flex-wrap:wrap;">
                    <div><span style="color:var(--text-muted);font-size:0.8rem;">TOTAL BEDS</span><br><strong id="rsTotalBeds">—</strong></div>
                    <div><span style="color:var(--text-muted);font-size:0.8rem;">OCCUPIED</span><br><strong id="rsOccupied" style="color:#f59e0b;">—</strong></div>
                    <div><span style="color:var(--text-muted);font-size:0.8rem;">AVAILABLE</span><br><strong id="rsAvailable" style="color:#10b981;">—</strong></div>
                    <div><span style="color:var(--text-muted);font-size:0.8rem;">ROOM FEE</span><br><strong id="rsRoomFee" style="color:var(--primary);">—</strong></div>
                </div>
            </div>

            <!-- Bed Selector -->
            <div id="bedSelectorWrap" style="display:none;">
                <label class="form-label" style="margin-bottom:0.75rem;">Select Bed <span style="color:var(--danger);">*</span></label>
                <div id="bedSelectorLoading" style="display:none;color:var(--text-muted);padding:1rem;">Loading beds...</div>
                <div id="bedGrid" style="display:flex;flex-wrap:wrap;gap:0.75rem;margin-bottom:1rem;"></div>
                <input type="hidden" name="bed_number" id="bedNumberInput" value="">
                <div id="bedSelectedDisplay" style="display:none;padding:0.5rem 0.75rem;background:rgba(56,189,248,0.1);border-radius:6px;color:var(--primary);font-weight:600;font-size:0.9rem;margin-top:0.5rem;">
                    <i class="fas fa-check-circle"></i> <span id="bedSelectedText"></span>
                </div>
            </div>
        </div>

        <!-- ===== SECTION 4: FINANCIAL DETAILS ===== -->
        <div style="background:#0f172a;border:1px solid var(--border);border-radius:8px;padding:1.5rem;margin-bottom:1.5rem;">
            <h4 style="color:var(--primary);margin-bottom:1.25rem;"><i class="fas fa-rupee-sign"></i> Financial Details</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Monthly Fee (Rs.) <span id="feeHint" style="color:var(--text-muted);font-size:0.8rem;"></span></label>
                        <input type="number" name="monthly_fee" id="monthlyFeeInput" class="form-control" step="0.01" min="0" placeholder="Leave blank to use room's fee" value="<?php echo htmlspecialchars($_POST['monthly_fee'] ?? ''); ?>">
                        <small style="color:var(--text-muted);">This is the student's fixed monthly fee. Changing the room's fee later will NOT affect this student.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Security Deposit (Rs.)</label>
                        <input type="number" name="security_deposit" id="securityDepositInput" class="form-control" step="0.01" min="0" placeholder="0" value="<?php echo htmlspecialchars($_POST['security_deposit'] ?? ''); ?>">
                        <small style="color:var(--text-muted);">Held separately. Never included in monthly fee calculations.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== SUBMIT ===== -->
        <div style="display:flex;gap:1rem;justify-content:flex-end;">
            <a href="<?php echo $config['base_url']; ?>/students" class="btn" style="background:#334155;color:white;">Cancel</a>
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <i class="fas fa-user-plus"></i> Onboard Student
            </button>
>>>>>>> 962ef01 (Update HMS)
        </div>
    </form>
</div>

<<<<<<< HEAD
<script>
(function() {
    const roomCatalog = <?php echo json_encode($roomCatalog, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const roomCheckbox = document.getElementById('enableRoomAllocation');
    const roomFields = document.querySelectorAll('.room-field');
    const bedOnlyFields = document.querySelectorAll('.bed-only');
    const fullRoomOccupantsSection = document.querySelector('.full-room-occupants-section');
    const occupantList = document.getElementById('occupantList');
    const addOccupantBtn = document.getElementById('addOccupantBtn');
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

    function buildOccupantRow(index) {
        const wrapper = document.createElement('div');
        wrapper.className = 'row';
        wrapper.style.marginBottom = '0.75rem';
        wrapper.innerHTML = `
            <div class="col-md-3 form-group">
                <label class="form-label">Name</label>
                <input type="text" name="room_occupants[${index}][full_name]" class="form-control" placeholder="Occupant name">
            </div>
            <div class="col-md-3 form-group">
                <label class="form-label">CNIC</label>
                <input type="text" name="room_occupants[${index}][cnic]" class="form-control" pattern="[0-9]{13}" placeholder="13 digits">
            </div>
            <div class="col-md-2 form-group">
                <label class="form-label">Phone</label>
                <input type="text" name="room_occupants[${index}][phone]" class="form-control" placeholder="03xx...">
            </div>
            <div class="col-md-3 form-group">
                <label class="form-label">Relation</label>
                <input type="text" name="room_occupants[${index}][relation]" class="form-control" placeholder="Brother / Friend">
            </div>
            <div class="col-md-1 form-group" style="display:flex; align-items:flex-end;">
                <button type="button" class="btn btn-sm btn-outline-danger remove-occupant-btn" aria-label="Remove occupant">Remove</button>
            </div>
        `;

        wrapper.querySelector('.remove-occupant-btn').addEventListener('click', function() {
            wrapper.remove();
        });

        return wrapper;
    }

    function ensureDefaultOccupantRows() {
        if (!occupantList) return;
        const currentRows = occupantList.querySelectorAll('.row').length;
        if (currentRows >= 2) return;

        for (let i = currentRows; i < 2; i++) {
            occupantList.appendChild(buildOccupantRow(i));
        }
    }

    function syncAllocationType() {
        const type = document.querySelector('input[name="allocation_type"]:checked')?.value || 'BED';
        allocationTypeHidden.value = type;
        const isBed = type === 'BED';
        bedOnlyFields.forEach(field => {
            field.style.display = isBed ? 'block' : 'none';
        });
        if (fullRoomOccupantsSection) {
            fullRoomOccupantsSection.style.display = isBed ? 'none' : 'block';
        }
        if (!isBed) {
            bedNumberInput.value = 0;
            bedHelp.textContent = 'Full room allocation does not require a bed number.';
            roomSelect.value = '';
            ensureDefaultOccupantRows();
        }
        renderRoomOptions(type);
        refreshSummary();
    }

    if (addOccupantBtn) {
        addOccupantBtn.addEventListener('click', function() {
            const nextIndex = occupantList.querySelectorAll('.row').length;
            occupantList.appendChild(buildOccupantRow(nextIndex));
        });
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

    if (occupantList) {
        ensureDefaultOccupantRows();
    }

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
=======
<style>
.bed-btn {
    width: 70px; height: 70px;
    border-radius: 8px;
    border: 2px solid var(--border);
    background: #1e293b;
    color: var(--text);
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    transition: all 0.2s;
}
.bed-btn:hover:not(:disabled) {
    border-color: var(--primary);
    background: rgba(56,189,248,0.1);
    color: var(--primary);
}
.bed-btn.selected {
    border-color: var(--primary);
    background: rgba(56,189,248,0.15);
    color: var(--primary);
}
.bed-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: rgba(239,68,68,0.08);
    border-color: rgba(239,68,68,0.3);
    color: #fca5a5;
}
.bed-btn .bed-label { font-size: 0.7rem; color: var(--text-muted); }
.bed-btn.selected .bed-label { color: var(--primary); }
.bed-btn:disabled .bed-label { color: #fca5a5; }
</style>

<script>
function loadBeds(roomId) {
    const bedWrap     = document.getElementById('bedSelectorWrap');
    const bedGrid     = document.getElementById('bedGrid');
    const bedInput    = document.getElementById('bedNumberInput');
    const bedDisplay  = document.getElementById('bedSelectedDisplay');
    const bedText     = document.getElementById('bedSelectedText');
    const loading     = document.getElementById('bedSelectorLoading');
    const roomSummary = document.getElementById('roomSummary');
    const feeInput    = document.getElementById('monthlyFeeInput');
    const sdInput     = document.getElementById('securityDepositInput');
    const feeHint     = document.getElementById('feeHint');

    // Reset
    bedInput.value = '';
    bedDisplay.style.display = 'none';
    bedText.textContent = '';
    bedGrid.innerHTML  = '';

    if (!roomId) {
        bedWrap.style.display = 'none';
        roomSummary.style.display = 'none';
        feeHint.textContent = '';
        return;
    }

    // Populate room summary from data attributes
    const sel    = document.getElementById('roomSelect');
    const opt    = sel.options[sel.selectedIndex];
    const total  = parseInt(opt.dataset.total || 0);
    const occ    = parseInt(opt.dataset.occupied || 0);
    const avail  = total - occ;
    const roomFee = parseFloat(opt.dataset.fee || 0);
    const roomSD  = parseFloat(opt.dataset.deposit || 0);

    document.getElementById('rsTotalBeds').textContent = total;
    document.getElementById('rsOccupied').textContent = occ;
    document.getElementById('rsAvailable').textContent = avail;
    document.getElementById('rsRoomFee').textContent = 'Rs. ' + roomFee.toLocaleString();
    roomSummary.style.display = 'block';

    feeHint.textContent = `(Room default: Rs. ${roomFee.toLocaleString()})`;

    // Auto-fill fee & deposit if empty
    if (!feeInput.value) feeInput.placeholder = `Default: Rs. ${roomFee.toLocaleString()}`;
    if (!sdInput.value && roomSD > 0) sdInput.placeholder = `Suggested: Rs. ${roomSD.toLocaleString()}`;

    // Load beds via AJAX
    bedWrap.style.display = 'block';
    bedGrid.style.display = 'none';
    loading.style.display = 'block';

    fetch(`<?php echo $config['base_url']; ?>/api/allocations/available-beds/${roomId}`)
        .then(r => r.json())
        .then(res => {
            loading.style.display = 'none';
            bedGrid.style.display = 'flex';

            if (!res.success) {
                bedGrid.innerHTML = '<p style="color:var(--danger);">Failed to load beds: ' + (res.message || 'Unknown error') + '</p>';
                return;
            }

            const beds = res.data.beds || [];
            beds.forEach(bed => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'bed-btn';
                btn.innerHTML = `<i class="fas fa-bed"></i><span>${bed.bed_number}</span><span class="bed-label">${bed.occupied ? 'Occupied' : 'Free'}</span>`;
                if (bed.occupied) {
                    btn.disabled = true;
                    btn.title = 'Occupied by: ' + (bed.student_name || 'Another student');
                } else {
                    btn.onclick = function() {
                        document.querySelectorAll('.bed-btn').forEach(b => b.classList.remove('selected'));
                        btn.classList.add('selected');
                        bedInput.value = bed.bed_number;
                        bedDisplay.style.display = 'block';
                        bedText.textContent = 'Bed ' + bed.bed_number + ' selected';
                    };
                }
                bedGrid.appendChild(btn);
            });
        })
        .catch(e => {
            loading.style.display = 'none';
            bedGrid.style.display = 'flex';
            bedGrid.innerHTML = '<p style="color:var(--danger);">Failed to load beds. Please refresh.</p>';
        });
}

// If room was pre-selected (after form submission error), reload beds
window.addEventListener('DOMContentLoaded', function() {
    const roomId = document.getElementById('roomSelect').value;
    if (roomId) loadBeds(roomId);
});

// Form validation
document.getElementById('onboardForm').addEventListener('submit', function(e) {
    const roomId   = document.getElementById('roomSelect').value;
    const bedInput = document.getElementById('bedNumberInput').value;
    if (roomId && !bedInput) {
        e.preventDefault();
        alert('Please select a bed from the bed selector before submitting.');
        return false;
    }
});
>>>>>>> 962ef01 (Update HMS)
</script>
