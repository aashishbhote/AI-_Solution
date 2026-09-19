<?php
declare(strict_types=1);
require __DIR__ . '/db_connection.php'; // $pdo

// null-safe esc
if (!function_exists('e')) {
  function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$blogs = [];
$dbErr = '';
try {
  // Pull published posts. If published_at is NULL, we fall back to created_at.
  $sql = "
    SELECT
      `id`, `title`, `category`, `excerpt`, `content`, `reads`,
      COALESCE(`published_at`, `created_at`) AS `pub_at`
    FROM `blogs`
    WHERE `is_active` = 1
    ORDER BY COALESCE(`published_at`, `created_at`, '1970-01-01 00:00:00') DESC, `id` DESC
  ";
  $blogs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  // Don’t crash the page; show a small removable hint instead.
  $dbErr = $e->getMessage();
}

// helpers
function dt_attr(?string $dt): string {
  if (!$dt) return date('Y-m-d');
  $ts = strtotime($dt); if (!$ts) return date('Y-m-d');
  return date('Y-m-d', $ts);
}
function dt_human(?string $dt): string {
  if (!$dt) return date('d M Y');
  $ts = strtotime($dt); if (!$ts) return date('d M Y');
  return date('d M Y', $ts);
}
function make_excerpt(?string $ex, ?string $content, int $limit=180): string {
  $txt = trim((string)($ex !== null && $ex !== '' ? $ex : strip_tags((string)$content)));
  if ($txt === '') return '';
  if (mb_strlen($txt) <= $limit) return $txt;
  return rtrim(mb_substr($txt, 0, $limit), " \t\n\r\0\x0B.,;:!?") . '…';
}
function word_estimate(?string $content): int {
  $w = str_word_count(strip_tags((string)$content));
  return max(120, $w ?: 600);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Blogs • AI-Solutions</title>

<link rel="stylesheet" href="./style.css?v=2">
  <link rel="stylesheet" href="./blogs.css?v=2">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

  <!-- Optional DEV hint. Remove this block when you’re done debugging. -->
  <?php if ($dbErr): ?>
    <div style="max-width:1100px;margin:10px auto;padding:10px 12px;border-radius:10px;background:#fff7ed;border:1px solid #fcd34d;color:#92400e">
      <strong>DB error:</strong> <?= e($dbErr) ?>
    </div>
  <?php else: ?>
    <!-- Loaded <?= count($blogs) ?> published post(s) -->
  <?php endif; ?>

  <nav>
    <ul><div class="logo"><img src="./ai.jpg" alt="AI-soultions logo"></div></ul>
    <ul>
      <li><a href="./index.php" class="links">Home</a></li>
      <li class="dropdown">
        <a href="./solutions.php" class="links" aria-haspopup="true" aria-expanded="false">Our Solutions</a>
        <ul class="dropdown-menu" role="menu" aria-label="Our Solutions submenu">
          <li><a href="./solutions.php" data-tab="ai" role="menuitem">AI Virtual Assistant</a></li>
          <li><a href="./solutions1.php" data-tab="prototyping" role="menuitem">Rapid Prototyping</a></li>
          <li><a href="./solutions2.php" data-tab="analytics" role="menuitem">Analytics & Insights</a></li>
          <li><a href="./solutions3.php" data-tab="security" role="menuitem">Security by Design</a></li>
        </ul>
      </li>
      <li><a href="./services.php" class="links">Services</a></li>
      <li><a href="./blogs.php" class="links">Blogs</a></li>
      <li><a href="./events.php" class="links">Events</a></li>
      <li><a href="./photos.php" class="links">Photos</a></li>
      <li><a href="./testimonals.php" class="links">Portfolios</a></li>
      <li><a href="./about.php" class="links">About us</a></li>
      <li><a class="contact-btn" href="./contact.php">Contact</a></li>
    </ul>
  </nav>

  <script>
  (function () {
    function fileOf(u) {
      try {
        const url = u.includes('://') ? new URL(u) : new URL(u, location.href);
        let f = url.pathname.split('/').filter(Boolean).pop() || '';
        if (!f) f = 'index.php';
        if (f.toLowerCase() === 'index.html') f = 'index.php';
        return f.toLowerCase();
      } catch { return ''; }
    }
    const current = fileOf(location.href);
    document.querySelectorAll('nav a[href]').forEach(a => {
      const target = fileOf(a.getAttribute('href'));
      if (!target) return;
      if (target === current) {
        a.classList.add('active');
        a.setAttribute('aria-current','page');
        const dd = a.closest('.dropdown');
        if (dd) dd.querySelector(':scope > a.links')?.classList.add('active');
      }
      a.addEventListener('click', () => {
        document.querySelectorAll('nav a.active').forEach(x => x.classList.remove('active'));
        a.classList.add('active');
        const dd = a.closest('.dropdown');
        if (dd) dd.querySelector(':scope > a.links')?.classList.add('active');
      });
    });
  })();
  </script>

  <header class="page-hero">
    <div class="container">
      <h1>Articles & Insights</h1>
      <p class="sub">Deep dives and practical tips linked directly to our solutions.</p>
    </div>
  </header>

  <section class="toolbar">
    <div class="container toolbar-inner">
      <div class="search" role="search">
        <span class="icon"><i class="fa fa-search"></i></span>
        <input id="searchInput" type="search" placeholder="Search articles…" aria-label="Search articles">
      </div>

      <div class="filters">
        <label class="sr-only" for="categorySelect">Category</label>
        <select id="categorySelect" aria-label="Filter by category">
          <option value="all">All topics</option>
          <option value="Assistant">AI Virtual Assistant</option>
          <option value="Prototyping">Rapid Prototyping</option>
          <option value="Analytics">Analytics & Insights</option>
          <option value="Security">Security by Design</option>
          <option value="Updates">Company Updates</option>
        </select>

        <label class="sr-only" for="sortSelect">Sort</label>
        <select id="sortSelect" aria-label="Sort posts">
          <option value="newest">Newest first</option>
          <option value="oldest">Oldest first</option>
          <option value="popular">Most read</option>
        </select>
      </div>
    </div>
  </section>

  <div class="container results-row" aria-live="polite">
    <div id="resultsCount"></div>
    <div id="activeFilter"></div>
  </div>

  <main class="container blog-layout" style="margin-top:.5rem">
    <section aria-label="Blog feed" class="feed-grid" id="feed">
      <!-- Pinned featured -->
      <article class="card card--featured item"
               data-pinned="1"
               data-category="Assistant"
               data-date="2025-08-22"
               data-reads="980"
               data-words="1180">
        <div class="card-body">
          <div class="meta">
            <span class="pill pill--assistant">AI Assistant</span>
            <time datetime="2025-08-22">22 Aug 2025</time> · <span class="readtime"></span>
          </div>
          <h2>Designing a Helpful Assistant: 7 Patterns that Boost Adoption</h2>
          <p class="excerpt">From quick replies to intent fallbacks, here are patterns that make assistants genuinely helpful—mapped to our AI Virtual Assistant solution.</p>
          <p class="related">Related service: <a href="./solutions.php">AI Virtual Assistant</a></p>
          <a class="more" href="./aivirtual.php">Read article</a>
        </div>
      </article>

      <!-- Other static cards -->
      <article class="card item" data-category="Prototyping" data-date="2025-07-30" data-reads="740" data-words="860">
        <div class="card-body">
          <div class="meta">
            <span class="pill pill--proto">Prototyping</span>
            <time datetime="2025-07-30">30 Jul 2025</time> · <span class="readtime"></span>
          </div>
          <h3>Rapid AI Prototyping: Validate in Weeks, Not Months</h3>
          <p class="excerpt">How to scope a safe PoC, choose demo data, and define acceptance criteria—exactly how our Rapid Prototyping solution works.</p>
          <p class="related">Related service: <a href="./solutions1.php">Rapid Prototyping</a></p>
          <a class="more" href="./rapids.php">Read article</a>
        </div>
      </article>

      <article class="card item" data-category="Analytics" data-date="2025-08-12" data-reads="610" data-words="930">
        <div class="card-body">
          <div class="meta">
            <span class="pill pill--analytics">Analytics</span>
            <time datetime="2025-08-12">12 Aug 2025</time> · <span class="readtime"></span>
          </div>
          <h3>Analytics that Matter: Turning Signals into Actions</h3>
          <p class="excerpt">Pick KPIs that drive decisions. We show dashboards and exports used in our Analytics & Insights offering.</p>
          <p class="related">Related service: <a href="./solutions2.php">Analytics & Insights</a></p>
          <a class="more" href="./analytics.php">Read article</a>
        </div>
      </article>

      <article class="card item" data-category="Security" data-date="2025-05-02" data-reads="520" data-words="1020">
        <div class="card-body">
          <div class="meta">
            <span class="pill pill--security">Security</span>
            <time datetime="2025-05-02">2 May 2025</time> · <span class="readtime"></span>
          </div>
          <h3>Security by Design: Practical OWASP Controls for Admins</h3>
          <p class="excerpt">Quick wins: strong hashing, CSRF tokens, security headers, and audit logs—the same practices in our Security by Design solution.</p>
          <p class="related">Related service: <a href="./solutions3.php">Security by Design</a></p>
          <a class="more" href="./security.php">Read article</a>
        </div>
      </article>

      <article class="card item" data-category="Updates" data-date="2025-08-29" data-reads="430" data-words="520">
        <div class="card-body">
          <div class="meta">
            <span class="pill pill--updates">Company</span>
            <time datetime="2025-08-29">29 Aug 2025</time> · <span class="readtime"></span>
          </div>
          <h3>Sunderland Demo Day: What We Showcased</h3>
          <p class="excerpt">A look at our affordable prototyping workflow and assistant demos from the latest in-person session.</p>
          <p class="related">Related service: <a href="./solutions1.php">Rapid Prototyping</a></p>
          <a class="more" href="./updates.php">Read article</a>
        </div>
      </article>

      <!-- DB blogs -->
      <?php if (!empty($blogs)): ?>
        <?php foreach ($blogs as $b):
          $cat = (string)($b['category'] ?? 'Updates');
          $pillClass = [
            'Assistant'   => 'pill--assistant',
            'Prototyping' => 'pill--proto',
            'Analytics'   => 'pill--analytics',
            'Security'    => 'pill--security',
            'Updates'     => 'pill--updates'
          ][$cat] ?? 'pill--updates';

          $words    = word_estimate($b['content'] ?? '');
          $reads    = (int)($b['reads'] ?? 0);
          $dateAttr = dt_attr($b['pub_at'] ?? null);
          $dateHuman= dt_human($b['pub_at'] ?? null);
          $excerpt  = make_excerpt($b['excerpt'] ?? null, $b['content'] ?? null);
        ?>
          <article class="card item"
                   data-category="<?= e($cat) ?>"
                   data-date="<?= e($dateAttr) ?>"
                   data-reads="<?= $reads ?>"
                   data-words="<?= $words ?>">
            <div class="card-body">
              <div class="meta">
                <span class="pill <?= e($pillClass) ?>"><?= e($cat) ?></span>
                <time datetime="<?= e($dateAttr) ?>"><?= e($dateHuman) ?></time> · <span class="readtime"></span>
              </div>
              <h3><?= e($b['title']) ?></h3>
              <?php if ($excerpt !== ''): ?><p class="excerpt"><?= e($excerpt) ?></p><?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <aside class="sidebar" aria-label="Sidebar">
      
      <section class="widget categories">
        <h4>Categories</h4>
        <ul>
          <li><button class="chip" data-cat="Assistant">AI Assistant</button></li>
          <li><button class="chip" data-cat="Prototyping">Prototyping</button></li>
          <li><button class="chip" data-cat="Analytics">Analytics</button></li>
          <li><button class="chip" data-cat="Security">Security</button></li>
          <li><button class="chip" data-cat="Updates">Company</button></li>
        </ul>
      </section>

      <section class="widget newsletter">
        <h4>Get updates</h4>
        <form onsubmit="event.preventDefault(); alert('Thanks! You’re subscribed.');">
          <label class="sr-only" for="email">Email</label>
          <input id="email" type="email" required placeholder="you@company.com" />
          <button class="btn-subscribe" type="submit">Subscribe</button>
        </form>
        <p class="muted tiny">Occasional highlights only. Unsubscribe anytime.</p>
      </section>
    </aside>
  </main>

  <section class="container" style="margin-top:1rem">
    <div id="emptyState" class="empty">No posts match your filters.</div>
  </section>

  <footer>
    <div class="footer-container">
      <div class="footer-column">
        <h3>Company</h3>
        <ul>
          <li><a href="about.php">About Us</a></li>
          <li><a href="contact.php">Contact</a></li>
        </ul>
      </div>
      <div class="footer-column">
        <h3>Our Solutions</h3>
        <ul>
          <li><a href="solutions.php">AI Virtual Assistant</a></li>
          <li><a href="solutions1.php">Rapid Prototyping</a></li>
          <li><a href="solutions2.php">Analytics & Insights</a></li>
          <li><a href="solutions3.php">Security by Design</a></li>
        </ul>
      </div>
      <div class="footer-column">
        <h3>Resources</h3>
        <ul>
          <li><a href="blogs.php">Blogs</a></li>
          <li><a href="events.php">Events</a></li>
          <li><a href="testimonials.php">Testimonials</a></li>
        </ul>
      </div>
      <div class="footer-column">
        <h3>Connect</h3>
        <div class="footer-socials">
          <a href="#"><i class="fab fa-facebook-f"></i></a>
          <a href="#"><i class="fab fa-linkedin-in"></i></a>
          <a href="#"><i class="fab fa-youtube"></i></a>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <p>© <span id="year"></span> <span class="brand">AI-Solutions</span>. All rights reserved.</p>
    </div>
  </footer>

  <script>
    const yearEl = document.getElementById('year'); if (yearEl) yearEl.textContent = new Date().getFullYear();

    (function(){
      const dropdown = document.querySelector('nav .dropdown');
      if (!dropdown) return;
      const trigger = dropdown.querySelector('a.links');
      trigger?.addEventListener('click', (e) => {
        if (!dropdown.classList.contains('open')) e.preventDefault();
        dropdown.classList.toggle('open');
        trigger.setAttribute('aria-expanded', dropdown.classList.contains('open') ? 'true' : 'false');
      });
      document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target)) {
          dropdown.classList.remove('open');
          trigger?.setAttribute('aria-expanded','false');
        }
      });
    })();

    // Reading time
    document.querySelectorAll('.item').forEach(card => {
      const words = Number(card.getAttribute('data-words')||'600');
      const mins = Math.max(1, Math.round(words/200));
      const slot = card.querySelector('.readtime');
      if (slot) slot.textContent = mins + ' min read';
    });

    // Filter/sort with pinned first
    const search = document.getElementById('searchInput');
    const category = document.getElementById('categorySelect');
    const sort = document.getElementById('sortSelect');
    const feed = document.getElementById('feed');
    const emptyState = document.getElementById('emptyState');
    const resultsCount = document.getElementById('resultsCount');
    const activeFilter = document.getElementById('activeFilter');

    function applyFilters(){
      const q   = (search?.value || '').toLowerCase().trim();
      const cat = category?.value || 'all';
      const all = [...feed.querySelectorAll('.item')];

      let visible = 0;
      all.forEach(card => {
        const inCat   = (cat === 'all') || (card.dataset.category === cat);
        const matches = !q || card.textContent.toLowerCase().includes(q);
        const show    = inCat && matches;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
      });

      const pinned = all.filter(el => el.dataset.pinned === '1' && el.style.display !== 'none');
      const normal = all.filter(el => el.dataset.pinned !== '1' && el.style.display !== 'none');

      normal.sort((a,b) => {
        if (sort?.value === 'oldest')  return new Date(a.dataset.date) - new Date(b.dataset.date);
        if (sort?.value === 'popular') return (+b.dataset.reads || 0) - (+a.dataset.reads || 0);
        return new Date(b.dataset.date) - new Date(a.dataset.date);
      });

      [...pinned, ...normal, ...all.filter(el => el.style.display === 'none')].forEach(el => feed.appendChild(el));

      if (resultsCount) resultsCount.textContent = visible + ' post' + (visible === 1 ? '' : 's');
      if (activeFilter) activeFilter.textContent =
        (cat === 'all' ? 'All topics' : ('Category: ' + cat)) + (q ? (' • Search: "' + q + '"') : '');
      if (emptyState)  emptyState.style.display = visible ? 'none' : 'block';
    }

    ['input','change'].forEach(evt=>{
      search?.addEventListener(evt, applyFilters);
      category?.addEventListener(evt, applyFilters);
      sort?.addEventListener(evt, applyFilters);
    });

    document.querySelectorAll('.chip').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        if (category) category.value = btn.dataset.cat || 'all';
        applyFilters();
        window.scrollTo({top: document.querySelector('.toolbar').offsetTop, behavior:'smooth'});
      });
    });

    applyFilters();
  </script>

  <script>
    const pageType = (() => {
      const path = location.pathname.toLowerCase();
      if (path.includes('/contact')) return 'contact';
      if (path === '/' || path === '/index.php') return 'home';
      return 'other';
    })();

    if (window.AIAssistant && typeof AIAssistant.init === 'function') {
      AIAssistant.init({
        mount: '#ai-assistant-root',
        page: pageType,
        brandName: 'AI-Solutions',
        welcomeDelayMs: 5000,
        suppressWelcomeHours: 24,
        faqEndpoint: null,
        primaryActions: [
          { label: 'Services', href: '/services' },
          { label: 'Events',   href: '/events' },
          { label: 'Contact',  href: '/contact' }
        ],
        contactForm: pageType === 'contact' ? {
          formSelector: '#contact-form',
          fields: {
            name:   'input[name="name"]',
            email:  'input[name="email"]',
            phone:  'input[name="phone"]',
            company:'input[name="company"]',
            country:'select[name="country"]',
            title:  'input[name="job_title"]',
            detail: 'textarea[name="job_details"]'
          }
        } : null
      });
    }
  </script>

