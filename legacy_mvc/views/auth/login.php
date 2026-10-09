<?php $config = require APP_ROOT . '/config/app.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($config['app_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { min-height: 100%; }
        body { font-family: 'Inter', sans-serif; background: radial-gradient(circle at top left, #164e63, transparent 42%), #0f172a; display: flex; align-items: center; justify-content: center; min-height: 100vh; color: #f8fafc; padding: 1rem; overflow-x: hidden; }
        .login-container { background: rgba(30,41,59,.92); border: 1px solid #334155; padding: 2.5rem; border-radius: 12px; box-shadow: 0 24px 60px rgba(0,0,0,.38); width: 100%; max-width: 400px; animation: loginRise .35s ease both; }
        .login-header { text-align: center; margin-bottom: 2rem; }
        .login-header h1 { font-size: clamp(1.3rem, 4vw, 1.7rem); color: #38bdf8; font-weight: 600; margin-bottom: 0.5rem; }
        .login-header p { color: #94a3b8; font-size: 0.9rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #cbd5e1; }
        .form-control { width: 100%; padding: 0.75rem 1rem; border-radius: 6px; border: 1px solid #334155; background-color: #0f172a; color: #f8fafc; font-family: inherit; font-size: 1rem; transition: border-color 0.2s, box-shadow 0.2s; }
        .form-control:focus { outline: none; border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.1); }
        .btn-submit { width: 100%; padding: 0.75rem; border-radius: 6px; border: none; background-color: #0284c7; color: white; font-size: 1rem; font-weight: 600; cursor: pointer; transition: transform .18s ease, background-color .18s ease, box-shadow .18s ease; }
        .btn-submit:hover { background-color: #0369a1; transform: translateY(-1px); box-shadow: 0 8px 18px rgba(2,132,199,.25); }
        .password-wrap { position: relative; }
        .password-wrap .form-control { padding-right: 3rem; }
        .toggle-password { position:absolute; right:.75rem; top:50%; transform:translateY(-50%); border:0; background:transparent; color:#94a3b8; cursor:pointer; }
        @keyframes loginRise { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .btn-submit:hover { background-color: #0369a1; }
        .alert { padding: 0.75rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.875rem; text-align: center; }
        .alert-error { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }

        @media (max-width: 480px) {
            body { padding: 0.75rem; }
            .login-container { padding: 1.25rem; border-radius: 10px; }
            .form-group { margin-bottom: 1rem; }
            .btn-submit { min-height: 46px; }
        }
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

        <form action="<?php echo rtrim($config['base_url'], '/'); ?>/login" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrap"><input type="password" id="password" name="password" class="form-control" required><button type="button" class="toggle-password" aria-label="Show password" onclick="togglePassword()"><i class="fas fa-eye"></i></button></div>
            </div>
            
            <button type="submit" class="btn-submit" id="loginButton">Sign In</button>
        </form>
    </div>
    <script>
    function togglePassword() { const input=document.getElementById('password'); const icon=document.querySelector('.toggle-password i'); input.type=input.type==='password'?'text':'password'; icon.className=input.type==='password'?'fas fa-eye':'fas fa-eye-slash'; }
    document.querySelector('form').addEventListener('submit', function(){ const button=document.getElementById('loginButton'); button.disabled=true; button.innerHTML='<i class="fas fa-spinner fa-spin"></i> Signing in...'; });
    </script>
</body>
</html>
