<?php
declare(strict_types=1);

// optional admin auth
// require __DIR__ . '/admin_auth.php';

require_once __DIR__ . '/db_connection.php'; // may define $pdo (PDO) or $conn (mysqli)

/* ----------------- DB ADAPTER: detect PDO or mysqli ----------------- */
$isPDO    = (isset($pdo) && $pdo instanceof PDO);
$isMySQLi = (isset($conn) && $conn instanceof mysqli);

if (!$isPDO && !$isMySQLi) {
  die("Database connection not found. Ensure db_connection.php sets \$pdo (PDO) or \$conn (mysqli).");
}

/* ----------------- helpers ----------------- */
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/** Add is_read column if missing */
function ensureIsReadColumn($db, bool $isPDO, string $table='contact_submissions'): void {
  if ($isPDO) {
    $st = $db->query("SHOW COLUMNS FROM `$table` LIKE 'is_read'");
    $has = (bool)$st->fetch(PDO::FETCH_ASSOC);
    if (!$has) $db->exec("ALTER TABLE `$table` ADD COLUMN `is_read` TINYINT(1) NOT NULL DEFAULT 0");
  } else {
    $res = $db->query("SHOW COLUMNS FROM `$table` LIKE 'is_read'");
    $has = $res && $res->num_rows > 0;
    $res && $res->free();
    if (!$has) { $db->query("ALTER TABLE `$table` ADD COLUMN `is_read` TINYINT(1) NOT NULL DEFAULT 0"); }
  }
}

/** Does table have created_at? */
function hasCreatedAt($db, bool $isPDO, string $table='contact_submissions'): bool {
  if ($isPDO) {
    $st = $db->query("SHOW COLUMNS FROM `$table` LIKE 'created_at'");
    return (bool)$st->fetch(PDO::FETCH_ASSOC);
  } else {
    $res = $db->query("SHOW COLUMNS FROM `$table` LIKE 'created_at'");
    $ok = $res && $res->num_rows > 0;
    $res && $res->free();
    return $ok;
  }
}

$table = 'contact_submissions';
ensureIsReadColumn($isPDO ? $pdo : $conn, $isPDO, $table);
$HAS_CREATED = hasCreatedAt($isPDO ? $pdo : $conn, $isPDO, $table);
$ORDER_FIELD = $HAS_CREATED ? 'created_at' : 'id';

/* ----------------- POST actions ----------------- */
$err = ''; $ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $id = (int)($_POST['id'] ?? 0);
  try {
    if ($action === 'toggle_read' && $id > 0) {
      $newVal = (int)($_POST['to'] ?? 0) ? 1 : 0;
      if ($isPDO) {
        $st = $pdo->prepare("UPDATE `$table` SET is_read=? WHERE id=?");
        $st->execute([$newVal, $id]);
      } else {
        $st = $conn->prepare("UPDATE `$table` SET is_read=? WHERE id=?");
        $st->bind_param('ii', $newVal, $id);
        $st->execute();
        $st->close();
      }
      $ok = $newVal ? 'Marked as read.' : 'Marked as unread.';
    }
    elseif ($action === 'delete' && $id > 0) {
      if ($isPDO) {
        $st = $pdo->prepare("DELETE FROM `$table` WHERE id=?");
        $st->execute([$id]);
      } else {
        $st = $conn->prepare("DELETE FROM `$table` WHERE id=?");
        $st->bind_param('i', $id);
        $st->execute();
        $st->close();
      }
      $ok = 'Deleted.';
    }
  } catch (Throwable $ex) {
    $err = $ex->getMessage();
  }
}

/* ----------------- GET filters ----------------- */
$status = $_GET['status'] ?? '';  // '', 'read', 'unread'
$q      = trim($_GET['q'] ?? '');

$where = [];
$params = [];
if ($status === 'read')   $where[] = "is_read = 1";
if ($status === 'unread') $where[] = "is_read = 0";

