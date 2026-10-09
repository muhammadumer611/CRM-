<?php
$config = require APP_ROOT . '/config/app.php';
$alumni = $data['alumni'];
$student = $data['student'];
$previousStay = $data['previous_stay'];
$securityDeposit = $data['security_deposit'];
$rooms = $data['rooms'];
$billingSettings = $data['billing_settings'];
$oldInput = $oldInput ?? [];
$escape = static function ($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
};
$joiningDate = $oldInput['joining_date'] ?? date('Y-m-d');
$selectedRoomId = (string)($oldInput['room_id'] ?? '');
$selectedBed = (string)($oldInput['bed_number'] ?? '');
$billingMode = $oldInput['first_month_billing_mode'] ?? 'full';
$securityStatus = strtoupper((string)($securityDeposit['status'] ?? 'NO RECORD'));
?>
<style>
    .readmit-shell { max-width: 1080px; margin: 0 auto; }
    .readmit-hero { position: relative; overflow: hidden; padding: clamp(1.4rem, 4vw, 2.3rem); border-radius: 20px; background: linear-gradient(120deg, rgba(14,165,233,.2), rgba(37,99,235,.12) 58%, rgba(15,23,42,.15)); border: 1px solid rgba(56,189,248,.28); margin-bottom: 1.25rem; }
    .readmit-hero:after { content: ""; position: absolute; width: 260px; height: 260px; border-radius: 50%; right: -80px; top: -150px; background: rgba(56,189,248,.11); }
    .readmit-eyebrow { color: var(--primary); text-transform: uppercase; letter-spacing: .11em; font-size: .72rem; font-weight: 800; }
    .readmit-hero h2 { margin: .55rem 0 .4rem; font-size: clamp(1.4rem, 3vw, 2rem); }
    .readmit-hero p { color: var(--text-muted); margin: 0; max-width: 700px; }
    .readmit-id { display: inline-flex; align-items: center; gap: .45rem; margin-top: 1rem; padding: .45rem .75rem; border-radius: 999px; background: rgba(56,189,248,.12); color: var(--primary); font-size: .82rem; font-weight: 700; }
    .readmit-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
    .readmit-card { min-width: 0; padding: clamp(1rem, 2.5vw, 1.4rem); border: 1px solid var(--border); border-radius: 16px; background: var(--card); box-shadow: 0 8px 24px var(--shadow-color); }
    .readmit-card-full { grid-column: 1 / -1; }
    .readmit-card h3 { display: flex; align-items: center; gap: .65rem; margin: 0 0 1.1rem; font-size: 1rem; }
    .readmit-card h3 i { color: var(--primary); }
    .readmit-details { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .9rem 1.25rem; }
    .readmit-detail { min-width: 0; }
    .readmit-detail span { display: block; overflow-wrap: anywhere; }
    .readmit-detail .label { color: var(--text-muted); font-size: .68rem; text-transform: uppercase; letter-spacing: .08em; font-weight: 800; margin-bottom: .25rem; }
    .readmit-detail .value { color: var(--text); font-size: .9rem; font-weight: 600; line-height: 1.5; }
    .readmit-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
    .readmit-form-grid .form-group { min-width: 0; margin: 0; }
    .readmit-form-grid .form-group-full { grid-column: 1 / -1; }
    .readmit-form-grid .form-control { width: 100%; }
    .readmit-help { margin-top: .4rem; color: var(--text-muted); font-size: .78rem; line-height: 1.55; }
    .readmit-callout { display: flex; align-items: flex-start; gap: .75rem; padding: .9rem 1rem; border: 1px solid rgba(56,189,248,.24); border-radius: 12px; background: rgba(56,189,248,.07); color: var(--text-secondary); font-size: .84rem; line-height: 1.55; }
    .readmit-callout i { color: var(--primary); margin-top: .15rem; }
    .readmit-callout.security { border-color: rgba(245,158,11,.28); background: rgba(245,158,11,.07); }
    .readmit-callout.security i { color: var(--warning); }
    .billing-preview { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .7rem; margin-top: 1rem; }
    .billing-preview > div { padding: .85rem; border: 1px solid var(--border); border-radius: 12px; background: var(--surface-muted); }
    .billing-preview small { display: block; color: var(--text-muted); font-size: .69rem; text-transform: uppercase; letter-spacing: .06em; font-weight: 800; }
    .billing-preview strong { display: block; margin-top: .25rem; font-size: 1rem; }
    .billing-preview .total strong { color: var(--primary); font-size: 1.2rem; }
    .readmit-review { padding: 1rem; margin-top: 1rem; border: 1px solid rgba(16,185,129,.25); border-radius: 12px; background: rgba(16,185,129,.06); }
    .readmit-review label { display: flex; align-items: flex-start; gap: .65rem; line-height: 1.5; font-size: .85rem; }
    .readmit-actions { display: flex; justify-content: space-between; align-items: center; gap: .75rem; flex-wrap: wrap; margin-top: 1.25rem; }
    .readmit-actions .action-right { display: flex; gap: .65rem; flex-wrap: wrap; justify-content: flex-end; }
    #bedFeedback { min-height: 1.25rem; color: var(--text-muted); font-size: .8rem; }
    @media (max-width: 700px) {
        .readmit-grid, .readmit-details, .readmit-form-grid { grid-template-columns: 1fr; }
        .readmit-card-full, .readmit-form-grid .form-group-full { grid-column: auto; }
        .billing-preview { grid-template-columns: 1fr; }
        .readmit-actions { align-items: stretch; }
        .readmit-actions > *, .readmit-actions .action-right { width: 100%; }
        .readmit-actions .action-right .btn { flex: 1; justify-content: center; }
    }
