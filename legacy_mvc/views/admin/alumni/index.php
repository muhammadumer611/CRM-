<?php $config = require APP_ROOT . '/config/app.php'; ?>
<style>
    .alumni-page .card-body { min-width: 0; }
    .alumni-filter-form { display: flex; flex-wrap: wrap; align-items: end; gap: 0.75rem; }
    .alumni-filter-form > div { min-width: 0; }
    .alumni-filter-form .search-field { flex: 1 1 280px; }
    .alumni-filter-form .filter-action { flex: 0 1 140px; }
    .alumni-table-wrap { width: 100%; overflow-x: auto; overscroll-behavior-x: contain; }
    .alumni-table-wrap table { min-width: 1180px; }
    .alumni-table-wrap td { vertical-align: middle; }
    .alumni-table-wrap td:last-child { white-space: nowrap; }
    .alumni-pagination { gap: 0.75rem; flex-wrap: wrap; }
    .alumni-pagination > div:last-child { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
    #alumniDetailsModal { padding: 0.75rem; }
    #alumniDetailsModal .modal-dialog { width: min(100%, 860px); max-height: 92vh; margin: auto; overflow: hidden; }
    #alumniDetailsModal .modal-content { max-height: 92vh; display: flex; flex-direction: column; }
    #alumniDetailsModal .modal-body { min-height: 0; overflow-y: auto; }
    .security-return-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; }
    .security-return-form .security-return-full { grid-column: 1 / -1; }
    .security-return-form .form-control { width: 100%; }
    @media (max-width: 600px) {
        .alumni-filter-form .filter-action { flex: 1 1 130px; }
        .alumni-filter-form .btn { width: 100%; justify-content: center; }
        .alumni-pagination { align-items: stretch !important; flex-direction: column; }
        .alumni-pagination > div:last-child { justify-content: space-between; }
        #alumniDetailsModal .modal-header { gap: 0.75rem; align-items: flex-start; }
        #alumniDetailsModal .modal-title { line-height: 1.35; }
        .security-return-form { grid-template-columns: 1fr; }
    }
</style>
<div class="card alumni-page">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0">Alumni Records</h3>
    </div>
    
    <div class="card-body">
        <form id="alumniFilterForm" class="alumni-filter-form mb-4">
            <div class="search-field">
                <input type="text" class="form-control" id="searchKeyword" placeholder="Search by Name, CNIC, or ID...">
            </div>
            <div class="filter-action">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Search</button>
            </div>
            <div class="filter-action">
                <button type="button" class="btn btn-secondary w-100" id="resetFilters"><i class="fas fa-undo"></i> Reset</button>
            </div>
        </form>

        <div class="table-responsive alumni-table-wrap">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>Original ID</th>
                        <th>Name / CNIC</th>
                        <th>Previous Room / Stay</th>
                        <th>Checkout Date</th>
                        <th>Reason</th>
                        <th>Fee Status</th>
                        <th>Current Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="alumniTableBody">
                    <tr><td colspan="8" class="text-center">Loading alumni records...</td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mt-3 alumni-pagination">
            <div id="paginationInfo">Showing 0 records</div>
            <div>
                <button class="btn btn-sm btn-outline-primary me-2" id="prevPage" disabled>Previous</button>
                <span id="currentPageDisplay" class="mx-2">Page 1</span>
                <button class="btn btn-sm btn-outline-primary ms-2" id="nextPage" disabled>Next</button>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="alumniDetailsModal" tabindex="-1" style="display: none;" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Alumni Record Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="closeModal()"></button>
      </div>
      <div class="modal-body" id="alumniDetailsBody">
        Loading...
      </div>
    </div>
  </div>
</div>

<script>
let currentPage = 1;
const limit = 25;

document.addEventListener('DOMContentLoaded', () => {
    loadAlumni();
    
    document.getElementById('alumniFilterForm').addEventListener('submit', (e) => {
        e.preventDefault();
        currentPage = 1;
        loadAlumni();
    });
    
    document.getElementById('resetFilters').addEventListener('click', () => {
        document.getElementById('alumniFilterForm').reset();
        currentPage = 1;
        loadAlumni();
    });
    
    document.getElementById('prevPage').addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            loadAlumni();
        }
    });
    
    document.getElementById('nextPage').addEventListener('click', () => {
        currentPage++;
        loadAlumni();
    });

    document.getElementById('alumniDetailsBody').addEventListener('submit', (event) => {
        if (event.target.matches('.security-return-form')) {
            event.preventDefault();
            submitSecurityReturn(event.target);
        }
    });
    document.getElementById('alumniDetailsBody').addEventListener('input', (event) => {
        if (event.target.matches('.security-return-form [name="security_deduction"]')) {
            const form = event.target.form;
            const remaining = Number(form.dataset.remaining || 0);
            const deduction = Math.max(0, Number(event.target.value || 0));
            const finalReturned = Math.max(0, remaining - deduction).toFixed(2);
            form.querySelector('#securityReturnAmount').value = finalReturned;
        }
    });
});

