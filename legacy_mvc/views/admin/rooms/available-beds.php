<?php $config = require APP_ROOT . '/config/app.php'; ?>
<div class="card">
    <!-- Page Header -->
    <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
            <a href="<?php echo $config['base_url']; ?>/dashboard"
               style="color:var(--text-muted);font-size:0.85rem;display:inline-flex;align-items:center;gap:0.35rem;">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <span style="color:var(--border);">/</span>
            <h3 class="card-title" style="margin:0;">
                <i class="fas fa-bed" style="color:#10b981;"></i>
                Available Beds
            </h3>
            <span style="background:rgba(16,185,129,0.15);color:#6ee7b7;border:1px solid rgba(16,185,129,0.3);
                         border-radius:999px;padding:0.2rem 0.75rem;font-size:0.8rem;font-weight:700;">
                <?php echo (int)$totalAvailableBeds; ?> bed<?php echo (int)$totalAvailableBeds === 1 ? '' : 's'; ?> available
            </span>
        </div>
        <div style="display:flex;gap:0.75rem;align-items:center;">
            <a href="<?php echo $config['base_url']; ?>/students/create" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Onboard Student
            </a>
            <a href="<?php echo $config['base_url']; ?>/rooms" class="btn" style="background:#334155;color:white;">
                <i class="fas fa-door-open"></i> All Rooms
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div style="margin-bottom:1.5rem;">
        <form action="<?php echo $config['base_url']; ?>/rooms/available-beds" method="GET"
              style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group" style="margin-bottom:0;flex:1;min-width:180px;">
                <label class="form-label">Search Room</label>
                <input type="text" name="search" class="form-control"
                       placeholder="Room number, block..."
                       value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
            </div>
            <?php if (!empty($roomTypes)): ?>
            <div class="form-group" style="margin-bottom:0;flex:0 0 160px;">
                <label class="form-label">Room Type</label>
                <select name="room_type" class="form-control">
                    <option value="">All Types</option>
                    <?php foreach ($roomTypes as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>"
                            <?php echo ($filters['room_type'] ?? '') === $type ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <?php if (!empty($floors)): ?>
            <div class="form-group" style="margin-bottom:0;flex:0 0 140px;">
                <label class="form-label">Floor</label>
                <select name="floor" class="form-control">
                    <option value="">All Floors</option>
                    <?php foreach ($floors as $fl): ?>
                        <option value="<?php echo htmlspecialchars($fl); ?>"
                            <?php echo ($filters['floor'] ?? '') === $fl ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($fl); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary" style="height:42px;">
                <i class="fas fa-filter"></i> Filter
            </button>
            <?php if (!empty($filters['search']) || !empty($filters['room_type']) || !empty($filters['floor'])): ?>
                <a href="<?php echo $config['base_url']; ?>/rooms/available-beds"
                   class="btn" style="height:42px;background:#334155;color:white;">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Available Beds Display -->
    <?php if (empty($rooms) || (int)$totalAvailableBeds === 0): ?>
        <div style="text-align:center;padding:4rem 2rem;background:#0f172a;border:1px solid var(--border);border-radius:10px;">
            <i class="fas fa-bed" style="font-size:3rem;margin-bottom:1rem;display:block;opacity:0.3;color:#94a3b8;"></i>
            <h4 style="margin-bottom:0.5rem;color:var(--text);font-weight:600;">No beds are currently available.</h4>
            <?php if (!empty($filters['search']) || !empty($filters['room_type']) || !empty($filters['floor'])): ?>
                <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:1rem;">
                    No available beds match your search criteria.
                </p>
                <a href="<?php echo $config['base_url']; ?>/rooms/available-beds" class="btn btn-primary btn-sm">
                    <i class="fas fa-sync-alt"></i> Clear Filters
                </a>
            <?php else: ?>
                <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:1rem;">
                    All beds across all active rooms are currently occupied.
                </p>
                <a href="<?php echo $config['base_url']; ?>/rooms/create" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add New Room
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(320px, 1fr));gap:1.25rem;">
            <?php foreach ($rooms as $room): ?>
            <?php
                $availCount = (int)$room['available_beds'];
                $totalBeds  = (int)$room['total_beds'];
                $occCount   = (int)$room['occupied_beds'];
                $statusColor = $availCount === $totalBeds ? '#10b981' : '#f59e0b';
                $statusBg    = $availCount === $totalBeds ? 'rgba(16,185,129,0.12)' : 'rgba(245,158,11,0.12)';
                $statusBorder= $availCount === $totalBeds ? 'rgba(16,185,129,0.25)' : 'rgba(245,158,11,0.25)';
            ?>
            <div style="background:#0f172a;border:1px solid var(--border);border-radius:10px;padding:1.25rem;display:flex;flex-direction:column;justify-content:space-between;box-shadow:0 4px 6px -1px rgba(0,0,0,0.2);">
                <!-- Room Header -->
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.75rem;">
                        <div>
                            <div style="font-size:1.15rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:0.5rem;">
                                <i class="fas fa-door-open" style="color:var(--primary);font-size:1rem;"></i>
                                <?php
                                    $label = '';
                                    if (!empty($room['block'])) $label .= htmlspecialchars($room['block']) . '-';
                                    $label .= htmlspecialchars($room['room_number']);
                                    echo $label;
                                ?>
                            </div>
                            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.2rem;">
                                <?php if (!empty($room['floor'])): ?>
                                    <span><?php echo htmlspecialchars($room['floor']); ?> Floor</span> &bull;
                                <?php endif; ?>
                                <span><?php echo htmlspecialchars($room['room_type']); ?> Room</span>
                            </div>
                        </div>
                        <span style="background:<?php echo $statusBg; ?>;color:<?php echo $statusColor; ?>;border:1px solid <?php echo $statusBorder; ?>;border-radius:999px;padding:0.2rem 0.6rem;font-size:0.72rem;font-weight:700;white-space:nowrap;">
                            <?php echo htmlspecialchars($room['status']); ?>
                        </span>
                    </div>

                    <!-- Room Capacity Bar -->
                    <div style="background:#1e293b;border-radius:8px;padding:0.75rem;margin-bottom:1rem;">
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.82rem;margin-bottom:0.4rem;">
                            <span style="color:var(--text-muted);">Capacity: <strong><?php echo $totalBeds; ?> Bed<?php echo $totalBeds === 1 ? '' : 's'; ?></strong></span>
                            <span style="color:#6ee7b7;font-weight:600;"><?php echo $availCount; ?> Available</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.75rem;color:var(--text-muted);">
                            <span>Occupied: <?php echo $occCount; ?></span>
                            <span>Available: <?php echo $availCount; ?></span>
                        </div>
                    </div>

                    <!-- Available Beds List -->
                    <div style="margin-bottom:0.75rem;">
                        <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-muted);margin-bottom:0.6rem;font-weight:700;">
                            Available Bed<?php echo count($room['available_bed_numbers']) === 1 ? '' : 's'; ?> (<?php echo count($room['available_bed_numbers']); ?>):
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                            <?php foreach ($room['available_bed_numbers'] as $bedNum): ?>
                                <a href="<?php echo $config['base_url']; ?>/students/create?room_id=<?php echo (int)$room['id']; ?>&bed_number=<?php echo (int)$bedNum; ?>"
                                   style="display:inline-flex;align-items:center;gap:0.4rem;background:rgba(16,185,129,0.12);color:#6ee7b7;border:1px solid rgba(16,185,129,0.3);padding:0.4rem 0.75rem;border-radius:6px;font-size:0.85rem;font-weight:600;text-decoration:none;transition:all 0.15s;"
                                   onmouseover="this.style.background='rgba(16,185,129,0.25)';this.style.transform='translateY(-1px)';"
                                   onmouseout="this.style.background='rgba(16,185,129,0.12)';this.style.transform='';"
                                   title="Click to assign student to Bed <?php echo (int)$bedNum; ?>">
                                    <i class="fas fa-bed" style="font-size:0.75rem;"></i>
                                    Bed <?php echo (int)$bedNum; ?>
                                    <span style="font-size:0.7rem;opacity:0.8;margin-left:0.2rem;"><i class="fas fa-plus-circle"></i></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Footer Quick Action -->
                <div style="padding-top:0.75rem;border-top:1px solid rgba(51,65,85,0.6);margin-top:0.5rem;display:flex;justify-content:flex-end;">
                    <a href="<?php echo $config['base_url']; ?>/students/create?room_id=<?php echo (int)$room['id']; ?>"
                       class="btn btn-sm" style="background:#1e3a5f;color:#93c5fd;border:1px solid rgba(59,130,246,0.3);"
                       title="Onboard student to this room">
                        <i class="fas fa-user-plus"></i> Assign Student
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>