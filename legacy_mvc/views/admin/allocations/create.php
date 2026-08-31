<?php $config = require APP_ROOT . '/config/app.php'; ?>
<style>
    .bed-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 1rem;
        margin-top: 0.75rem;
    }
    .bed-card {
        background: #0f172a;
        border: 2px solid var(--border);
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
        transition: all 0.2s ease;
        position: relative;
        user-select: none;
    }
    .bed-card.available {
        cursor: pointer;
        border-color: rgba(56, 189, 248, 0.4);
    }
    .bed-card.available:hover {
        border-color: var(--primary);
        background: rgba(56, 189, 248, 0.08);
        transform: translateY(-2px);
    }
    .bed-card.selected {
        border-color: var(--primary) !important;
        background: rgba(56, 189, 248, 0.18) !important;
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.3);
    }
    .bed-card.occupied {
        opacity: 0.7;
        cursor: not-allowed;
        border-color: rgba(239, 68, 68, 0.3);
        background: rgba(15, 23, 42, 0.5);
    }
    .bed-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        display: block;
    }
    .bed-number {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 0.35rem;
    }
    .bed-status-badge {
        display: inline-block;
        padding: 0.25rem 0.6rem;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 9999px;
        text-transform: uppercase;
    }
    .badge-available {
        background: rgba(16, 185, 129, 0.15);
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }
    .badge-occupied {
        background: rgba(239, 68, 68, 0.15);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }
    .room-summary-box {
        background: #0f172a;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        display: none;
    }
</style>

