<?php
// admin_blogs.php — Manage blog posts (no images)
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
    if ($action === 'add_blog') {
      $title   = trim($_POST['title'] ?? '');
      $cat     = $_POST['category'] ?? '';
      $excerpt = trim($_POST['excerpt'] ?? '');
      $content = trim($_POST['content'] ?? '');
      $reads   = max(0, (int)($_POST['reads'] ?? 0));
      $pubAt   = trim($_POST['published_at'] ?? '');
      if ($pubAt !== '') {
  if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $pubAt)) {
    $pubAt = str_replace('T', ' ', $pubAt) . ':00'; // -> YYYY-MM-DD HH:MM:SS
  }
} else {
  $pubAt = null;
}
      $active  = isset($_POST['is_active']) ? 1 : 0;

      if ($title === '') throw new RuntimeException('Title is required.');
      if (!in_array($cat, ['Assistant','Prototyping','Analytics','Security','Updates'], true)) {
        throw new RuntimeException('Invalid category.');
      }
      $pubAt = ($pubAt !== '') ? $pubAt : null;

      $pdo->prepare("
        INSERT INTO `blogs`
              (`title`, `category`, `excerpt`, `content`, `reads`, `published_at`, `is_active`)
            VALUES (?,?,?,?,?,?,?)
          ")->execute([$title, $cat, $excerpt ?: null, $content ?: null, $reads, $pubAt, $active]);

      $ok = 'Blog post added.';
    }
    elseif ($action === 'edit_blog') {
      $id      = (int)($_POST['id'] ?? 0);
      $title   = trim($_POST['title'] ?? '');
      $cat     = $_POST['category'] ?? '';
      $excerpt = trim($_POST['excerpt'] ?? '');
      $content = trim($_POST['content'] ?? '');
      $reads   = max(0, (int)($_POST['reads'] ?? 0));
      $pubAt   = trim($_POST['published_at'] ?? '');
      $active  = isset($_POST['is_active']) ? 1 : 0;

      if ($id <= 0) throw new RuntimeException('Invalid blog id.');
      if ($title === '') throw new RuntimeException('Title is required.');
      if (!in_array($cat, ['Assistant','Prototyping','Analytics','Security','Updates'], true)) {
        throw new RuntimeException('Invalid category.');
      }
      $pubAt = ($pubAt !== '') ? $pubAt : null;

      $pdo->prepare("
  UPDATE `blogs`
     SET `title`=?, `category`=?, `excerpt`=?, `content`=?, `reads`=?, `published_at`=?, `is_active`=?
   WHERE `id`=?
")->execute([$title, $cat, $excerpt ?: null, $content ?: null, $reads, $pubAt, $active, $id]);

      $ok = 'Blog post updated.';
    }
    elseif ($action === 'toggle_blog') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) throw new RuntimeException('Invalid blog id.');
$pdo->prepare("UPDATE `blogs` SET `is_active` = 1 - `is_active` WHERE `id`=?")
    ->execute([$id]);
          $ok = 'Visibility toggled.';
    }
    elseif ($action === 'delete_blog') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) throw new RuntimeException('Invalid blog id.');
$pdo->prepare("DELETE FROM `blogs` WHERE `id`=?")->execute([$id]);
      $ok = 'Blog post deleted.';
    }
  } catch (Throwable $ex) {
    $err = $ex->getMessage();
  }
}

