<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/db_connection.php';

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }

$uid  = $_SESSION['pwd_reset_user_id'] ?? null;
$time = $_SESSION['pwd_reset_until']   ?? 0;

if (!$uid || time() > (int)$time) {
  header('Location: forget.php'); // restart flow
  exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    $error = 'Invalid request token.';
  } else {
    $p1 = $_POST['password'] ?? '';
    $p2 = $_POST['password_confirm'] ?? '';

    $lenOK = strlen($p1) >= 10;
    $mixOK = preg_match('/[A-Z]/', $p1) && preg_match('/[a-z]/', $p1) && preg_match('/\d/', $p1) && preg_match('/[^A-Za-z0-9]/', $p1);

    if ($p1 !== $p2) {
      $error = 'Passwords do not match.';
    } elseif (!$lenOK || !$mixOK) {
      $error = 'Use at least 10 characters including upper, lower, number, and a symbol.';
    } else {
      $hash = defined('PASSWORD_ARGON2ID')
        ? password_hash($p1, PASSWORD_ARGON2ID)
        : password_hash($p1, PASSWORD_DEFAULT);

      $upd = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
      $upd->execute([$hash, (int)$uid]);

      // Invalidate any remaining/unused OTPs
      $pdo->prepare("UPDATE admin_password_otps SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL")
          ->execute([(int)$uid]);

      unset($_SESSION['pwd_reset_user_id'], $_SESSION['pwd_reset_until']);
      $success = true;
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Reset Password</title>
  <link rel="stylesheet" href="login.css">
</head>
<body>
  <div class="card">
    <h1>Reset Password</h1>

    <?php if ($error): ?><p class="error">❌ <?= e($error) ?></p><?php endif; ?>

    <?php if ($success): ?>
      <p class="success">✅ Password updated. You can now <a href="admin_login.php">log in</a>.</p>
    <?php else: ?>
      <form method="post" action="">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
        <label for="password">New password</label>
        <input type="password" id="password" name="password" autocomplete="new-password" required>
        <label for="password_confirm">Confirm new password</label>
        <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
        <button class="login-btn" type="submit">Update password</button>
      </form>
      <p class="muted small" style="margin-top:10px"><a href="admin_login.php">Back to login</a></p>
    <?php endif; ?>
  </div>
</body>
</html>
