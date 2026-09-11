<?php $config = require APP_ROOT . '/config/app.php'; ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-calendar-day"></i> Reservation Details</h3>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <a href="<?php echo $config['base_url']; ?>/reservations" class="btn" style="background:#334155;color:white;">Back</a>
            <?php if (in_array($reservation['status'], ['PENDING', 'CONFIRMED'], true)): ?>
                <a href="<?php echo $config['base_url']; ?>/reservations/convert/<?php echo (int)$reservation['id']; ?>" class="btn btn-primary">Convert to Student</a>
                <?php if ($reservation['status'] === 'PENDING'): ?>
                    <form method="POST" action="<?php echo $config['base_url']; ?>/reservations/confirm/<?php echo (int)$reservation['id']; ?>" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <button type="submit" class="btn btn-primary">Confirm Arrival</button>
                    </form>
                <?php endif; ?>
                <form method="POST" action="<?php echo $config['base_url']; ?>/reservations/cancel/<?php echo (int)$reservation['id']; ?>" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="cancelled_by_name" value="<?php echo htmlspecialchars(\App\Core\Auth::user()); ?>">
                    <button type="submit" class="btn btn-danger">Cancel Reservation</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card" style="margin-bottom:0;">
                <h4 style="color:var(--primary);margin-bottom:1rem;">Personal Information</h4>
                <div class="form-group"><label class="form-label">Name</label><div class="form-control" style="background:#0f172a;"><?php echo htmlspecialchars($reservation['full_name']); ?></div></div>
                <div class="form-group"><label class="form-label">CNIC</label><div class="form-control" style="background:#0f172a;"><?php echo htmlspecialchars($reservation['cnic']); ?></div></div>
                <div class="form-group"><label class="form-label">Phone</label><div class="form-control" style="background:#0f172a;"><?php echo htmlspecialchars($reservation['phone']); ?></div></div>
                <div class="form-group"><label class="form-label">District</label><div class="form-control" style="background:#0f172a;"><?php echo htmlspecialchars($reservation['district']); ?></div></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card" style="margin-bottom:0;">
                <h4 style="color:var(--primary);margin-bottom:1rem;">Reservation</h4>
                <div class="form-group"><label class="form-label">Room</label><div class="form-control" style="background:#0f172a;">Room <?php echo htmlspecialchars($reservation['room_number']); ?></div></div>
                <div class="form-group"><label class="form-label">Bed</label><div class="form-control" style="background:#0f172a;">Bed <?php echo (int)$reservation['bed_number']; ?></div></div>
                <div class="form-group"><label class="form-label">Reservation Date</label><div class="form-control" style="background:#0f172a;"><?php echo htmlspecialchars($reservation['reservation_date']); ?></div></div>
                <div class="form-group"><label class="form-label">Expected Arrival</label><div class="form-control" style="background:#0f172a;"><?php echo htmlspecialchars($reservation['expected_arrival_date']); ?></div></div>
                <?php $displayStatus = $reservation['status'] === 'PENDING' ? 'RESERVED' : $reservation['status']; ?>
                <div class="form-group"><label class="form-label">Status</label><div class="form-control" style="background:#0f172a;"><span class="badge badge-warning"><?php echo htmlspecialchars($displayStatus); ?></span></div></div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:1.5rem;">
        <h4 style="color:var(--primary);margin-bottom:1rem;">Financial</h4>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group"><label class="form-label">Booking Amount</label><div class="form-control" style="background:#0f172a;">Rs. <?php echo number_format((float)$reservation['reservation_amount'], 0); ?></div></div>
            </div>
            <div class="col-md-4">
                <div class="form-group"><label class="form-label">Payment Date</label><div class="form-control" style="background:#0f172a;"><?php echo !empty($reservation['payments']) ? htmlspecialchars($reservation['payments'][0]['payment_date']) : '—'; ?></div></div>
            </div>
            <div class="col-md-4">
                <div class="form-group"><label class="form-label">Payment Reference</label><div class="form-control" style="background:#0f172a;"><?php echo !empty($reservation['payments']) ? htmlspecialchars($reservation['payments'][0]['transaction_ref']) : '—'; ?></div></div>
            </div>
        </div>
        <?php if (!empty($reservation['payments'])): ?>
            <table style="margin-top:1rem;">
                <thead><tr><th>Payment Date</th><th>Amount</th><th>Method</th><th>Reference</th></tr></thead>
                <tbody>
                    <?php foreach ($reservation['payments'] as $payment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($payment['payment_date']); ?></td>
                            <td>Rs. <?php echo number_format((float)$payment['amount'], 0); ?></td>
                            <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                            <td><?php echo htmlspecialchars($payment['transaction_ref']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
