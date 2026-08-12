<?php $config = require __DIR__ . '/../../../config/app.php'; ?>
<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Record Payment</h3>
        <a href="<?php echo $config['base_url']; ?>/fees" class="btn" style="background: #334155; color: white;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div style="background-color: #0f172a; padding: 1.5rem; border-radius: 6px; margin-bottom: 2rem; border: 1px solid var(--border);">
        <h4 style="margin-bottom: 1rem; color: var(--primary);">Fee Details</h4>
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
            <span style="color: var(--text-muted);">Student:</span>
            <strong><?php echo htmlspecialchars($fee['full_name'] . ' (' . $fee['student_id_str'] . ')'); ?></strong>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
            <span style="color: var(--text-muted);">Billing Period:</span>
            <span><?php echo date('F Y', mktime(0, 0, 0, $fee['billing_month'], 1, $fee['billing_year'])); ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
            <span style="color: var(--text-muted);">Total Amount:</span>
            <span>Rs. <?php echo number_format($fee['amount'], 2); ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
            <span style="color: var(--text-muted);">Previously Paid:</span>
            <span style="color: var(--success);">Rs. <?php echo number_format($fee['paid_amount'], 2); ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--border); padding-top: 0.5rem; margin-top: 0.5rem; font-weight: bold; font-size: 1.1rem;">
            <span>Remaining Balance:</span>
            <span style="color: var(--danger);">Rs. <?php echo number_format($fee['amount'] - $fee['paid_amount'], 2); ?></span>
        </div>
    </div>

    <form action="<?php echo $config['base_url']; ?>/fees/storePayment/<?php echo $fee['id']; ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        
        <div class="form-group">
            <label class="form-label">Payment Amount (Rs.) *</label>
            <input type="number" name="paid_amount" class="form-control" step="0.01" min="1" max="<?php echo $fee['amount'] - $fee['paid_amount']; ?>" value="<?php echo $fee['amount'] - $fee['paid_amount']; ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">Payment Method *</label>
            <select name="payment_method" class="form-control" required>
                <option value="Cash">Cash</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Credit Card">Credit Card</option>
                <option value="JazzCash / EasyPaisa">JazzCash / EasyPaisa</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Transaction ID / Reference (Optional)</label>
            <input type="text" name="transaction_ref" class="form-control" placeholder="For bank transfers or mobile payments">
        </div>

        <div style="margin-top: 2rem;">
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; font-size: 1.1rem; padding: 0.75rem;"><i class="fas fa-check-circle"></i> Confirm Payment</button>
        </div>
    </form>
</div>
