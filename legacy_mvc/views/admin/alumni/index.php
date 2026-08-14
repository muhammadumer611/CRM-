<?php $config = require APP_ROOT . '/config/app.php'; ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0">Alumni Records</h3>
    </div>
    
    <div class="card-body">
        <form id="alumniFilterForm" class="row g-3 mb-4">
            <div class="col-md-4">
                <input type="text" class="form-control" id="searchKeyword" placeholder="Search by Name, CNIC, or ID...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Search</button>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-secondary w-100" id="resetFilters"><i class="fas fa-undo"></i> Reset</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>Original ID</th>
                        <th>Name / CNIC</th>
                        <th>Previous Room</th>
                        <th>Date Left</th>
                        <th>Reason</th>
                        <th>Fee Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="alumniTableBody">
                    <tr><td colspan="7" class="text-center">Loading alumni records...</td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mt-3">
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
});

function loadAlumni() {
    const searchParams = new URLSearchParams();
    searchParams.append('page', currentPage);
    searchParams.append('limit', limit);
    
    const keyword = document.getElementById('searchKeyword').value;
    if(keyword) searchParams.append('search', keyword);
    
    const tbody = document.getElementById('alumniTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center">Loading...</td></tr>';
    
    fetch(`<?php echo $config['base_url']; ?>/api/alumni?${searchParams.toString()}`)
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                renderTable(response.data.records);
                updatePagination(response.data.pagination);
            } else {
                tbody.innerHTML = `<tr><td colspan="7" class="text-danger text-center">${response.message}</td></tr>`;
            }
        })
        .catch(err => {
            tbody.innerHTML = '<tr><td colspan="7" class="text-danger text-center">Failed to load alumni data.</td></tr>';
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
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No alumni records found.</td></tr>';
        return;
    }
    
    tbody.innerHTML = '';
    records.forEach(record => {
        let feeBadge = record.final_fee_status === 'Cleared' 
            ? '<span class="badge bg-success">Cleared</span>' 
            : `<span class="badge bg-danger">${escapeHTML(record.final_fee_status)}</span>`;
            
        let roomStr = record.previous_room ? `${escapeHTML(record.previous_room)} (Bed: ${escapeHTML(record.previous_bed)})` : '<span class="text-muted">None</span>';

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
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="viewDetails(${record.id})">
                    <i class="fas fa-eye"></i> Details
                </button>
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
                        ${additionalMarkup}
                    </div>
                `;
            }
        })
        .catch(err => {
            body.innerHTML = '<p class="text-danger">Failed to load record details.</p>';
        });
}

function closeModal() {
    const modal = document.getElementById('alumniDetailsModal');
    modal.style.display = 'none';
    modal.classList.remove('show');
}
</script>
