<?php
// mailer.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

/**
 * Send a 6-digit OTP to $toEmail via SMTP.
 */
function sendOTPEmail(string $toEmail, string $otp): bool {
  $mail = new PHPMailer(true);
  try {
    // ========== OPTION A: Gmail SMTP ==========
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->Port       = 587;
    $mail->SMTPAuth   = true;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Username   = 'malipurnima2058@gmail.com';       // <-- change
    $mail->Password   = 'dkzo lqup wuqj ivmh';   // <-- change (no spaces)
    $mail->setFrom('malipurnima2058@gmail.com', 'AI-Solutions Admin');

    // ========== OPTION B: Local Mailpit (DEV) ==========
    // $mail->isSMTP();
    // $mail->Host       = '127.0.0.1';
    // $mail->Port       = 1025;
    // $mail->SMTPAuth   = false;
    // $mail->SMTPSecure = false;
    // $mail->setFrom('no-reply@local.test', 'AI-Solutions Admin');

    $mail->addAddress($toEmail);
    $mail->isHTML(true);
    $mail->Subject = 'Your AI-Solutions password reset code';
    $safeCode = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');

    $mail->Body = "
      <div style='font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif'>
        <p>Here is your one-time code:</p>
        <p style='font-size:22px;font-weight:700;letter-spacing:.25rem'>{$safeCode}</p>
        <p>This code expires in <strong>10 minutes</strong>. If you didn’t request it, you can ignore this email.</p>
      </div>";
    $mail->AltBody = "Your one-time code: {$otp}\nValid for 10 minutes.";

    $mail->send();
    return true;
  } catch (Exception $e) {
    error_log('Mail error: ' . $e->getMessage());
    return false;
  }
}
