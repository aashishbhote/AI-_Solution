<?php
// admin_services.php — Admin page for Services + Service Features
declare(strict_types=1);

require __DIR__ . '/admin_auth.php';     // session + ensure_post_csrf + sets $_SESSION['csrf']
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
function slugify(string $s): string {
  $s = strtolower(trim($s));
  $s = preg_replace('/[^a-z0-9]+/', '-', $s);
  return trim($s, '-') ?: 'item';
}
function unique_slug(PDO $pdo, string $title, ?int $ignoreId = null, string $table = 'services'): string {
  $base = slugify($title);
  $cand = $base; $i = 2;
  while (true) {
    $sql = $ignoreId
      ? "SELECT id FROM {$table} WHERE slug=? AND id<>? LIMIT 1"
      : "SELECT id FROM {$table} WHERE slug=? LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute($ignoreId ? [$cand, $ignoreId] : [$cand]);
    if (!$st->fetch()) return $cand;
    $cand = $base . '-' . $i++;
  }
}

/* ---------- POST actions ---------- */
$err = ''; $ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  ensure_post_csrf($_POST['csrf'] ?? '');
  $action = $_POST['action'] ?? '';

  try {
    /* --- Services --- */
    if ($action === 'add_service') {
      $title = trim($_POST['title'] ?? '');
      $icon  = trim($_POST['icon_emoji'] ?? '🧩');
      $cat   = trim($_POST['category'] ?? 'general');
      $desc  = trim($_POST['short_desc'] ?? '');
      $link  = trim($_POST['link_url'] ?? '');
      $order = (int)($_POST['sort_order'] ?? 0);
      $active = isset($_POST['is_active']) ? 1 : 0;

      if ($title === '' || $desc === '') throw new RuntimeException('Title and short description are required.');
      $slug = unique_slug($pdo, $title, null, 'services');

      $pdo->prepare("INSERT INTO services (title, slug, icon_emoji, category, short_desc, link_url, is_active, sort_order)
                     VALUES (?,?,?,?,?,?,?,?)")
          ->execute([$title, $slug, $icon, $cat, $desc, $link ?: null, $active, $order]);

      $ok = 'Service added.';
    }
    elseif ($action === 'toggle_service') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) throw new RuntimeException('Invalid ID.');
      $pdo->prepare("UPDATE services SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
      $ok = 'Service visibility toggled.';
    }
    elseif ($action === 'edit_service') {
      $id    = (int)($_POST['id'] ?? 0);
      $title = trim($_POST['title'] ?? '');
      $icon  = trim($_POST['icon_emoji'] ?? '🧩');
      $cat   = trim($_POST['category'] ?? 'general');
      $desc  = trim($_POST['short_desc'] ?? '');
      $link  = trim($_POST['link_url'] ?? '');
      $order = (int)($_POST['sort_order'] ?? 0);
      $active = isset($_POST['is_active']) ? 1 : 0;

      if ($id <= 0) throw new RuntimeException('Invalid ID.');
      if ($title === '' || $desc === '') throw new RuntimeException('Title and short description are required.');

      $slug = unique_slug($pdo, $title, $id, 'services');

      $pdo->prepare("UPDATE services
                       SET title=?, slug=?, icon_emoji=?, category=?, short_desc=?, link_url=?, is_active=?, sort_order=?
                     WHERE id=?")
          ->execute([$title, $slug, $icon, $cat, $desc, $link ?: null, $active, $order, $id]);

      $ok = 'Service updated.';
    }
    elseif ($action === 'delete_service') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) throw new RuntimeException('Invalid ID.');
      $pdo->prepare("DELETE FROM service_features WHERE service_id=?")->execute([$id]);
      $pdo->prepare("DELETE FROM services WHERE id=?")->execute([$id]);
      $ok = 'Service deleted.';
    }

    /* --- Service features --- */
    elseif ($action === 'add_feature') {
      $serviceId = (int)($_POST['service_id'] ?? 0);
      $text      = trim($_POST['feature_text'] ?? '');
      if ($serviceId <= 0 || $text === '') throw new RuntimeException('Feature text required.');

      $next = (int)$pdo->query("SELECT COALESCE(MAX(sort_order), 0) FROM service_features WHERE service_id={$serviceId}")
                       ->fetchColumn() + 1;

      $pdo->prepare("INSERT INTO service_features (service_id, feature_text, sort_order) VALUES (?,?,?)")
          ->execute([$serviceId, $text, $next]);

      $ok = 'Feature added.';
    }
    elseif ($action === 'delete_feature') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) throw new RuntimeException('Invalid feature id.');
      $pdo->prepare("DELETE FROM service_features WHERE id=?")->execute([$id]);
      $ok = 'Feature deleted.';
    }

  } catch (Throwable $ex) {
    $err = $ex->getMessage();
  }
}