/* ---------- Fetch (newest first, then id desc) ---------- */
$blogs = [];
try {
  $blogs = $pdo->query("
  SELECT *
  FROM `blogs`
  ORDER BY COALESCE(`published_at`, '1970-01-01 00:00:00') DESC, `id` DESC
")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $err = $err ?: 'Failed to load posts.';
}

/* ---------- Navbar bits (match dashboard; no images) ---------- */
$adminName   = $_SESSION['admin_name']  ?? 'Purnima Mali';
$adminEmail  = $_SESSION['admin_email'] ?? 'malipurnima2058@gmail.com';
$active      = 'admin_blogs.php'; // highlight Blogs
$initialSrc  = trim($adminName ?: $adminEmail ?: 'A');
$avatarInitial = strtoupper(mb_substr($initialSrc, 0, 1, 'UTF-8')) ?: 'A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin • Blogs</title>
<?php require __DIR__.'/admin_head_snippet.php'; ?>  <!-- boot (applies data-theme & tokens early) -->
<link rel="stylesheet" href="./admin-theme.css?v=1">   <!-- global tokens & component colors -->
<!-- then your page-specific CSS: -->
<link rel="stylesheet" href="./admin_dashboard.css?v=2">  <!-- or admin_services.css, etc. -->
  <link rel="stylesheet" href="./admin_blogs.css?v=2"><!-- reuse cards/buttons -->
  <style>
    .admin-form textarea.big {
      min-height: 200px;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji","Segoe UI Emoji";
      line-height: 1.5;
    }
    .post-meta { margin:.25rem 0 .75rem; color: var(--muted,#8aa0b5); font-size:.95rem; }
    .pill-cat { display:inline-block; font-size:.75rem; padding:.2rem .5rem; border-radius:999px; background:#243447; color:#b8d4ff; margin-left:.35rem }
    /* brand text since we removed the logo image */
    .brand-name { font-weight:600; letter-spacing:.2px; }
  </style>
</head>
<body>

  <!-- ===== Dashboard-style Navbar (no images) ===== -->
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

    <h1>Blogs</h1>

    <!-- Add blog -->
    <article class="card add-card">
      <h3>Add Blog Post</h3>
      <form method="post" class="admin-form">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
        <input type="hidden" name="action" value="add_blog">

        <div class="grid">
          <div>
            <label>Title
              <input type="text" name="title" required>
            </label>
          </div>
          <div>
            <label>Category
              <select name="category" required>
                <option value="Assistant">Assistant</option>
                <option value="Prototyping">Prototyping</option>
                <option value="Analytics">Analytics</option>
                <option value="Security">Security</option>
                <option value="Updates">Updates</option>
              </select>
            </label>
          </div>
          <div>
            <label>Reads
              <input type="number" name="reads" value="0" min="0">
            </label>
          </div>
          <div>
            <label>Published at
              <input type="datetime-local" name="published_at">
            </label>
          </div>
        </div>

        <label>Excerpt
          <input type="text" name="excerpt" maxlength="400" placeholder="Optional short summary">
        </label>

        <label>Content
          <textarea name="content" class="big" placeholder="Write your article (plain text or HTML)"></textarea>
        </label>

        <label style="margin-top:.25rem">
          <input type="checkbox" name="is_active" checked> Published
        </label>

        <button class="btn" type="submit">Add Post</button>
      </form>
    </article>

    <!-- Existing posts -->
    <?php if ($blogs): ?>
      <?php foreach ($blogs as $b): ?>
        <article class="card">
          <div class="admin-toolbar">
            <form method="post">
              <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
              <input type="hidden" name="action" value="toggle_blog">
              <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
              <button class="btn small ghost"><?= $b['is_active'] ? 'Unpublish' : 'Publish' ?></button>
            </form>

            <button class="btn small" data-edit="#edit-<?= (int)$b['id'] ?>">Edit</button>

            <form method="post" onsubmit="return confirm('Delete this blog post?')">
              <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
              <input type="hidden" name="action" value="delete_blog">
              <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
              <button class="btn small warn">Delete</button>
            </form>
          </div>

          <div class="card-head" style="margin-bottom:.25rem">
            <h3 style="margin:0">
              <?= e($b['title']) ?>
              <span class="status-pill <?= $b['is_active'] ? 'on' : 'off' ?>">
                <?= $b['is_active'] ? 'Published' : 'Draft' ?>
              </span>
            </h3>
          </div>

          <p class="post-meta">
            <strong>Category:</strong> <span class="pill-cat"><?= e($b['category']) ?></span>
            &nbsp; • &nbsp; <strong>Reads:</strong> <?= (int)$b['reads'] ?>
            <?php if (!empty($b['published_at'])): ?>
              &nbsp; • &nbsp; <strong>Published:</strong> <?= date('d M Y H:i', strtotime($b['published_at'])) ?>
            <?php else: ?>
              &nbsp; • &nbsp; <em>Not scheduled</em>
            <?php endif; ?>
          </p>

          <?php if (!empty($b['excerpt'])): ?>
            <p class="muted" style="margin:.35rem 0 .75rem"><?= e($b['excerpt']) ?></p>
          <?php endif; ?>

          <?php if (!empty($b['content'])): ?>
            <details>
              <summary>Preview content</summary>
              <div style="margin-top:.5rem; padding:.75rem; background:#0e1724; border:1px solid #243447; border-radius:12px; overflow:auto">
                <pre style="white-space:pre-wrap; margin:0; font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size:.95rem;">
<?= e($b['content']) ?></pre>
              </div>
            </details>
          <?php endif; ?>

          <!-- Inline editor -->
          <details id="edit-<?= (int)$b['id'] ?>" class="edit-details" style="margin-top:1rem">
            <summary>Edit post</summary>
            <form method="post" class="admin-form">
              <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
              <input type="hidden" name="action" value="edit_blog">
              <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">

              <div class="grid">
                <div>
                  <label>Title
                    <input type="text" name="title" value="<?= e($b['title']) ?>" required>
                  </label>
                </div>
                <div>
                  <label>Category
                    <select name="category" required>
                      <?php
                        $cats = ['Assistant','Prototyping','Analytics','Security','Updates'];
                        foreach ($cats as $c) {
                          $sel = ($b['category'] === $c) ? 'selected' : '';
                          echo '<option value="'.e($c).'" '.$sel.'>'.e($c).'</option>';
                        }
                      ?>
                    </select>
                  </label>
                </div>
                <div>
                  <label>Reads
                    <input type="number" name="reads" min="0" value="<?= (int)$b['reads'] ?>">
                  </label>
                </div>
                <div>
                  <label>Published at
                    <input type="datetime-local" name="published_at"
                      value="<?= !empty($b['published_at']) ? date('Y-m-d\TH:i', strtotime($b['published_at'])) : '' ?>">
                  </label>
                </div>
              </div>

              <label>Excerpt
                <input type="text" name="excerpt" maxlength="400" value="<?= e((string)$b['excerpt']) ?>">
              </label>

              <label>Content
                <textarea name="content" class="big"><?= e((string)$b['content']) ?></textarea>
              </label>

              <label style="margin-top:.25rem">
                <input type="checkbox" name="is_active" <?= $b['is_active'] ? 'checked' : '' ?>> Published
              </label>

              <button class="btn">Save changes</button>
            </form>
          </details>
        </article>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="muted">No blog posts yet. Use “Add Blog Post”.</p>
    <?php endif; ?>
  </div>

  <!-- ===== JS: edit shortcut + mobile menu + account dropdown ===== -->
  <script>
    // open <details> editor when clicking small "Edit" buttons
    document.querySelectorAll('[data-edit]').forEach(btn => {
      const sel = btn.getAttribute('data-edit');
      btn.addEventListener('click', () => {
        const d = document.querySelector(sel);
        if (d) { d.open = true; d.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
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

  <script src="./admin_logout.js?v=1"></script>

</body>
</html>
