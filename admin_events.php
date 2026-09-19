<?php
// admin_events.php — Manage Upcoming/Past Events
declare(strict_types=1);

require __DIR__ . '/admin_auth.php';     // session + $_SESSION['csrf']
require __DIR__ . '/db_connection.php';  // $pdo

/* ---------- helpers (null-safe) ---------- */
if (!function_exists('e')) {
  function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  ensure_post_csrf($_POST['csrf'] ?? '');
  $action = $_POST['action'] ?? '';
  try {
    if ($action === 'add_event') {
      $title  = trim($_POST['title'] ?? '');
      $desc   = trim($_POST['description'] ?? '');
      $loc    = trim($_POST['location'] ?? '');
      $type   = $_POST['type'] ?? 'online';
      $start  = trim($_POST['start_datetime'] ?? '');
      $end    = trim($_POST['end_datetime'] ?? '');
      $active = isset($_POST['is_active']) ? 1 : 0;

      if ($title === '' || $start === '' || $end === '') {
        throw new RuntimeException('Title, start and end are required.');
      }

      $pdo->prepare("
        INSERT INTO events (title, description, location, start_datetime, end_datetime, type, is_active)
        VALUES (?,?,?,?,?,?,?)
      ")->execute([$title, $desc, $loc, $start, $end, $type, $active]);

      $ok = 'Event added.';
    }
    elseif ($action === 'edit_event') {
      $id     = (int)($_POST['id'] ?? 0);
      $title  = trim($_POST['title'] ?? '');
      $desc   = trim($_POST['description'] ?? '');
      $loc    = trim($_POST['location'] ?? '');
      $type   = $_POST['type'] ?? 'online';
      $start  = trim($_POST['start_datetime'] ?? '');
      $end    = trim($_POST['end_datetime'] ?? '');
      $active = isset($_POST['is_active']) ? 1 : 0;

      if ($id <= 0) throw new RuntimeException('Invalid event id.');
      if ($title === '' || $start === '' || $end === '') {
        throw new RuntimeException('Title, start and end are required.');
      }

      $pdo->prepare("
        UPDATE events
           SET title=?, description=?, location=?, start_datetime=?, end_datetime=?, type=?, is_active=?
         WHERE id=?
      ")->execute([$title, $desc, $loc, $start, $end, $type, $active, $id]);

      $ok = 'Event updated.';
    }
    elseif ($action === 'toggle_event') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) throw new RuntimeException('Invalid event id.');
      $pdo->prepare("UPDATE events SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
      $ok = 'Visibility toggled.';
    }
    elseif ($action === 'delete_event') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) throw new RuntimeException('Invalid event id.');
      $pdo->prepare("DELETE FROM events WHERE id=?")->execute([$id]);
      $ok = 'Event deleted.';
    }
  } catch (Throwable $ex) {
    $err = $ex->getMessage();
  }
}