<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title">New Room Allocation</h3>
            <small style="color: var(--text-muted);">Assign an active student to an available room and bed</small>
        </div>
        <a href="<?php echo $config['base_url']; ?>/allocations" class="btn" style="background: #334155; color: white;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <form action="<?php echo $config['base_url']; ?>/allocations/store" method="POST" id="allocationForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <input type="hidden" name="bed_number" id="selected_bed_number" value="" required>
        
        <div class="row">
            <div class="col-md-6 form-group">
                <label class="form-label">Select Student *</label>
                <select name="student_id" class="form-control" required>
                    <option value="">-- Choose Active Student --</option>
                    <?php if (empty($students)): ?>
                        <option value="" disabled>No unallocated active students found</option>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>">
                                <?php echo htmlspecialchars($student['student_id_str'] . ' — ' . $student['full_name'] . ' (' . \App\Services\StudentService::formatCnic($student['cnic']) . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <small style="color: var(--text-muted);">Only active students without an existing active room allocation are shown.</small>
            </div>
            
            <div class="col-md-6 form-group">
                <label class="form-label">Select Room *</label>
                <select name="room_id" class="form-control" id="room_select" required>
                    <option value="">-- Choose Room --</option>
                    <?php foreach ($rooms as $room): ?>
                        <?php $availBeds = max(0, $room['total_beds'] - $room['occupied_beds']); ?>
                        <option value="<?php echo $room['id']; ?>" data-avail="<?php echo $availBeds; ?>" data-total="<?php echo $room['total_beds']; ?>">
                            <?php echo htmlspecialchars('Room ' . $room['room_number'] . ' (' . $room['block'] . ' - Floor ' . $room['floor'] . ') — ' . $availBeds . '/' . $room['total_beds'] . ' Beds Available'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12" id="roomSummaryBoxContainer">
                <div id="roomSummaryBox" class="room-summary-box">
                    <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 1rem; align-items: center;">
                        <div>
                            <strong id="summaryRoomName" style="font-size: 1.1rem; color: var(--primary);">Room Details</strong>
                            <div id="summaryRoomDetails" style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;"></div>
                        </div>
                        <div id="summaryRoomBadges" style="display: flex; gap: 0.5rem; flex-wrap: wrap;"></div>
                    </div>
                </div>
            </div>

            <div class="col-12 form-group">
                <label class="form-label" style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Visual Bed Selector * <span style="font-weight: normal; color: var(--text-muted);">(Click an available bed to select)</span></span>
                    <span id="bedSelectionStatus" style="font-weight: 600; color: #f59e0b;">No bed selected</span>
                </label>

                <div id="bedGridContainer">
                    <div style="padding: 2rem; text-align: center; border: 2px dashed var(--border); border-radius: 8px; color: var(--text-muted);">
                        <i class="fas fa-arrow-up" style="font-size: 1.5rem; margin-bottom: 0.5rem; display: block;"></i>
                        Please select a room above to view available beds.
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 form-group">
                <label class="form-label">Joining Date *</label>
                <input type="date" name="joining_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="col-md-6 form-group">
                <label class="form-label">Remarks / Notes (Optional)</label>
                <input type="text" name="remarks" class="form-control" placeholder="e.g. Assigned top bed, paid cash deposit">
            </div>
        </div>

        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary" id="submitBtn"><i class="fas fa-check"></i> Confirm Allocation</button>
            <a href="<?php echo $config['base_url']; ?>/allocations" class="btn" style="background: #334155; color: white;">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roomSelect = document.getElementById('room_select');
    const bedGridContainer = document.getElementById('bedGridContainer');
    const selectedBedInput = document.getElementById('selected_bed_number');
    const bedSelectionStatus = document.getElementById('bedSelectionStatus');
    const roomSummaryBox = document.getElementById('roomSummaryBox');
    const summaryRoomName = document.getElementById('summaryRoomName');
    const summaryRoomDetails = document.getElementById('summaryRoomDetails');
    const summaryRoomBadges = document.getElementById('summaryRoomBadges');
    const allocationForm = document.getElementById('allocationForm');

    roomSelect.addEventListener('change', function() {
        const roomId = this.value;
        selectedBedInput.value = '';
        bedSelectionStatus.textContent = 'No bed selected';
        bedSelectionStatus.style.color = '#f59e0b';

        if (!roomId) {
            bedGridContainer.innerHTML = `
                <div style="padding: 2rem; text-align: center; border: 2px dashed var(--border); border-radius: 8px; color: var(--text-muted);">
                    <i class="fas fa-arrow-up" style="font-size: 1.5rem; margin-bottom: 0.5rem; display: block;"></i>
                    Please select a room above to view available beds.
                </div>
            `;
            roomSummaryBox.style.display = 'none';
            return;
        }

        bedGridContainer.innerHTML = `
            <div style="padding: 2rem; text-align: center; color: var(--primary);">
                <i class="fas fa-spinner fa-spin" style="font-size: 1.8rem; margin-bottom: 0.5rem; display: block;"></i>
                Loading live bed layout...
            </div>
        `;

        fetch(`<?php echo $config['base_url']; ?>/api/allocations/available-beds/${roomId}`)
            .then(res => res.json())
            .then(res => {
                if (!res.success || !res.data) {
                    bedGridContainer.innerHTML = `<div class="alert alert-error">${res.message || 'Failed to load bed information.'}</div>`;
                    roomSummaryBox.style.display = 'none';
                    return;
                }

                const data = res.data;
                
                // Render Room Summary
                summaryRoomName.textContent = `Room ${data.room_number} — ${data.block} (Floor ${data.floor})`;
                summaryRoomDetails.textContent = `Type: ${data.room_type} | Monthly Fee: Rs. ${Number(data.monthly_fee).toLocaleString()} | Security Deposit: Rs. ${Number(data.security_deposit).toLocaleString()}`;
                
                summaryRoomBadges.innerHTML = `
                    <span class="badge" style="background: #334155; color: #93c5fd;">Total: ${data.total_beds} Beds</span>
                    <span class="badge badge-success">Available: ${data.available_beds}</span>
                    <span class="badge badge-danger">Occupied: ${data.occupied_beds}</span>
                `;
                roomSummaryBox.style.display = 'block';

                // Render Bed Grid
                let gridHtml = '<div class="bed-grid">';
                data.beds.forEach(bed => {
                    if (bed.is_occupied) {
                        const occName = bed.occupant ? escapeHtml(bed.occupant.student_name) : 'Occupied';
                        const occId = bed.occupant && bed.occupant.student_id_str ? `(${escapeHtml(bed.occupant.student_id_str)})` : '';
                        gridHtml += `
                            <div class="bed-card occupied" title="Occupied by ${occName}">
                                <span class="bed-icon" style="color: #f87171;"><i class="fas fa-bed"></i></span>
                                <div class="bed-number" style="color: #cbd5e1;">Bed ${bed.bed_number}</div>
                                <span class="bed-status-badge badge-occupied">Occupied</span>
                                <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.35rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    ${occName} <small>${occId}</small>
                                </div>
                            </div>
                        `;
                    } else {
                        gridHtml += `
                            <div class="bed-card available" data-bed="${bed.bed_number}" onclick="selectBed(${bed.bed_number}, this)" tabindex="0">
                                <span class="bed-icon" style="color: #38bdf8;"><i class="fas fa-bed"></i></span>
                                <div class="bed-number" style="color: #f8fafc;">Bed ${bed.bed_number}</div>
                                <span class="bed-status-badge badge-available">Available</span>
                                <div style="font-size: 0.75rem; color: #34d399; margin-top: 0.35rem;">
                                    <i class="fas fa-hand-pointer"></i> Click to select
                                </div>
                            </div>
                        `;
                    }
                });
                gridHtml += '</div>';

                bedGridContainer.innerHTML = gridHtml;
            })
            .catch(err => {
                bedGridContainer.innerHTML = '<div class="alert alert-error">Network error while fetching bed layout. Please try again.</div>';
                roomSummaryBox.style.display = 'none';
            });
    });

    allocationForm.addEventListener('submit', function(e) {
        if (!selectedBedInput.value) {
            e.preventDefault();
            alert('Please select an available bed from the visual bed selector.');
            return false;
        }
    });
});

function selectBed(bedNumber, element) {
    document.querySelectorAll('.bed-card.available').forEach(card => card.classList.remove('selected'));
    element.classList.add('selected');
    
    document.getElementById('selected_bed_number').value = bedNumber;
    const statusSpan = document.getElementById('bedSelectionStatus');
    statusSpan.textContent = `✓ Bed ${bedNumber} Selected`;
    statusSpan.style.color = '#34d399';
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>

