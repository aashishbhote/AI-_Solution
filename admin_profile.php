<?php
// admin_profile.php — Manage Account page (Profile • Security • Appearance • Privacy & Data • Branding)
declare(strict_types=1);

require __DIR__ . '/admin_auth.php';     // session + guard + CSRF
require __DIR__ . '/db_connection.php';  // provides $pdo (PDO)
require __DIR__ . '/site_settings.php';  // branding helpers (brand_*)

if (!function_exists('e')) {
  function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}

/* ----------------------------------------------- Helpers ----------------------------------------------- */
function post(string $key, $default = '') { return $_POST[$key] ?? $default; }
function has_file(string $key): bool {
  return isset($_FILES[$key]) && is_array($_FILES[$key]) && (($_FILES[$key]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);
}
function fail(string $msg) { http_response_code(400); echo $msg; exit; }
function ensure_csrf(): void {
  if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) fail('Invalid CSRF token.');
}

/* --------------------------------------- Data access (`admin_users`) ----------------------------------- */
function get_admin_row(PDO $pdo, int $adminId): array {
  $row = [
    'id'             => $adminId,
    'email'          => $_SESSION['admin_email']   ?? 'admin@example.com',
    'name'           => $_SESSION['admin_name']    ?? 'Admin',
    'tz'             => $_SESSION['admin_tz']      ?? 'UTC',
    'locale'         => $_SESSION['admin_locale']  ?? 'en',
    'theme'          => $_SESSION['admin_theme']   ?? 'auto',
    'accent'         => $_SESSION['admin_accent']  ?? '#2563eb',
    'density'        => $_SESSION['admin_density'] ?? 'comfortable',
    'reduced_motion' => (int)($_SESSION['admin_rm'] ?? 0),
    'avatar_path'    => $_SESSION['admin_avatar']  ?? null,
  ];
  try {
    $st = $pdo->prepare("SELECT id, email, name, tz, locale, theme, accent, density, reduced_motion, avatar_path FROM admin_users WHERE id = ?");
    $st->execute([$adminId]);
    if ($db = $st->fetch(PDO::FETCH_ASSOC)) $row = array_replace($row, $db);
  } catch (Throwable $e) {}
  return $row;
}

function update_admin(PDO $pdo, int $adminId, array $fields): void {
  if (!$fields) return;
  $allowed = ['name','tz','locale','theme','accent','density','reduced_motion','avatar_path','is_active'];
  $fields  = array_intersect_key($fields, array_flip($allowed));
  if (!$fields) return;
  try {
    $cols = []; $vals = [];
    foreach ($fields as $k => $v) { $cols[] = "`$k` = ?"; $vals[] = $v; }
    $vals[] = $adminId;
    $sql = "UPDATE admin_users SET ".implode(',', $cols)." WHERE id = ?";
    $st = $pdo->prepare($sql); $st->execute($vals);
  } catch (Throwable $e) { error_log('[acct] update_admin error: '.$e->getMessage()); }
}

function change_password(PDO $pdo, int $adminId, string $current, string $new, string &$flashErr): bool {
  try {
    $st = $pdo->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
    $st->execute([$adminId]);
    $hash = $st->fetchColumn();
    if (!$hash) { $flashErr = 'Your account could not be found. Please sign out and in again.'; return false; }
    if (!password_verify($current, (string)$hash)) { $flashErr = 'Current password is incorrect.'; return false; }
    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $st = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
    return $st->execute([$newHash, $adminId]) === true;
  } catch (Throwable $e) { $flashErr = 'Could not change password (server error).'; error_log('[acct] change_password error: '.$e->getMessage()); return false; }
}

/* ----------------------------------------------- Controller -------------------------------------------- */
$adminId = (int)($_SESSION['admin_id'] ?? 0);
if ($adminId <= 0) { header('Location: admin_login.php'); exit; }