if ($q !== '') {
  $where[] = "(name LIKE :q OR company LIKE :q OR email LIKE :q OR phone LIKE :q OR message LIKE :q)";
}

$sqlBase = "FROM `$table`";
if ($where) $sqlBase .= " WHERE " . implode(" AND ", $where);

/* ----------------- count ----------------- */
$total = 0;
if ($isPDO) {
  $sql = "SELECT COUNT(*) $sqlBase";
  $st = $pdo->prepare($sql);
  if ($q !== '') $st->bindValue(':q', "%$q%", PDO::PARAM_STR);
  $st->execute();
  $total = (int)$st->fetchColumn();
} else {
  // mysqli: rebuild with ? placeholders repeated
  $types=''; $vals=[];
  $sql = "SELECT COUNT(*) $sqlBase";
  if ($q !== '') {
    // replace :q with ?
    $sql = str_replace(":q", "?", $sql);
    // five occurrences in our WHERE clause—BUT we used only one :q above (PDO style).
    // For mysqli path, we’ll rebuild WHERE differently:
    $where2 = [];
    if ($status === 'read')   $where2[] = "is_read = 1";
    if ($status === 'unread') $where2[] = "is_read = 0";
    if ($q !== '') {
      $where2[] = "(name LIKE ? OR company LIKE ? OR email LIKE ? OR phone LIKE ? OR message LIKE ?)";
      for ($i=0; $i<5; $i++) { $types.='s'; $vals[]="%$q%"; }
    }
    $sqlBase = "FROM `$table`" . ($where2 ? " WHERE ".implode(' AND ',$where2) : '');
    $sql = "SELECT COUNT(*) $sqlBase";
  }
  $st = $conn->prepare($sql);
  if ($types) $st->bind_param($types, ...$vals);
  $st->execute();
  $st->bind_result($total);
  $st->fetch();
  $st->close();
}

/* ----------------- pagination ----------------- */
$per   = 12;
$page  = max(1, (int)($_GET['page'] ?? 1));
$off   = ($page - 1) * $per;
$pages = max(1, (int)ceil(($total ?: 0)/$per));

/* ----------------- list ----------------- */
$rows = [];
if ($isPDO) {
  $sql = "SELECT id, name, company, email, phone, message, consent, is_read"
       . ($HAS_CREATED ? ", created_at" : "")
       . " $sqlBase ORDER BY `$ORDER_FIELD` DESC, id DESC LIMIT :off, :per";
  $st = $pdo->prepare($sql);
  if ($q !== '') $st->bindValue(':q', "%$q%", PDO::PARAM_STR);
  $st->bindValue(':off', (int)$off, PDO::PARAM_INT);
  $st->bindValue(':per', (int)$per, PDO::PARAM_INT);
  $st->execute();
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
} else {
  // rebuild where with ?s for mysqli
  $where2 = [];
  $types=''; $vals=[];
  if ($status === 'read')   $where2[] = "is_read = 1";
  if ($status === 'unread') $where2[] = "is_read = 0";
  if ($q !== '') {
    $where2[] = "(name LIKE ? OR company LIKE ? OR email LIKE ? OR phone LIKE ? OR message LIKE ?)";
    for ($i=0; $i<5; $i++) { $types.='s'; $vals[]="%$q%"; }
  }
  $sqlBase = "FROM `$table`" . ($where2 ? " WHERE ".implode(' AND ',$where2) : '');
  $sql = "SELECT id,name,company,email,phone,message,consent,is_read"
       . ($HAS_CREATED ? ",created_at" : "")
       . " $sqlBase ORDER BY `$ORDER_FIELD` DESC, id DESC LIMIT ?, ?";
  $types .= 'ii'; $vals[] = $off; $vals[] = $per;

  $st = $conn->prepare($sql);
  $st->bind_param($types, ...$vals);
  $st->execute();
  $res = $st->get_result();
  $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $res && $res->free();
  $st->close();
}

