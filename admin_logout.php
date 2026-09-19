<?php
// logout.php — destroys the session then sends user to admin_login.php
session_start();

// 1) Clear all session variables
$_SESSION = [];

// 2) Kill the session cookie (defense in depth)
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 3) Destroy the session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// 4) Redirect to the admin login page
header('Location: admin_login.php');
exit;
