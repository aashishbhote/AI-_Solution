<?php
// admin_portfolio.php — Manage Past Portfolios + Read/Unread Customer Feedback
declare(strict_types=1);

require __DIR__ . '/admin_auth.php';     // session + sets $_SESSION['csrf']
require __DIR__ . '/db_connection.php';  // provides $pdo (PDO)

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

/* ---------- Bootstrap tables safely (no-op if already exist) ---------- */
try {
  // Portfolios
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS portfolios (
      id INT AUTO_INCREMENT PRIMARY KEY,
      title VARCHAR(180) NOT NULL,
      summary TEXT NULL,
      is_active TINYINT(1) NOT NULL DEFAULT 1,
      sort_order INT NOT NULL DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  // Portfolio features
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS portfolio_features (
      id INT AUTO_INCREMENT PRIMARY KEY,
      portfolio_id INT NOT NULL,
      feature_text VARCHAR(500) NOT NULL,
      sort_order INT NOT NULL DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");

  // Customer feedback (read-only for admin; can only toggle read/unread)
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS customer_feedback (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(150) NOT NULL,
      company VARCHAR(180) NULL,
      role_title VARCHAR(180) NULL,
      rating DECIMAL(3,1) NULL,      -- 0..5 (one decimal)
      message TEXT NOT NULL,
      is_read TINYINT(1) NOT NULL DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");
} catch (Throwable $e) {
  $bootstrapErr = $e->getMessage();
}

/* ---------- POST actions ---------- */
$err = ''; $ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  ensure_post_csrf($_POST['csrf'] ?? '');
  $action = $_POST['action'] ?? '';

  try {
    /* --- Portfolios CRUD --- */
    if ($action === 'add_portfolio') {
      $title = trim($_POST['title'] ?? '');
      $summary = trim($_POST['summary'] ?? '');
      $order = (int)($_POST['sort_order'] ?? 0);
      $active = isset($_POST['is_active']) ? 1 : 0;
      if ($title === '') throw new RuntimeException('Title is required.');
      $pdo->prepare("INSERT INTO portfolios (title, summary, is_active, sort_order)
                     VALUES (?,?,?,?)")->execute([$title, $summary ?: null, $active, $order]);
      $ok = 'Portfolio added.';
    }
    elseif ($action === 'edit_portfolio') {
      $id = (int)($_POST['id'] ?? 0);
      $title = trim($_POST['title'] ?? '');
      $summary = trim($_POST['summary'] ?? '');
      $order = (int)($_POST['sort_order'] ?? 0);
      $active = isset($_POST['is_active']) ? 1 : 0;
      if ($id <= 0) throw new RuntimeException('Invalid portfolio id.');
      if ($title === '') throw new RuntimeException('Title is required.');
      $pdo->prepare("UPDATE portfolios
                        SET title=?, summary=?, is_active=?, sort_order=?
                      WHERE id=?")
          ->execute([$title, $summary ?: null, $active, $order, $id]);
      $ok = 'Portfolio updated.';
    }
    elseif ($action === 'toggle_portfolio') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) throw new RuntimeException('Invalid portfolio id.');
      $pdo->prepare("UPDATE portfolios SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
      $ok = 'Portfolio visibility toggled.';
    }
    elseif ($action === 'delete_portfolio') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) throw new RuntimeException('Invalid portfolio id.');
      $pdo->prepare("DELETE FROM portfolios WHERE id=?")->execute([$id]);
      $ok = 'Portfolio deleted.';
    }

    /* --- Features --- */
    elseif ($action === 'add_feature') {
      $pid = (int)($_POST['portfolio_id'] ?? 0);
      $text = trim($_POST['feature_text'] ?? '');
      $fOrder = (int)($_POST['feature_order'] ?? 0);
      if ($pid <= 0 || $text === '') throw new RuntimeException('Feature text is required.');
      $pdo->prepare("INSERT INTO portfolio_features (portfolio_id, feature_text, sort_order)
                     VALUES (?,?,?)")->execute([$pid, $text, $fOrder]);
      $ok = 'Feature added.';
    }
    elseif ($action === 'delete_feature') {
      $fid = (int)($_POST['id'] ?? 0);
      if ($fid <= 0) throw new RuntimeException('Invalid feature id.');
      $pdo->prepare("DELETE FROM portfolio_features WHERE id=?")->execute([$fid]);
      $ok = 'Feature deleted.';
    }

    /* --- Feedback: mark read/unread (no add/approve) --- */
    elseif ($action === 'toggle_read') {
      $id = (int)($_POST['id'] ?? 0);
      $to = (int)($_POST['to'] ?? 0) ? 1 : 0;
      if ($id <= 0) throw new RuntimeException('Invalid feedback id.');
      $pdo->prepare("UPDATE customer_feedback SET is_read=? WHERE id=?")->execute([$to, $id]);
      $ok = $to ? 'Marked as read.' : 'Marked as unread.';
    }
    elseif ($action === 'delete_feedback') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) throw new RuntimeException('Invalid feedback id.');
      $pdo->prepare("DELETE FROM customer_feedback WHERE id=?")->execute([$id]);
      $ok = 'Feedback deleted.';
    }

  } catch (Throwable $ex) {
    $err = $ex->getMessage();
  }
}

