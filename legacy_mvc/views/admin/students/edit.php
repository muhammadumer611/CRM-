<?php $config = require APP_ROOT . '/config/app.php'; ?>
<?php $resolvedBackUrl = !empty($backUrl) ? $backUrl : $config['base_url'] . '/students'; ?>
<?php
    $vehicleTypeValue = trim((string)($student['vehicle_type'] ?? ''));
    $vehicleTypeIsCustom = $vehicleTypeValue !== '' && !in_array($vehicleTypeValue, ['Motorcycle / Bike', 'Car'], true);
?>
<div style="max-width:900px;margin:0 auto;">

    <!-- Header -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
        <div>
            <h2 style="margin:0;"><?php echo htmlspecialchars($student['full_name']); ?></h2>
            <span class="badge" style="background:#334155;font-size:0.85rem;margin-top:0.25rem;display:inline-block;">
                <?php echo htmlspecialchars($student['student_id_str']); ?>
            </span>
            <?php if ($student['status'] === 'Active'): ?>
                <span class="badge badge-success" style="margin-left:0.5rem;">Active</span>
            <?php else: ?>
                <span class="badge badge-danger" style="margin-left:0.5rem;">Inactive</span>
            <?php endif; ?>
        </div>
        <a href="<?php echo $resolvedBackUrl; ?>" class="btn" style="background:#334155;color:white;">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <!-- Current Room Allocation Info -->
    <?php if (!empty($student['room_number'])): ?>
    <div style="background:rgba(56,189,248,0.08);border:1px solid rgba(56,189,248,0.25);border-radius:8px;padding:1rem;margin-bottom:1.5rem;display:flex;gap:2rem;flex-wrap:wrap;align-items:center;">
        <div><i class="fas fa-bed" style="color:var(--primary);"></i> <strong>Room:</strong> <?php echo htmlspecialchars($student['block'] . '-' . $student['room_number']); ?></div>
        <div><strong>Bed:</strong> <?php echo (int)$student['bed_number']; ?></div>
        <div><strong>Floor:</strong> <?php echo htmlspecialchars($student['floor'] ?? '—'); ?></div>
        <div><strong>Type:</strong> <?php echo htmlspecialchars($student['room_type'] ?? '—'); ?></div>
        <div><strong>Since:</strong> <?php echo $student['allocation_date'] ? date('M d, Y', strtotime($student['allocation_date'])) : '—'; ?></div>
    </div>
    <?php else: ?>
    <div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:1rem;margin-bottom:1.5rem;">
        <i class="fas fa-exclamation-triangle" style="color:#fca5a5;"></i>
        <span style="color:#fca5a5;margin-left:0.5rem;">No room allocated. <a href="<?php echo $config['base_url']; ?>/allocations/create" style="color:var(--primary);">Allocate a room →</a></span>
    </div>
    <?php endif; ?>

    <!-- Edit Form -->
    <div class="card" style="margin-bottom:1.5rem;">
        <h4 style="color:var(--primary);margin-bottom:1.25rem;"><i class="fas fa-edit"></i> Edit Profile</h4>
        <form action="<?php echo $config['base_url']; ?>/students/update/<?php echo (int)$student['id']; ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="_back_url" value="<?php echo htmlspecialchars($resolvedBackUrl); ?>">

            <!-- Personal Info -->
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($student['full_name']); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">CNIC *</label>
                        <input type="text" name="cnic" class="form-control" required maxlength="15"
                               value="<?php echo htmlspecialchars(\App\Services\StudentService::formatCnic($student['cnic'])); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Phone *</label>
                        <input type="text" name="phone" class="form-control" required value="<?php echo htmlspecialchars($student['phone']); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Blood Group</label>
                        <select name="blood_group" class="form-control">
                            <option value="">Select...</option>
                            <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                                <option value="<?php echo $bg; ?>" <?php echo $student['blood_group'] === $bg ? 'selected' : ''; ?>><?php echo $bg; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="Active"   <?php echo $student['status'] === 'Active'   ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo $student['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label">Address *</label>
                        <input type="text" name="address" class="form-control" required value="<?php echo htmlspecialchars($student['address']); ?>">
                    </div>
                </div>
            </div>

            <!-- Guardian Info -->
            <hr style="border-color:var(--border);margin:1.25rem 0;">
            <h5 style="color:var(--primary);margin-bottom:1rem;"><i class="fas fa-users"></i> Guardian</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Guardian Name *</label>
                        <input type="text" name="guardian_name" class="form-control" required value="<?php echo htmlspecialchars($student['guardian_name']); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Relation *</label>
                        <select name="relation" class="form-control" required>
                            <?php foreach(['Father','Mother','Brother','Sister','Uncle','Aunt','Spouse','Other'] as $rel): ?>
                                <option value="<?php echo $rel; ?>" <?php echo $student['relation'] === $rel ? 'selected' : ''; ?>><?php echo $rel; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Guardian Phone *</label>
                        <input type="text" name="guardian_phone" class="form-control" required value="<?php echo htmlspecialchars($student['guardian_phone']); ?>">
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label">Guardian Address</label>
                        <input type="text" name="guardian_address" class="form-control" value="<?php echo htmlspecialchars($student['guardian_address'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <hr style="border-color:var(--border);margin:1.25rem 0;">
            <h5 style="color:var(--primary);margin-bottom:1rem;"><i class="fas fa-user-tie"></i> Resident & Vehicle Information</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Resident Type *</label>
                        <select name="resident_type" id="residentTypeEdit" class="form-control">
                            <?php foreach(['Student','Job / Working'] as $type): ?>
                                <option value="<?php echo $type; ?>" <?php echo (($student['resident_type'] ?? 'Student') === $type ? 'selected' : ''); ?>><?php echo $type; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6" id="collegeUniversityEditWrap" style="display:<?php echo (($student['resident_type'] ?? 'Student') === 'Student' ? 'block' : 'none'); ?>;">
                    <div class="form-group">
                        <label class="form-label">College / University Name</label>
                        <input type="text" name="college_university" class="form-control" value="<?php echo htmlspecialchars($student['college_university'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6" id="jobWorkplaceEditWrap" style="display:<?php echo (($student['resident_type'] ?? 'Student') === 'Job / Working' ? 'block' : 'none'); ?>;">
                    <div class="form-group">
                        <label class="form-label">Job / Workplace</label>
                        <input type="text" name="job_workplace" class="form-control" value="<?php echo htmlspecialchars($student['job_workplace'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Vehicle Number</label>
                        <input type="text" name="vehicle_number" class="form-control" value="<?php echo htmlspecialchars($student['vehicle_number'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Vehicle Type</label>
                        <select name="vehicle_type" id="vehicleTypeEdit" class="form-control">
                            <option value="">Select...</option>
                            <?php foreach(['Motorcycle / Bike','Car','Other'] as $vt): ?>
                                <option value="<?php echo $vt; ?>" <?php echo (($vehicleTypeIsCustom ? 'Other' : $vehicleTypeValue) === $vt ? 'selected' : ''); ?>><?php echo $vt; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6" id="vehicleTypeOtherEditWrap" style="display:<?php echo ($vehicleTypeIsCustom ? 'block' : 'none'); ?>;">
                    <div class="form-group">
                        <label class="form-label">Vehicle Type (Other)</label>
                        <input type="text" name="vehicle_type_other" class="form-control" value="<?php echo htmlspecialchars($vehicleTypeIsCustom ? $vehicleTypeValue : ''); ?>">
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label">Note</label>
                        <textarea name="note" class="form-control" rows="4"><?php echo htmlspecialchars($student['note'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Monthly Fee -->
            <hr style="border-color:var(--border);margin:1.25rem 0;">
            <h5 style="color:var(--primary);margin-bottom:1rem;"><i class="fas fa-rupee-sign"></i> Monthly Fee</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Student's Monthly Fee (Rs.)</label>
                        <input type="number" name="monthly_fee" class="form-control" step="0.01" min="0"
                               value="<?php echo $student['monthly_fee'] !== null ? htmlspecialchars($student['monthly_fee']) : ''; ?>"
                               placeholder="e.g. 8000">
                        <small style="color:var(--text-muted);">
                            This is the student's individual fee. Changing the room's default fee will NOT affect this.
                            <?php if (!empty($student['room_monthly_fee'])): ?>
                                Room default: Rs. <?php echo number_format($student['room_monthly_fee'], 0); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:1rem;justify-content:flex-end;margin-top:1rem;">
                <a href="<?php echo $resolvedBackUrl; ?>" class="btn" style="background:#334155;color:white;">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>

    <!-- Security Deposit -->
    <?php if ($securityDeposit): ?>
    <div class="card" style="margin-bottom:1.5rem;">
        <h4 style="color:var(--primary);margin-bottom:1rem;"><i class="fas fa-shield-alt"></i> Security Deposit</h4>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;">
            <div>
                <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;">Original Amount</div>
                <div style="font-weight:700;margin-top:0.25rem;">Rs. <?php echo number_format($securityDeposit['original_amount'], 2); ?></div>
            </div>
            <div>
                <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;">Remaining</div>
                <div style="font-weight:700;color:#c4b5fd;margin-top:0.25rem;">Rs. <?php echo number_format($securityDeposit['remaining_amount'], 2); ?></div>
            </div>
            <div>
                <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;">Status</div>
                <div style="font-weight:700;margin-top:0.25rem;"><?php echo htmlspecialchars($securityDeposit['status']); ?></div>
            </div>
        </div>
        <small style="color:var(--text-muted);margin-top:0.75rem;display:block;">
            <i class="fas fa-info-circle"></i> Security deposits are managed separately during student checkout/alumni conversion.
        </small>
    </div>
    <?php endif; ?>

    <!-- Fee History -->
    <?php if (!empty($feeHistory)): ?>
    <div class="card">
        <h4 style="color:var(--primary);margin-bottom:1rem;"><i class="fas fa-file-invoice-dollar"></i> Fee History</h4>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Period</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feeHistory as $f): ?>
                    <?php
                        $statusColors = ['Paid'=>'#10b981','Partial'=>'#f59e0b','Pending'=>'#ef4444','Overdue'=>'#991b1b'];
                        $sc = $statusColors[$f['status']] ?? '#94a3b8';
                    ?>
                    <tr>
                        <td><code style="font-size:0.75rem;color:var(--text-muted);"><?php echo htmlspecialchars($f['invoice_number'] ?? '#'.$f['id']); ?></code></td>
                        <td><?php echo date('M Y', mktime(0,0,0,$f['billing_month'],1,$f['billing_year'])); ?></td>
                        <td>Rs. <?php echo number_format($f['amount'], 0); ?></td>
                        <td style="color:var(--success);">Rs. <?php echo number_format($f['paid_amount'], 0); ?></td>
                        <td>
                            <span class="badge" style="background:<?php echo $sc; ?>22;color:<?php echo $sc; ?>;border:1px solid <?php echo $sc; ?>44;border-radius:999px;padding:0.2rem 0.5rem;font-size:0.73rem;font-weight:700;">
                                <?php echo $f['status']; ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo $config['base_url']; ?>/fees/pay/<?php echo $f['id']; ?>" class="btn btn-sm" style="background:#1e293b;color:var(--primary);">
                                <i class="fas fa-receipt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
