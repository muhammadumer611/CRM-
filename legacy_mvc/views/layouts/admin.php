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
        body { font-family: 'Inter', sans-serif; background-color: var(--bg); color: var(--text); display: flex; min-height: 100vh; }
        a { text-decoration: none; color: inherit; }
        
        .sidebar { width: 260px; background-color: var(--sidebar); border-right: 1px solid var(--border); display: flex; flex-direction: column; }
        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid var(--border); text-align: center; font-size: 1.25rem; font-weight: 700; color: var(--primary); }
        .sidebar-nav { padding: 1rem 0; flex: 1; }
        .nav-item { display: flex; align-items: center; padding: 0.75rem 1.5rem; color: var(--text-muted); transition: all 0.2s; }
        .nav-item:hover, .nav-item.active { background-color: rgba(56,189,248,0.1); color: var(--primary); border-right: 3px solid var(--primary); }
        .nav-item i { width: 24px; font-size: 1.1rem; }
        
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .topbar { background-color: var(--sidebar); border-bottom: 1px solid var(--border); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .topbar-title { font-size: 1.25rem; font-weight: 600; }
        .user-menu { display: flex; align-items: center; gap: 1rem; }
        .logout-btn { color: var(--danger); font-weight: 500; font-size: 0.9rem; }
        .notification-wrap { position: relative; }
        .notification-bell { position: relative; color: var(--text); cursor: pointer; }
        .notification-badge { position: absolute; top: -8px; right: -12px; background: #ef4444; color: white; border-radius: 999px; min-width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 600; padding: 0 5px; }
        .notification-dropdown { position: absolute; right: 0; top: 42px; width: 320px; background: #0f172a; border: 1px solid var(--border); border-radius: 8px; box-shadow: 0 10px 20px rgba(0,0,0,0.25); display: none; z-index: 50; }
        .notification-wrap:hover .notification-dropdown, .notification-wrap:focus-within .notification-dropdown { display: block; }
        .notification-item { display: block; padding: 0.75rem 1rem; border-bottom:1px solid var(--border); }
        .notification-item:hover { background: rgba(56,189,248,0.06); }
        .notification-item.unread { background: rgba(14,165,233,0.04); }
        .notification-item-title { font-weight: 600; margin-bottom: 0.2rem; }
        .notification-item-meta { font-size: 0.75rem; color: var(--text-muted); }
        
        .content { padding: 2rem; overflow-y: auto; flex: 1; }
        .card { background-color: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .card-title { font-size: 1.1rem; font-weight: 600; }
        
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 1rem; border-bottom: 1px solid var(--border); color: var(--text-muted); font-weight: 500; font-size: 0.875rem; white-space: nowrap; }
        td { padding: 1rem; border-bottom: 1px solid var(--border); font-size: 0.9rem; white-space: nowrap; }
        tr:hover td { background-color: rgba(255,255,255,0.02); }
        
        .btn { padding: 0.5rem 1rem; border-radius: 6px; border: none; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background-color: var(--primary-dark); color: white; }
        .btn-primary:hover { background-color: #0369a1; }
        .btn-danger { background-color: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid rgba(239,68,68,0.2); }
        .btn-danger:hover { background-color: rgba(239, 68, 68, 0.2); }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
        
        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-size: 0.875rem; color: var(--text-muted); }
        .form-control { width: 100%; padding: 0.75rem; border-radius: 6px; border: 1px solid var(--border); background-color: var(--bg); color: var(--text); font-family: inherit; }
        .form-control:focus { outline: none; border-color: var(--primary); }
        select.form-control { appearance: none; }
        textarea.form-control { resize: vertical; min-height: 100px; }
        
        .row { display: flex; flex-wrap: wrap; margin: -0.75rem; }
        .col-md-6 { width: 50%; padding: 0.75rem; }
        .col-md-4 { width: 33.333%; padding: 0.75rem; }
        .col-12 { width: 100%; padding: 0.75rem; }
        
        @media (max-width: 768px) {
            .col-md-6, .col-md-4 { width: 100%; }
        }
        
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem; }
        .alert-success { background-color: rgba(16,185,129,0.1); color: var(--success); border: 1px solid rgba(16,185,129,0.2); }
        .alert-error { background-color: rgba(239,68,68,0.1); color: var(--danger); border: 1px solid rgba(239,68,68,0.2); }
        
        .badge { padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .badge-success { background-color: rgba(16,185,129,0.1); color: var(--success); }
        .badge-danger { background-color: rgba(239,68,68,0.1); color: var(--danger); }
        .badge-warning { background-color: rgba(245,158,11,0.1); color: #f59e0b; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            HMS Admin
        </div>
        <div class="sidebar-nav">
            <a href="<?php echo $config['base_url']; ?>/dashboard" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="<?php echo $config['base_url']; ?>/students" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'student') !== false ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i> Students
            </a>
            <a href="<?php echo $config['base_url']; ?>/rooms" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'room') !== false ? 'active' : ''; ?>">
                <i class="fas fa-bed"></i> Rooms
            </a>
            <a href="<?php echo $config['base_url']; ?>/allocations" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'allocation') !== false ? 'active' : ''; ?>">
                <i class="fas fa-key"></i> Allocations
            </a>
            <a href="<?php echo $config['base_url']; ?>/fees" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'fee') !== false ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave"></i> Fees
            </a>
            <a href="<?php echo $config['base_url']; ?>/notifications" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'notification') !== false ? 'active' : ''; ?>">
                <i class="fas fa-bell"></i> Notifications
            </a>
            <a href="<?php echo $config['base_url']; ?>/audit-logs" class="nav-item <?php echo strpos($_SERVER['REQUEST_URI'], 'audit') !== false ? 'active' : ''; ?>">
                <i class="fas fa-history"></i> Audit Logs
            </a>
        </div>
    </div>
    
    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title"><?php echo htmlspecialchars($title ?? 'Dashboard'); ?></div>
            <div class="user-menu">
                <div class="notification-wrap">
                    <a href="<?php echo $config['base_url']; ?>/notifications" class="notification-bell" aria-label="Notifications">
                        <i class="fas fa-bell"></i>
                        <?php $notificationService = new \App\Services\NotificationService(); $unreadCount = $notificationService->getUnreadCount(); ?>
                        <?php if ($unreadCount > 0): ?>
                            <span class="notification-badge"><?php echo (int)$unreadCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="notification-dropdown">
                        <?php $recent = $notificationService->getRecentUnread(5); ?>
                        <?php if (empty($recent)): ?>
                            <div class="notification-item">
                                <div class="notification-item-title">No new notifications</div>
                                <div class="notification-item-meta">You're all caught up.</div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent as $item): ?>
                                <a href="<?php echo $config['base_url']; ?>/notifications" class="notification-item unread">
                                    <div class="notification-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                    <div class="notification-item-meta"><?php echo htmlspecialchars(substr($item['message'], 0, 70)); ?><?php echo strlen((string)$item['message']) > 70 ? '...' : ''; ?></div>
                                    <div class="notification-item-meta"><?php echo htmlspecialchars(date('M d, H:i', strtotime($item['created_at']))); ?></div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <a href="<?php echo $config['base_url']; ?>/notifications" class="notification-item" style="text-align:center; font-weight:600;">
                            View All Notifications
                        </a>
                    </div>
                </div>
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
</body>
</html>
