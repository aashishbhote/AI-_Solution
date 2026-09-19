<?php
// admin_photos.php — Manage event photos
declare(strict_types=1);

require __DIR__ . '/admin_auth.php';     // session + $_SESSION['csrf']
require __DIR__ . '/db_connection.php';  // $pdo

/* ---------- helpers ---------- */
if (!function_exists('e')) {
  function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('ensure_post_csrf')) {
  function ensure_post_csrf(string $t): void {
    if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
      http_response_code(403); exit('Bad CSRF');
    }
  }
}

/* ---------- POST actions ---------- */
$err = ''; $ok = '';
$uploadDir = __DIR__ . '/uploads/photos/';    // make sure this folder exists and is writable
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  ensure_post_csrf($_POST['csrf'] ?? '');
  $action = $_POST['action'] ?? '';
  try {
    if ($action === 'add_photo') {
      if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Please choose a photo to upload.');
      }
      $file = $_FILES['photo'];
      $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
        throw new RuntimeException('Only JPG, PNG, GIF, WEBP allowed.');
      }
      $safeName = uniqid('ph_', true) . '.' . $ext;
      $dest = $uploadDir . $safeName;
      if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Failed to move uploaded file.');
      }
      $caption = trim($_POST['caption'] ?? '');
      $pdo->prepare("INSERT INTO photos (filename, caption, is_active, created_at) VALUES (?,?,1,NOW())")
          ->execute([$safeName, $caption]);
      $ok = 'Photo uploaded.';
    }
    elseif ($action === 'delete_photo') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) throw new RuntimeException('Invalid photo id.');
      $stmt = $pdo->prepare("SELECT filename FROM photos WHERE id=?");
      $stmt->execute([$id]);
      $file = $stmt->fetchColumn();
      if ($file && file_exists($uploadDir.$file)) {
        unlink($uploadDir.$file);
      }
      $pdo->prepare("DELETE FROM photos WHERE id=?")->execute([$id]);
      $ok = 'Photo deleted.';
    }
    elseif ($action === 'toggle_photo') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) throw new RuntimeException('Invalid photo id.');
      $pdo->prepare("UPDATE photos SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
      $ok = 'Visibility toggled.';
    }
  } catch (Throwable $ex) {
    $err = $ex->getMessage();
  }
}

/* ---------- Fetch ---------- */
$photos = $pdo->query("SELECT * FROM photos ORDER BY created_at DESC, id DESC")
              ->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Navbar bits ---------- */
