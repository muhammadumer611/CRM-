<?php $config = require APP_ROOT . '/config/app.php'; ?>
<?php $summary = $reportData['financial_summary'] ?? []; ?>
<?php $monthlyRevenue = $reportData['monthly_revenue'] ?? []; ?>
<?php $occupancySummary = $reportData['occupancy_summary'] ?? []; ?>
<?php $roomOccupancy = $reportData['room_occupancy'] ?? []; ?>
<?php $studentAnalytics = $reportData['student_analytics'] ?? []; ?>
<?php $feePerformance = $reportData['fee_performance'] ?? []; ?>
<?php $overdueFees = $reportData['overdue_fees'] ?? []; ?>
<?php $topOutstanding = $reportData['top_outstanding_students'] ?? []; ?>
<?php $paymentMethods = $reportData['payment_methods'] ?? []; ?>
<?php $recentActivity = $reportData['recent_activity'] ?? []; ?>

<style>
    .report-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; }
    .stat-box { background: linear-gradient(135deg, #1e293b, #0f172a); border: 1px solid var(--border); border-radius: 12px; padding: 1rem; }
    .stat-box .label { color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; }
    .stat-box .value { font-size: 1.5rem; margin-top: 0.5rem; font-weight: 700; }
    .chart-wrap { background: #0f172a; border: 1px solid var(--border); border-radius: 10px; padding: 1rem; }
    .bar-row { display: flex; align-items: end; gap: 0.25rem; height: 220px; }
    .bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; }
    .bar { width: 100%; max-width: 30px; border-radius: 6px 6px 0 0; background: linear-gradient(180deg, #38bdf8, #2563eb); min-height: 10px; }
    .print-hidden { display: inline-flex; }
    .report-section { margin-top: 2rem; }
    @media print {
        body * { visibility: hidden; }
        .print-report, .print-report * { visibility: visible; }
        .print-report { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print, .sidebar, .topbar, .nav-item, .logout-btn, .btn, .alert { display: none !important; }
        .content { padding: 0 !important; }
        .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    }
</style>

<div class="print-report">
    <div class="card">
        <div class="card-header no-print">
            <div>
                <h3 class="card-title">Reports & Analytics</h3>
                <small style="color: var(--text-muted);">Admin financial and occupancy overview</small>
            </div>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                <a href="<?php echo $config['base_url']; ?>/dashboard" class="btn" style="background:#1e293b; color:white;"><i class="fas fa-arrow-left"></i> Back</a>
                <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
                <a href="<?php echo $config['base_url']; ?>/reports/export/csv<?php echo $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>" class="btn" style="background:#10b981; color:white;"><i class="fas fa-file-csv"></i> Export CSV</a>
            </div>
        </div>

        <form method="GET" action="<?php echo $config['base_url']; ?>/reports" class="no-print" style="margin-bottom: 1.5rem;">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Date Filter</label>
                        <select name="date_filter" class="form-control">
                            <option value="today" <?php echo (($filters['date_filter'] ?? 'this_month') === 'today') ? 'selected' : ''; ?>>Today</option>
                            <option value="this_week" <?php echo (($filters['date_filter'] ?? 'this_month') === 'this_week') ? 'selected' : ''; ?>>This Week</option>
                            <option value="this_month" <?php echo (($filters['date_filter'] ?? 'this_month') === 'this_month') ? 'selected' : ''; ?>>This Month</option>
                            <option value="last_month" <?php echo (($filters['date_filter'] ?? 'this_month') === 'last_month') ? 'selected' : ''; ?>>Last Month</option>
                            <option value="this_year" <?php echo (($filters['date_filter'] ?? 'this_month') === 'this_year') ? 'selected' : ''; ?>>This Year</option>
                            <option value="custom" <?php echo (($filters['date_filter'] ?? '') === 'custom') ? 'selected' : ''; ?>>Custom Date Range</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-control">
                            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo (($filters['year'] ?? date('Y')) == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($filters['from_date'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($filters['to_date'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group" style="margin-top: 1.6rem;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-filter"></i> Apply Filters</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="report-grid" style="margin-top: 1rem;">
            <div class="stat-box">
                <div class="label">Total Invoiced</div>
                <div class="value">Rs. <?php echo number_format((float)($summary['total_invoiced'] ?? 0), 2); ?></div>
            </div>
            <div class="stat-box">
                <div class="label">Total Collected</div>
                <div class="value">Rs. <?php echo number_format((float)($summary['total_collected'] ?? 0), 2); ?></div>
            </div>
            <div class="stat-box">
                <div class="label">Total Outstanding</div>
                <div class="value">Rs. <?php echo number_format((float)($summary['total_outstanding'] ?? 0), 2); ?></div>
            </div>
            <div class="stat-box">
                <div class="label">Total Overdue</div>
                <div class="value">Rs. <?php echo number_format((float)($summary['total_overdue'] ?? 0), 2); ?></div>
            </div>
            <div class="stat-box">
                <div class="label">Total Discounts</div>
                <div class="value">Rs. <?php echo number_format((float)($summary['total_discounts'] ?? 0), 2); ?></div>
            </div>
            <div class="stat-box">
                <div class="label">Total Payments</div>
                <div class="value"><?php echo number_format((int)($summary['total_payments'] ?? 0)); ?></div>
            </div>
        </div>

        <div class="report-section">
            <h4 style="margin-bottom:1rem;">Monthly Revenue Report</h4>
            <div class="chart-wrap">
                <div style="margin-bottom:1rem; font-weight:600; color: var(--text-muted);">Revenue for <?php echo htmlspecialchars($filters['year'] ?? date('Y')); ?></div>
                <?php $maxRevenue = 0; foreach ($monthlyRevenue as $monthRow) { $maxRevenue = max($maxRevenue, (float)$monthRow['total_invoiced'], (float)$monthRow['total_collected']); } ?>
                <div class="bar-row">
                    <?php foreach ($monthlyRevenue as $monthRow): ?>
                        <?php $collectedHeight = $maxRevenue > 0 ? ((float)$monthRow['total_collected'] / $maxRevenue) * 100 : 0; ?>
                        <?php $invoicedHeight = $maxRevenue > 0 ? ((float)$monthRow['total_invoiced'] / $maxRevenue) * 100 : 0; ?>
                        <div class="bar-col" title="<?php echo $monthRow['month_name']; ?>">
                            <div style="width: 100%; display:flex; align-items:end; justify-content:center; gap:0.15rem; height: 180px;">
                                <div class="bar" style="height: <?php echo $invoicedHeight; ?>%; background: linear-gradient(180deg, #fbbf24, #f59e0b);"></div>
                                <div class="bar" style="height: <?php echo $collectedHeight; ?>%; background: linear-gradient(180deg, #38bdf8, #2563eb);"></div>
                            </div>
                            <small style="color: var(--text-muted);"><?php echo substr($monthRow['month_name'], 0, 3); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top: 1rem; font-size: 0.8rem; color: var(--text-muted);">
                    <span style="margin-right:1rem;"><span style="display:inline-block; width:12px; height:12px; background:#f59e0b; border-radius: 3px; vertical-align:middle; margin-right:0.35rem;"></span> Invoiced</span>
                    <span><span style="display:inline-block; width:12px; height:12px; background:#38bdf8; border-radius: 3px; vertical-align:middle; margin-right:0.35rem;"></span> Collected</span>
                </div>
            </div>

            <div class="table-responsive" style="margin-top: 1rem;">
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Total Invoiced</th>
                            <th>Total Collected</th>
                            <th>Outstanding</th>
                            <th>Number of Payments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($monthlyRevenue)): ?>
                            <tr><td colspan="5" style="text-align:center; padding: 1.5rem;">No revenue records found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($monthlyRevenue as $monthRow): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($monthRow['month_name']); ?></td>
                                    <td>Rs. <?php echo number_format((float)$monthRow['total_invoiced'], 2); ?></td>
                                    <td>Rs. <?php echo number_format((float)$monthRow['total_collected'], 2); ?></td>
                                    <td>Rs. <?php echo number_format((float)$monthRow['outstanding'], 2); ?></td>
                                    <td><?php echo number_format((int)$monthRow['payment_count']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="report-section">
            <h4 style="margin-bottom:1rem;">Occupancy Analytics</h4>
            <div class="report-grid">
                <div class="stat-box">
                    <div class="label">Total Rooms</div>
                    <div class="value"><?php echo (int)($occupancySummary['total_rooms'] ?? 0); ?></div>
                </div>
                <div class="stat-box">
                    <div class="label">Total Beds</div>
                    <div class="value"><?php echo (int)($occupancySummary['total_beds'] ?? 0); ?></div>
                </div>
                <div class="stat-box">
                    <div class="label">Occupied Beds</div>
                    <div class="value"><?php echo (int)($occupancySummary['occupied_beds'] ?? 0); ?></div>
                </div>
                <div class="stat-box">
                    <div class="label">Available Beds</div>
                    <div class="value"><?php echo (int)($occupancySummary['available_beds'] ?? 0); ?></div>
                </div>
                <div class="stat-box">
                    <div class="label">Occupancy %</div>
                    <div class="value"><?php echo number_format((float)($occupancySummary['occupancy_percentage'] ?? 0), 2); ?>%</div>
                </div>
            </div>

            <div class="table-responsive" style="margin-top:1rem;">
                <table>
                    <thead>
                        <tr>
                            <th>Room Number</th>
                            <th>Block</th>
                            <th>Floor</th>
                            <th>Total Beds</th>
                            <th>Occupied Beds</th>
                            <th>Available Beds</th>
                            <th>Occupancy %</th>
                            <th>Room Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($roomOccupancy)): ?>
                            <tr><td colspan="8" style="text-align:center; padding: 1.5rem;">No room occupancy data found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($roomOccupancy as $room): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                    <td><?php echo htmlspecialchars($room['block']); ?></td>
                                    <td><?php echo htmlspecialchars($room['floor']); ?></td>
                                    <td><?php echo (int)$room['total_beds']; ?></td>
                                    <td><?php echo (int)$room['occupied_beds']; ?></td>
                                    <td><?php echo (int)$room['available_beds']; ?></td>
                                    <td><?php echo number_format((float)$room['occupancy_percentage'], 2); ?>%</td>
                                    <td>
                                        <?php if ($room['room_status'] === 'Available'): ?>
                                            <span class="badge badge-success"><?php echo htmlspecialchars($room['room_status']); ?></span>
                                        <?php elseif ($room['room_status'] === 'Occupied'): ?>
                                            <span class="badge badge-danger"><?php echo htmlspecialchars($room['room_status']); ?></span>
                                        <?php elseif ($room['room_status'] === 'Partially Occupied'): ?>
                                            <span class="badge badge-warning"><?php echo htmlspecialchars($room['room_status']); ?></span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #334155; color:#cbd5e1;">Disabled</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="report-section">
            <h4 style="margin-bottom:1rem;">Student Analytics</h4>
            <div class="report-grid">
                <div class="stat-box"><div class="label">Total Students</div><div class="value"><?php echo (int)($studentAnalytics['total_students'] ?? 0); ?></div></div>
                <div class="stat-box"><div class="label">Active Students</div><div class="value"><?php echo (int)($studentAnalytics['active_students'] ?? 0); ?></div></div>
                <div class="stat-box"><div class="label">Inactive Students</div><div class="value"><?php echo (int)($studentAnalytics['inactive_students'] ?? 0); ?></div></div>
                <div class="stat-box"><div class="label">New This Month</div><div class="value"><?php echo (int)($studentAnalytics['new_students_this_month'] ?? 0); ?></div></div>
                <div class="stat-box"><div class="label">Left This Month</div><div class="value"><?php echo (int)($studentAnalytics['students_left_this_month'] ?? 0); ?></div></div>
                <div class="stat-box"><div class="label">Total Alumni</div><div class="value"><?php echo (int)($studentAnalytics['total_alumni'] ?? 0); ?></div></div>
            </div>
        </div>

        <div class="report-section">
            <h4 style="margin-bottom:1rem;">Fee Performance</h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Number of Records</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Remaining Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $statuses = ['Paid', 'Pending', 'Partial', 'Overdue']; ?>
                        <?php foreach ($statuses as $status): ?>
                            <?php $row = $feePerformance[$status] ?? ['record_count' => 0, 'total_amount' => 0, 'paid_amount' => 0, 'remaining_amount' => 0]; ?>
                            <tr>
                                <td>
                                    <?php if ($status === 'Paid'): ?><span class="badge badge-success"><?php echo $status; ?></span>
                                    <?php elseif ($status === 'Pending'): ?><span class="badge badge-danger"><?php echo $status; ?></span>
                                    <?php elseif ($status === 'Partial'): ?><span class="badge badge-warning"><?php echo $status; ?></span>
                                    <?php else: ?><span class="badge" style="background:#7f1d1d; color:white;">Overdue</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo (int)$row['record_count']; ?> invoices</td>
                                <td>Rs. <?php echo number_format((float)$row['total_amount'], 2); ?></td>
                                <td>Rs. <?php echo number_format((float)$row['paid_amount'], 2); ?></td>
                                <td>Rs. <?php echo number_format((float)$row['remaining_amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="report-section">
            <h4 style="margin-bottom:1rem;">Overdue Fees</h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Room</th>
                            <th>Billing Month</th>
                            <th>Due Date</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Remaining</th>
                            <th>Days Overdue</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($overdueFees)): ?>
                            <tr><td colspan="11" style="text-align:center; padding: 1.5rem;">No overdue invoices found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($overdueFees as $fee): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fee['invoice_number']); ?></td>
                                    <td><?php echo htmlspecialchars($fee['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($fee['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($fee['room_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('F', mktime(0,0,0, $fee['billing_month'], 1)); ?></td>
                                    <td><?php echo htmlspecialchars($fee['due_date']); ?></td>
                                    <td>Rs. <?php echo number_format((float)$fee['total_amount'], 2); ?></td>
                                    <td>Rs. <?php echo number_format((float)$fee['paid_amount'], 2); ?></td>
                                    <td>Rs. <?php echo number_format((float)$fee['remaining_amount'], 2); ?></td>
                                    <td><?php echo (int)$fee['days_overdue']; ?></td>
                                    <td><span class="badge badge-danger"><?php echo htmlspecialchars($fee['status']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="report-section">
            <h4 style="margin-bottom:1rem;">Top Outstanding Students</h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Room</th>
                            <th>Outstanding Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topOutstanding)): ?>
                            <tr><td colspan="5" style="text-align:center; padding: 1.5rem;">No outstanding balances found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($topOutstanding as $index => $student): ?>
                                <tr>
                                    <td>#<?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['room_number'] ?? 'N/A'); ?></td>
                                    <td>Rs. <?php echo number_format((float)$student['outstanding_amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="report-section">
            <h4 style="margin-bottom:1rem;">Payment Method Analytics</h4>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Payment Method</th>
                            <th>Number of Transactions</th>
                            <th>Total Collected</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($paymentMethods)): ?>
                            <tr><td colspan="3" style="text-align:center; padding:1.5rem;">No payment method data found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($paymentMethods as $method): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($method['payment_method'] ?? 'N/A'); ?></td>
                                    <td><?php echo (int)($method['transaction_count'] ?? 0); ?></td>
                                    <td>Rs. <?php echo number_format((float)($method['total_collected'] ?? 0), 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="report-section">
            <h4 style="margin-bottom:1rem;">Recent Activity</h4>
            <ul style="list-style:none; padding:0;">
                <?php if (empty($recentActivity)): ?>
                    <li style="color: var(--text-muted);">No recent activity available.</li>
                <?php else: ?>
                    <?php foreach ($recentActivity as $log): ?>
                        <li style="padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                            <div style="font-weight:600; color: var(--primary);"><?php echo htmlspecialchars($log['action'] ?? 'Activity'); ?></div>
                            <div style="color: var(--text-muted); font-size: 0.85rem; margin-top:0.25rem;">
                                <?php echo htmlspecialchars($log['description'] ?? ''); ?>
                            </div>
                            <div style="font-size: 0.75rem; opacity: 0.8; margin-top:0.25rem;">
                                <?php echo htmlspecialchars($log['username'] ?? 'System'); ?> • <?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>
