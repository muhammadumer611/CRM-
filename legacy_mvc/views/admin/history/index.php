<?php $config = require __DIR__ . '/../../../config/app.php'; ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0">Student History & Audit Trail</h3>
    </div>
    
    <div class="card-body">
        <form id="historyFilterForm" class="row g-3 mb-4">
            <div class="col-md-3">
                <input type="text" class="form-control" id="searchKeyword" placeholder="Search description or event...">
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" id="searchStudent" placeholder="Student ID (e.g. STU-0001)">
            </div>
            <div class="col-md-2">
                <select class="form-select form-control" id="searchEvent">
                    <option value="">All Events</option>
                    <option value="STUDENT_CREATED">Student Created</option>
                    <option value="STUDENT_UPDATED">Student Updated</option>
                    <option value="STUDENT_DISABLED">Student Disabled</option>
                    <option value="STUDENT_ENABLED">Student Enabled</option>
                    <option value="ROOM_ALLOCATED">Room Allocated</option>
                    <option value="ROOM_CHANGED">Room Changed</option>
                    <option value="ROOM_DEALLOCATED">Room Deallocated</option>
                    <option value="FEE_CREATED">Fee Created</option>
                    <option value="FEE_PAYMENT">Fee Payment</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" id="searchDateFrom" placeholder="Date From">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" id="searchDateTo" placeholder="Date To">
            </div>
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                <button type="button" class="btn btn-secondary" id="resetFilters"><i class="fas fa-undo"></i> Reset</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Student</th>
                        <th>Event</th>
                        <th>Description</th>
                        <th>Admin</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    <tr><td colspan="6" class="text-center">Loading history...</td></tr>
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
<div class="modal fade" id="historyDetailsModal" tabindex="-1" style="display: none;" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">History Record Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="closeModal()"></button>
      </div>
      <div class="modal-body" id="historyDetailsBody">
        Loading...
      </div>
    </div>
  </div>
</div>

<script>
let currentPage = 1;
const limit = 25;

document.addEventListener('DOMContentLoaded', () => {
    loadHistory();
    
    document.getElementById('historyFilterForm').addEventListener('submit', (e) => {
        e.preventDefault();
        currentPage = 1;
        loadHistory();
    });
    
    document.getElementById('resetFilters').addEventListener('click', () => {
        document.getElementById('historyFilterForm').reset();
        currentPage = 1;
        loadHistory();
    });
    
    document.getElementById('prevPage').addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            loadHistory();
        }
    });
    
    document.getElementById('nextPage').addEventListener('click', () => {
        currentPage++;
        loadHistory();
    });
});

function loadHistory() {
    const searchParams = new URLSearchParams();
    searchParams.append('page', currentPage);
    searchParams.append('limit', limit);
    
    const keyword = document.getElementById('searchKeyword').value;
    if(keyword) searchParams.append('search', keyword);
    
    const student = document.getElementById('searchStudent').value;
    if(student) searchParams.append('student_id_str', student); // Note: Backend uses student_id for numeric ID, search handles keyword
    
    const event = document.getElementById('searchEvent').value;
    if(event) searchParams.append('event_type', event);
    
    const dateFrom = document.getElementById('searchDateFrom').value;
    if(dateFrom) searchParams.append('date_from', dateFrom);
    
    const dateTo = document.getElementById('searchDateTo').value;
    if(dateTo) searchParams.append('date_to', dateTo);

    const tbody = document.getElementById('historyTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center">Loading...</td></tr>';
    
    fetch(`<?php echo $config['base_url']; ?>/student-history?${searchParams.toString()}`)
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                renderTable(response.data.records);
                updatePagination(response.data.pagination);
            } else {
                tbody.innerHTML = `<tr><td colspan="6" class="text-danger text-center">${response.message}</td></tr>`;
            }
        })
        .catch(err => {
            tbody.innerHTML = '<tr><td colspan="6" class="text-danger text-center">Failed to load history data.</td></tr>';
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
    const tbody = document.getElementById('historyTableBody');
    if (!records || records.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No history records found.</td></tr>';
        return;
    }
    
    tbody.innerHTML = '';
    records.forEach(record => {
        let adminText = record.admin_username ? escapeHTML(record.admin_username) : '<span class="text-muted">System</span>';
        
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${escapeHTML(record.created_at)}</td>
            <td><strong>${escapeHTML(record.student_name)}</strong><br><small class="text-muted">${escapeHTML(record.student_id_str)}</small></td>
            <td><span class="badge bg-info">${escapeHTML(record.event_type)}</span></td>
            <td>${escapeHTML(record.description)}</td>
            <td>${adminText}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="viewDetails(${record.id})">
                    <i class="fas fa-eye"></i> View
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
    const modal = document.getElementById('historyDetailsModal');
    modal.style.display = 'block';
    modal.classList.add('show');
    
    const body = document.getElementById('historyDetailsBody');
    body.innerHTML = 'Loading...';
    
    fetch(`<?php echo $config['base_url']; ?>/student-history/${id}`)
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                const r = response.data;
                const oldVal = r.old_value ? JSON.stringify(r.old_value, null, 2) : 'None';
                const newVal = r.new_value ? JSON.stringify(r.new_value, null, 2) : 'None';
                
                body.innerHTML = `
                    <div class="row mb-3">
                        <div class="col-sm-6"><strong>Student:</strong> ${escapeHTML(r.student_name)} (${escapeHTML(r.student_id_str)})</div>
                        <div class="col-sm-6"><strong>Date:</strong> ${escapeHTML(r.created_at)}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-6"><strong>Event Type:</strong> <span class="badge bg-info">${escapeHTML(r.event_type)}</span></div>
                        <div class="col-sm-6"><strong>Admin:</strong> ${r.admin_username ? escapeHTML(r.admin_username) : 'System'}</div>
                    </div>
                    <div class="mb-4">
                        <strong>Description:</strong>
                        <div class="p-2 bg-light border mt-1">${escapeHTML(r.description)}</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Old Values:</strong>
                            <pre class="bg-light p-2 border mt-1" style="max-height: 200px; overflow-y: auto;">${oldVal}</pre>
                        </div>
                        <div class="col-md-6">
                            <strong>New Values:</strong>
                            <pre class="bg-light p-2 border mt-1" style="max-height: 200px; overflow-y: auto;">${newVal}</pre>
                        </div>
                    </div>
                `;
            }
        })
        .catch(err => {
            body.innerHTML = '<p class="text-danger">Failed to load record details.</p>';
        });
}

function closeModal() {
    const modal = document.getElementById('historyDetailsModal');
    modal.style.display = 'none';
    modal.classList.remove('show');
}
</script>
