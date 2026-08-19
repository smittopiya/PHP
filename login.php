<?php
/**
 * login.php — Secure login page for Smart Dairy.
 * Username: MomaiDairy  |  Password: MomaiDairy
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// If already logged in, go to dashboard
if (isLoggedIn()) {
    header('Location: /milk-management/index.php');
    exit;
}

$error = '';
$dairyName = setting('dairy_name', 'Smart Dairy');

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === AUTH_USERNAME && password_verify($password, AUTH_PASS_HASH)) {
        $_SESSION['auth_logged_in'] = true;
        $_SESSION['auth_user']      = $username;
        $_SESSION['auth_login_at']  = time();
        header('Location: /milk-management/index.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — <?= htmlspecialchars($dairyName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Inter', -apple-system, sans-serif;
    background: #050E1C;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
  }

  /* Animated background glow */
  body::before {
    content: '';
    position: fixed;
    width: 600px; height: 600px;
    background: radial-gradient(circle, rgba(201,162,39,0.08) 0%, transparent 70%);
    top: -100px; right: -100px;
    border-radius: 50%;
    animation: pulse 8s ease-in-out infinite alternate;
  }
  body::after {
    content: '';
    position: fixed;
    width: 500px; height: 500px;
    background: radial-gradient(circle, rgba(37,99,235,0.06) 0%, transparent 70%);
    bottom: -100px; left: -100px;
    border-radius: 50%;
    animation: pulse 10s ease-in-out infinite alternate-reverse;
  }
  @keyframes pulse {
    from { transform: scale(1); opacity: 0.6; }
    to   { transform: scale(1.3); opacity: 1; }
  }

  .login-card {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 400px;
    margin: 20px;
    background: linear-gradient(145deg, #0D1E33 0%, #112240 100%);
    border: 1.5px solid rgba(201,162,39,0.20);
    border-radius: 20px;
    padding: 44px 36px 36px;
    box-shadow:
      0 24px 80px rgba(0,0,0,0.50),
      0 0 60px rgba(201,162,39,0.06),
      inset 0 1px 0 rgba(255,255,255,0.04);
  }

  /* Brand Header */
  .brand {
    text-align: center;
    margin-bottom: 32px;
  }
  .brand-icon {
    font-size: 3rem;
    display: block;
    margin-bottom: 10px;
    filter: drop-shadow(0 4px 16px rgba(201,162,39,0.35));
  }
  .brand-name {
    font-family: 'Playfair Display', serif;
    font-size: 1.6rem;
    font-weight: 700;
    background: linear-gradient(135deg, #C9A227, #F0C040);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: 0.5px;
  }
  .brand-sub {
    font-size: 0.72rem;
    color: #5B7BA0;
    letter-spacing: 3px;
    text-transform: uppercase;
    font-weight: 600;
    margin-top: 4px;
  }

  /* Form */
  .form-group {
    margin-bottom: 20px;
  }
  .form-label {
    display: block;
    font-size: 0.78rem;
    font-weight: 600;
    color: #8BA3C7;
    margin-bottom: 7px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
  }
  .form-input {
    width: 100%;
    padding: 13px 16px;
    background: rgba(5,14,28,0.60);
    border: 1.5px solid #1D3461;
    border-radius: 10px;
    color: #E8EDF8;
    font-size: 0.95rem;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    outline: none;
    transition: all 0.25s ease;
  }
  .form-input::placeholder { color: #3D5A80; }
  .form-input:focus {
    border-color: #C9A227;
    box-shadow: 0 0 0 3px rgba(201,162,39,0.15);
    background: rgba(10,22,40,0.80);
  }

  /* Error */
  .error-msg {
    background: rgba(220,38,38,0.12);
    border: 1px solid rgba(220,38,38,0.3);
    color: #F87171;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    margin-bottom: 20px;
    text-align: center;
    animation: shake 0.4s ease;
  }
  @keyframes shake {
    0%,100% { transform: translateX(0); }
    25%     { transform: translateX(-6px); }
    75%     { transform: translateX(6px); }
  }

  /* Button */
  .login-btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #C9A227, #A07B10);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    letter-spacing: 0.5px;
    transition: all 0.25s ease;
    box-shadow: 0 6px 24px rgba(201,162,39,0.30);
    margin-top: 6px;
  }
  .login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 36px rgba(201,162,39,0.45);
    filter: brightness(1.08);
  }
  .login-btn:active {
    transform: translateY(0);
    box-shadow: 0 4px 16px rgba(201,162,39,0.25);
  }

  /* Footer */
  .login-footer {
    text-align: center;
    margin-top: 24px;
    font-size: 0.72rem;
    color: #3D5A80;
  }

  /* Show/hide password toggle */
  .pw-wrap {
    position: relative;
  }
  .pw-toggle {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #5B7BA0;
    cursor: pointer;
    font-size: 1.1rem;
    padding: 4px;
    transition: color 0.2s;
  }
  .pw-toggle:hover { color: #C9A227; }
</style>
</head>
<body>

<div class="login-card">
  <div class="brand">
    <span class="brand-icon">🥛</span>
    <div class="brand-name"><?= htmlspecialchars($dairyName) ?></div>
    <div class="brand-sub">Management Suite</div>
  </div>

  <?php if ($error): ?>
  <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div class="form-group">
      <label class="form-label" for="username">👤 Username</label>
      <input class="form-input" type="text" id="username" name="username"
             placeholder="Enter username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
             required autofocus>
    </div>

    <div class="form-group">
      <label class="form-label" for="password">🔒 Password</label>
      <div class="pw-wrap">
        <input class="form-input" type="password" id="password" name="password"
               placeholder="Enter password" required>
        <button type="button" class="pw-toggle" onclick="togglePw()" id="pwBtn">👁</button>
      </div>
    </div>

    <button type="submit" class="login-btn">🔐 Login to Dashboard</button>
  </form>

  <div class="login-footer">
    Secured Access &bull; Smart Dairy &copy; <?= date('Y') ?>
  </div>
</div>

<script>
function togglePw() {
  var pw  = document.getElementById('password');
  var btn = document.getElementById('pwBtn');
  if (pw.type === 'password') {
    pw.type = 'text';
    btn.textContent = '🙈';
  } else {
    pw.type = 'password';
    btn.textContent = '👁';
  }
}
</script>
</body>
</html>

