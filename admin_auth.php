<?php
// admin_auth.php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/** Adjust this to your htdocs project folder name */
const APP_BASE = '/ai_solution';                 // <-- IMPORTANT
const LOGIN_ROUTE = APP_BASE . '/admin_login.php';

if (empty($_SESSION['admin_id'])) {
  header('Location: ' . LOGIN_ROUTE);            // sends to /ai_solution/admin_login.php
  exit;
}

if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
function ensure_post_csrf(string $token): void {
  if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
    http_response_code(400);
    exit('Invalid request token');
  }
}
