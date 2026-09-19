<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/db_connection.php';
require __DIR__ . '/mailer.php';

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function mask_email(string $email): string {
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return 'your email';
  [$u,$d] = explode('@',$email,2);
  return substr($u,0,1) . str_repeat('*', max(1, strlen($u)-2)) . substr($u,-1) . '@' . $d;
}

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST'){
  if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    $error = 'Invalid request token.';
  } else {
    $email = trim($_POST['email'] ?? '');
    // Save for the OTP step (no enumeration in UI)
    $_SESSION['reset_email_raw']  = $email;
    $_SESSION['reset_email_hint'] = mask_email($email);
    $_SESSION['reset_resend_count'] = 0;   // allow 3 resends from otp.php

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $stmt = $pdo->prepare("SELECT id,email FROM admin_users WHERE email=? LIMIT 1");
      $stmt->execute([$email]);
      if ($user = $stmt->fetch()) {
        // (Optional) invalidate older unused codes for this user
        $pdo->prepare("UPDATE admin_password_otps SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL")
            ->execute([(int)$user['id']]);

        $otp = (string)random_int(100000, 999999);
        $otpHash = hash('sha256', $otp);
        $expires = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

        $pdo->prepare("
          INSERT INTO admin_password_otps (user_id, otp_hash, expires_at, attempts, max_attempts)
          VALUES (?, ?, ?, 0, 5)
        ")->execute([(int)$user['id'], $otpHash, $expires]);

        @sendOTPEmail($user['email'], $otp);   // PHPMailer SMTP
      } else {
        // behave the same if email not found
        usleep(150000);
      }
    }
    header('Location: otp.php');
    exit;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Forgot Password</title>
  <link rel="stylesheet" href="login.css">
</head>
<body>
  <div class="card">
    <h1>Forgot Password</h1>
    <p class="muted"><em>Enter your admin email. We’ll send a 6-digit code to reset your password.</em></p>
    <?php if ($error): ?><p class="error">❌ <?= e($error) ?></p><?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
      <label for="email">Your admin email</label>
      <input type="email" id="email" name="email" placeholder="e.g. admin@company.com" required>
      <button class="login-btn" type="submit">Send code</button>
    </form>

    <p style="margin-top:10px"><a href="admin_login.php">Back to login</a></p>
  </div>
</body>
</html>
