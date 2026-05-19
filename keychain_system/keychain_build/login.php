<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? SITE_URL.'/admin/dashboard.php' : SITE_URL.'/dashboard.php'));
    exit;
}

$tab    = $_GET['tab'] ?? 'login';
$errors = [];
$success = '';

// ---- Handle Login ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'login') {
        $email    = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$email || !$password) {
            $errors[] = 'Please fill in all fields.';
        } else {
            $user = login($email, $password);
            if ($user) {
                setFlash('success', 'Welcome back, ' . $user['name'] . '! 👋');
                header('Location: ' . ($user['role'] === 'admin' ? SITE_URL.'/admin/dashboard.php' : SITE_URL.'/dashboard.php'));
                exit;
            } else {
                $errors[] = 'Invalid email or password.';
            }
        }
        $tab = 'login';
    }

    if ($_POST['action'] === 'register') {
        $name     = sanitize($_POST['name'] ?? '');
        $email    = sanitize($_POST['email'] ?? '');
        $phone    = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!$name || !$email || !$password || !$confirm) {
            $errors[] = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        } else {
            $db = getDB();
            $check = $db->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $errors[] = 'Email is already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'customer')");
                $stmt->execute([$name, $email, $phone, $hash]);
                // Auto-login
                login($email, $password);
                setFlash('success', 'Account created! Welcome to KeyChain Studio 🎉');
                header('Location: ' . SITE_URL . '/dashboard.php');
                exit;
            }
        }
        $tab = 'register';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — KeyChain Studio</title>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔑</text></svg>">
</head>
<body>

<div class="auth-page">
  <!-- Left Decorative Panel -->
  <div class="auth-left">
    <div style="position:relative;z-index:1;max-width:380px;">
      <div class="auth-logo">🔑 KeyChain Studio</div>
      <p class="auth-tagline">Turn your favorite photos into personalized keychains. Each piece tells a story — yours.</p>
      <div style="margin-top:48px;display:grid;gap:20px;">
        <div style="display:flex;gap:14px;align-items:center;">
          <div style="width:44px;height:44px;background:rgba(255,255,255,0.1);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">📸</div>
          <div>
            <div style="color:#fff;font-weight:600;font-size:14px;margin-bottom:2px;">Upload Any Photo</div>
            <div style="color:rgba(255,255,255,0.5);font-size:13px;">JPG, PNG, WEBP up to 5MB</div>
          </div>
        </div>
        <div style="display:flex;gap:14px;align-items:center;">
          <div style="width:44px;height:44px;background:rgba(255,255,255,0.1);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">🎨</div>
          <div>
            <div style="color:#fff;font-weight:600;font-size:14px;margin-bottom:2px;">6 Unique Designs</div>
            <div style="color:rgba(255,255,255,0.5);font-size:13px;">Circle, heart, star & more</div>
          </div>
        </div>
        <div style="display:flex;gap:14px;align-items:center;">
          <div style="width:44px;height:44px;background:rgba(255,255,255,0.1);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">📦</div>
          <div>
            <div style="color:#fff;font-weight:600;font-size:14px;margin-bottom:2px;">Order Tracking</div>
            <div style="color:rgba(255,255,255,0.5);font-size:13px;">Stay updated every step</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Form Panel -->
  <div class="auth-right">
    <div class="auth-box">
      <div style="margin-bottom:28px;">
        <h1 class="auth-title"><?= $tab === 'register' ? 'Create account' : 'Welcome back' ?></h1>
        <p class="auth-sub"><?= $tab === 'register' ? 'Join KeyChain Studio today' : 'Sign in to your account' ?></p>
      </div>

      <!-- Tabs -->
      <div class="auth-tabs">
        <button class="auth-tab <?= $tab === 'login' ? 'active' : '' ?>" onclick="switchTab('login')">Sign In</button>
        <button class="auth-tab <?= $tab === 'register' ? 'active' : '' ?>" onclick="switchTab('register')">Register</button>
      </div>

      <!-- Errors -->
      <?php foreach ($errors as $err): ?>
        <div class="flash flash-error"><span class="flash-icon">✕</span><?= htmlspecialchars($err) ?></div>
      <?php endforeach; ?>

      <!-- Login Form -->
      <div id="loginForm" style="display:<?= $tab === 'login' ? 'block' : 'none' ?>">
        <form method="POST" novalidate>
          <input type="hidden" name="action" value="login">
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
          </div>
          <button type="submit" class="btn btn-primary w-full" style="margin-top:8px;">Sign In</button>
        </form>
        <div style="margin-top:20px;padding:14px;background:var(--cream);border-radius:var(--radius-sm);font-size:12px;color:var(--light);">
          <strong style="color:var(--mid);">Demo credentials:</strong><br>
          Admin: admin@keychain.com / password
        </div>
      </div>

      <!-- Register Form -->
      <div id="registerForm" style="display:<?= $tab === 'register' ? 'block' : 'none' ?>">
        <form method="POST" novalidate>
          <input type="hidden" name="action" value="register">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" placeholder="Your name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
          </div>
          <div class="two-col">
            <div class="form-group">
              <label class="form-label">Email *</label>
              <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" placeholder="09xx xxx xxxx" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
          </div>
          <div class="two-col">
            <div class="form-group">
              <label class="form-label">Password *</label>
              <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
            </div>
            <div class="form-group">
              <label class="form-label">Confirm *</label>
              <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-full" style="margin-top:8px;">Create Account</button>
        </form>
      </div>

      <div class="auth-switch">
        <?php if ($tab === 'login'): ?>
          Don't have an account? <a href="#" onclick="switchTab('register')">Register here</a>
        <?php else: ?>
          Already have an account? <a href="#" onclick="switchTab('login')">Sign in</a>
        <?php endif; ?>
      </div>
      <div class="auth-switch" style="margin-top:8px;">
        <a href="<?= SITE_URL ?>" style="color:var(--light);">← Back to Home</a>
      </div>
    </div>
  </div>
</div>

<script>
function switchTab(tab) {
  document.getElementById('loginForm').style.display    = tab === 'login'    ? 'block' : 'none';
  document.getElementById('registerForm').style.display = tab === 'register' ? 'block' : 'none';
  document.querySelectorAll('.auth-tab').forEach((t, i) => {
    t.classList.toggle('active', (i === 0 && tab === 'login') || (i === 1 && tab === 'register'));
  });
}
</script>
</body>
</html>
