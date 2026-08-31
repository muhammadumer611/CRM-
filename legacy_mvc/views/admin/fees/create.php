<?php $config = require APP_ROOT . '/config/app.php'; ?>
<div class="card" style="max-width:600px;margin:0 auto;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice-dollar"></i> Create Fee Invoice</h3>
        <a href="<?php echo $config['base_url']; ?>/fees" class="btn" style="background:#334155;color:white;">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <form action="<?php echo $config['base_url']; ?>/fees/store" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

        <div class="form-group">
            <label class="form-label">Student *</label>
            <select name="student_id" id="studentSelect" class="form-control" required onchange="setDefaultFee(this)">
                <option value="">— Select Student —</option>
                <?php foreach ($students as $s): ?>
                    <option value="<?php echo $s['id']; ?>"
                            data-fee="<?php echo $s['monthly_fee'] ?? ''; ?>"
                            <?php echo (($_POST['student_id'] ?? '') == $s['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s['full_name'] . ' (' . $s['student_id_str'] . ')'); ?>
                        <?php if ($s['monthly_fee']): ?>— Rs. <?php echo number_format($s['monthly_fee'], 0); ?>/mo<?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Billing Month *</label>
                    <select name="billing_month" class="form-control" required>
                        <?php for($m=1; $m<=12; ++$m): ?>
                            <option value="<?php echo $m; ?>" <?php echo (int)($_POST['billing_month'] ?? date('n')) === $m ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0,0,0,$m,1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Billing Year *</label>
                    <select name="billing_year" class="form-control" required>
                        <?php $y = date('Y'); for($i=$y-1; $i<=$y+1; ++$i): ?>
                            <option value="<?php echo $i; ?>" <?php echo (int)($_POST['billing_year'] ?? $y) === $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Amount (Rs.) *</label>
            <input type="number" name="amount" id="feeAmount" class="form-control" step="0.01" min="1"
                   value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>"
                   placeholder="Auto-filled from student's monthly fee" required>
            <small style="color:var(--text-muted);">Student's personal monthly fee will be auto-filled when you select a student.</small>
        </div>

        <div class="form-group">
            <label class="form-label">Due Date</label>
            <input type="date" name="due_date" class="form-control"
                   value="<?php echo htmlspecialchars($_POST['due_date'] ?? date('Y-m-') . '10'); ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Remarks (Optional)</label>
            <textarea name="remarks" class="form-control" rows="2" placeholder="Any notes..."><?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?></textarea>
        </div>

        <div style="margin-top:1.5rem;display:flex;gap:1rem;">
            <a href="<?php echo $config['base_url']; ?>/fees" class="btn" style="background:#334155;color:white;">Cancel</a>
            <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;">
                <i class="fas fa-plus"></i> Create Invoice
            </button>
        </div>
    </form>
</div>

<script>
function setDefaultFee(sel) {
    const opt = sel.options[sel.selectedIndex];
    const fee = opt.dataset.fee;
    const amountField = document.getElementById('feeAmount');
    if (fee) {
        amountField.value = parseFloat(fee).toFixed(2);
    } else {
        amountField.value = '';
    }
}
window.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('studentSelect');
    if (sel.value) setDefaultFee(sel);
});
</script>
