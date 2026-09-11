<?php $config = require APP_ROOT . '/config/app.php'; ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-calendar-check"></i> Reservations</h3>
        <a href="<?php echo $config['base_url']; ?>/reservations/create" class="btn btn-primary"><i class="fas fa-plus"></i> New Reservation</a>
    </div>

    <form method="GET" action="<?php echo $config['base_url']; ?>/reservations" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;margin-bottom:1rem;">
        <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="Search by name, CNIC, phone, room..." style="max-width:380px;">
        <select name="status" class="form-control" style="max-width:180px;">
            <option value="">All Status</option>
            <option value="PENDING" <?php echo (($filters['status'] ?? '') === 'PENDING') ? 'selected' : ''; ?>>PENDING</option>
            <option value="CONFIRMED" <?php echo (($filters['status'] ?? '') === 'CONFIRMED') ? 'selected' : ''; ?>>CONFIRMED</option>
            <option value="ARRIVED" <?php echo (($filters['status'] ?? '') === 'ARRIVED') ? 'selected' : ''; ?>>ARRIVED</option>
            <option value="CANCELLED" <?php echo (($filters['status'] ?? '') === 'CANCELLED') ? 'selected' : ''; ?>>CANCELLED</option>
            <option value="EXPIRED" <?php echo (($filters['status'] ?? '') === 'EXPIRED') ? 'selected' : ''; ?>>EXPIRED</option>
        </select>
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if (!empty($filters['search']) || !empty($filters['status'])): ?>
            <a href="<?php echo $config['base_url']; ?>/reservations" class="btn" style="background:#334155;color:white;">Clear</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Reservation ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Room</th>
                    <th>Bed</th>
                    <th>Booking Amount</th>
                    <th>Reservation Date</th>
                    <th>Expected Arrival</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservations)): ?>
                    <tr><td colspan="10" style="text-align:center;color:var(--text-muted);">No reservations found.</td></tr>
                <?php else: ?>
                    <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td>#<?php echo (int)$reservation['id']; ?></td>
                            <td><?php echo htmlspecialchars($reservation['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['phone']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['room_number']); ?></td>
                            <td><?php echo (int)$reservation['bed_number']; ?></td>
                            <td>Rs. <?php echo number_format((float)$reservation['reservation_amount'], 0); ?></td>
                            <td><?php echo htmlspecialchars($reservation['reservation_date']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['expected_arrival_date']); ?></td>
                            <td>
                                <span class="badge <?php
                                    echo $reservation['status'] === 'PENDING' ? 'badge-warning' : (
                                        $reservation['status'] === 'CONFIRMED' ? 'badge-secondary' : (
                                            $reservation['status'] === 'ARRIVED' ? 'badge-success' : (
                                                $reservation['status'] === 'CANCELLED' ? 'badge-danger' : 'badge-secondary'
                                            )
                                        )
                                    );
                                ?>"><?php echo htmlspecialchars($reservation['status']); ?></span>
                            </td>
                            <td style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                                <a href="<?php echo $config['base_url']; ?>/reservations/view/<?php echo (int)$reservation['id']; ?>" class="btn btn-sm" style="background:#1e293b;color:#e2e8f0;border:1px solid var(--border);">View</a>
                                <?php if ($reservation['status'] === 'PENDING'): ?>
                                    <form method="POST" action="<?php echo $config['base_url']; ?>/reservations/confirm/<?php echo (int)$reservation['id']; ?>" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <button type="submit" class="btn btn-sm btn-primary">Confirm</button>
                                    </form>
                                <?php endif; ?>
                                <?php if (in_array($reservation['status'], ['PENDING', 'CONFIRMED'], true)): ?>
                                    <a href="<?php echo $config['base_url']; ?>/reservations/convert/<?php echo (int)$reservation['id']; ?>" class="btn btn-sm btn-primary">Convert</a>
                                <?php endif; ?>
                                <?php if (!in_array($reservation['status'], ['CANCELLED', 'ARRIVED'], true)): ?>
                                    <form method="POST" action="<?php echo $config['base_url']; ?>/reservations/cancel/<?php echo (int)$reservation['id']; ?>" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="text" name="cancelled_by_name" class="form-control" placeholder="Cancelled By" required>
                                        <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total > $perPage): ?>
        <div style="margin-top:1rem;color:var(--text-muted);">
            Page <?php echo (int)$page; ?> of <?php echo (int)ceil($total / $perPage); ?>
        </div>
    <?php endif; ?>
</div>