/* ---------- Fetch ---------- */
$events = [];
try {
  $events = $pdo->query("SELECT * FROM events ORDER BY start_datetime ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $err = $err ?: 'Failed to load events.';
}

/* ---------- Navbar state / avatar (same as dashboard) ---------- */
$adminName   = $_SESSION['admin_name']  ?? 'Purnima Mali';
$adminEmail  = $_SESSION['admin_email'] ?? 'malipurnima2058@gmail.com';
$active      = 'admin_events.php'; // highlight Events

$initialSrc     = trim($adminName ?: $adminEmail ?: 'A');
$avatarInitial  = strtoupper(mb_substr($initialSrc, 0, 1, 'UTF-8')) ?: 'A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin • Events</title>
<?php require __DIR__.'/admin_head_snippet.php'; ?>  <!-- boot (applies data-theme & tokens early) -->
<link rel="stylesheet" href="./admin-theme.css?v=1">   <!-- global tokens & component colors -->
<!-- then your page-specific CSS: -->
<link rel="stylesheet" href="./admin_dashboard.css?v=2">  <!-- or admin_services.css, etc. -->

 <link rel="stylesheet" href="./admin_events.css?v=3">
</head>
<body>

  <!-- ===== Dashboard-style Navbar ===== -->
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

  <!-- ===== Page content ===== -->
  <div class="wrap">
  <?php if ($err): ?><div class="card error">❌ <?= e($err) ?></div><?php endif; ?>
  <?php if ($ok):  ?><div class="card success">✅ <?= e($ok) ?></div><?php endif; ?>

  <h1>Events</h1>

  <!-- Add event -->
  <article class="card add-card">
    <h3>Add Event</h3>
    <form method="post" class="admin-form">
      <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
      <input type="hidden" name="action" value="add_event">

      <label>Title <input type="text" name="title" required></label>
      <label>Description <textarea name="description" rows="3"></textarea></label>
      <label>Location <input type="text" name="location" placeholder="Sunderland, UK / Online"></label>

      <div class="grid">
        <div>
          <label>Start <input type="datetime-local" name="start_datetime" required></label>
        </div>
        <div>
          <label>End <input type="datetime-local" name="end_datetime" required></label>
        </div>
      </div>

      <div class="row">
        <label>Type
          <select name="type">
            <option value="online">Online</option>
            <option value="inperson">In person</option>
          </select>
        </label>
        <label><input type="checkbox" name="is_active" checked> Published</label>
      </div>

      <button class="btn">Add</button>
    </form>
  </article>

  <!-- Existing events -->
  <?php foreach ($events as $ev): ?>
    <article class="card event-card">
      <div class="event-head">
        <!-- Left: date badge -->
        <div class="date-badge">
          <div class="dayline"><?= date('d M Y', strtotime($ev['start_datetime'])) ?></div>
          <div class="timeline">
            <?= date('H:i', strtotime($ev['start_datetime'])) ?> – <?= date('H:i', strtotime($ev['end_datetime'])) ?>
          </div>
        </div>

        <!-- Right: main -->
        <div class="event-main">
          <div class="card-head">
            <h3 class="event-title">
              <?= e($ev['title']) ?>
              <span class="status-pill <?= $ev['is_active'] ? 'on' : 'off' ?>">
                <?= $ev['is_active'] ? 'Published' : 'Draft' ?>
              </span>
            </h3>
          </div>

          <p class="event-kicker">
            <?= ($ev['type'] === 'inperson' ? 'In person' : 'Online') ?> •
            <?= e($ev['location'] ?: '—') ?>
          </p>

          <?php if (!empty($ev['description'])): ?>
            <p class="event-desc"><?= nl2br(e($ev['description'])) ?></p>
          <?php endif; ?>

          <div class="admin-toolbar">
            <form method="post">
              <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
              <input type="hidden" name="action" value="toggle_event">
              <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
              <button class="btn small ghost"><?= $ev['is_active'] ? 'Unpublish' : 'Publish' ?></button>
            </form>

            <button class="btn small" data-edit="#edit-<?= (int)$ev['id'] ?>">Edit</button>

            <form method="post" onsubmit="return confirm('Delete this event?')">
              <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
              <input type="hidden" name="action" value="delete_event">
              <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
              <button class="btn small warn">Delete</button>
            </form>
          </div>

          <!-- inline editor -->
          <details id="edit-<?= (int)$ev['id'] ?>" class="edit-details">
            <summary class="sr-only">Edit event</summary>
            <form method="post" class="admin-form">
              <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
              <input type="hidden" name="action" value="edit_event">
              <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">

              <label>Title <input type="text" name="title" value="<?= e($ev['title']) ?>" required></label>
              <label>Description <textarea name="description" rows="3"><?= e($ev['description']) ?></textarea></label>
              <label>Location <input type="text" name="location" value="<?= e($ev['location']) ?>"></label>

              <div class="grid">
                <div>
                  <label>Start
                    <input type="datetime-local" name="start_datetime"
                      value="<?= date('Y-m-d\TH:i', strtotime($ev['start_datetime'])) ?>" required>
                  </label>
                </div>
                <div>
                  <label>End
                    <input type="datetime-local" name="end_datetime"
                      value="<?= date('Y-m-d\TH:i', strtotime($ev['end_datetime'])) ?>" required>
                  </label>
                </div>
              </div>

              <div class="row">
                <label>Type
                  <select name="type">
                    <option value="online"  <?= $ev['type']==='online'?'selected':'' ?>>Online</option>
                    <option value="inperson" <?= $ev['type']==='inperson'?'selected':'' ?>>In person</option>
                  </select>
                </label>
                <label><input type="checkbox" name="is_active" <?= $ev['is_active']?'checked':'' ?>> Published</label>
              </div>

              <button class="btn">Save changes</button>
            </form>
          </details>
        </div><!-- /.event-main -->
      </div><!-- /.event-head -->
    </article>
  <?php endforeach; ?>
</div>

<!-- ===== JS: edit shortcut + mobile menu + account dropdown ===== -->
<script>
  // open details editor when clicking "Edit"
  document.querySelectorAll('[data-edit]').forEach(btn=>{
    const sel = btn.getAttribute('data-edit');
    btn.addEventListener('click', ()=>{
      const d = document.querySelector(sel);
      if (d) { d.open = true; d.scrollIntoView({behavior:'smooth', block:'center'}); }
    });
  });

  // mobile nav toggle
  (function(){
    const t = document.querySelector('.toggle');
    const links = document.getElementById('adminNavLinks');
    if (t && links) {
      t.addEventListener('click', ()=>{
        const open = links.classList.toggle('open');
        t.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    }
  })();

  // account dropdown (click outside / Esc to close)
  (function(){
    const btn = document.getElementById('accountBtn');
    const menu = document.getElementById('accountMenu');
    if (!btn || !menu) return;
    const close = () => { menu.classList.remove('open'); btn.setAttribute('aria-expanded','false'); };
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const open = menu.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', (e) => { if (!menu.contains(e.target)) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
  })();
</script>
<script src="./admin_logout.js?v=1"></script>
</body>
</html>
