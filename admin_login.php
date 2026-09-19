<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/db_connection.php'; // PDO $pdo

// ---- Init login tracking ----
$_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? 0;
$_SESSION['lockout_until']  = $_SESSION['lockout_until']  ?? 0;

// ---- CSRF token ----
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$now = time();
$error = '';
$success = false;

if ($now < (int)$_SESSION['lockout_until']) {
  $remaining = (int)$_SESSION['lockout_until'] - $now;
  $error = "Too many failed attempts. Try again in " . ceil($remaining / 60) . " minute(s).";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    $error = "Invalid request token.";
  } else {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
      $error = "Enter a valid email and password.";
    } else {
      $stmt = $pdo->prepare("SELECT id, email, password_hash, role FROM admin_users WHERE email = ? LIMIT 1");
      $stmt->execute([$email]);
      $user = $stmt->fetch();

      if ($user && password_verify($password, $user['password_hash'])) {
        // success
        $_SESSION['login_attempts'] = 0;
        $_SESSION['lockout_until']  = 0;

        $_SESSION['admin_id']    = (int)$user['id'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_role']  = $user['role'];
        session_regenerate_id(true);

        $success = true;
      } else {
        $_SESSION['login_attempts']++;
        if ($_SESSION['login_attempts'] >= 3) {
          $_SESSION['lockout_until'] = $now + 3 * 60; // 3 minutes
          $error = "Too many failed attempts. Locked for 3 minutes.";
        } else {
          $error = "Incorrect email or password. (" . $_SESSION['login_attempts'] . "/3)";
        }
      }
    }
  }
}
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Login</title>
  <link rel="stylesheet" href="login.css?v=2">
</head>
<body>
  <div class="card">
    <h1>Admin Login</h1>

    <?php if ($error): ?><p class="error">❌ <?= e($error) ?></p><?php endif; ?>

    <?php if ($now >= (int)$_SESSION['lockout_until']): ?>
      <form method="post" action="">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" autocomplete="username" required>

        <label for="password">Password</label>
        <div class="password-field">
          <input type="password" id="password" name="password" autocomplete="current-password" required>
          <button type="button" id="togglePassword" aria-label="Toggle password">👁</button>
        </div>

        <button type="submit" class="login-btn">Login</button>

        <p class="muted small" style="margin-top:10px">
          <a href="forget.php">Forgot your password?</a>
        </p>
      </form>
    <?php else: ?>
      <p class="info">⏳ Your account is temporarily locked due to failed attempts.</p>
      <p class="muted small">This page will refresh when the lockout ends…</p>
    <?php endif; ?>
  </div>

  <script>
    // Toggle password visibility
    document.getElementById("togglePassword")?.addEventListener("click", function () {
      const pwd = document.getElementById("password");
      if (!pwd) return;
      if (pwd.type === "password") { pwd.type = "text"; this.textContent = "🙈"; }
      else { pwd.type = "password"; this.textContent = "👁"; }
    });

    // Redirect on success
    <?php if ($success): ?>
      alert("✅ Welcome to the Dashboard!");
      window.location.href = "./admin_dashboard.php";
    <?php endif; ?>

    // Auto-reload when lockout expires
    <?php if ($now < (int)$_SESSION['lockout_until']): ?>
      setTimeout(function(){ location.reload(); }, <?= (int)($_SESSION['lockout_until'] - $now) * 1000 ?>);
    <?php endif; ?>
  </script>
</body>
</html>