/* ----------------- quick counts ----------------- */
$cnt_unread = 0; $cnt_read = 0;
if ($isPDO) {
  $qc = $pdo->query("SELECT is_read, COUNT(*) c FROM `$table` GROUP BY is_read");
  foreach ($qc as $r) {
    if ((int)$r['is_read'] === 1) $cnt_read = (int)$r['c']; else $cnt_unread = (int)$r['c'];
  }
} else {
  $rc = $conn->query("SELECT is_read, COUNT(*) c FROM `$table` GROUP BY is_read");
  if ($rc) {
    while ($r = $rc->fetch_assoc()) {
      if ((int)$r['is_read'] === 1) $cnt_read = (int)$r['c']; else $cnt_unread = (int)$r['c'];
    }
    $rc->free();
  }
}
$cnt_all = $cnt_read + $cnt_unread;

/* ----------------- navbar bits ----------------- */
$active = basename($_SERVER['PHP_SELF']);
$adminName  = $_SESSION['admin_name']  ?? 'Purnima Mali';
$adminEmail = $_SESSION['admin_email'] ?? 'malipurnima2058@gmail.com';
$avatarInitial = strtoupper(substr($adminName ?: $adminEmail, 0, 1));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin • Contact Enquiries</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
<?php require __DIR__.'/admin_head_snippet.php'; ?>  <!-- boot (applies data-theme & tokens early) -->
<link rel="stylesheet" href="./admin-theme.css?v=1">   <!-- global tokens & component colors -->
<!-- then your page-specific CSS: -->
<link rel="stylesheet" href="./admin_dashboard.css?v=2">  <!-- or admin_services.css, etc. -->
  <link rel="stylesheet" href="./admin_contact.css">
