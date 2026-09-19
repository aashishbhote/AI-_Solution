<?php
// site_settings.php
declare(strict_types=1);

/* App-wide brand settings stored in DB + cached in session. */

if (session_status() !== PHP_SESSION_ACTIVE) @session_start();

function ss_db(): PDO {
  static $pdoOnce = null;
  if ($pdoOnce instanceof PDO) return $pdoOnce;

  if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
    $pdoOnce = $GLOBALS['pdo']; return $pdoOnce;
  }

  $paths = [
    __DIR__.'/ai_solution/db_connection.php',
    __DIR__.'/db_connection.php',
    dirname(__DIR__).'/ai_solution/db_connection.php'
  ];
  foreach ($paths as $p) { if (is_file($p)) { require_once $p; break; } }

  if (!isset($GLOBALS['pdo']) || !($GLOBALS['pdo'] instanceof PDO)) {
    throw new RuntimeException('DB connection not found for site settings.');
  }
  $pdoOnce = $GLOBALS['pdo'];
  return $pdoOnce;
}

function get_setting(string $key, string $default = ''): string {
  if (!isset($_SESSION['site_settings'])) $_SESSION['site_settings'] = [];
  if (array_key_exists($key, $_SESSION['site_settings'])) {
    return (string)$_SESSION['site_settings'][$key];
  }
  $pdo = ss_db();
  $st = $pdo->prepare("SELECT val FROM site_settings WHERE `key` = ?");
  $st->execute([$key]);
  $val = $st->fetchColumn();
  $val = (is_string($val) && $val !== '') ? $val : $default;
  $_SESSION['site_settings'][$key] = $val;
  return $val;
}

function set_setting(string $key, string $val): void {
  $pdo = ss_db();
  $st = $pdo->prepare("INSERT INTO site_settings (`key`,`val`) VALUES (?, ?)
                       ON DUPLICATE KEY UPDATE `val`=VALUES(`val`), updated_at=CURRENT_TIMESTAMP");
  $st->execute([$key, $val]);
  $_SESSION['site_settings'][$key] = $val;
}

/* ---------- Brand helpers ---------- */
function brand_name(): string { return get_setting('brand_name', 'AI-Solutions'); }
function brand_logo_path(): string { return get_setting('brand_logo', 'ai.jpg'); }

function brand_logo_url(): string {
  $p = brand_logo_path();
  $v = get_setting('brand_logo_ver', '1');
  return $p . (str_contains($p,'?') ? '&' : '?') . 'v=' . rawurlencode($v);
}

/* Logo modification (non-destructive, CSS filters) */
function brand_logo_mode(): string {
  $m = strtolower(get_setting('brand_logo_mode', 'none'));
  return in_array($m, ['none','grayscale','invert','huerotate'], true) ? $m : 'none';
}
function brand_logo_hue(): int {
  $h = (int)get_setting('brand_logo_hue', '0');
  if     ($h <   0) $h = 0;
  elseif ($h > 360) $h = 360;
  return $h;
}
function brand_logo_brightness(): int {
  $x = (int)get_setting('brand_logo_brightness', '100');
  if ($x < 50) $x = 50; if ($x > 150) $x = 150; return $x;
}
function brand_logo_contrast(): int {
  $x = (int)get_setting('brand_logo_contrast', '100');
  if ($x < 50) $x = 50; if ($x > 150) $x = 150; return $x;
}
function brand_logo_saturate(): int {
  $x = (int)get_setting('brand_logo_saturate', '100');
  if ($x < 0) $x = 0; if ($x > 200) $x = 200; return $x;
}

/** Returns `style="filter: ..."` ready to drop on the <img>. */
function brand_logo_style_attr(): string {
  $filters = [];

  switch (brand_logo_mode()) {
    case 'grayscale': $filters[] = 'grayscale(1)'; break;
    case 'invert':    $filters[] = 'invert(1)';    break;
    case 'huerotate': $filters[] = 'hue-rotate(' . brand_logo_hue() . 'deg)'; break;
    default: /* none */ break;
  }

  $b = brand_logo_brightness(); if ($b !== 100) $filters[] = "brightness({$b}%)";
  $c = brand_logo_contrast();   if ($c !== 100) $filters[] = "contrast({$c}%)";
  $s = brand_logo_saturate();   if ($s !== 100) $filters[] = "saturate({$s}%)";

  if (!$filters) return '';
  return 'style="filter: ' . implode(' ', $filters) . ';"';
}