$active        = basename($_SERVER['PHP_SELF']);
$adminName     = $_SESSION['admin_name']  ?? 'Purnima Mali';
$adminEmail    = $_SESSION['admin_email'] ?? 'malipurnima2058@gmail.com';
$avatarInitial = strtoupper(substr($adminName !== '' ? $adminName : $adminEmail, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin • Photos</title>
<?php require __DIR__.'/admin_head_snippet.php'; ?>  <!-- boot (applies data-theme & tokens early) -->
<link rel="stylesheet" href="./admin-theme.css?v=1">   <!-- global tokens & component colors -->
<!-- then your page-specific CSS: -->
<link rel="stylesheet" href="./admin_dashboard.css?v=2">  <!-- or admin_services.css, etc. -->
  <link rel="stylesheet" href="./admin_photos.css?v=3">
</head>
<body>
<nav class="admin-nav" id="adminNav">
    <!-- Brand -->
    <a href="admin_dashboard.php" class="brand-link">
      <img src="./ai.jpg" alt="AI-Solutions logo" class="brand-logo">
      <span class="brand-name">AI-Solutions Admin</span>
    </a>

    <!-- Sidebar-local burger -->
    <button class="toggle" type="button" aria-label="Toggle menu" aria-expanded="false">☰</button>

    <!-- ===== Management (Admin) ===== -->
    <div class="nav-group group--admin">
      <div class="group-label">Management</div>
      <ul class="links" id="adminNavLinks">
        <li><a href="admin_dashboard.php" class="<?= $active==='admin_dashboard.php'?'active':'' ?>">Dashboard</a></li>
        <li><a href="admin_services.php"  class="<?= $active==='admin_services.php'?'active':'' ?>">Services</a></li>
        <li><a href="admin_blogs.php"     class="<?= $active==='admin_blogs.php'?'active':'' ?>">Blogs</a></li>
        <li><a href="admin_events.php"    class="<?= $active==='admin_events.php'?'active':'' ?>">Events</a></li>
        <li><a href="admin_photos.php"    class="<?= $active==='admin_photos.php'?'active':'' ?>">Photos</a></li>
                <li><a href="admin_portfolios.php"    class="<?= $active==='admin_portfolios.php'?'active':'' ?>">Portfolios</a></li>

        <li><a href="admin_contact.php" class="<?= $active==='admin_contact.php'?'active':'' ?>">Contact</a></li>
      </ul>
    </div>

    <!-- ===== Customer site (View) ===== -->
    <div class="nav-group group--public">
      <div class="group-label">Customer site</div>
      <ul class="links external">
        <li><a href="index.php"     target="_blank" rel="noopener">Home<span class="ext" aria-hidden="true">↗</span></a></li>
        <li><a href="services.php"  target="_blank" rel="noopener">Services<span class="ext" aria-hidden="true">↗</span></a></li>
        <li><a href="blogs.php"     target="_blank" rel="noopener">Blogs<span class="ext" aria-hidden="true">↗</span></a></li>
        <li><a href="events.php"    target="_blank" rel="noopener">Events<span class="ext" aria-hidden="true">↗</span></a></li>
        <li><a href="photos.php"    target="_blank" rel="noopener">Photos<span class="ext" aria-hidden="true">↗</span></a></li>
        <li><a href="testimonals.php"   target="_blank" rel="noopener">Portfolios<span class="ext" aria-hidden="true">↗</span></a></li>
        <li><a href="contact.php"   target="_blank" rel="noopener">Contact<span class="ext" aria-hidden="true">↗</span></a></li>
      </ul>
    </div>

    <!-- Account -->
    <div class="account">
      <button class="avatar-btn" id="accountBtn" type="button" aria-haspopup="menu" aria-expanded="false" title="Account">
        <span class="avatar"><?= e($avatarInitial) ?></span>
      </button>

      <div class="account-menu" id="accountMenu" role="menu" aria-label="Account menu">
        <div class="account-header">
          <span class="avatar large"><?= e($avatarInitial) ?></span>
          <div class="who">
            <div class="name"><?= e($adminName) ?></div>
            <div class="email"><?= e($adminEmail) ?></div>
            <div class="role">Admin</div>
          </div>
        </div>
        <div class="account-actions">
          <a href="admin_profile.php" role="menuitem">Manage account</a>
          <a class="logout" href="admin_logout.php" role="menuitem">Logout</a>
        </div>
      </div>
    </div>
  </nav>

  <div class="wrap">
    <?php if ($err): ?><div class="card error">❌ <?= e($err) ?></div><?php endif; ?>
    <?php if ($ok):  ?><div class="card success">✅ <?= e($ok) ?></div><?php endif; ?>

    <h1>Photos</h1>

    <!-- Upload -->
    <article class="card add-card">
      <h3>Add Photo</h3>
      <form method="post" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
        <input type="hidden" name="action" value="add_photo">

        <label>Photo <input type="file" name="photo" accept="image/*" required></label>
        <label>Caption <input type="text" name="caption" placeholder="Optional caption"></label>
        <button class="btn">Upload</button>
      </form>
    </article>

    <!-- List -->
    <div class="photo-grid">
      <?php foreach ($photos as $ph): ?>
        <article class="card photo-card">
          <img src="uploads/photos/<?= e($ph['filename']) ?>" alt="<?= e($ph['caption']) ?>" class="thumb">
          <?php if ($ph['caption']): ?><p class="caption"><?= e($ph['caption']) ?></p><?php endif; ?>
          <div class="admin-toolbar">
            <form method="post">
              <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
              <input type="hidden" name="action" value="toggle_photo">
              <input type="hidden" name="id" value="<?= (int)$ph['id'] ?>">
              <button class="btn small ghost"><?= $ph['is_active'] ? 'Unpublish' : 'Publish' ?></button>
            </form>
            <form method="post" onsubmit="return confirm('Delete this photo?')">
              <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
              <input type="hidden" name="action" value="delete_photo">
              <input type="hidden" name="id" value="<?= (int)$ph['id'] ?>">
              <button class="btn small warn">Delete</button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>

  </html>
</body>
</html>