</head>
<body>
  <nav class="admin-nav" id="adminNav">
    <a href="admin_dashboard.php" class="brand-link">
      <img src="./ai.jpg" alt="AI-Solutions logo" class="brand-logo">
      <span class="brand-name">AI-Solutions Admin</span>
    </a>
    <button class="toggle" type="button" aria-label="Toggle menu" aria-expanded="false">☰</button>

    <div class="nav-group group--admin">
      <div class="group-label">Management</div>
      <ul class="links" id="adminNavLinks">
        <li><a href="admin_dashboard.php" class="<?= $active==='admin_dashboard.php'?'active':'' ?>">Dashboard</a></li>
        <li><a href="admin_services.php"  class="<?= $active==='admin_services.php'?'active':'' ?>">Services</a></li>
        <li><a href="admin_blogs.php"     class="<?= $active==='admin_blogs.php'?'active':'' ?>">Blogs</a></li>
        <li><a href="admin_events.php"    class="<?= $active==='admin_events.php'?'active':'' ?>">Events</a></li>
        <li><a href="admin_photos.php"    class="<?= $active==='admin_photos.php'?'active':'' ?>">Photos</a></li>
        <li><a href="admin_portfolios.php"    class="<?= $active==='admin_portfolios.php'?'active':'' ?>">Portfolios</a></li>

        <li><a href="admin_contact.php"   class="<?= $active==='admin_contact.php'?'active':'' ?>">Contact</a></li>
      </ul>
    </div>

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

    <div class="account">
      <button class="avatar-btn" id="accountBtn" type="button" aria-haspopup="menu" aria-expanded="false">
        <span class="avatar"><?= e($avatarInitial) ?></span>
      </button>
      <div class="account-menu" id="accountMenu" role="menu">
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
    <h1 style="margin-top:0">Contact Enquiries</h1>

    <div class="stats">
      <div class="stat">All: <strong><?= $cnt_all ?></strong></div>
      <div class="stat">Unread: <strong><?= $cnt_unread ?></strong></div>
      <div class="stat">Read: <strong><?= $cnt_read ?></strong></div>
    </div>

    <form class="toolbar" method="get">
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, company, email, phone, message…">
      <select name="status">
        <option value="">All</option>
        <option value="unread" <?= $status==='unread'?'selected':'' ?>>Unread</option>
        <option value="read"   <?= $status==='read'?'selected':'' ?>>Read</option>
      </select>
      <button class="btn">Apply</button>
    </form>

    <?php if ($err): ?><div class="card error">❌ <?= e($err) ?></div><?php endif; ?>
    <?php if ($ok):  ?><div class="card success">✅ <?= e($ok) ?></div><?php endif; ?>

    <?php if (!$rows): ?>
      <p class="muted">No enquiries found.</p>
    <?php else: ?>
      <?php foreach ($rows as $r): ?>
        <article class="card" style="margin-bottom:.75rem">
          <div class="row-top">
            <div>
              <strong><?= e($r['name']) ?></strong>
              <?php if ((int)$r['is_read'] === 0): ?>
                <span class="pill pill-unread">Unread</span>
              <?php else: ?>
                <span class="pill pill-read">Read</span>
              <?php endif; ?>
            </div>
            <form method="post" class="actions">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <?php if ((int)$r['is_read'] === 0): ?>
                <input type="hidden" name="to" value="1">
                <button class="btn small ghost" name="action" value="toggle_read">Mark as read</button>
              <?php else: ?>
                <input type="hidden" name="to" value="0">
                <button class="btn small ghost" name="action" value="toggle_read">Mark as unread</button>
              <?php endif; ?>
              <button class="btn small warn" name="action" value="delete" onclick="return confirm('Delete this submission?')">Delete</button>
            </form>
          </div>

          <p class="meta" style="margin:.25rem 0 .5rem">
            <?= e($r['company']) ?> •
            <a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a> •
            <?= e($r['phone']) ?>
            <?php if ($HAS_CREATED && !empty($r['created_at'])): ?>
              • <?= date('d M Y H:i', strtotime($r['created_at'])) ?>
            <?php endif; ?>
          </p>

          <details>
            <summary><?= (int)$r['is_read']===0 ? 'Read message' : 'View message' ?></summary>
            <div class="message" style="margin-top:.5rem"><?= nl2br(e($r['message'])) ?></div>
          </details>
        </article>
      <?php endforeach; ?>

      <div class="pagination">
        <?php for ($p=1; $p<=$pages; $p++): ?>
          <?php if ($p===$page): ?>
            <span class="current"><?= $p ?></span>
          <?php else: ?>
            <a href="?<?= http_build_query(['q'=>$q,'status'=>$status,'page'=>$p]) ?>"><?= $p ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>

  <script>
    (function(){
      const sidebar = document.getElementById('adminNav');
      const localToggle = sidebar?.querySelector('.toggle');
      function toggleSidebar(){ const open = sidebar.classList.toggle('open'); localToggle?.setAttribute('aria-expanded', open ? 'true' : 'false'); }
      localToggle?.addEventListener('click', toggleSidebar);

      const btn = document.getElementById('accountBtn');
      const menu = document.getElementById('accountMenu');
      function closeMenu(){ if(!menu||!btn) return; menu.classList.remove('open'); btn.setAttribute('aria-expanded','false'); }
      btn?.addEventListener('click', (e)=>{ e.stopPropagation(); const willOpen=!menu.classList.contains('open'); document.querySelectorAll('.account-menu.open').forEach(m=>m.classList.remove('open')); if(willOpen){menu.classList.add('open'); btn.setAttribute('aria-expanded','true');} else {closeMenu();}});
      document.addEventListener('click', (e)=>{ if(menu&&btn&&!menu.contains(e.target)&&e.target!==btn) closeMenu(); });
      document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closeMenu(); });
    })();
  </script>

  <script src="./admin_logout.js?v=1"></script>

</body>
</html>