<!-- ===== Pro AI Chat (drop-in) — paste just before </body> ===== -->
<script>
(function(){
  const ENDPOINT = 'ai/ai-chat-widget.php'; // <-- adjust if your endpoint lives elsewhere

  // ---------- Styles ----------
  const css = `
  :root{
    --ai-brand:#2563eb; --ai-brand-2:#7c3aed;
    --ai-bg:#0b1020; --ai-panel:#0e1426; --ai-ghost:#111831;
    --ai-text:#e6ecff; --ai-muted:#a8b3cf;
    --ai-user:#1f6feb; --ai-bot:#2b3a67;
    --ai-ring: 0 0 0 3px rgba(37,99,235,.25);
    --ai-radius:18px; --ai-shadow:0 14px 48px rgba(0,0,0,.45);
  }
  #aiFab{
    position:fixed; right:20px; bottom:20px; z-index:10000;
    width:56px; height:56px; border-radius:50%;
    border:0; cursor:pointer; display:grid; place-items:center;
    background: linear-gradient(135deg, var(--ai-brand), var(--ai-brand-2));
    color:#fff; box-shadow: var(--ai-shadow);
    transition: transform .15s ease, box-shadow .2s ease, opacity .2s;
  }
  #aiFab:hover{ transform: translateY(-1px); box-shadow: 0 18px 60px rgba(0,0,0,.55);}
  #aiFab svg{ width:26px; height:26px; }
  #aiFab .ai-badge{
    position:absolute; top:-2px; right:-2px; width:12px; height:12px; border-radius:50%;
    background:#22c55e; outline:3px solid #fff; display:none;
  }

  #aiChatPanel{
    position:fixed; right:20px; bottom:88px; z-index:10000;
    width:380px; max-width:calc(100vw - 32px); height:560px; max-height:calc(100vh - 120px);
    background: linear-gradient(180deg, var(--ai-panel), var(--ai-ghost));
    border:1px solid rgba(108,130,179,.25); border-radius: var(--ai-radius);
    box-shadow: var(--ai-shadow); display:none; overflow:hidden;
    transform-origin: bottom right; animation: ai-pop .18s ease;
  }
  @keyframes ai-pop { from { transform:scale(.98); opacity:.0 } to { transform:scale(1); opacity:1 } }

  .ai-head{
    display:flex; align-items:center; justify-content:space-between;
    padding:10px 12px; background: linear-gradient(135deg, rgba(37,99,235,.28), rgba(124,58,237,.22));
    border-bottom:1px solid rgba(108,130,179,.2);
  }
  .ai-title{ display:flex; align-items:center; gap:8px; color:#fff; font:600 14.5px/1.2 system-ui,Segoe UI,Roboto,Arial}
  .ai-title .ai-dot{ width:9px; height:9px; border-radius:50%; background:#22c55e; box-shadow:0 0 0 3px rgba(34,197,94,.2)}
  .ai-actions button{
    display:inline-grid; place-items:center; width:34px; height:34px; border-radius:10px;
    border:0; background:transparent; color:#dbe4ff; cursor:pointer;
  }
  .ai-actions button:hover{ background:rgba(255,255,255,.06) }
  .ai-actions svg{ width:18px; height:18px }

  .ai-body{ display:flex; flex-direction:column; height:calc(100% - 54px); }
  .ai-messages{
    flex:1; overflow:auto; padding:14px 14px 8px; scrollbar-width:thin; color:var(--ai-text);
  }
  .ai-row{ display:flex; margin:8px 0; gap:8px; }
  .ai-row.user{ justify-content:flex-end }
  .ai-bubble{
    max-width:78%; padding:10px 12px; border-radius:14px;
    background:#182341; color:#e9edff; font:14px/1.4 system-ui,Segoe UI,Roboto,Arial;
    box-shadow: 0 6px 22px rgba(0,0,0,.25); white-space:pre-wrap; word-wrap:break-word;
  }
  .ai-row.user .ai-bubble{
    background: linear-gradient(135deg, #1f6feb, #2563eb);
  }
  .ai-row.bot .ai-bubble{
    background: linear-gradient(135deg, #243253, #2b3a67);
  }
  .ai-time{ display:block; font-size:11px; color:var(--ai-muted); margin-top:4px; }

  .ai-typing{ display:inline-flex; gap:4px; align-items:center }
  .ai-typing i{ width:6px; height:6px; border-radius:50%; background:#c7d2fe; opacity:.45; animation: ai-dots 1.2s infinite }
  .ai-typing i:nth-child(2){ animation-delay:.15s } .ai-typing i:nth-child(3){ animation-delay:.3s }
  @keyframes ai-dots{ 0%,80%,100%{ transform:translateY(0)} 40%{ transform:translateY(-3px)} }

  .ai-input{
    display:flex; gap:8px; padding:10px; border-top:1px solid rgba(108,130,179,.18); background:rgba(12,18,34,.6);
  }
  .ai-input input{
    flex:1; padding:11px 12px; border-radius:12px; border:1px solid rgba(108,130,179,.25);
    background:#0b1120; color:#e8eeff; font:14px system-ui,Segoe UI,Roboto,Arial; outline:none;
  }
  .ai-input input:focus{ box-shadow: var(--ai-ring) }
  .ai-input button{
    padding:0 14px; border-radius:12px; border:0; cursor:pointer; color:#fff;
    background: linear-gradient(135deg, var(--ai-brand), var(--ai-brand-2));
    display:inline-grid; place-items:center; min-width:44px;
  }
  .ai-input button[disabled]{ opacity:.65; cursor:not-allowed }

  /* Mobile tweaks */
  @media (max-width: 480px){
    #aiChatPanel{ right:12px; bottom:84px; width:calc(100vw - 24px); height:60vh }
    #aiFab{ right:12px; bottom:12px }
  }`;

  const style = document.createElement('style');
  style.id = 'ai-chat-embed-styles';
  style.textContent = css;
  document.head.appendChild(style);

  // ---------- DOM ----------
  const fab = document.createElement('button');
  fab.id = 'aiFab';
  fab.setAttribute('aria-label','Open chat');
  fab.innerHTML = `
    <span class="ai-badge" aria-hidden="true"></span>
    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path d="M4 6.5C4 5.12 5.12 4 6.5 4h11A2.5 2.5 0 0 1 20 6.5v7A2.5 2.5 0 0 1 17.5 16H12l-3.8 3c-.7.55-1.7.03-1.7-.83V16H6.5A2.5 2.5 0 0 1 4 13.5v-7Z" fill="currentColor"/>
    </svg>`;
  document.body.appendChild(fab);

  const panel = document.createElement('div');
  panel.id = 'aiChatPanel';
  panel.innerHTML = `
    <div class="ai-head">
      <div class="ai-title"><span class="ai-dot" aria-hidden="true"></span> AI Assistant</div>
      <div class="ai-actions">
        <button id="aiMinBtn" title="Minimize" aria-label="Minimize">
          <svg viewBox="0 0 24 24"><path fill="currentColor" d="M5 12h14v2H5z"/></svg>
        </button>
        <button id="aiCloseBtn" title="Close" aria-label="Close">
          <svg viewBox="0 0 24 24"><path fill="currentColor" d="M18.3 5.71 12 12l6.3 6.29-1.42 1.42L10.59 13.41 4.29 19.71 2.87 18.29 9.17 12 2.87 5.71 4.29 4.29 10.59 10.59 16.88 4.29z"/></svg>
        </button>
      </div>
    </div>
    <div class="ai-body">
      <div class="ai-messages" id="aiMsgs" role="log" aria-live="polite" aria-label="Chat messages"></div>
      <form class="ai-input" id="aiForm">
        <input id="aiInput" type="text" placeholder="Type a message…" autocomplete="off" />
        <button id="aiSend" type="submit" title="Send">
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
        </button>
      </form>
    </div>`;
  document.body.appendChild(panel);

  // ---------- Logic ----------
  const msgsEl = panel.querySelector('#aiMsgs');
  const form   = panel.querySelector('#aiForm');
  const input  = panel.querySelector('#aiInput');
  const sendBtn= panel.querySelector('#aiSend');
  const minBtn = panel.querySelector('#aiMinBtn');
  const closeBtn = panel.querySelector('#aiCloseBtn');
  const badge  = fab.querySelector('.ai-badge');

  let open = false;
  const history = []; // [{role:'user'|'assistant', content:'...'}]

  function nowTime(){
    const d = new Date();
    return d.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
  }

  function addMsg(role, text, isTyping=false){
    const row = document.createElement('div');
    row.className = `ai-row ${role}`;
    const bubble = document.createElement('div');
    bubble.className = 'ai-bubble';
    if (isTyping){
      bubble.innerHTML = '<span class="ai-typing" aria-label="Assistant is typing"><i></i><i></i><i></i></span>';
    } else {
      bubble.textContent = text;
    }
    const time = document.createElement('span');
    time.className = 'ai-time';
    time.textContent = nowTime();
    bubble.appendChild(time);
    row.appendChild(bubble);
    msgsEl.appendChild(row);
    msgsEl.scrollTop = msgsEl.scrollHeight;
    return {row, bubble};
  }

  function openPanel(){
    panel.style.display = 'block';
    open = true; badge.style.display = 'none';
    setTimeout(()=>input.focus(), 0);
  }
  function closePanel(){
    panel.style.display = 'none';
    open = false;
  }

  fab.addEventListener('click', openPanel);
  minBtn.addEventListener('click', closePanel);
  closeBtn.addEventListener('click', closePanel);
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape' && open) closePanel(); });

  // Greeting message (once)
  addMsg('bot', 'Hi! How can I help you today?');

  form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const text = input.value.trim();
    if(!text) return;
    input.value = ''; input.focus();
    history.push({role:'user', content:text});
    addMsg('user', text);

    // show typing
    sendBtn.disabled = true;
    const typing = addMsg('bot', '', true);

    try{
      const res = await fetch(ENDPOINT, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({messages: history})
      });
      const data = await res.json();
      // replace typing with reply
      typing.row.remove();
      const reply = data.reply || data.error || 'Sorry, something went wrong.';
      history.push({role:'assistant', content: reply});
      addMsg('bot', reply);
      if(!open){ badge.style.display = 'block'; }
    }catch(err){
      typing.row.remove();
      addMsg('bot','Network error. Please try again.');
      if(!open){ badge.style.display = 'block'; }
    }finally{
      sendBtn.disabled = false;
    }
  });
})();
</script>

   
</body>
</html>
