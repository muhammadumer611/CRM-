<?php $config = require __DIR__ . '/../../../config/app.php'; ?>
<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Update Complaint</h3>
        <a href="<?php echo $config['base_url']; ?>/complaints" class="btn" style="background: #334155; color: white;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div style="background-color: #0f172a; padding: 1.5rem; border-radius: 6px; margin-bottom: 2rem; border: 1px solid var(--border);">
        <h4 style="margin-bottom: 1rem; color: var(--primary);">Complaint Details</h4>
        
        <div style="margin-bottom: 1.5rem;">
            <div style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.25rem;">Student:</div>
            <div style="font-weight: 500;"><?php echo htmlspecialchars($complaint['full_name'] . ' (' . $complaint['student_id_str'] . ')'); ?></div>
        </div>
        
        <div class="row" style="margin-bottom: 1.5rem;">
            <div class="col-md-4">
                <div style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.25rem;">Date Submitted:</div>
                <div><?php echo date('F d, Y H:i', strtotime($complaint['created_at'])); ?></div>
            </div>
            <div class="col-md-4">
                <div style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.25rem;">Priority:</div>
                <div><span class="badge" style="background-color: #475569;"><?php echo htmlspecialchars($complaint['priority']); ?></span></div>
            </div>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <div style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.25rem;">Subject:</div>
            <div style="font-weight: 600; font-size: 1.1rem;"><?php echo nl2br(htmlspecialchars($complaint['subject'])); ?></div>
        </div>
        
        <div>
            <div style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.25rem;">Description:</div>
            <div style="background: #1e293b; padding: 1rem; border-radius: 6px; line-height: 1.5; border: 1px solid #334155;"><?php echo nl2br(htmlspecialchars($complaint['description'])); ?></div>
        </div>
    </div>

    <form action="<?php echo $config['base_url']; ?>/complaints/update/<?php echo $complaint['id']; ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        
        <div class="form-group">
            <label class="form-label">Status *</label>
            <select name="status" class="form-control" required>
                <option value="Open" <?php echo $complaint['status'] == 'Open' ? 'selected' : ''; ?>>Open</option>
                <option value="In Progress" <?php echo $complaint['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="Resolved" <?php echo $complaint['status'] == 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                <option value="Closed" <?php echo $complaint['status'] == 'Closed' ? 'selected' : ''; ?>>Closed</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Admin Response</label>
            <textarea name="admin_response" class="form-control" rows="5" placeholder="Enter resolution details or response to student..."><?php echo htmlspecialchars($complaint['admin_response'] ?? ''); ?></textarea>
        </div>

        <div style="margin-top: 2rem;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Complaint</button>
        </div>
    </form>
</div>
