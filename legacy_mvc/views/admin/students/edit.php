<?php $config = require APP_ROOT . '/config/app.php'; ?>
<style>
    /* Custom Tabs & Modals to replace missing Bootstrap JS */
    .custom-tabs { display: flex; list-style: none; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); }
    .custom-tab-item { padding: 0.75rem 1.5rem; cursor: pointer; color: var(--text-muted); border-bottom: 2px solid transparent; }
    .custom-tab-item.active { color: var(--primary); border-bottom: 2px solid var(--primary); }
    .custom-tab-item:hover { color: var(--primary); }
    .custom-tab-pane { display: none; }
    .custom-tab-pane.active { display: block; }
    
    .custom-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
    .custom-modal.show { display: flex; }
    .custom-modal-content { background: var(--card); border: 1px solid var(--border); border-radius: 8px; width: 100%; max-width: 600px; padding: 1.5rem; position: relative; }
    .custom-modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1rem; }
    .custom-close { cursor: pointer; font-size: 1.5rem; line-height: 1; color: var(--text-muted); }
    .custom-close:hover { color: var(--danger); }
</style>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Student</h3>
        <div>
            <?php if ($student['status'] === 'Active'): ?>
                <button type="button" class="btn btn-danger" onclick="openAlumniModal()" style="margin-right: 0.5rem;"><i class="fas fa-sign-out-alt"></i> Mark as Alumni</button>
            <?php else: ?>
                <button type="button" class="btn" style="background: #0284c7; color: white; margin-right: 0.5rem;" onclick="checkAlumniRecord()"><i class="fas fa-eye"></i> View Alumni Record</button>
            <?php endif; ?>
            <a href="<?php echo $config['base_url']; ?>/students" class="btn" style="background: #334155; color: white;"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

<ul class="custom-tabs" id="studentTabs">
    <li class="custom-tab-item active" data-target="profile">Student Profile</li>
    <li class="custom-tab-item" data-target="history">History</li>
</ul>

<div class="tab-content" id="studentTabsContent">
    <div class="custom-tab-pane active" id="profile">
        <form action="<?php echo $config['base_url']; ?>/students/update/<?php echo $student['id']; ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" id="raw_student_id" value="<?php echo htmlspecialchars($student['student_id_str']); ?>">
            
            <h4 style="margin-bottom: 1rem; color: var(--primary); border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">Personal Information</h4>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="form-label">Student ID</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['student_id_str']); ?>" readonly style="background: #334155; cursor: not-allowed; color: #cbd5e1;">
                </div>
                <div class="col-md-6 form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="Active" <?php echo $student['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $student['status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                </div>
                <div class="col-md-6 form-group">
                    <label class="form-label">CNIC (13 digits without dashes) *</label>
                    <input type="text" name="cnic" class="form-control" pattern="[0-9]{13}" title="13 digit numeric CNIC" value="<?php echo htmlspecialchars($student['cnic']); ?>" required>
                </div>
                <div class="col-md-6 form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($student['phone']); ?>" required>
                </div>
                <div class="col-md-6 form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>">
                </div>
                <div class="col-md-6 form-group">
                    <label class="form-label">Blood Group</label>
                    <select name="blood_group" class="form-control">
                        <option value="">Select</option>
                        <?php 
                        $bgs = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
                        foreach ($bgs as $bg) {
                            $sel = ($student['blood_group'] == $bg) ? 'selected' : '';
                            echo "<option value=\"$bg\" $sel>$bg</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-12 form-group">
                    <label class="form-label">Permanent Address *</label>
                    <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($student['address']); ?></textarea>
                </div>
            </div>
            
            <h4 style="margin-top: 1rem; margin-bottom: 1rem; color: var(--primary); border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">Guardian Information</h4>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="form-label">Guardian Name *</label>
                    <input type="text" name="guardian_name" class="form-control" value="<?php echo htmlspecialchars($student['guardian_name']); ?>" required>
                </div>
                <div class="col-md-6 form-group">
                    <label class="form-label">Relation *</label>
                    <input type="text" name="relation" class="form-control" value="<?php echo htmlspecialchars($student['relation']); ?>" placeholder="e.g. Father, Brother" required>
                </div>
                <div class="col-md-6 form-group">
                    <label class="form-label">Guardian Phone *</label>
                    <input type="text" name="guardian_phone" class="form-control" value="<?php echo htmlspecialchars($student['guardian_phone']); ?>" required>
                </div>
                <div class="col-md-6 form-group">
                    <label class="form-label">Guardian CNIC *</label>
                    <input type="text" name="guardian_cnic" class="form-control" value="<?php echo htmlspecialchars($student['guardian_cnic']); ?>" required>
                </div>
            </div>
            
            <div style="margin-top: 2rem;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Student</button>
            </div>
        </form>
    </div>
    
    <div class="custom-tab-pane" id="history">
        <div class="table-responsive mt-3">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Event</th>
                        <th>Description</th>
                        <th>Admin</th>
                        <th>Values</th>
                    </tr>
                </thead>
                <tbody id="studentHistoryTable">
                    <tr><td colspan="5" class="text-center" style="text-align: center;">Loading history...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<!-- Alumni Conversion Modal -->
<div class="custom-modal" id="alumniModal">
    <div class="custom-modal-content">
        <div class="custom-modal-header">
            <h4><i class="fas fa-sign-out-alt"></i> Mark as Alumni</h4>
            <span class="custom-close" onclick="closeAlumniModal()">&times;</span>
        </div>
        <div id="alumniModalBody">
            <p>Are you sure you want to transfer <strong><?php echo htmlspecialchars($student['full_name']); ?></strong> to alumni?</p>
            <form id="alumniForm">
                <input type="hidden" id="alumniStudentId" value="<?php echo $student['id']; ?>">
                <div class="form-group">
                    <label class="form-label">Leaving Date *</label>
                    <input type="date" id="alumniLeavingDate" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Leaving Reason *</label>
                    <select id="alumniLeavingReason" class="form-control" required>
                        <option value="">Select Reason</option>
                        <option value="Course Completed">Course Completed</option>
                        <option value="Graduation">Graduation</option>
                        <option value="Transfer">Transfer</option>
                        <option value="Personal Reasons">Personal Reasons</option>
                        <option value="Hostel Change">Hostel Change</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Remarks (Optional)</label>
                    <textarea id="alumniRemarks" class="form-control" rows="2"></textarea>
                </div>
                
                <div style="margin-top: 1rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-danger" id="alumniSubmitBtn">Confirm & Transfer</button>
                    <button type="button" class="btn btn-secondary" onclick="closeAlumniModal()" style="background:#475569; color:white;">Cancel</button>
                </div>
            </form>
            <div id="alumniResponse" style="margin-top: 1rem;"></div>
        </div>
    </div>
</div>

<script>
function escapeHTML(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.addEventListener('DOMContentLoaded', () => {
    // Custom Tabs Logic
    const tabs = document.querySelectorAll('.custom-tab-item');
    const panes = document.querySelectorAll('.custom-tab-pane');
    let historyLoaded = false;
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            panes.forEach(p => p.classList.remove('active'));
            
            tab.classList.add('active');
            const targetId = tab.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');
            
            if (targetId === 'history' && !historyLoaded) {
                loadStudentHistory();
                historyLoaded = true;
            }
        });
    });
    
    // Alumni Form Submit Logic
    const alumniForm = document.getElementById('alumniForm');
    if (alumniForm) {
        alumniForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const btn = document.getElementById('alumniSubmitBtn');
            const responseDiv = document.getElementById('alumniResponse');
            
            btn.disabled = true;
            btn.innerHTML = 'Processing...';
            
            const payload = {
                student_id: document.getElementById('alumniStudentId').value,
                leaving_date: document.getElementById('alumniLeavingDate').value,
                leaving_reason: document.getElementById('alumniLeavingReason').value,
                remarks: document.getElementById('alumniRemarks').value,
                csrf_token: '<?php echo htmlspecialchars($csrf_token); ?>'
            };
            
            fetch('<?php echo $config['base_url']; ?>/api/alumni/transfer', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': payload.csrf_token
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    responseDiv.innerHTML = `<div class="alert alert-success">${escapeHTML(data.message)}</div>`;
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    responseDiv.innerHTML = `<div class="alert alert-error">${escapeHTML(data.message)}</div>`;
                    btn.disabled = false;
                    btn.innerHTML = 'Confirm & Transfer';
                }
            })
            .catch(err => {
                responseDiv.innerHTML = `<div class="alert alert-error">Network error occurred.</div>`;
                btn.disabled = false;
                btn.innerHTML = 'Confirm & Transfer';
            });
        });
    }
});

