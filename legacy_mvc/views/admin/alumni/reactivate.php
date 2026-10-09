<?php
$config = require APP_ROOT . '/config/app.php';
$alumni = $data['alumni'];
$student = $data['student'];
$previousStay = $data['previous_stay'];
$securityDeposits = $data['security_deposits'] ?? [];
$securityTransactions = $data['security_transactions'] ?? [];
$rooms = $data['rooms'];
$billingSettings = $data['billing_settings'];
$oldInput = $oldInput ?? [];
$escape = static function ($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
};
$joiningDate = $oldInput['joining_date'] ?? date('Y-m-d');
$selectedRoomId = (string)($oldInput['room_id'] ?? '');
$selectedBed = (string)($oldInput['bed_number'] ?? '');
$monthlyFeeInput = (string)($oldInput['monthly_fee'] ?? '');
$billingMode = $oldInput['first_month_billing_mode'] ?? 'full';
$securityOption = (string)($oldInput['security_option'] ?? '');
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
    .billing-preview .preview-error { grid-column: 1 / -1; color: var(--danger); }
    .security-history { display: grid; gap: .75rem; margin-bottom: 1rem; }
    .security-history-item { padding: .9rem; border: 1px solid var(--border); border-radius: 12px; background: var(--surface-muted); }
    .security-transactions { width: 100%; margin-top: .75rem; border-collapse: collapse; font-size: .78rem; }
    .security-transactions th, .security-transactions td { padding: .45rem; border-bottom: 1px solid var(--border); text-align: left; vertical-align: top; }
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
                <h3><i class="fa-solid fa-shield-halved"></i> Previous security balances &amp; history</h3>
                <?php if ($securityDeposits): ?>
                    <div class="security-history">
                        <?php foreach ($securityDeposits as $deposit): ?>
                            <article class="security-history-item">
                                <div class="readmit-details">
                                    <div class="readmit-detail"><span class="label">Deposit #<?php echo (int)$deposit['id']; ?> · Original</span><span class="value">Rs. <?php echo number_format((float)$deposit['original_amount'], 2); ?></span></div>
                                    <div class="readmit-detail"><span class="label">Remaining / refundable balance</span><span class="value">Rs. <?php echo number_format((float)$deposit['remaining_amount'], 2); ?></span></div>
                                    <div class="readmit-detail"><span class="label">Status</span><span class="value"><?php echo $escape($deposit['status']); ?></span></div>
                                    <div class="readmit-detail"><span class="label">Expected payment date</span><span class="value"><?php echo $escape($deposit['expected_payment_date'] ?: '—'); ?></span></div>
                                </div>
                                <?php if (!empty($deposit['transactions'])): ?>
                                    <div class="table-responsive">
                                        <table class="security-transactions">
                                            <thead><tr><th>Transaction</th><th>Amount</th><th>Payment details / reason</th><th>Date / processed by</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($deposit['transactions'] as $transaction): ?>
                                                    <tr>
                                                        <td><?php echo $escape($transaction['transaction_type']); ?></td>
                                                        <td>Rs. <?php echo number_format((float)$transaction['amount'], 2); ?></td>
                                                        <td><?php echo $escape(trim(($transaction['payment_method'] ?? '') . ' ' . ($transaction['reference_number'] ?? '') . ' ' . ($transaction['reason'] ?? ''))); ?></td>
                                                        <td><?php echo $escape($transaction['payment_date'] ?? '—'); ?><?php if (!empty($transaction['processed_by_name'])): ?><br><?php echo $escape($transaction['processed_by_name']); ?><?php endif; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="readmit-details" style="margin-bottom:1rem;"><div class="readmit-detail"><span class="label">Previous ledger status</span><span class="value">No security deposit record</span></div></div>
                <?php endif; ?>
                <div class="readmit-callout security">
                    <i class="fa-solid fa-lock"></i>
                    <div>Previous pending, waived, paid, returned, or held deposits remain separate and unchanged. Any remaining old balance must be settled through the existing Alumni security-return workflow.</div>
                </div>
            </section>

            <section class="readmit-card readmit-card-full">
                <h3><i class="fa-solid fa-vault"></i> New security deposit for this stay</h3>
                <div class="readmit-form-grid">
                    <div class="form-group form-group-full">
                        <label class="form-label" for="securityOption">Security decision</label>
                        <select class="form-control" id="securityOption" name="security_option">
                            <option value="" <?php echo $securityOption === '' ? 'selected' : ''; ?>>No new deposit (leave previous balances unchanged)</option>
                            <option value="NO_SECURITY" <?php echo $securityOption === 'NO_SECURITY' ? 'selected' : ''; ?>>Waive new deposit</option>
                            <option value="SECURITY_LATER" <?php echo $securityOption === 'SECURITY_LATER' ? 'selected' : ''; ?>>Record new deposit as pending payment</option>
                            <option value="PAY_NOW" <?php echo $securityOption === 'PAY_NOW' ? 'selected' : ''; ?>>Record new deposit received now</option>
                        </select>
                        <div class="readmit-help">A new deposit is optional. A pending or waived record is not recorded as money received; a HOLD transaction is created only for a confirmed payment.</div>
                    </div>
                    <div class="form-group" id="newSecurityAmountGroup" style="display:none;">
                        <label class="form-label" for="newSecurityAmount">New security amount (Rs.) *</label>
                        <input class="form-control" type="number" id="newSecurityAmount" name="security_amount" min="0.01" step="0.01"
                               value="<?php echo $escape($oldInput['security_amount'] ?? ''); ?>">
                    </div>
                    <div class="form-group" id="securityExpectedDateGroup" style="display:none;">
                        <label class="form-label" for="securityExpectedDate">Expected payment date *</label>
                        <input class="form-control" type="date" id="securityExpectedDate" name="security_expected_payment_date"
                               value="<?php echo $escape($oldInput['security_expected_payment_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group form-group-full" id="securityPendingNoteGroup" style="display:none;">
                        <label class="form-label" for="securityLaterNote">Pending security note / reason *</label>
                        <textarea class="form-control" id="securityLaterNote" name="security_later_note" rows="2"><?php echo $escape($oldInput['security_later_note'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group form-group-full" id="securityWaiverReasonGroup" style="display:none;">
                        <label class="form-label" for="securityWaiverReason">Waiver reason *</label>
                        <textarea class="form-control" id="securityWaiverReason" name="security_waiver_reason" rows="2"><?php echo $escape($oldInput['security_waiver_reason'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group" id="securityPaymentDateGroup" style="display:none;">
                        <label class="form-label" for="securityPaymentDate">Date received *</label>
                        <input class="form-control" type="date" id="securityPaymentDate" name="security_payment_date" max="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo $escape($oldInput['security_payment_date'] ?? date('Y-m-d')); ?>">
                    </div>
                    <div class="form-group" id="securityPaymentMethodGroup" style="display:none;">
                        <label class="form-label" for="securityPaymentMethod">Payment method *</label>
                        <select class="form-control" id="securityPaymentMethod" name="security_payment_method">
                            <option value="">Select payment method</option>
                            <?php foreach (['Cash', 'Bank Transfer', 'Online', 'Card', 'JazzCash', 'EasyPaisa', 'Other'] as $method): ?>
                                <option value="<?php echo $escape($method); ?>" <?php echo ($oldInput['security_payment_method'] ?? '') === $method ? 'selected' : ''; ?>><?php echo $escape($method); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" id="securityReferenceGroup" style="display:none;">
                        <label class="form-label" for="securityReference">Reference number (optional)</label>
                        <input class="form-control" type="text" id="securityReference" name="security_reference_number" maxlength="100"
                               value="<?php echo $escape($oldInput['security_reference_number'] ?? ''); ?>">
                    </div>
                    <div class="form-group form-group-full" id="securityPaymentNoteGroup" style="display:none;">
                        <label class="form-label" for="securityPaymentNote">Payment note / remarks</label>
                        <textarea class="form-control" id="securityPaymentNote" name="security_payment_note" rows="2"><?php echo $escape($oldInput['security_payment_note'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group form-group-full readmit-help">Processed by: <?php echo $escape($processed_by_name); ?> (authenticated admin)</div>
                </div>
            </section>

            <section class="readmit-card readmit-card-full">
                <h3><i class="fa-solid fa-calendar-check"></i> New re-admission details</h3>
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
                        <label class="form-label" for="monthlyFee">Monthly fee for this stay (Rs.) *</label>
                        <input class="form-control" type="number" id="monthlyFee" name="monthly_fee" min="0.01" step="0.01"
                               value="<?php echo $escape($monthlyFeeInput); ?>" required disabled>
                        <div class="readmit-help" id="roomRateHelp" role="status">Choose a room to retrieve its current database rate. If no room rate is configured, enter the agreed student fee as in normal admission.</div>
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
                    <div><small>Monthly fee</small><strong id="previewFee">—</strong></div>
                    <div class="total"><small>First-month amount</small><strong id="previewTotal">—</strong></div>
                    <div id="previewError" class="preview-error" role="alert"></div>
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
                        <button class="btn btn-primary" type="submit" id="confirmReadmission" disabled>
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
    const securityOption = document.getElementById('securityOption');
    const priorBed = <?php echo json_encode($selectedBed); ?>;
    const priorRoomId = <?php echo json_encode($selectedRoomId); ?>;
    let currentRoomRate = null;
    let loadedRateRoomId = '';
    let roomRequest = null;
    let previewRequest = null;
    let previewValid = false;
    let submitting = false;

    function setMoneyText(elementId, amount) {
        document.getElementById(elementId).textContent = `Rs. ${Number(amount).toFixed(2)}`;
    }

    function updateSubmitState() {
        confirmButton.disabled = submitting || !loadedRateRoomId || loadedRateRoomId !== roomSelect.value
            || !previewValid || !bedSelect.value || !monthlyFee.value;
    }

    async function updateBillingPreview() {
        if (previewRequest) previewRequest.abort();
        previewRequest = null;
        previewValid = false;
        document.getElementById('billingPeriod').textContent = '—';
        document.getElementById('previewFee').textContent = '—';
        document.getElementById('previewTotal').textContent = '—';
        const errorOutput = document.getElementById('previewError');
        errorOutput.textContent = '';

        const mode = modeSelect.value;
        const manual = mode === 'manual';
        discountGroup.style.display = manual ? 'block' : 'none';
        discountReasonGroup.style.display = manual ? 'block' : 'none';
        discountInput.required = manual;
        const discount = Number(discountInput.value || 0);
        discountReason.required = manual && discount > 0;
        const fee = Number(monthlyFee.value);
        if (!loadedRateRoomId || loadedRateRoomId !== roomSelect.value || !Number.isFinite(fee) || fee <= 0) {
            errorOutput.textContent = 'Choose a room and load its valid current rate to calculate the first-month amount.';
            updateSubmitState();
            return;
        }
        if (!joiningDate.value) {
            errorOutput.textContent = 'Enter a return date to calculate the first-month amount.';
            updateSubmitState();
            return;
        }
        const params = new URLSearchParams({
            monthly_fee: monthlyFee.value,
            joining_date: joiningDate.value,
            first_month_billing_mode: mode,
            first_month_discount: discountInput.value || '0',
            first_month_discount_reason: discountReason.value || ''
        });
        previewRequest = new AbortController();
        errorOutput.textContent = 'Calculating with the admission billing rules…';
        try {
            const response = await fetch(`${baseUrl}/api/alumni/readmit/billing-preview?${params}`, {
                credentials: 'same-origin',
                signal: previewRequest.signal
            });
            const result = await response.json();
            if (!response.ok || !result.success || !result.data) {
                throw new Error(result.message || 'Could not calculate the first-month amount.');
            }
            const calculation = result.data;
            const monthDate = new Date(Date.UTC(calculation.billing_year, calculation.billing_month - 1, 1));
            document.getElementById('billingPeriod').textContent = new Intl.DateTimeFormat(undefined, {
                month: 'long',
                year: 'numeric',
                timeZone: 'UTC'
            }).format(monthDate);
            setMoneyText('previewFee', calculation.monthly_fee);
            setMoneyText('previewTotal', calculation.net_payable);
            errorOutput.textContent = '';
            previewValid = true;
        } catch (error) {
            if (error.name !== 'AbortError') errorOutput.textContent = error.message || 'Billing calculation failed; confirmation is disabled.';
        } finally {
            updateSubmitState();
        }
    }

    async function loadRoomData(preserveSelection) {
        const roomId = roomSelect.value;
        if (roomRequest) roomRequest.abort();
        currentRoomRate = null;
        loadedRateRoomId = '';
        monthlyFee.value = '';
        monthlyFee.disabled = true;
        bedSelect.innerHTML = '<option value="">Select an available bed</option>';
        bedSelect.disabled = true;
        bedFeedback.textContent = '';
        bedFeedback.style.color = 'var(--text-muted)';
        document.getElementById('roomRateHelp').textContent = roomId
            ? 'Loading current room rate and live bed availability…'
            : 'Choose a room to retrieve its current database rate. If no room rate is configured, enter the agreed student fee as in normal admission.';
        updateBillingPreview();
        if (!roomId) return;

        roomRequest = new AbortController();
        bedFeedback.textContent = 'Loading current room rate and checking live bed availability…';
        try {
            const response = await fetch(`${baseUrl}/api/alumni/readmit/room/${encodeURIComponent(roomId)}`, {
                credentials: 'same-origin',
                signal: roomRequest.signal
            });
            const result = await response.json();
            if (!response.ok || !result.success || !result.data) {
                throw new Error(result.message || 'Could not retrieve the selected room’s current rate.');
            }
            if (roomSelect.value !== roomId) return;
            const rate = result.data.monthly_fee === null ? null : Number(result.data.monthly_fee);
            if (rate !== null && (!Number.isFinite(rate) || rate <= 0)) {
                throw new Error('The selected room rate could not be retrieved. Refresh or choose another room.');
            }
            currentRoomRate = rate;
            loadedRateRoomId = roomId;
            const existingFee = preserveSelection && roomId === priorRoomId ? Number(<?php echo json_encode($monthlyFeeInput); ?>) : NaN;
            monthlyFee.value = Number.isFinite(existingFee) && existingFee > 0
                ? existingFee.toFixed(2)
                : (rate !== null ? rate.toFixed(2) : '');
            monthlyFee.disabled = false;
            document.getElementById('roomRateHelp').textContent = rate !== null
                ? `Current database room rate: Rs. ${rate.toFixed(2)}. You may enter a stay-specific rate, as permitted by the existing admin admission flow. The selected amount is saved with this stay’s first-month invoice and history.`
                : 'This room has no monthly rate configured (database value is Rs. 0.00). Enter the agreed student fee using the existing normal-admission convention; the room rate will not be changed.';
            (result.data.available_bed_numbers || []).forEach((bed) => {
                const option = document.createElement('option');
                option.value = String(bed);
                option.textContent = `Bed ${bed} · Available`;
                if (preserveSelection && roomId === priorRoomId && String(bed) === priorBed) option.selected = true;
                bedSelect.appendChild(option);
            });
            bedSelect.disabled = !(result.data.available_bed_numbers || []).length;
            bedFeedback.textContent = bedSelect.disabled
                ? 'No beds are currently available in this room.'
                : `${result.data.available_bed_numbers.length} available bed${result.data.available_bed_numbers.length === 1 ? '' : 's'}. Availability is rechecked when you confirm.`;
            await updateBillingPreview();
        } catch (error) {
            if (error.name === 'AbortError') return;
            currentRoomRate = null;
            loadedRateRoomId = '';
            monthlyFee.value = '';
            monthlyFee.disabled = true;
            bedFeedback.textContent = error.message || 'Could not retrieve the room rate or bed availability.';
            bedFeedback.style.color = 'var(--danger)';
            document.getElementById('roomRateHelp').textContent = 'Room rate unavailable. Confirmation is blocked until a valid rate is retrieved.';
            document.getElementById('previewError').textContent = error.message || 'Room rate unavailable; first-month billing cannot be calculated.';
            updateSubmitState();
        }
    }

    function updateSecurityFields() {
        const option = securityOption.value;
        const sections = [
            ['newSecurityAmountGroup', ['newSecurityAmount'], ['SECURITY_LATER', 'PAY_NOW']],
            ['securityExpectedDateGroup', ['securityExpectedDate'], ['SECURITY_LATER']],
            ['securityPendingNoteGroup', ['securityLaterNote'], ['SECURITY_LATER']],
            ['securityWaiverReasonGroup', ['securityWaiverReason'], ['NO_SECURITY']],
            ['securityPaymentDateGroup', ['securityPaymentDate'], ['PAY_NOW']],
            ['securityPaymentMethodGroup', ['securityPaymentMethod'], ['PAY_NOW']],
            ['securityReferenceGroup', ['securityReference'], ['PAY_NOW']],
            ['securityPaymentNoteGroup', ['securityPaymentNote'], ['PAY_NOW']]
        ];
        sections.forEach(([groupId, fieldIds, allowed]) => {
            const visible = allowed.includes(option);
            document.getElementById(groupId).style.display = visible ? 'block' : 'none';
            fieldIds.forEach((fieldId) => {
                const field = document.getElementById(fieldId);
                field.disabled = !visible;
                field.required = visible && !['securityReference', 'securityPaymentNote'].includes(fieldId);
            });
        });
    }

    roomSelect.addEventListener('change', () => loadRoomData(false));
    bedSelect.addEventListener('change', updateSubmitState);
    monthlyFee.addEventListener('input', updateBillingPreview);
    joiningDate.addEventListener('change', updateBillingPreview);
    modeSelect.addEventListener('change', updateBillingPreview);
    discountInput.addEventListener('input', updateBillingPreview);
    discountReason.addEventListener('input', updateBillingPreview);
    securityOption.addEventListener('change', updateSecurityFields);
    updateSecurityFields();
    if (roomSelect.value) loadRoomData(true);
    else updateBillingPreview();

    form.addEventListener('submit', (event) => {
        const fee = Number(monthlyFee.value);
        if (!bedSelect.value || !loadedRateRoomId || loadedRateRoomId !== roomSelect.value
            || !Number.isFinite(fee) || fee <= 0 || !previewValid) {
            event.preventDefault();
            bedFeedback.textContent = 'A valid room rate, billing preview, and available bed are required before confirming.';
            bedFeedback.style.color = 'var(--danger)';
            if (!bedSelect.value) bedSelect.focus();
            else monthlyFee.focus();
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
        submitting = true;
        confirmButton.disabled = true;
        confirmButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing re-admission…';
    });
})();
</script>