$flash = ''; $flashErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  ensure_csrf();
  $action = (string)post('action');

  if ($action === 'profile.save') {
    $name   = trim((string)post('name'));
    $tz     = trim((string)post('tz', 'UTC'));
    $locale = trim((string)post('locale','en'));
    $fields = ['name'=>$name, 'tz'=>$tz, 'locale'=>$locale];

    if (has_file('avatar')) {
      $f = $_FILES['avatar'];
      if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && ($f['size'] ?? 0) > 0) {
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) { $flashErr = 'Avatar must be a JPG, PNG, or WEBP.'; }
        else {
          $dir = __DIR__ . '/uploads/avatars'; if (!is_dir($dir)) @mkdir($dir, 0775, true);
          $fname = 'admin_'.$adminId.'_'.time().'.'.$ext; $dest = $dir.'/'.$fname;
          if (move_uploaded_file($f['tmp_name'], $dest)) { $rel = 'uploads/avatars/'.$fname; $fields['avatar_path'] = $rel; $_SESSION['admin_avatar'] = $rel; }
          else { $flashErr = 'Failed to upload avatar.'; }
        }
      }
    }

    update_admin($pdo, $adminId, $fields);
    if ($name !== '') $_SESSION['admin_name'] = $name;
    $_SESSION['admin_tz'] = $tz; $_SESSION['admin_locale'] = $locale;
    if (!$flashErr) $flash = 'Profile updated.';

  } elseif ($action === 'security.password') {
    $cur = (string)post('current_password'); $new = (string)post('new_password'); $rep = (string)post('confirm_password');
    if (strlen($new) < 8) { $flashErr = 'New password must be at least 8 characters.'; }
    elseif ($new !== $rep) { $flashErr = 'New password confirmation does not match.'; }
    else { if (!change_password($pdo, $adminId, $cur, $new, $flashErr)) { if (!$flashErr) $flashErr = 'Current password is incorrect.'; } else { $flash = 'Password changed successfully.'; } }

  } elseif ($action === 'appearance.save') {
    $theme   = in_array((string)post('theme','auto'), ['auto','light','dark'], true) ? (string)post('theme','auto') : 'auto';
    $accent  = preg_match('/^#([0-9a-f]{6})$/i', (string)post('accent')) ? strtolower((string)post('accent')) : '#2563eb';
    $density = in_array((string)post('density','comfortable'), ['comfortable','compact'], true) ? (string)post('density','comfortable') : 'comfortable';
    $rm      = post('reduced_motion') ? 1 : 0;
    update_admin($pdo, $adminId, ['theme'=>$theme, 'accent'=>$accent, 'density'=>$density, 'reduced_motion'=>$rm]);
    $_SESSION['admin_theme']=$theme; $_SESSION['admin_accent']=$accent; $_SESSION['admin_density']=$density; $_SESSION['admin_rm']=$rm;
    $flash = 'Appearance saved.';

  } elseif ($action === 'privacy.download') {
    $row = get_admin_row($pdo, $adminId);
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="my-account-export.json"');
    echo json_encode([
      'profile' => ['id'=>$row['id'], 'name'=>$row['name'], 'email'=>$row['email'], 'tz'=>$row['tz'], 'locale'=>$row['locale']],
      'preferences' => ['theme'=>$row['theme'], 'accent'=>$row['accent'], 'density'=>$row['density'], 'reduced_motion'=>(bool)$row['reduced_motion']],
      'exported_at' => gmdate('c'),
    ], JSON_PRETTY_PRINT); exit;

  } elseif ($action === 'privacy.deactivate') {
    update_admin($pdo, $adminId, ['is_active' => 0]);
    session_regenerate_id(true); $_SESSION = []; session_destroy();
    header('Location: admin_login.php?msg=account_deactivated'); exit;

  /* ---------------------- Branding: MODIFY (not replace) ---------------------- */
  } elseif ($action === 'branding.save') {
    // Name
    $newName = trim((string)post('brand_name','AI-Solutions'));
    if ($newName !== '') set_setting('brand_name', $newName);

    // Optional: upload new file (still supported, but not required)
    if (has_file('brand_logo')) {
      $f = $_FILES['brand_logo'];
      if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && ($f['size'] ?? 0) > 0) {
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['png','jpg','jpeg','webp','svg'], true)) { $flashErr = 'Logo must be PNG, JPG, WEBP or SVG.'; }
        else {
          $dir = __DIR__ . '/uploads/branding'; if (!is_dir($dir)) @mkdir($dir, 0775, true);
          $fname = 'logo_'.time().'.'.$ext; $dest = $dir.'/'.$fname;
          if (move_uploaded_file($f['tmp_name'], $dest)) {
            $rel = 'uploads/branding/'.$fname; set_setting('brand_logo', $rel); set_setting('brand_logo_ver', (string)time());
            $flash = trim(($flash ? $flash.' ' : '').'Logo file updated.');
          } else { $flashErr = 'Failed to upload logo.'; }
        }
      }
    }

    // Modification (CSS filters)
    $mode = strtolower((string)post('brand_logo_mode','none'));
    if (!in_array($mode, ['none','grayscale','invert','huerotate'], true)) $mode = 'none';
    $hue = max(0, min(360, (int)post('brand_logo_hue', 0)));
    $bri = max(50, min(150, (int)post('brand_logo_brightness', 100)));
    $con = max(50, min(150, (int)post('brand_logo_contrast',   100)));
    $sat = max(0,  min(200, (int)post('brand_logo_saturate',   100)));

    set_setting('brand_logo_mode', (string)$mode);
    set_setting('brand_logo_hue', (string)$hue);
    set_setting('brand_logo_brightness', (string)$bri);
    set_setting('brand_logo_contrast', (string)$con);
    set_setting('brand_logo_saturate', (string)$sat);

    if (!$flashErr) $flash = trim(($flash ? $flash.' ' : '').'Branding saved.');
  }
}