function loadAlumni() {
    const searchParams = new URLSearchParams();
    searchParams.append('page', currentPage);
    searchParams.append('limit', limit);
    
    const keyword = document.getElementById('searchKeyword').value;
    if(keyword) searchParams.append('search', keyword);
    
    const tbody = document.getElementById('alumniTableBody');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center">Loading...</td></tr>';
    
    fetch(`<?php echo $config['base_url']; ?>/api/alumni?${searchParams.toString()}`)
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                renderTable(response.data.records);
                updatePagination(response.data.pagination);
            } else {
                tbody.innerHTML = `<tr><td colspan="8" class="text-danger text-center">${escapeHTML(response.message)}</td></tr>`;
            }
        })
        .catch(err => {
            tbody.innerHTML = '<tr><td colspan="8" class="text-danger text-center">Failed to load alumni data.</td></tr>';
        });
}

function escapeHTML(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderTable(records) {
    const tbody = document.getElementById('alumniTableBody');
    if (!records || records.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No alumni records found.</td></tr>';
        return;
    }
    
    tbody.innerHTML = '';
    records.forEach(record => {
        let feeBadge = record.final_fee_status === 'Cleared' 
            ? '<span class="badge bg-success">Cleared</span>' 
            : `<span class="badge bg-danger">${escapeHTML(record.final_fee_status)}</span>`;
            
        let roomStr = record.previous_room
            ? `${escapeHTML(record.previous_room)}${record.previous_bed ? ` (Bed: ${escapeHTML(record.previous_bed)})` : ''}<br><small class="text-muted">Stay: ${escapeHTML(record.joining_date || '—')} to ${escapeHTML(record.leaving_date || '—')}</small>`
            : '<span class="text-muted">None</span>';
        const studentStatus = String(record.current_student_status || 'Unknown');
        const isEligible = studentStatus === 'Inactive';
        const statusMarkup = isEligible
            ? '<span class="badge bg-secondary">Inactive · Alumni</span>'
            : studentStatus === 'Active'
                ? `<span class="badge bg-success">Active${record.current_room ? ` · ${escapeHTML(record.current_room)}${record.current_bed ? ` / Bed ${escapeHTML(record.current_bed)}` : ''}` : ''}</span>`
                : `<span class="badge bg-secondary">${escapeHTML(studentStatus)}</span>`;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><span class="badge bg-dark">${escapeHTML(record.original_student_id)}</span></td>
            <td>
                <strong>${escapeHTML(record.name)}</strong><br>
                <small class="text-muted">${escapeHTML(record.cnic)}</small>
            </td>
            <td>${roomStr}</td>
            <td>${escapeHTML(record.leaving_date)}</td>
            <td>${escapeHTML(record.leaving_reason)}</td>
            <td>${feeBadge}</td>
            <td>${statusMarkup}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="viewDetails(${record.id})">
                    <i class="fas fa-eye"></i> Details
                </button>
                ${isEligible ? `<a class="btn btn-sm btn-primary" href="<?php echo $config['base_url']; ?>/alumni/readmit/${Number(record.id)}"><i class="fa-solid fa-arrow-rotate-left"></i> Reactivate Student</a>` : ''}
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function updatePagination(pagination) {
    document.getElementById('currentPageDisplay').textContent = `Page ${pagination.current_page} of ${pagination.total_pages || 1}`;
    document.getElementById('paginationInfo').textContent = `Total Records: ${pagination.total_records}`;
    
    document.getElementById('prevPage').disabled = (pagination.current_page <= 1);
    document.getElementById('nextPage').disabled = (pagination.current_page >= pagination.total_pages);
}

function getStatusBadgeMarkup(status) {
    const normalized = (status || '').toString().trim();
    const label = normalized || 'Unknown';
    const lower = normalized.toLowerCase();

    if (lower.includes('cleared')) {
        return `<span class="status-badge badge-success">${escapeHTML(label)}</span>`;
    }
    if (lower.includes('pending')) {
        return `<span class="status-badge badge-warning">${escapeHTML(label)}</span>`;
    }
    if (lower.includes('overdue') || lower.includes('due')) {
        return `<span class="status-badge badge-danger">${escapeHTML(label)}</span>`;
    }
    return `<span class="status-badge badge-secondary">${escapeHTML(label)}</span>`;
}

function parseJsonSafely(value) {
    if (!value) return {};
    if (typeof value === 'object') return value;

    try {
        return JSON.parse(value);
    } catch (error) {
        return {};
    }
}

function canReturnSecurityStatus(securityDeposit) {
    return ['PAID', 'HELD', 'ADJUSTED', 'PARTIALLY_REFUNDED'].includes(String(securityDeposit.status || '').toUpperCase())
        && Number(securityDeposit.remaining_amount || 0) > 0;
}

function renderDetailSection(title, iconClass, fields) {
    const fieldMarkup = fields.map((field, index) => {
        const isFullWidth = field.fullWidth === true;
        const value = field.value ?? 'N/A';
        return `
            <div class="detail-item${isFullWidth ? ' detail-item-full' : ''}">
                <span class="detail-label">${escapeHTML(field.label)}</span>
                <span class="detail-value${field.muted ? ' detail-value-muted' : ''}">${field.html ? field.html : escapeHTML(value)}</span>
            </div>
        `;
    }).join('');

    return `
        <div class="alumni-section">
            <div class="section-header">
                <i class="${iconClass}"></i>
                <h3>${escapeHTML(title)}</h3>
            </div>
            <div class="details-grid">
                ${fieldMarkup}
            </div>
        </div>
    `;
}

function viewDetails(id) {
    const modal = document.getElementById('alumniDetailsModal');
    modal.style.display = 'block';
    modal.classList.add('show');
    
    const body = document.getElementById('alumniDetailsBody');
    body.innerHTML = 'Loading...';
    
    fetch(`<?php echo $config['base_url']; ?>/api/alumni/${id}`)
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                const r = response.data;
                const guardian = parseJsonSafely(r.guardian_info);
                const studentFields = [
                    { label: 'Original Student ID', value: r.original_student_id },
                    { label: 'Alumni ID', value: r.id },
                    { label: 'Name', value: r.name },
                    { label: 'CNIC', value: r.cnic },
                    { label: 'Phone', value: r.phone },
                    { label: 'Fee Status', html: getStatusBadgeMarkup(r.final_fee_status) }
                ];

                const accommodationFields = [
                    { label: 'Previous Room', value: `${r.previous_room || 'N/A'}${r.previous_bed ? ` (Bed: ${r.previous_bed})` : ''}` },
                    { label: 'Joining Date', value: r.joining_date || 'N/A' },
                    { label: 'Leaving Date', value: r.leaving_date || 'N/A' },
                    { label: 'Reason', value: r.leaving_reason || 'N/A' },
                    { label: 'Remarks', value: r.remarks || 'No remarks provided.', fullWidth: true }
                ];

                const guardianFields = [
                    { label: 'Guardian Name', value: guardian.name || 'N/A' },
                    { label: 'Relationship', value: guardian.relation || 'N/A' },
                    { label: 'Guardian Phone', value: guardian.phone || 'N/A' },
                    { label: 'Guardian CNIC', value: guardian.cnic || 'N/A' }
                ];

                const securityDeposit = r.security_deposit;
                let securityMarkup = '';
                if (securityDeposit) {
                    const securityTransactions = Array.isArray(r.security_transactions) ? r.security_transactions : [];
                    const totalDeducted = securityTransactions
                        .filter(transaction => ['ADJUSTMENT', 'FORFEIT'].includes(transaction.transaction_type))
                        .reduce((total, transaction) => total + Number(transaction.amount || 0), 0);
                    const totalReturned = securityTransactions
                        .filter(transaction => transaction.transaction_type === 'REFUND')
                        .reduce((total, transaction) => total + Number(transaction.amount || 0), 0);
                    const securityFields = [
                        { label: 'Security Record Status', value: securityDeposit.status },
                        { label: 'Settlement Status', value: canReturnSecurityStatus(securityDeposit) ? 'HELD / PENDING RETURN' : securityDeposit.status },
                        { label: 'Original Security', value: `Rs. ${Number(securityDeposit.original_amount || 0).toFixed(2)}` },
                        { label: 'Returned', value: `Rs. ${totalReturned.toFixed(2)}` },
                        { label: 'Deduction', value: `Rs. ${totalDeducted.toFixed(2)}` },
                        { label: 'Remaining Security', value: `Rs. ${Number(securityDeposit.remaining_amount || 0).toFixed(2)}` },
                        { label: 'Processed By', value: securityDeposit.processed_by_name || 'N/A' },
                        { label: 'Checked Out By', value: r.checked_out_by_name || 'N/A' },
                        { label: 'Security Reason / Note', value: securityDeposit.note || 'N/A', fullWidth: true }
                    ];
                    if (securityDeposit.expected_payment_date) {
                        securityFields.splice(5, 0, { label: 'Expected Payment Date', value: securityDeposit.expected_payment_date });
                    }
                    const remainingSecurity = Number(securityDeposit.remaining_amount || 0);
                    const canReturnSecurity = canReturnSecurityStatus(securityDeposit);
                    if (canReturnSecurity) {
                        securityFields.splice(5, 0, { label: 'Return Status', value: 'Return pending' });
                    }
                    const securityRows = securityTransactions.length
                        ? securityTransactions.map(transaction => `
                            <tr>
                                <td>${escapeHTML(transaction.payment_date || transaction.created_at || '—')}</td>
                                <td>${escapeHTML(String(transaction.transaction_type || '').replace(/_/g, ' '))}</td>
                                <td>Rs. ${Number(transaction.amount || 0).toFixed(2)}</td>
                                <td>${escapeHTML(transaction.payment_method || '—')}</td>
                                <td>${escapeHTML(transaction.reference_number || '—')}</td>
                                <td>${escapeHTML(transaction.processed_by_name || transaction.admin_username || '—')}</td>
                                <td>${escapeHTML(transaction.reason || '—')}</td>
                            </tr>
                        `).join('')
                        : '<tr><td colspan="7" class="text-center">No security transactions recorded.</td></tr>';
                    const lifecycleHistory = Array.isArray(r.security_lifecycle_history) ? r.security_lifecycle_history : [];
                    const lifecycleRows = lifecycleHistory.length
                        ? lifecycleHistory.map(event => `
                            <tr>
                                <td>${escapeHTML(event.created_at || '—')}</td>
                                <td>${escapeHTML(String(event.event_type || '').replace(/_/g, ' '))}</td>
                                <td>${escapeHTML(event.description || '—')}</td>
                                <td>${escapeHTML(event.admin_username || '—')}</td>
                            </tr>
                        `).join('')
                        : '<tr><td colspan="4" class="text-center">No security lifecycle events recorded.</td></tr>';
                    const securityReturnMarkup = canReturnSecurity
                        ? `
                            <div class="alumni-section">
                                <div class="section-header"><i class="fa-solid fa-hand-holding-dollar"></i><h3>Security Return</h3></div>
                                <p>Security remains held after checkout. Record its return or deduction here; the remaining balance will become Rs. 0.00.</p>
                                <form class="security-return-form" data-alumni-id="${Number(r.id)}" data-remaining="${remainingSecurity}">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <div>
                                        <label class="form-label" for="securityReturnHeld">Security Held (Rs.)</label>
                                        <input class="form-control" id="securityReturnHeld" type="text" value="${remainingSecurity.toFixed(2)}" readonly>
                                    </div>
                                    <div>
                                        <label class="form-label" for="securityReturnGross">Amount Returned Before Deduction (Rs.)</label>
                                        <input class="form-control" id="securityReturnGross" type="text" value="${remainingSecurity.toFixed(2)}" readonly>
                                    </div>
                                    <div>
                                        <label class="form-label" for="securityReturnDeduction">Security Deduction (Rs.)</label>
                                        <input class="form-control" id="securityReturnDeduction" name="security_deduction" type="number" min="0" max="${remainingSecurity.toFixed(2)}" step="0.01" value="0" required>
                                    </div>
                                    <div>
                                        <label class="form-label" for="securityReturnAmount">Final Returned (Rs.)</label>
                                        <input class="form-control" id="securityReturnAmount" type="text" value="${remainingSecurity.toFixed(2)}" readonly>
                                    </div>
                                    <div class="security-return-full">
                                        <label class="form-label" for="securityReturnRemarks">Reason / Remarks</label>
                                        <textarea class="form-control" id="securityReturnRemarks" name="remarks" rows="2" placeholder="Required when entering a deduction"></textarea>
                                    </div>
                                    <div>
                                        <label class="form-label" for="securityReturnProcessedBy">Processed By *</label>
                                        <input class="form-control" id="securityReturnProcessedBy" name="processed_by_name" value="<?php echo htmlspecialchars((string)(\App\Core\Auth::user() ?: 'Admin'), ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>
                                    <div>
                                        <label class="form-label">Checked Out By</label>
                                        <input class="form-control" value="${escapeHTML(r.checked_out_by_name || 'N/A')}" readonly>
                                    </div>
                                    <div class="security-return-full">
                                        <div class="security-return-message" role="status" aria-live="polite"></div>
                                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-hand-holding-dollar"></i> Record Security Return</button>
                                    </div>
                                </form>
                            </div>
                        `
                        : '';
                    securityMarkup = `
                        ${renderDetailSection('Security Deposit Settlement', 'fa-solid fa-shield-halved', securityFields)}
                        ${securityReturnMarkup}
                        <div class="alumni-section">
                            <div class="section-header"><i class="fa-solid fa-clock-rotate-left"></i><h3>Security Transaction History</h3></div>
                            <div class="alumni-table-wrap">
                                <table>
                                    <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Method</th><th>Reference</th><th>Processed By</th><th>Reason</th></tr></thead>
                                    <tbody>${securityRows}</tbody>
                                </table>
                            </div>
                            <div class="alumni-section">
                                <div class="section-header"><i class="fa-solid fa-list-check"></i><h3>Security & Alumni Audit History</h3></div>
                                <div class="alumni-table-wrap">
                                    <table>
                                        <thead><tr><th>Date</th><th>Event</th><th>Details</th><th>Admin</th></tr></thead>
                                        <tbody>${lifecycleRows}</tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    securityMarkup = renderDetailSection('Security Deposit', 'fa-solid fa-shield-halved', [{ label: 'Status', value: 'No security deposit record' }]);
                }

                const additionalInfo = [
                    r.additional_info,
                    r.additional_information,
                    r.notes,
                    r.status,
                    r.student_status
                ].find(value => value !== null && value !== undefined && value !== '');

                let additionalMarkup = '';
                if (additionalInfo) {
                    additionalMarkup = `
                        <div class="alumni-section">
                            <div class="section-header">
                                <i class="fa-solid fa-circle-info"></i>
                                <h3>Additional Information</h3>
                            </div>
                            <div class="detail-item detail-item-full">
                                <span class="detail-label">Notes</span>
                                <span class="detail-note">${escapeHTML(additionalInfo)}</span>
                            </div>
                        </div>
                    `;
                }

                body.innerHTML = `
                    <div class="alumni-details">
                        ${renderDetailSection('Student Information', 'fa-solid fa-user-graduate', studentFields)}
                        ${renderDetailSection('Accommodation & Duration', 'fa-solid fa-house-user', accommodationFields)}
                        ${renderDetailSection('Guardian Information', 'fa-solid fa-user-shield', guardianFields)}
                        ${securityMarkup}
                        ${additionalMarkup}
                    </div>
                `;
            }
        })
        .catch(err => {
            body.innerHTML = '<p class="text-danger">Failed to load record details.</p>';
        });
}

async function submitSecurityReturn(form) {
    const formData = new FormData(form);
    const deductionValue = formData.get('security_deduction');
    const deduction = Number(deductionValue);
    const remaining = Number(form.dataset.remaining || 0);
    const remarks = String(formData.get('remarks') || '').trim();
    const message = form.querySelector('.security-return-message');
    const button = form.querySelector('button[type="submit"]');

    if (!Number.isFinite(deduction) || deduction < 0 || deduction > remaining
        || !/^\d+(\.\d{1,2})?$/.test(String(deductionValue || '').trim())) {
        message.textContent = 'Enter a deduction between Rs. 0.00 and the remaining held amount.';
        message.className = 'security-return-message text-danger';
        return;
    }
    if (deduction > 0 && !remarks) {
        message.textContent = 'Reason / Remarks are required when entering a deduction.';
        message.className = 'security-return-message text-danger';
        form.querySelector('[name="remarks"]').focus();
        return;
    }

    button.disabled = true;
    button.textContent = 'Processing security return...';
    message.textContent = '';
    try {
        const response = await fetch(`<?php echo $config['base_url']; ?>/api/alumni/${form.dataset.alumniId}/security-return`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                csrf_token: formData.get('csrf_token'),
                security_deduction: deductionValue,
                remarks,
                processed_by_name: formData.get('processed_by_name')
            })
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Unable to record security return.');
        }
        message.textContent = result.message;
        message.className = 'security-return-message text-success';
        viewDetails(Number(form.dataset.alumniId));
    } catch (error) {
        message.textContent = error.message || 'Unable to record security return.';
        message.className = 'security-return-message text-danger';
        button.disabled = false;
        button.innerHTML = '<i class="fa-solid fa-hand-holding-dollar"></i> Record Security Return';
    }
}

function closeModal() {
    const modal = document.getElementById('alumniDetailsModal');
    modal.style.display = 'none';
    modal.classList.remove('show');
}
</script>
