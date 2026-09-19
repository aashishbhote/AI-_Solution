<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/db_connection.php';
require __DIR__ . '/mailer.php';

// -------- Time handling: force PHP & this MySQL session to UTC --------
date_default_timezone_set('UTC');
try { $pdo->exec("SET time_zone = '+00:00'"); } catch (Throwable $e) { /* ignore */ }

// -------- Security headers (optional but recommended) --------
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 0');
header('Cache-Control: no-store, no-cache, must-revalidate');

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$emailRaw  = $_SESSION['reset_email_raw']  ?? '';
$emailHint = $_SESSION['reset_email_hint'] ?? 'your email';
if (!$emailRaw) { header('Location: forget.php'); exit; }

$resendCount = (int)($_SESSION['reset_resend_count'] ?? 0);
$info = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Small jitter to make brute-force timing less predictable
  usleep(random_int(20_000, 60_000));

  // CSRF
  if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    $error = 'Invalid request token.';
  } else {
    $action = $_POST['action'] ?? 'verify';

    // Find user silently
    $stmt = $pdo->prepare("SELECT id,email FROM admin_users WHERE email=? LIMIT 1");
    $stmt->execute([$emailRaw]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($action === 'resend') {
      if ($resendCount >= 3) {
        $error = 'Resend limit reached. Please start again.';
      } else {
        if ($user) {
          // Invalidate old unused codes (stamp used_at in UTC)
          $pdo->prepare("UPDATE admin_password_otps SET used_at = UTC_TIMESTAMP() WHERE user_id=? AND used_at IS NULL")
              ->execute([(int)$user['id']]);

          // New OTP
          $otp = (string)random_int(100000, 999999);            // keep as string to preserve leading zeros
          $otpHash = hash('sha256', $otp);
          $expires = (new DateTime('now', new DateTimeZone('UTC')))
                      ->modify('+10 minutes')
                      ->format('Y-m-d H:i:s');

          $pdo->prepare("
            INSERT INTO admin_password_otps (user_id, otp_hash, expires_at, attempts, max_attempts)
            VALUES (?, ?, ?, 0, 5)
          ")->execute([(int)$user['id'], $otpHash, $expires]);

          // Send email (don’t reveal existence of the account by message content)
          @sendOTPEmail($user['email'], $otp);

          $_SESSION['reset_resend_count'] = ++$resendCount;
          $info = 'A new code was sent.';
        } else {
          // Keep response generic
          usleep(150000);
          $info = 'A new code was sent.';
        }
      }
    } else { // verify
      $code = preg_replace('/\D+/', '', $_POST['otp'] ?? '');
      if (strlen($code) !== 6) {
        $error = 'Enter the 6-digit code.';
      } else {
        if (!$user) {
          usleep(150000);
          $error = 'Invalid or expired code.';
        } else {
          $uid = (int)$user['id'];
          $q = $pdo->prepare("
            SELECT id, otp_hash, attempts, max_attempts, expires_at
            FROM admin_password_otps
            WHERE user_id = ? AND used_at IS NULL AND expires_at > UTC_TIMESTAMP()
            ORDER BY id DESC
            LIMIT 1
          ");
          $q->execute([$uid]);
          $row = $q->fetch(PDO::FETCH_ASSOC);

          if (!$row) {
            $error = 'Invalid or expired code.';
          } elseif ((int)$row['attempts'] >= (int)$row['max_attempts']) {
            $error = 'Too many attempts. Please request a new code.';
          } else {
            $ok = hash_equals((string)$row['otp_hash'], hash('sha256', $code));
            if ($ok) {
              // Mark this code as used with UTC timestamp
              $pdo->prepare("UPDATE admin_password_otps SET used_at = UTC_TIMESTAMP() WHERE id = ?")
                  ->execute([(int)$row['id']]);

              // Open a short reset window
              $_SESSION['pwd_reset_user_id'] = $uid;
              $_SESSION['pwd_reset_until']   = time() + 10 * 60; // 10 minutes to open reset.php

              // Clear reset-email hints and resend counter
              unset($_SESSION['reset_email_raw'], $_SESSION['reset_email_hint'], $_SESSION['reset_resend_count']);

              header('Location: reset.php');
              exit;
            } else {
              // Bump attempts
              $pdo->prepare("UPDATE admin_password_otps SET attempts = attempts + 1 WHERE id = ?")
                  ->execute([(int)$row['id']]);
              $error = 'Invalid or expired code.';
            }
          }
        }
      }
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Verify Code</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="login.css">
  <style>
    .card{max-width:420px;margin:8vh auto;padding:24px;border:1px solid #e5e7eb;border-radius:16px;background:#fff}
    .muted{color:#6b7280}
    .small{font-size:.9rem}
    .error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:12px;padding:10px 12px}
    .success{background:#ecfeff;color:#155e75;border:1px solid #a5f3fc;border-radius:12px;padding:10px 12px}
    label{display:block;margin:12px 0 6px;font-weight:600}
    input[type="text"]{width:100%;padding:12px 14px;border:1px solid #e5e7eb;border-radius:12px;font-size:1.1rem;letter-spacing:.28em;text-align:center}
    .login-btn{margin-top:12px;display:inline-block;width:100%;padding:12px 14px;border:0;border-radius:12px;background:#2563eb;color:#fff;font-weight:700;cursor:pointer}
    .login-btn[disabled]{opacity:.5;cursor:not-allowed}
  </style>
</head>
<body>
  <div class="card">
    <h1>Check your inbox</h1>
    <p class="muted">
      We sent a 6-digit code to <strong><?= e($emailHint) ?></strong>.
      You can resend <strong><?= 3 - (int)($_SESSION['reset_resend_count'] ?? 0) ?></strong> more time(s).
    </p>

    <?php if ($error): ?><p class="error">❌ <?= e($error) ?></p><?php endif; ?>
    <?php if ($info):  ?><p class="success">✅ <?= e($info) ?></p><?php endif; ?>

    <!-- Verify form -->
    <form method="post" style="margin-bottom:12px" autocomplete="off" novalidate>
      <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
      <input type="hidden" name="action" value="verify">
      <label for="otp">One-Time Code</label>
      <input type="text" id="otp" name="otp" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="••••••" required>
      <button class="login-btn" type="submit">Verify</button>
    </form>

    <!-- Resend button (disabled after 3) -->
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
      <input type="hidden" name="action" value="resend">
      <button class="login-btn" type="submit" <?= ($resendCount>=3?'disabled':'') ?>>Resend code</button>
    </form>

    <p class="muted small" style="margin-top:10px">
      <a href="forget.php">Start over</a> · <a href="admin_login.php">Back to login</a>
    </p>
  </div>

  <script>
    // Auto-focus and select input; allow entering only digits
    const otp = document.getElementById('otp');
    otp?.focus();
    otp?.addEventListener('input', () => {
      otp.value = otp.value.replace(/\D+/g, '').slice(0, 6);
    });
  </script>
</body>
</html>