/* ---------- Fetch data ---------- */
$services = [];
try {
  $services = $pdo->query("SELECT * FROM services ORDER BY sort_order ASC, title ASC")->fetchAll(PDO::FETCH_ASSOC);
  $featuresStmt = $pdo->prepare("SELECT id, feature_text FROM service_features WHERE service_id=? ORDER BY sort_order ASC, id ASC");
} catch (Throwable $e) {
  $err = $err ?: 'Failed to load data.';
}

/* ---------- Navbar state / avatar ---------- */
$adminName   = $_SESSION['admin_name']  ?? 'Purnima Mali';
$adminEmail  = $_SESSION['admin_email'] ?? 'malipurnima2058@gmail.com';
$active      = 'admin_services.php'; // highlight Services
$initialSrc  = trim($adminName ?: $adminEmail ?: 'A');
$avatarInitial = strtoupper(mb_substr($initialSrc, 0, 1, 'UTF-8')) ?: 'A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin • Services & Features</title>
<?php require __DIR__.'/admin_head_snippet.php'; ?>  <!-- boot (applies data-theme & tokens early) -->
<link rel="stylesheet" href="./admin-theme.css?v=1">   <!-- global tokens & component colors -->
<!-- then your page-specific CSS: -->
<link rel="stylesheet" href="./admin_dashboard.css?v=2">  <!-- or admin_services.css, etc. -->

  <link rel="stylesheet" href="./admin_services.css?v=3">
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

    <h1>Services</h1>

    <div class="service-grid">

      <!-- Add Service -->
      <article class="card service-item add-card">
        <div class="card-head">
          <div class="icon">➕</div>
          <h3>Add Service</h3>
        </div>

        <form method="post" class="admin-form">
          <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
          <input type="hidden" name="action" value="add_service">

          <div class="grid">
            <div>
              <label>Title</label>
              <input type="text" name="title" required>
            </div>
            <div>
              <label>Icon (emoji)</label>
              <input type="text" name="icon_emoji" value="🧩" maxlength="8">
            </div>
            <div>
              <label>Category</label>
              <input type="text" name="category" value="general">
            </div>
            <div>
              <label>Link URL (optional)</label>
              <input type="text" name="link_url" placeholder="services.php#anchor">
            </div>
            <div style="grid-column:1/-1">
              <label>Short description</label>
              <textarea name="short_desc" rows="2" required></textarea>
            </div>
            <div>
              <label>Sort order</label>
              <input type="number" name="sort_order" value="0">
            </div>
            <div>
              <label>Published</label><br>
              <input type="checkbox" name="is_active"> Show on site
            </div>
          </div>

          <div class="card-actions">
            <button class="btn" type="submit">Add</button>
          </div>
        </form>
      </article>

      <!-- Existing Services -->
      <?php if ($services): ?>
        <?php foreach ($services as $s): ?>
          <article class="card service-item" data-category="<?= e($s['category'] ?? '') ?>">

            <div class="admin-toolbar">
              <form method="post">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                <input type="hidden" name="action" value="toggle_service">
                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                <button class="btn small ghost"><?= $s['is_active'] ? 'Unpublish' : 'Publish' ?></button>
              </form>

              <button class="btn small" data-edit="#edit-<?= (int)$s['id'] ?>">Edit</button>

              <form method="post" onsubmit="return confirm('Delete this service and its features?')">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                <input type="hidden" name="action" value="delete_service">
                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                <button class="btn small warn">Delete</button>
              </form>
            </div>

            <div class="card-head">
              <div class="icon"><?= e($s['icon_emoji'] ?: '🧩') ?></div>
              <h3>
                <?= e($s['title']) ?>
                <span class="status-pill <?= $s['is_active'] ? 'on' : 'off' ?>">
                  <?= $s['is_active'] ? 'Published' : 'Draft' ?>
                </span>
              </h3>
            </div>

            <?php if (!empty($s['short_desc'])): ?>
              <p class="muted"><?= e($s['short_desc']) ?></p>
            <?php endif; ?>

            <?php
              $svcFeatures = [];
              try { $featuresStmt->execute([$s['id']]); $svcFeatures = $featuresStmt->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) {}
              if ($svcFeatures):
            ?>
              <ul class="feature-list">
                <?php foreach ($svcFeatures as $f): ?>
                  <li>
                    <?= e($f['feature_text']) ?>
                    <form method="post" class="feature-del" onsubmit="return confirm('Delete feature?')">
                      <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                      <input type="hidden" name="action" value="delete_feature">
                      <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                      <button class="link danger" title="Delete">×</button>
                    </form>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>

            <!-- Inline editor -->
            <details id="edit-<?= (int)$s['id'] ?>" class="edit-details">
              <summary>Edit service</summary>
              <form method="post" class="admin-form">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                <input type="hidden" name="action" value="edit_service">
                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">

                <div class="grid">
                  <div>
                    <label>Title</label>
                    <input type="text" name="title" value="<?= e($s['title']) ?>" required>
                  </div>
                  <div>
                    <label>Icon (emoji)</label>
                    <input type="text" name="icon_emoji" value="<?= e($s['icon_emoji']) ?>" maxlength="8">
                  </div>
                  <div>
                    <label>Category</label>
                    <input type="text" name="category" value="<?= e($s['category']) ?>">
                  </div>
                  <div>
                    <label>Link URL</label>
                    <input type="text" name="link_url" value="<?= e((string)$s['link_url']) ?>">
                  </div>
                  <div style="grid-column:1/-1">
                    <label>Short description</label>
                    <textarea name="short_desc" rows="2" required><?= e($s['short_desc']) ?></textarea>
                  </div>
                  <div>
                    <label>Sort order</label>
                    <input type="number" name="sort_order" value="<?= (int)$s['sort_order'] ?>">
                  </div>
                  <div>
                    <label>Published</label><br>
                    <input type="checkbox" name="is_active" <?= $s['is_active'] ? 'checked' : '' ?>> Show on site
                  </div>
                </div>

                <div class="card-actions"><button class="btn">Save changes</button></div>
              </form>

              <form method="post" class="admin-form feature-add">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                <input type="hidden" name="action" value="add_feature">
                <input type="hidden" name="service_id" value="<?= (int)$s['id'] ?>">
                <div class="row">
                  <input type="text" name="feature_text" placeholder="Add a feature…" required style="flex:1">
                  <button class="btn">Add feature</button>
                </div>
              </form>
            </details>
          </article>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="muted">No services yet. Use the “Add Service” card.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- ===== JS: mobile menu + account dropdown + edit shortcut ===== -->
  <script>
    // open <details> editor when clicking small "Edit" buttons on service cards
    document.querySelectorAll('[data-edit]').forEach(btn => {
      const sel = btn.getAttribute('data-edit');
      btn.addEventListener('click', () => {
        const d = document.querySelector(sel);
        if (d) d.open = true;
        d?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    });

    // mobile nav toggle
    (function(){
      const t = document.querySelector('.toggle');
      const links = document.getElementById('adminNavLinks');
      if (t && links) {
        t.addEventListener('click', () => {
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

  </html>
</body>
</html>