function openAlumniModal() {
    document.getElementById('alumniModal').classList.add('show');
}

function closeAlumniModal() {
    document.getElementById('alumniModal').classList.remove('show');
}

function checkAlumniRecord() {
    const studentIdStr = document.getElementById('raw_student_id').value;
    
    fetch(`<?php echo $config['base_url']; ?>/api/alumni/student/${studentIdStr}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data) {
                window.location.href = `<?php echo $config['base_url']; ?>/alumni`;
                // Note: The ideal UX would be to open the Alumni Details modal or route to an alumni details page,
                // but since /alumni is the main page for it, redirecting there is safe.
            } else {
                alert('No alumni record found for this student.');
            }
        })
        .catch(err => alert('Failed to check alumni record.'));
}

function loadStudentHistory() {
    const studentId = <?php echo $student['id']; ?>;
    const tbody = document.getElementById('studentHistoryTable');
    
    fetch(`<?php echo $config['base_url']; ?>/student-history/student/${studentId}`)
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                const records = response.data.records;
                if (!records || records.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No history records found.</td></tr>';
                    return;
                }
                
                tbody.innerHTML = '';
                records.forEach(record => {
                    const oldVal = record.old_value ? JSON.stringify(record.old_value) : '';
                    const newVal = record.new_value ? JSON.stringify(record.new_value) : '';
                    
                    let valsHtml = '';
                    if (oldVal || newVal) {
                        const safeOld = escapeHTML(oldVal);
                        const safeNew = escapeHTML(newVal);
                        // Using a generic alert for now since we removed bootstrap modals
                        valsHtml = `<button type="button" class="btn btn-sm" style="background:var(--border); color:white;" onclick='alert("Data changes recorded. See main Audit page for full diff.")'><i class="fas fa-database"></i></button>`;
                    }
                    
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${escapeHTML(record.created_at)}</td>
                        <td><span class="badge" style="background-color:var(--primary-dark)">${escapeHTML(record.event_type)}</span></td>
                        <td>${escapeHTML(record.description)}</td>
                        <td>${record.admin_username ? escapeHTML(record.admin_username) : 'System'}</td>
                        <td>${valsHtml}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="5" class="text-danger" style="text-align: center;">${escapeHTML(response.message)}</td></tr>`;
            }
        })
        .catch(err => {
            tbody.innerHTML = '<tr><td colspan="5" class="text-danger" style="text-align: center;">Failed to load history data.</td></tr>';
        });
}
</script>
