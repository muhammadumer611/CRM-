<?php $config = require APP_ROOT . '/config/app.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'Admin'); ?> - <?php echo htmlspecialchars($config['app_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #38bdf8; --primary-dark: #0284c7; --bg: #0f172a; --sidebar: #1e293b; --card: #1e293b; --text: #f8fafc; --text-muted: #94a3b8; --border: #334155; --danger: #ef4444; --success: #10b981; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 16px; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg); color: var(--text); display: flex; min-height: 100vh; overflow-x: hidden; }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; height: auto; display: block; }
        .app-shell { display: flex; width: 100%; min-height: 100vh; }
        .sidebar { width: 260px; background-color: var(--sidebar); border-right: 1px solid var(--border); display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid var(--border); text-align: center; font-size: 1.25rem; font-weight: 700; color: var(--primary); }
        .sidebar-nav { padding: 1rem 0; flex: 1; }
        .nav-item { display: flex; align-items: center; padding: 0.75rem 1.5rem; color: var(--text-muted); transition: all 0.2s; }
        .nav-item:hover, .nav-item.active { background-color: rgba(56,189,248,0.1); color: var(--primary); border-right: 3px solid var(--primary); }
        .nav-item i { width: 24px; font-size: 1.1rem; }
        
        .main-content { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .topbar { background-color: var(--sidebar); border-bottom: 1px solid var(--border); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
        .topbar-title { font-size: 1.25rem; font-weight: 600; }
        .user-menu { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; justify-content: flex-end; }
        .logout-btn { color: var(--danger); font-weight: 500; font-size: 0.9rem; }
        .content { padding: 2rem; overflow-y: auto; flex: 1; }
        .card { background-color: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 1.5rem; animation: pageRise .28s ease both; transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(0,0,0,.18); border-color: rgba(56,189,248,.35); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .card-title { font-size: 1.1rem; font-weight: 600; }

        .modal { position: fixed; inset: 0; display: none; align-items: center; justify-content: center; background: rgba(15, 23, 42, 0.7); z-index: 2000; padding: 1.5rem; }
        .modal.show { display: flex; }
        .modal-dialog { width: min(100%, 960px); max-height: 90vh; overflow: hidden; border-radius: 12px; border: 1px solid var(--border); background: #0f172a; box-shadow: 0 20px 40px rgba(2, 6, 23, 0.45); }
        .checkout-dialog { max-height: min(90vh, 900px); display: flex; flex-direction: column; min-height: 0; }
        .checkout-dialog > div:first-child { flex: 0 0 auto; }
        .checkout-form { min-height: 0; overflow-y: auto; padding: 0 0.25rem 0.25rem 0; overscroll-behavior: contain; }
        .modal-content { background: var(--card); border: 1px solid var(--border); border-radius: 12px; }
        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); }
        .modal-title { font-size: 1.1rem; font-weight: 700; }
        .modal-body { padding: 1.5rem; max-height: calc(90vh - 120px); overflow-y: auto; }
        .btn-close { appearance: none; background: transparent; border: 1px solid var(--border); color: var(--text); border-radius: 6px; width: 2rem; height: 2rem; cursor: pointer; }

        .alumni-details { width: 100%; display: flex; flex-direction: column; gap: 20px; box-sizing: border-box; }
        .alumni-section { width: 100%; box-sizing: border-box; background: rgba(15, 27, 45, 0.66); border: 1px solid rgba(148, 163, 184, 0.18); border-radius: 16px; padding: 20px; }
        .section-header { display: flex; align-items: center; gap: 10px; margin: 0 0 18px; }
        .section-header i { color: var(--primary); width: 18px; text-align: center; }
        .section-header h3 { margin: 0; font-size: 16px; line-height: 1.4; font-weight: 700; color: var(--text); }
        .details-grid { width: 100%; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px 24px; box-sizing: border-box; }
        .detail-item { min-width: 0; display: flex; flex-direction: column; gap: 7px; box-sizing: border-box; }
        .detail-item-full { grid-column: 1 / -1; }
        .detail-label { display: block; font-size: 11px; line-height: 1.4; letter-spacing: 0.06em; text-transform: uppercase; font-weight: 700; color: var(--text-muted); }
        .detail-value { display: block; min-width: 0; font-size: 15px; line-height: 1.6; font-weight: 600; color: var(--text); overflow-wrap: anywhere; word-break: break-word; }
        .detail-note { display: block; width: 100%; box-sizing: border-box; padding: 0.8rem 0.9rem; border-radius: 10px; border: 1px solid var(--border); background: rgba(15, 23, 42, 0.45); color: var(--text); overflow-wrap: anywhere; word-break: break-word; }
        .status-badge { display: inline-flex; align-items: center; justify-content: center; padding: 0.35rem 0.65rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; line-height: 1; }
        .badge-success { background-color: rgba(16,185,129,0.12); color: #6ee7b7; border: 1px solid rgba(16,185,129,0.25); }
        .badge-warning { background-color: rgba(245,158,11,0.12); color: #fbbf24; border: 1px solid rgba(245,158,11,0.25); }
        .badge-danger { background-color: rgba(239,68,68,0.12); color: #fca5a5; border: 1px solid rgba(239,68,68,0.25); }
        .badge-secondary { background-color: rgba(148,163,184,0.12); color: var(--text-muted); border: 1px solid rgba(148,163,184,0.25); }

        .modal-body .alumni-details,
        .modal-body .alumni-section,
        .modal-body .section-header,
        .modal-body .details-grid,
        .modal-body .detail-item,
        .modal-body .detail-label,
        .modal-body .detail-value,
        .modal-body .detail-note,
        .modal-body .section-header h3 {
            position: static !important; float: none !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; min-width: 0 !important; height: auto !important; display: block !important; box-sizing: border-box !important; overflow-wrap: anywhere; word-break: break-word;
        }
        .modal-body .detail-item { display: flex !important; flex-direction: column !important; }
        .modal-body .details-grid { display: grid !important; }
        .modal-body .section-header { display: flex !important; align-items: center !important; }
        .modal-body .row,
        .modal-body [class*="col-"],
        .modal-body .col-sm-6,
        .modal-body .col-md-6,
        .modal-body .col-md-4,
        .modal-body .col-12 {
            display: block !important; width: auto !important; max-width: none !important; float: none !important; margin: 0 !important; padding: 0 !important;
        }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 1rem; border-bottom: 1px solid var(--border); color: var(--text-muted); font-weight: 500; font-size: 0.875rem; white-space: nowrap; }
        td { padding: 1rem; border-bottom: 1px solid var(--border); font-size: 0.9rem; white-space: normal; }
        tr:hover td { background-color: rgba(255,255,255,0.02); }
        .btn { padding: 0.5rem 1rem; border-radius: 6px; border: none; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(2,132,199,.18); }
        .btn:active { transform: translateY(0); box-shadow: none; }
        .btn-primary { background-color: var(--primary-dark); color: white; }
        .btn-primary:hover { background-color: #0369a1; }
        .btn-danger { background-color: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid rgba(239,68,68,0.2); }
        .btn-danger:hover { background-color: rgba(239, 68, 68, 0.2); }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-size: 0.875rem; color: var(--text-muted); }
        .form-control { width: 100%; padding: 0.75rem; border-radius: 6px; border: 1px solid var(--border); background-color: var(--bg); color: var(--text); font-family: inherit; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(56,189,248,.12); }
        select.form-control { appearance: none; }
        textarea.form-control { resize: vertical; min-height: 100px; }
        .row { display: flex; flex-wrap: wrap; margin: -0.75rem; }
        .col-md-6 { width: 50%; padding: 0.75rem; }
        .col-md-4 { width: 33.333%; padding: 0.75rem; }
        .col-12 { width: 100%; padding: 0.75rem; }
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem; }
        .alert-success { background-color: rgba(16,185,129,0.1); color: var(--success); border: 1px solid rgba(16,185,129,0.2); }
        .alert-error { background-color: rgba(239,68,68,0.1); color: var(--danger); border: 1px solid rgba(239,68,68,0.2); }
        .badge { padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .badge-success { background-color: rgba(16,185,129,0.1); color: var(--success); }
        .badge-danger { background-color: rgba(239,68,68,0.1); color: var(--danger); }
        .badge-warning { background-color: rgba(245,158,11,0.1); color: #f59e0b; }
        .mobile-menu-toggle { display: none; border: 1px solid var(--border); background: rgba(15,23,42,0.7); color: var(--text); border-radius: 8px; width: 2.5rem; height: 2.5rem; align-items: center; justify-content: center; cursor: pointer; }
        .sidebar-backdrop { display: none; }

        @media (max-width: 900px) {
            .app-shell { display: block; }
            .sidebar { position: fixed; left: 0; top: 0; height: 100vh; z-index: 1200; transform: translateX(-105%); transition: transform 0.25s ease; box-shadow: 0 20px 40px rgba(2,6,23,0.4); }
            body.sidebar-open .sidebar { transform: translateX(0); }
            .mobile-menu-toggle { display: inline-flex; }
            .topbar { padding: 0.875rem 1rem; }
            .topbar-title { font-size: 1.05rem; }
            .user-menu { gap: 0.5rem; font-size: 0.75rem; }
            .content { padding: 1rem; }
            .card { padding: 1rem; }
            .card-header { flex-wrap: wrap; gap: 0.75rem; }
            .sidebar-backdrop { display: block; position: fixed; inset: 0; background: rgba(2,6,23,0.6); opacity: 0; pointer-events: none; transition: opacity 0.25s ease; z-index: 1100; }
            body.sidebar-open .sidebar-backdrop { opacity: 1; pointer-events: auto; }
            .details-grid { grid-template-columns: 1fr; }
            .detail-item-full { grid-column: auto; }
            .modal-dialog { width: 100%; }
            .checkout-dialog { max-height: 95vh; }
            .modal-header, .modal-body { padding-left: 1rem; padding-right: 1rem; }
        }

        @keyframes pageRise { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; } }

        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1100; }
        .hamburger-btn { display:none; background:none; border:1px solid var(--border); color:var(--text); border-radius:6px; padding:0.4rem 0.6rem; cursor:pointer; font-size:1rem; }

        @media (max-width: 768px) {
            .sidebar { left:0; transform:translateX(-105%); transition:transform 0.25s ease; z-index:1200; }
            body.sidebar-open .sidebar { transform:translateX(0); }
            body.sidebar-open .sidebar-overlay { display:block; }
            .hamburger-btn { display:inline-flex; align-items:center; gap:0.4rem; }
            .main-content { width:100%; }
            td, th { padding:0.6rem 0.5rem; font-size:0.82rem; }
            .col-md-6, .col-md-4 { width: 100%; }
            .row { margin: 0; }
            .user-menu span { display: none; }
            .logout-btn { font-size: 0.75rem; }
            .table-responsive table { min-width: 680px; }
            .table-responsive { margin-left: -0.25rem; margin-right: -0.25rem; }
            form[style*="display:flex"] { width: 100%; }
            form[style*="display:flex"] .form-control[style*="max-width"] { max-width: 100% !important; flex: 1 1 100%; }
            td form { display: flex !important; align-items: center; flex-wrap: wrap; gap: 0.4rem; }
            td form .form-control { min-width: 130px; flex: 1 1 130px; }
        }

        @media (max-width: 480px) {
            .topbar { align-items: center; padding:0.75rem; }
            .topbar-title { max-width: 75%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .content { padding:0.75rem; }
            .card { padding:0.875rem; margin-bottom:1rem; }
            .card-header { align-items:flex-start; }
            .card-header > .btn, .card-header > a.btn { width:auto; }
            .btn { width:100%; justify-content:center; }
            .card-header .btn { width:auto; }
            .form-group { margin-bottom:1rem; }
            .modal { padding:0.75rem; }
            .modal-dialog { max-height:95vh; }
            .modal-header { padding:0.875rem; }
            .modal-body { padding:0.875rem; max-height:calc(95vh - 90px); }
            .section-header { align-items:flex-start; }
            .details-grid { gap:1rem; }
        }

        @media (max-width: 360px) {
            html { font-size:15px; }
            .topbar-title { font-size:0.95rem; }
            .user-menu { gap:0.35rem; }
            .logout-btn { font-size:0.7rem; }
            th, td { padding:0.5rem 0.35rem; font-size:0.75rem; }
        }
    </style>
</head>
<body>
    <!-- Mobile sidebar overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            HMS Admin
        </div>
        <div class="sidebar-nav">
            <a href="<?php echo $config['base_url']; ?>/dashboard" class="nav-item <?php echo preg_match('#/dashboard#', $_SERVER['REQUEST_URI']) ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="<?php echo $config['base_url']; ?>/students" class="nav-item <?php echo preg_match('#/student#', $_SERVER['REQUEST_URI']) ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i> Students
            </a>
            <a href="<?php echo $config['base_url']; ?>/reservations" class="nav-item <?php echo preg_match('#/reservation#', $_SERVER['REQUEST_URI']) ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Reservations
            </a>
            <a href="<?php echo $config['base_url']; ?>/rooms" class="nav-item <?php echo preg_match('#/room#', $_SERVER['REQUEST_URI']) ? 'active' : ''; ?>">
                <i class="fas fa-door-open"></i> Rooms
            </a>
            <a href="<?php echo $config['base_url']; ?>/fees" class="nav-item <?php echo preg_match('#/fee#', $_SERVER['REQUEST_URI']) ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave"></i> Fees
            </a>
            <a href="<?php echo $config['base_url']; ?>/alumni" class="nav-item <?php echo preg_match('#/alumni#', $_SERVER['REQUEST_URI']) ? 'active' : ''; ?>">
                <i class="fas fa-user-check"></i> Alumni
            </a>
            <a href="<?php echo $config['base_url']; ?>/reports" class="nav-item <?php echo preg_match('#/report#', $_SERVER['REQUEST_URI']) ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a href="<?php echo $config['base_url']; ?>/history" class="nav-item <?php echo preg_match('#/history#', $_SERVER['REQUEST_URI']) ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> History
            </a>
            <a href="<?php echo $config['base_url']; ?>/account-settings" class="nav-item <?php echo preg_match('#/account-settings#', $_SERVER['REQUEST_URI']) ? 'active' : ''; ?>">
                <i class="fas fa-user-cog"></i> Account Settings
            </a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="topbar">
            <div style="display:flex;align-items:center;gap:0.75rem;">
                <button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Menu">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="topbar-title"><?php echo htmlspecialchars($title ?? 'Dashboard'); ?></div>
            </div>
            <div class="user-menu">
                <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars(\App\Core\Auth::user()); ?></span>
                <a href="<?php echo $config['base_url']; ?>/logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <div class="content">
            <?php 
            $success = \App\Core\Session::get('success');
            $error = \App\Core\Session::get('error');
            \App\Core\Session::remove('success');
            \App\Core\Session::remove('error');
            if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php echo $content ?? ''; ?>
        </div>
    </div>

<script>
function toggleSidebar() {
    var s = document.getElementById('sidebar');
    var o = document.getElementById('sidebarOverlay');
    if (s && o) {
        document.body.classList.toggle('sidebar-open');
    }
}
// Close sidebar when a nav link is clicked on mobile
document.querySelectorAll('.sidebar .nav-item').forEach(function(el) {
    el.addEventListener('click', function() {
        if (window.innerWidth <= 900 && document.body.classList.contains('sidebar-open')) toggleSidebar();
    });
});
</script>
<script>
function maskDigits(value, groups) {
    var digits = String(value || '').replace(/\D/g, '').slice(0, groups.reduce(function(a, b) { return a + b; }, 0));
    var output = '', offset = 0;
    groups.forEach(function(size, index) {
        if (offset >= digits.length) return;
        if (index > 0) output += '-';
        output += digits.slice(offset, offset + size);
        offset += size;
    });
    return output;
}
function applyIdentityMasks(root) {
    (root || document).querySelectorAll('input[name="cnic"], input[name$="[cnic]"]').forEach(function(input) {
        input.maxLength = 15;
        input.addEventListener('input', function() { input.value = maskDigits(input.value, [5, 7, 1]); });
    });
    (root || document).querySelectorAll('input[name="phone"], input[name="guardian_phone"], input[name$="[phone]"], input[name$="[guardian_phone]"]').forEach(function(input) {
        input.maxLength = 12;
        input.addEventListener('input', function() { input.value = maskDigits(input.value, [4, 7]); });
    });
}
applyIdentityMasks(document);
document.querySelectorAll('form').forEach(function(form) {
    form.addEventListener('submit', function() {
        var button = form.querySelector('button[type="submit"]');
        if (!button || button.dataset.submitting === '1') return;
        button.dataset.submitting = '1';
        button.disabled = true;
        button.classList.add('is-loading');
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    });
});
</script>
</body>
</html>
