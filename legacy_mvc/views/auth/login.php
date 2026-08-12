<?php $config = require __DIR__ . '/../../config/app.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($config['app_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; display: flex; align-items: center; justify-content: center; min-height: 100vh; color: #f8fafc; }
        .login-container { background-color: #1e293b; padding: 2.5rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); width: 100%; max-width: 400px; }
        .login-header { text-align: center; margin-bottom: 2rem; }
        .login-header h1 { font-size: 1.5rem; color: #38bdf8; font-weight: 600; margin-bottom: 0.5rem; }
        .login-header p { color: #94a3b8; font-size: 0.9rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #cbd5e1; }
        .form-control { width: 100%; padding: 0.75rem 1rem; border-radius: 6px; border: 1px solid #334155; background-color: #0f172a; color: #f8fafc; font-family: inherit; font-size: 1rem; transition: border-color 0.2s, box-shadow 0.2s; }
        .form-control:focus { outline: none; border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.1); }
        .btn-submit { width: 100%; padding: 0.75rem; border-radius: 6px; border: none; background-color: #0284c7; color: white; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background-color 0.2s; }
        .btn-submit:hover { background-color: #0369a1; }
        .alert { padding: 0.75rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.875rem; text-align: center; }
        .alert-error { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><?php echo htmlspecialchars($config['app_name']); ?></h1>
            <p>Sign in to your admin account</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo $config['base_url']; ?>/login" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn-submit">Sign In</button>
        </form>
    </div>
</body>
</html>