</style>

<div class="readmit-shell">
    <section class="readmit-hero">
        <div class="readmit-eyebrow"><i class="fa-solid fa-arrow-rotate-left"></i> Alumni Re-Admission</div>
        <h2>Welcome a student back</h2>
        <p>Review their previous stay, choose a return date and currently available bed, then confirm the new stay. The student's profile and prior records remain intact.</p>
        <div class="readmit-id"><i class="fa-solid fa-id-card"></i> <?php echo $escape($student['student_id_str']); ?> <span aria-hidden="true">·</span> <?php echo $escape($student['full_name']); ?></div>
    </section>

    <?php if (empty($rooms)): ?>
        <div class="readmit-callout security"><i class="fa-solid fa-triangle-exclamation"></i><div>No rooms currently have an available, unreserved bed. The student's records have not been changed.</div></div>
    <?php endif; ?>

    <form action="<?php echo $escape($config['base_url']); ?>/alumni/readmit/<?php echo (int)$alumni['id']; ?>" method="POST" id="readmissionForm">
        <input type="hidden" name="csrf_token" value="<?php echo $escape($csrf_token); ?>">
        <div class="readmit-grid">
            <section class="readmit-card">
                <h3><i class="fa-solid fa-user-graduate"></i> Student information</h3>
                <div class="readmit-details">
                    <div class="readmit-detail"><span class="label">Full name</span><span class="value"><?php echo $escape($student['full_name']); ?></span></div>
                    <div class="readmit-detail"><span class="label">Student ID</span><span class="value"><?php echo $escape($student['student_id_str']); ?></span></div>
                    <div class="readmit-detail"><span class="label">CNIC</span><span class="value"><?php echo $escape($student['cnic']); ?></span></div>
                    <div class="readmit-detail"><span class="label">Phone</span><span class="value"><?php echo $escape($student['phone']); ?></span></div>
                    <div class="readmit-detail"><span class="label">Email</span><span class="value"><?php echo $escape($student['email'] ?: '—'); ?></span></div>
                    <div class="readmit-detail"><span class="label">Current status</span><span class="value">Inactive / Alumni</span></div>
                </div>
            </section>

            <section class="readmit-card">
                <h3><i class="fa-solid fa-clock-rotate-left"></i> Previous stay</h3>
                <div class="readmit-details">
                    <div class="readmit-detail"><span class="label">Previous room</span><span class="value"><?php echo $escape(($previousStay['block'] ?? $alumni['previous_room'] ?? '') . (isset($previousStay['room_number']) ? '-' . $previousStay['room_number'] : '')); ?></span></div>
                    <div class="readmit-detail"><span class="label">Previous bed</span><span class="value"><?php echo $escape($previousStay['bed_number'] ?? $alumni['previous_bed'] ?? '—'); ?></span></div>
                    <div class="readmit-detail"><span class="label">Previous admission</span><span class="value"><?php echo $escape($previousStay['joining_date'] ?? $alumni['joining_date'] ?? '—'); ?></span></div>
                    <div class="readmit-detail"><span class="label">Checkout date</span><span class="value"><?php echo $escape($previousStay['leaving_date'] ?? $alumni['leaving_date'] ?? '—'); ?></span></div>
                    <div class="readmit-detail"><span class="label">Checkout reason</span><span class="value"><?php echo $escape($alumni['leaving_reason'] ?? '—'); ?></span></div>
                    <div class="readmit-detail"><span class="label">Previous monthly fee</span><span class="value">Rs. <?php echo number_format((float)($student['monthly_fee'] ?? 0), 2); ?></span></div>
                </div>
            </section>

            <section class="readmit-card readmit-card-full">
                <h3><i class="fa-solid fa-shield-halved"></i> Existing security deposit</h3>
                <?php if ($securityDeposit): ?>
                    <div class="readmit-details" style="margin-bottom:1rem;">
                        <div class="readmit-detail"><span class="label">Original security</span><span class="value">Rs. <?php echo number_format((float)$securityDeposit['original_amount'], 2); ?></span></div>
                        <div class="readmit-detail"><span class="label">Current balance</span><span class="value">Rs. <?php echo number_format((float)$securityDeposit['remaining_amount'], 2); ?></span></div>
                        <div class="readmit-detail"><span class="label">Ledger status</span><span class="value"><?php echo $escape($securityStatus); ?></span></div>
                        <div class="readmit-detail"><span class="label">Expected payment date</span><span class="value"><?php echo $escape($securityDeposit['expected_payment_date'] ?: '—'); ?></span></div>
                    </div>
                <?php else: ?>
                    <div class="readmit-details" style="margin-bottom:1rem;"><div class="readmit-detail"><span class="label">Ledger status</span><span class="value">No security deposit record</span></div></div>
                <?php endif; ?>
                <div class="readmit-callout security">
                    <i class="fa-solid fa-lock"></i>
                    <div>The previous security deposit and its transaction history will not be changed or reused. Re-admission does not create a new security deposit automatically.</div>
                </div>
            </section>

            <section class="readmit-card readmit-card-full">
                <h3><i class="fa-solid fa-bed"></i> New stay details</h3>
                <div class="readmit-form-grid">
                    <div class="form-group">
                        <label class="form-label" for="joiningDate">Return / admission date *</label>
                        <input class="form-control" type="date" id="joiningDate" name="joining_date"
                               min="<?php echo $escape($previousStay['leaving_date']); ?>"
                               max="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo $escape($joiningDate); ?>" required>
                        <div class="readmit-help">Must be on or after the previous checkout date and cannot be in the future.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="allocatedBy">Processed by *</label>
                        <input class="form-control" type="text" id="allocatedBy" name="allocated_by_name"
                               value="<?php echo $escape($oldInput['allocated_by_name'] ?? $processed_by_name); ?>" maxlength="100" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="roomSelect">Available room *</label>
                        <select class="form-control" id="roomSelect" name="room_id" required>
                            <option value="">Choose an available room</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo (int)$room['id']; ?>"
                                        data-fee="<?php echo $escape(number_format((float)$room['monthly_fee'], 2, '.', '')); ?>"
                                        data-label="<?php echo $escape($room['block'] . '-' . $room['room_number']); ?>"
                                        <?php echo $selectedRoomId === (string)$room['id'] ? 'selected' : ''; ?>>
                                    <?php echo $escape('Block ' . $room['block'] . ' · Room ' . $room['room_number'] . ' · ' . $room['available_beds'] . ' of ' . $room['total_beds'] . ' beds free'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="readmit-help">Only rooms with current capacity are listed. Bed availability is checked again during save.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="bedSelect">Available bed *</label>
                        <select class="form-control" id="bedSelect" name="bed_number" required>
                            <option value="">Select a room first</option>
                        </select>
                        <div id="bedFeedback" role="status"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="monthlyFee">Current applicable monthly fee</label>
                        <input class="form-control" type="text" id="monthlyFee" readonly value="Select a room">
                        <div class="readmit-help">Uses the selected room's current monthly rate for future calendar-month billing.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="billingMode">First-month billing *</label>
                        <select class="form-control" id="billingMode" name="first_month_billing_mode">
                            <option value="full" <?php echo $billingMode === 'full' ? 'selected' : ''; ?>>Full monthly fee</option>
                            <?php if (($billingSettings['manual_first_month_discount_enabled'] ?? '1') === '1'): ?>
                                <option value="manual" <?php echo $billingMode === 'manual' ? 'selected' : ''; ?>>Manual adjustment</option>
                            <?php endif; ?>
                        </select>
                        <div class="readmit-help">Applies the same first-month options and calendar-month billing rules used for admission.</div>
                    </div>
                    <div class="form-group" id="discountGroup" style="display:none;">
                        <label class="form-label" for="firstMonthDiscount">First-month adjustment (Rs.)</label>
                        <input class="form-control" type="number" id="firstMonthDiscount" name="first_month_discount"
                               min="0" step="0.01" value="<?php echo $escape($oldInput['first_month_discount'] ?? '0'); ?>">
                    </div>
                    <div class="form-group" id="discountReasonGroup" style="display:none;">
                        <label class="form-label" for="discountReason">Adjustment reason</label>
                        <input class="form-control" type="text" id="discountReason" name="first_month_discount_reason"
                               maxlength="255" value="<?php echo $escape($oldInput['first_month_discount_reason'] ?? ''); ?>">
                    </div>
                    <div class="form-group form-group-full">
                        <label class="form-label" for="remarks">Stay remarks (optional)</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3" maxlength="2000"><?php echo $escape($oldInput['remarks'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="billing-preview" aria-live="polite">
                    <div><small>Billing period</small><strong id="billingPeriod">—</strong></div>
                    <div><small>Monthly fee</small><strong id="previewFee">Rs. 0.00</strong></div>
                    <div class="total"><small>First-month amount</small><strong id="previewTotal">Rs. 0.00</strong></div>
                </div>
                <div class="readmit-help" id="billingNote">Any existing invoice in this calendar month is preserved; a duplicate invoice will not be created.</div>

                <div class="readmit-review">
                    <label>
                        <input type="checkbox" id="reviewConfirmed" required>
                        <span>I reviewed the student identity, return date, room/bed, monthly fee, and first-month billing before confirming this re-admission.</span>
                    </label>
                </div>

                <div class="readmit-actions">
                    <a class="btn" href="<?php echo $escape($config['base_url']); ?>/alumni" style="background:#334155;color:#fff;"><i class="fa-solid fa-arrow-left"></i> Back to Alumni</a>
                    <div class="action-right">
                        <button class="btn btn-primary" type="submit" id="confirmReadmission" <?php echo empty($rooms) ? 'disabled' : ''; ?>>
                            <i class="fa-solid fa-user-check"></i> Confirm Re-Admission
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </form>
</div>

<script>
(() => {
    const baseUrl = <?php echo json_encode($config['base_url']); ?>;
    const roomSelect = document.getElementById('roomSelect');
    const bedSelect = document.getElementById('bedSelect');
    const bedFeedback = document.getElementById('bedFeedback');
    const monthlyFee = document.getElementById('monthlyFee');
    const joiningDate = document.getElementById('joiningDate');
    const modeSelect = document.getElementById('billingMode');
    const discountInput = document.getElementById('firstMonthDiscount');
    const discountReason = document.getElementById('discountReason');
    const discountGroup = document.getElementById('discountGroup');
    const discountReasonGroup = document.getElementById('discountReasonGroup');
    const form = document.getElementById('readmissionForm');
    const confirmButton = document.getElementById('confirmReadmission');
    const priorBed = <?php echo json_encode($selectedBed); ?>;
    const billingSettings = <?php echo json_encode([
        'rounding_method' => $billingSettings['rounding_method'] ?? 'nearest_rupee',
        'maximum_first_month_discount' => (float)($billingSettings['maximum_first_month_discount'] ?? 100)
    ]); ?>;
    let activeRequest = null;

    function selectedRoomFee() {
        const option = roomSelect.options[roomSelect.selectedIndex];
        return option && option.value ? Number(option.dataset.fee || 0) : 0;
    }

    function updateBillingPreview() {
        const fee = selectedRoomFee();
        const dateValue = joiningDate.value;
        const period = dateValue
            ? new Intl.DateTimeFormat(undefined, { month: 'long', year: 'numeric', timeZone: 'UTC' }).format(new Date(dateValue + 'T00:00:00Z'))
            : 'Select return date';
        let discount = 0;
        if (modeSelect.value === 'manual' && discountInput) {
            const entered = Number(discountInput.value || 0);
            const maxPercent = Math.max(0, Math.min(100, Number(billingSettings.maximum_first_month_discount || 0)));
            discount = Math.min(Math.max(0, entered), fee, fee * maxPercent / 100);
            discount = billingSettings.rounding_method === 'nearest_rupee' ? Math.round(discount) : Math.round(discount * 100) / 100;
        }
        document.getElementById('billingPeriod').textContent = period;
        document.getElementById('previewFee').textContent = `Rs. ${fee.toFixed(2)}`;
        document.getElementById('previewTotal').textContent = `Rs. ${Math.max(0, fee - discount).toFixed(2)}`;
        discountGroup.style.display = modeSelect.value === 'manual' ? 'block' : 'none';
        discountReasonGroup.style.display = modeSelect.value === 'manual' ? 'block' : 'none';
        if (discountInput) {
            discountInput.max = fee.toFixed(2);
            discountInput.required = modeSelect.value === 'manual';
        }
        if (discountReason) discountReason.required = modeSelect.value === 'manual' && discount > 0;
    }

    async function loadBeds(preserveSelection) {
        const roomId = roomSelect.value;
        bedSelect.innerHTML = '<option value="">Select an available bed</option>';
        bedSelect.disabled = true;
        bedFeedback.textContent = '';
        monthlyFee.value = selectedRoomFee() > 0 ? `Rs. ${selectedRoomFee().toFixed(2)}` : 'Select a room';
        updateBillingPreview();
        if (!roomId) return;

        if (activeRequest) activeRequest.abort();
        activeRequest = new AbortController();
        bedFeedback.textContent = 'Checking live bed availability…';
        try {
            const response = await fetch(`${baseUrl}/api/allocations/available-beds/${encodeURIComponent(roomId)}`, {
                credentials: 'same-origin',
                signal: activeRequest.signal
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Could not load bed availability.');
            const beds = result.data.available_bed_numbers || [];
            beds.forEach((bed) => {
                const option = document.createElement('option');
                option.value = String(bed);
                option.textContent = `Bed ${bed} · Available`;
                if (preserveSelection && String(bed) === priorBed) option.selected = true;
                bedSelect.appendChild(option);
            });
            bedSelect.disabled = beds.length === 0;
            bedFeedback.textContent = beds.length ? `${beds.length} available bed${beds.length === 1 ? '' : 's'}. Availability is rechecked when you confirm.` : 'No beds are currently available in this room.';
        } catch (error) {
            if (error.name === 'AbortError') return;
            bedFeedback.textContent = error.message || 'Failed to check bed availability.';
            bedFeedback.style.color = 'var(--danger)';
        }
    }

    roomSelect.addEventListener('change', () => loadBeds(false));
    joiningDate.addEventListener('change', updateBillingPreview);
    modeSelect.addEventListener('change', updateBillingPreview);
    if (discountInput) discountInput.addEventListener('input', updateBillingPreview);
    if (discountReason) discountReason.addEventListener('input', updateBillingPreview);
    updateBillingPreview();
    if (roomSelect.value) loadBeds(true);

    form.addEventListener('submit', (event) => {
        if (!bedSelect.value) {
            event.preventDefault();
            bedFeedback.textContent = 'Select an available bed before continuing.';
            bedFeedback.style.color = 'var(--danger)';
            bedSelect.focus();
            return;
        }
        if (modeSelect.value === 'manual' && Number(discountInput.value || 0) > 0 && !discountReason.value.trim()) {
            event.preventDefault();
            discountReason.focus();
            return;
        }
        const selectedRoom = roomSelect.options[roomSelect.selectedIndex];
        const roomName = selectedRoom ? selectedRoom.dataset.label : 'selected room';
        const summary = `Re-admit ${<?php echo json_encode($student['full_name']); ?>} to ${roomName}, Bed ${bedSelect.value}, effective ${joiningDate.value}, at ${monthlyFee.value} per month.`;
        if (!window.confirm(`${summary}\n\nConfirm this new stay? Previous fees and security history will be preserved.`)) {
            event.preventDefault();
            return;
        }
        if (confirmButton) {
            confirmButton.disabled = true;
            confirmButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing re-admission…';
        }
    });
})();
</script>