/* ---------- Fetch data ---------- */
try {
  $portfolios = $pdo->query("SELECT * FROM portfolios ORDER BY sort_order ASC, title ASC")
                    ->fetchAll(PDO::FETCH_ASSOC);
  $featuresStmt = $pdo->prepare("SELECT id, feature_text, sort_order
                                 FROM portfolio_features
                                 WHERE portfolio_id=?
                                 ORDER BY sort_order ASC, id ASC");
} catch (Throwable $e) {
  $portfolios = [];
  $err = $err ?: 'Failed to load portfolios.';
}

/* Feedback filters/search */
$fb_status = $_GET['fb_status'] ?? '';  // '', 'read', 'unread'
$fb_q      = trim($_GET['fb_q'] ?? '');

$where = [];
$params = [];
if ($fb_status === 'read')   $where[] = 'is_read = 1';
if ($fb_status === 'unread') $where[] = 'is_read = 0';
if ($fb_q !== '') {
  $where[] = "(name LIKE ? OR company LIKE ? OR role_title LIKE ? OR message LIKE ?)";
  for ($i=0; $i<4; $i++) $params[] = "%{$fb_q}%";
}
$sql = "SELECT * FROM customer_feedback";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY created_at DESC, id DESC";
try {
  $st = $pdo->prepare($sql);
  $st->execute($params);
  $feedback = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $feedback = [];
  $err = $err ?: 'Failed to load feedback.';
}

/* ---------- Navbar bits ---------- */
$active = basename($_SERVER['PHP_SELF']);
$adminName   = $_SESSION['admin_name']  ?? 'Purnima Mali';
$adminEmail  = $_SESSION['admin_email'] ?? 'malipurnima2058@gmail.com';
if (function_exists('mb_substr')) {
  $avatarInitial = strtoupper(mb_substr($adminName ?: $adminEmail, 0, 1));
} else {
  $avatarInitial = strtoupper(substr($adminName ?: $adminEmail, 0, 1));
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin • Portfolios & Feedback</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
<?php require __DIR__.'/admin_head_snippet.php'; ?>
<link rel="stylesheet" href="./admin-theme.css?v=1">   <!-- add this -->
<link rel="stylesheet" href="./admin_dashboard.css?v=2"> 
 <link rel="stylesheet" href="./admin_portfolios.css">
  
</head>
<body>

  <!-- Sidebar (matches your other admin pages) -->
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

  <!-- Content -->
  <div class="wrap">
    <?php if (!empty($bootstrapErr)): ?>
      <div class="card error">⚠️ <?= e($bootstrapErr) ?></div>
    <?php endif; ?>
    <?php if ($err): ?><div class="card error">❌ <?= e($err) ?></div><?php endif; ?>
    <?php if ($ok):  ?><div class="card success">✅ <?= e($ok) ?></div><?php endif; ?>

    <h1>Portfolios & Customer Feedback</h1>

    <div class="two-col">
      <!-- Left: Portfolios -->
      <section>
        <article class="card add-card">
          <h3>Add Portfolio</h3>
          <form method="post" class="admin-form">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="action" value="add_portfolio">
            <div class="grid">
              <div style="grid-column:1/-1">
                <label>Title
                  <input type="text" name="title" required>
                </label>
              </div>
              <div style="grid-column:1/-1">
                <label>Summary (short)
                  <textarea name="summary" rows="3" placeholder="Short description"></textarea>
                </label>
              </div>
              <div>
                <label>Sort order
                  <input type="number" name="sort_order" value="0">
                </label>
              </div>
              <div>
                <label>Published</label><br>
                <input type="checkbox" name="is_active" checked> Show on site
              </div>
            </div>
            <button class="btn">Add</button>
          </form>
        </article>

        <?php if (!empty($portfolios)): ?>
          <?php foreach ($portfolios as $p): ?>
            <article class="card" style="margin-bottom:.75rem">
              <div class="admin-toolbar">
                <form method="post">
                  <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                  <input type="hidden" name="action" value="toggle_portfolio">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button class="btn small ghost"><?= $p['is_active'] ? 'Unpublish' : 'Publish' ?></button>
                </form>

                <button class="btn small" data-edit="#edit-<?= (int)$p['id'] ?>">Edit</button>

                <form method="post" onsubmit="return confirm('Delete this portfolio and its features?')">
                  <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                  <input type="hidden" name="action" value="delete_portfolio">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button class="btn small warn">Delete</button>
                </form>
              </div>

              <div class="card-head">
                <h3>
                  <?= e($p['title']) ?>
                  <span class="status-pill <?= $p['is_active'] ? 'on' : 'off' ?>">
                    <?= $p['is_active'] ? 'Published' : 'Draft' ?>
                  </span>
                </h3>
              </div>

              <?php if (!empty($p['summary'])): ?>
                <p class="muted" style="margin:.35rem 0 .5rem"><?= e($p['summary']) ?></p>
              <?php endif; ?>

              <?php
                $pf = [];
                try { $featuresStmt->execute([$p['id']]); $pf = $featuresStmt->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) {}
                if ($pf):
              ?>
                <ul class="feature-list">
                  <?php foreach ($pf as $f): ?>
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

              <!-- Inline edit + add feature -->
              <details id="edit-<?= (int)$p['id'] ?>" class="edit-details">
                <summary>Edit portfolio</summary>
                <form method="post" class="admin-form">
                  <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                  <input type="hidden" name="action" value="edit_portfolio">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">

                  <div class="grid">
                    <div style="grid-column:1/-1">
                      <label>Title
                        <input type="text" name="title" value="<?= e($p['title']) ?>" required>
                      </label>
                    </div>
                    <div style="grid-column:1/-1">
                      <label>Summary
                        <textarea name="summary" rows="3"><?= e((string)$p['summary']) ?></textarea>
                      </label>
                    </div>
                    <div>
                      <label>Sort order
                        <input type="number" name="sort_order" value="<?= (int)$p['sort_order'] ?>">
                      </label>
                    </div>
                    <div>
                      <label>Published</label><br>
                      <input type="checkbox" name="is_active" <?= $p['is_active'] ? 'checked' : '' ?>> Show on site
                    </div>
                  </div>

                  <button class="btn">Save changes</button>
                </form>

                <form method="post" class="admin-form feature-add" style="margin-top:.5rem">
                  <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                  <input type="hidden" name="action" value="add_feature">
                  <input type="hidden" name="portfolio_id" value="<?= (int)$p['id'] ?>">
                  <div class="grid">
                    <div style="grid-column:1/-1">
                      <label>Feature text
                        <input type="text" name="feature_text" placeholder="Add a feature…" required>
                      </label>
                    </div>
                    <div>
                      <label>Feature order
                        <input type="number" name="feature_order" value="0">
                      </label>
                    </div>
                  </div>
                  <button class="btn">Add feature</button>
                </form>
              </details>
            </article>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="muted">No portfolios yet. Use “Add Portfolio”.</p>
        <?php endif; ?>
      </section>

      <!-- Right: Customer Feedback (read-only; mark read/unread) -->
      <section>
        <article class="card">
          <h3>Customer Feedback</h3>
          <form class="toolbar" method="get">
            <input type="search" name="fb_q" value="<?= e($fb_q) ?>" placeholder="Search name, company, role, message…">
            <select name="fb_status">
              <option value="">All</option>
              <option value="unread" <?= $fb_status==='unread'?'selected':'' ?>>Unread</option>
              <option value="read"   <?= $fb_status==='read'?'selected':'' ?>>Read</option>
            </select>
            <button class="btn">Apply</button>
          </form>

          <?php if (!empty($feedback)): ?>
            <?php foreach ($feedback as $r): ?>
              <div class="card" style="margin:8px 0; padding:16px">
                <div class="admin-toolbar" style="justify-content:space-between">
                  <div>
                    <strong><?= e($r['name']) ?></strong>
                    <?php if (!empty($r['role_title'])): ?>
                      <span class="muted">• <?= e($r['role_title']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($r['company'])): ?>
                      <span class="muted">• <?= e($r['company']) ?></span>
                    <?php endif; ?>
                    <?php if (!is_null($r['rating'])): ?>
                      <span class="muted">• Rating: <?= e((string)$r['rating']) ?>/5</span>
                    <?php endif; ?>
                    <?php if (!empty($r['created_at'])): ?>
                      <span class="muted">• <?= date('d M Y H:i', strtotime($r['created_at'])) ?></span>
                    <?php endif; ?>
                    <?php if ((int)$r['is_read'] === 1): ?>
                      <span class="pill read"   style="margin-left:.5rem">Read</span>
                    <?php else: ?>
                      <span class="pill unread" style="margin-left:.5rem">Unread</span>
                    <?php endif; ?>
                  </div>

                  <form method="post" style="display:flex; gap:6px">
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <?php if ((int)$r['is_read'] === 1): ?>
                      <input type="hidden" name="to" value="0">
                      <button class="btn small ghost" name="action" value="toggle_read">Mark as unread</button>
                    <?php else: ?>
                      <input type="hidden" name="to" value="1">
                      <button class="btn small ghost" name="action" value="toggle_read">Mark as read</button>
                    <?php endif; ?>
                    <button class="btn small warn" name="action" value="delete_feedback"
                            onclick="return confirm('Delete this feedback?')">Delete</button>
                  </form>
                </div>

                <details style="margin-top:.5rem">
                  <summary><?= (int)$r['is_read']===1 ? 'View message' : 'Read message' ?></summary>
                  <div style="margin-top:.5rem"><?= nl2br(e($r['message'])) ?></div>
                </details>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="muted">No feedback found for the chosen filters.</p>
          <?php endif; ?>
        </article>
      </section>
    </div>
  </div>

  <script>
    // open <details> editor when clicking small "Edit" buttons
    document.querySelectorAll('[data-edit]').forEach(btn => {
      const sel = btn.getAttribute('data-edit');
      btn.addEventListener('click', () => {
        const d = document.querySelector(sel);
        if (d) d.open = true, d.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    });

    // Sidebar toggle + account dropdown
    (function(){
      const sidebar = document.getElementById('adminNav');
      const localToggle = sidebar?.querySelector('.toggle');
      function toggleSidebar(){
        const open = sidebar.classList.toggle('open');
        localToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
      }
      localToggle?.addEventListener('click', toggleSidebar);

      const btn  = document.getElementById('accountBtn');
      const menu = document.getElementById('accountMenu');
      function closeMenu(){ if(!menu||!btn) return; menu.classList.remove('open'); btn.setAttribute('aria-expanded','false'); }
      btn?.addEventListener('click', (e)=>{ e.stopPropagation();
        const willOpen=!menu.classList.contains('open');
        document.querySelectorAll('.account-menu.open').forEach(m=>m.classList.remove('open'));
        if(willOpen){menu.classList.add('open'); btn.setAttribute('aria-expanded','true');} else {closeMenu();}
      });
      document.addEventListener('click', (e)=>{ if(menu&&btn&&!menu.contains(e.target)&&e.target!==btn) closeMenu(); });
      document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closeMenu(); });
    })();
  </script>


</html>
</body>
</html>
