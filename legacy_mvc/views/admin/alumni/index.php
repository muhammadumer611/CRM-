<?php $config = require __DIR__ . '/../../../config/app.php'; ?>
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
                const guardian = r.guardian_info ? JSON.parse(r.guardian_info) : {};
                
                body.innerHTML = `
                    <div class="row mb-3">
                        <div class="col-sm-6"><strong>Alumni ID:</strong> ${escapeHTML(r.id)}</div>
                        <div class="col-sm-6"><strong>Original Student ID:</strong> <span class="badge bg-dark">${escapeHTML(r.original_student_id)}</span></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-6"><strong>Name:</strong> ${escapeHTML(r.name)}</div>
                        <div class="col-sm-6"><strong>CNIC:</strong> ${escapeHTML(r.cnic)}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-6"><strong>Phone:</strong> ${escapeHTML(r.phone)}</div>
                        <div class="col-sm-6"><strong>Fee Status:</strong> ${escapeHTML(r.final_fee_status)}</div>
                    </div>
                    <hr>
                    <h6 class="mb-3">Accommodation & Duration</h6>
                    <div class="row mb-3">
                        <div class="col-sm-6"><strong>Previous Room:</strong> ${escapeHTML(r.previous_room || 'N/A')} (Bed: ${escapeHTML(r.previous_bed || 'N/A')})</div>
                        <div class="col-sm-6"><strong>Joining Date:</strong> ${escapeHTML(r.joining_date || 'N/A')}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-6"><strong>Leaving Date:</strong> ${escapeHTML(r.leaving_date)}</div>
                        <div class="col-sm-6"><strong>Reason:</strong> ${escapeHTML(r.leaving_reason)}</div>
                    </div>
                    <div class="mb-3">
                        <strong>Remarks:</strong>
                        <div class="p-2 bg-light border mt-1">${escapeHTML(r.remarks || 'No remarks provided.')}</div>
                    </div>
                    <hr>
                    <h6 class="mb-3">Guardian Information</h6>
                    <div class="row mb-3">
                        <div class="col-sm-6"><strong>Name:</strong> ${escapeHTML(guardian.name || 'N/A')}</div>
                        <div class="col-sm-6"><strong>Relation:</strong> ${escapeHTML(guardian.relation || 'N/A')}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-6"><strong>Phone:</strong> ${escapeHTML(guardian.phone || 'N/A')}</div>
                        <div class="col-sm-6"><strong>CNIC:</strong> ${escapeHTML(guardian.cnic || 'N/A')}</div>
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