/* ----------------------------------------------- Render ----------------------------------------------- */
$row = get_admin_row($pdo, $adminId);

$adminName   = $row['name']   ?? ($_SESSION['admin_name']   ?? 'Admin');
$adminEmail  = $row['email']  ?? ($_SESSION['admin_email']  ?? 'admin@example.com');
$accent      = $row['accent'] ?? '#2563eb';
$theme       = $row['theme']  ?? 'auto';
$density     = $row['density']?? 'comfortable';
$rm          = !empty($row['reduced_motion']);
$avatarPath  = $row['avatar_path'] ?: null;

if (function_exists('mb_substr')) $avatarInitial = strtoupper(mb_substr($adminName ?: $adminEmail, 0, 1));
else $avatarInitial = strtoupper(substr($adminName ?: $adminEmail, 0, 1));

$curBrandName = brand_name();
$curLogoUrl   = brand_logo_url();
?>
<!doctype html>
<html lang="en" data-theme="<?= e($theme) ?>">
<head>
  <meta charset="utf-8">
  <title>Admin • Manage account</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <?php if (is_file(__DIR__.'/admin_head_snippet.php')) require __DIR__.'/admin_head_snippet.php'; ?>
  <link rel="stylesheet" href="./admin-theme.css?v=1">
  <link rel="stylesheet" href="./admin_dashboard.css?v=2">
  <style>
    html,body{height:100%}
    body{ margin:0; font:15px/1.5 system-ui,Segoe UI,Roboto,Arial; color:var(--text); background:var(--page); }
    .wrap{ width:min(1200px,92%); margin:28px auto 80px; }
    .card{ background:var(--card); border:1px solid var(--border); border-radius:16px; box-shadow:var(--shadow-1); padding:18px 18px 16px; margin-bottom:16px; }
    h1{ font-size:1.6rem; margin:0 0 10px; }
    .muted{ color:var(--muted); }
    .tabs{ display:flex; gap:10px; flex-wrap:wrap; margin:14px 0 16px; }
    .tabs button{ appearance:none; border:1px solid var(--border); background:var(--card); color:var(--text); border-radius:999px; padding:8px 14px; cursor:pointer; font-weight:600; }
    .tabs button.active{ border-color: color-mix(in lab, var(--accent) 40%, transparent); box-shadow: 0 0 0 3px color-mix(in lab, var(--accent) 35%, transparent); }
    .panel{ display:none; } .panel.active{ display:block; }
    .row{ display:grid; grid-template-columns:200px 1fr; gap:14px; align-items:center; margin:12px 0; }
    .row label{ font-weight:600; }
    .row input[type="text"], .row input[type="email"], .row input[type="password"], .row select, .row input[type="color"]{ width:100%; padding:10px 12px; border-radius:12px; border:1px solid var(--border); background:transparent; color:var(--text); }
    .hint{ color:var(--muted); font-size:.92rem; }
    .btn{ appearance:none; border:0; border-radius:12px; padding:10px 14px; cursor:pointer; font-weight:700; background:var(--accent); color:#fff; box-shadow:0 10px 24px -12px color-mix(in lab, var(--accent) 65%, transparent); }
    .btn.secondary{ background:transparent; color:var(--text); border:1px solid var(--border); }
    .btn.danger{ background:#ef4444; }
    .actions{ display:flex; gap:10px; margin-top:10px; }
    .avatar{ display:inline-grid; place-items:center; width:56px; height:56px; border-radius:50%; background:color-mix(in lab, var(--accent) 18%, white); color:#0b1220; font-weight:800; letter-spacing:.2px; border:1px solid var(--border); }
    .avatar img{ width:56px; height:56px; object-fit:cover; border-radius:50%; display:block; }
    .flash{ padding:10px 12px; border-radius:12px; margin-bottom:12px; font-weight:600; }
    .flash.ok{ background:#ecfeff; color:#155e75; border:1px solid #a5f3fc; }
    .flash.err{ background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
    .grid-2{ display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    @media (max-width:720px){ .row{ grid-template-columns:1fr; } .grid-2{ grid-template-columns:1fr; } }
    dialog.modal{ border:0; border-radius:16px; padding:0; box-shadow:0 30px 120px rgba(0,0,0,.45); width:min(520px, 92%); background:var(--card); color:var(--text); }
    .modal .hd{ padding:16px 18px; border-bottom:1px solid var(--border); font-weight:800; }
    .modal .bd{ padding:16px 18px; }
    .modal .ft{ padding:14px 18px; border-top:1px solid var(--border); display:flex; gap:10px; justify-content:flex-end; }
  </style>
</head>

<body>
  <div class="wrap">
    <div class="card">
      <h1>Manage account</h1>
      <p class="muted">Signed in as <strong><?= e($adminEmail) ?></strong></p>
      <?php if ($flash): ?><div class="flash ok"><?= e($flash) ?></div><?php endif; ?>
      <?php if ($flashErr): ?><div class="flash err"><?= e($flashErr) ?></div><?php endif; ?>

      <div class="tabs" role="tablist" aria-label="Manage account tabs">
        <button class="active" data-tab="profile" role="tab" aria-selected="true">Profile</button>
        <button data-tab="security" role="tab" aria-selected="false">Security</button>
        <button data-tab="appearance" role="tab" aria-selected="false">Appearance</button>
        <button data-tab="privacy" role="tab" aria-selected="false">Privacy & Data</button>
        <button data-tab="branding" role="tab" aria-selected="false">Branding</button>
      </div>

      <!-- ===== Profile ===== -->
      <section id="tab-profile" class="panel active" role="tabpanel">
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf'] ?? '') ?>">
          <input type="hidden" name="action" value="profile.save">
          <div class="row">
            <label>Avatar</label>
            <div>
              <span class="avatar">
                <?php if ($avatarPath): ?><img alt="Avatar" src="<?= e($avatarPath) ?>"><?php else: ?><?= e($avatarInitial) ?><?php endif; ?>
              </span>
              <div class="actions" style="margin-top:8px;"><input type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp"></div>
              <div class="hint">JPG/PNG/WEBP — square works best (≥ 256×256).</div>
            </div>
          </div>
          <div class="row"><label for="name">Name</label><input id="name" name="name" type="text" value="<?= e($adminName) ?>" required></div>
          <div class="row"><label for="email">Email</label><input id="email" type="email" value="<?= e($adminEmail) ?>" disabled><div class="hint">Email is managed by the system.</div></div>
          <div class="row">
            <label for="tz">Time zone</label>
            <select id="tz" name="tz">
              <?php $tzs = ['UTC','America/New_York','Europe/London','Europe/Paris','Asia/Kolkata','Asia/Kathmandu','Asia/Singapore','Asia/Dubai','Australia/Sydney'];
              $ctz = $row['tz'] ?: 'UTC'; foreach ($tzs as $tz) { $sel = $tz === $ctz ? 'selected' : ''; echo "<option $sel>".e($tz)."</option>"; } ?>
            </select>
          </div>
          <div class="row">
            <label for="locale">Language</label>
            <select id="locale" name="locale">
              <?php $locs = ['en'=>'English','fr'=>'Français','de'=>'Deutsch','es'=>'Español','hi'=>'हिन्दी','ne'=>'नेपाली'];
              $curL = $row['locale'] ?: 'en'; foreach ($locs as $k=>$v) { $sel = $k === $curL ? 'selected' : ''; echo "<option value='".e($k)."' $sel>".e($v)."</option>"; } ?>
            </select>
          </div>
          <div class="actions"><button class="btn" type="submit">Save profile</button><a class="btn secondary" href="admin_dashboard.php">Back to dashboard</a></div>
        </form>
      </section>

      <!-- ===== Security ===== -->
      <section id="tab-security" class="panel" role="tabpanel">
        <form method="post" class="grid-2">
          <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf'] ?? '') ?>">
          <input type="hidden" name="action" value="security.password">
          <div class="row"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
          <div class="row"><label for="new_password">New password</label><input id="new_password" name="new_password" type="password" minlength="8" autocomplete="new-password" required></div>
          <div class="row"><label for="confirm_password">Confirm new password</label><input id="confirm_password" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required></div>
          <div class="row" style="grid-column:1/-1"><label></label><div class="hint">Use at least 8 characters. Avoid common or breached passwords.</div></div>
          <div class="actions" style="grid-column:1/-1"><button class="btn" type="submit">Change password</button><button class="btn secondary" type="reset">Reset</button></div>
        </form>
      </section>

      <!-- ===== Appearance ===== -->
      <section id="tab-appearance" class="panel" role="tabpanel">
        <form method="post" id="appearanceForm">
          <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf'] ?? '') ?>">
          <input type="hidden" name="action" value="appearance.save">
          <div class="row">
            <label>Theme</label>
            <div>
              <label><input type="radio" name="theme" value="auto"  <?= $theme==='auto'?'checked':'' ?>> Auto</label>&nbsp;&nbsp;
              <label><input type="radio" name="theme" value="light" <?= $theme==='light'?'checked':'' ?>> Light</label>&nbsp;&nbsp;
              <label><input type="radio" name="theme" value="dark"  <?= $theme==='dark'?'checked':'' ?>> Dark</label>
              <div class="hint">Auto matches your system preference.</div>
            </div>
          </div>
          <div class="row">
            <label for="accent">Accent color</label>
            <div>
              <input id="accent" name="accent" type="color" value="<?= e($accent) ?>">
              <div class="preview" id="accentPreview" aria-live="polite">
                <span>Preview:</span>
                <span class="chip">Primary</span>
                <span class="kbd">Ctrl + K</span>
                <button type="button" class="btn">Action</button>
              </div>
            </div>
          </div>
          <div class="row"><label>Density</label><div><label><input type="radio" name="density" value="comfortable" <?= $density==='comfortable'?'checked':'' ?>> Comfortable</label>&nbsp;&nbsp;<label><input type="radio" name="density" value="compact" <?= $density==='compact'?'checked':'' ?>> Compact</label></div></div>
          <div class="row"><label for="reduced_motion">Reduced motion</label><div><label><input id="reduced_motion" type="checkbox" name="reduced_motion" <?= $rm?'checked':'' ?>> Prefer reduced animations</label></div></div>
          <div class="actions"><button class="btn" type="submit">Save appearance</button></div>
        </form>
      </section>

      <!-- ===== Privacy & Data ===== -->
      <section id="tab-privacy" class="panel" role="tabpanel">
        <div class="card" style="padding:12px; margin:0 0 12px;">
          <strong>Download my data</strong>
          <p class="muted">Export your profile and preferences as JSON.</p>
          <form method="post" class="actions">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf'] ?? '') ?>">
            <input type="hidden" name="action" value="privacy.download">
            <button class="btn secondary" type="submit">Download JSON</button>
          </form>
        </div>
        <div class="danger-area">
          <strong>Deactivate my account</strong>
          <p class="muted">Your admin account will be deactivated and you’ll be signed out immediately.</p>
          <div class="actions"><button type="button" class="btn danger" id="btnDeactivate">Deactivate account</button></div>
        </div>
      </section>

      <!-- ===== Branding (MODIFY) ===== -->
      <section id="tab-branding" class="panel" role="tabpanel">
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf'] ?? '') ?>">
          <input type="hidden" name="action" value="branding.save">

          <div class="row">
            <label for="brand_name">Brand name</label>
            <input id="brand_name" name="brand_name" type="text" value="<?= e($curBrandName) ?>" required>
            <div class="hint">Shown next to the logo in the navbar.</div>
          </div>

          <div class="row">
            <label>Current logo</label>
            <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
              <img src="<?= e($curLogoUrl) ?>" alt="Current logo" style="height:48px; border-radius:10px; border:1px solid var(--border); padding:6px; background:#fff;" <?= brand_logo_style_attr(); ?>>
              <div class="hint">Recommended: transparent PNG/SVG around 160×48.</div>
            </div>
          </div>

          <div class="row">
            <label for="brand_logo">Upload new logo (optional)</label>
            <input id="brand_logo" name="brand_logo" type="file" accept=".png,.jpg,.jpeg,.webp,.svg">
          </div>

          <!-- Modification controls (non-destructive) -->
          <div class="row">
            <label for="brand_logo_mode">Modification</label>
            <select id="brand_logo_mode" name="brand_logo_mode">
              <?php $m = brand_logo_mode(); ?>
              <option value="none"      <?= $m==='none'?'selected':'' ?>>None</option>
              <option value="grayscale" <?= $m==='grayscale'?'selected':'' ?>>Grayscale</option>
              <option value="invert"    <?= $m==='invert'?'selected':'' ?>>Invert</option>
              <option value="huerotate" <?= $m==='huerotate'?'selected':'' ?>>Hue rotate (tint)</option>
            </select>
          </div>
          <div class="row">
            <label for="brand_logo_hue">Hue (0–360)</label>
            <input id="brand_logo_hue" name="brand_logo_hue" type="number" min="0" max="360" value="<?= e((string)brand_logo_hue()) ?>">
            <div class="hint">Only used when “Hue rotate” is selected.</div>
          </div>
          <div class="row">
            <label for="brand_logo_brightness">Brightness (50–150)</label>
            <input id="brand_logo_brightness" name="brand_logo_brightness" type="number" min="50" max="150" value="<?= e((string)brand_logo_brightness()) ?>">
          </div>
          <div class="row">
            <label for="brand_logo_contrast">Contrast (50–150)</label>
            <input id="brand_logo_contrast" name="brand_logo_contrast" type="number" min="50" max="150" value="<?= e((string)brand_logo_contrast()) ?>">
          </div>
          <div class="row">
            <label for="brand_logo_saturate">Saturation (0–200)</label>
            <input id="brand_logo_saturate" name="brand_logo_saturate" type="number" min="0" max="200" value="<?= e((string)brand_logo_saturate()) ?>">
          </div>

          <div class="actions">
            <button class="btn" type="submit">Save branding</button>
            <a class="btn secondary" href="admin_dashboard.php">Back to dashboard</a>
          </div>
        </form>
      </section>

    </div>
  </div>

  <!-- Modal (deactivate) -->
  <dialog id="confirmDeactivate" class="modal">
    <div class="hd">Deactivate account?</div>
    <div class="bd">Are you sure you want to deactivate your admin account? This will sign you out right away.</div>
    <div class="ft">
      <button type="button" class="btn secondary" id="cancelDeactivate">Cancel</button>
      <form method="post"><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf'] ?? '') ?>"><input type="hidden" name="action" value="privacy.deactivate"><button type="submit" class="btn danger">Yes, deactivate</button></form>
    </div>
  </dialog>

  <script>
/* Tabs */
const tabs = document.querySelectorAll('.tabs button');
const panels = {
  profile: document.getElementById('tab-profile'),
  security: document.getElementById('tab-security'),
  appearance: document.getElementById('tab-appearance'),
  privacy: document.getElementById('tab-privacy'),
  branding: document.getElementById('tab-branding'),
};
tabs.forEach(btn => {
  btn.addEventListener('click', () => {
    tabs.forEach(b => { b.classList.toggle('active', b === btn); b.setAttribute('aria-selected', b === btn ? 'true' : 'false'); });
    Object.values(panels).forEach(p => p.classList.remove('active'));
    panels[btn.dataset.tab]?.classList.add('active');
  });
});

/* Appearance live preview + persistence */
const appearanceForm = document.getElementById('appearanceForm');
const accentInput    = document.getElementById('accent');
function resolveAndApplyTheme(choice) {
  if (choice === 'auto') {
    const isDark = matchMedia('(prefers-color-scheme: dark)').matches;
    document.documentElement.setAttribute('data-theme', 'auto');
    document.documentElement.dataset.resolvedTheme = isDark ? 'dark' : 'light';
  } else {
    document.documentElement.removeAttribute('data-resolved-theme');
    document.documentElement.setAttribute('data-theme', choice);
  }
}
accentInput?.addEventListener('input', (e) => { document.documentElement.style.setProperty('--accent', e.target.value); });
document.querySelectorAll('input[name="theme"]').forEach(r => {
  r.addEventListener('change', () => { const v = document.querySelector('input[name="theme"]:checked')?.value || 'auto'; resolveAndApplyTheme(v); });
});
appearanceForm?.addEventListener('submit', () => {
  const theme   = document.querySelector('input[name="theme"]:checked')?.value || 'auto';
  const accent  = accentInput?.value || '#2563eb';
  const density = document.querySelector('input[name="density"]:checked')?.value || 'comfortable';
  const rm      = document.getElementById('reduced_motion')?.checked ? '1' : '0';
  localStorage.setItem('admin.theme', theme); localStorage.setItem('admin.accent', accent);
  localStorage.setItem('admin.density', density); localStorage.setItem('admin.rm', rm);
  document.documentElement.style.setProperty('--accent', accent); resolveAndApplyTheme(theme);
});
matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
  if ((localStorage.getItem('admin.theme') || 'auto') === 'auto') resolveAndApplyTheme('auto');
});
const dlg = document.getElementById('confirmDeactivate');
document.getElementById('btnDeactivate')?.addEventListener('click', () => dlg.showModal());
document.getElementById('cancelDeactivate')?.addEventListener('click', () => dlg.close());
const initialTheme = document.querySelector('input[name="theme"]:checked')?.value || 'auto'; resolveAndApplyTheme(initialTheme);
  </script>
</body>
</html>
