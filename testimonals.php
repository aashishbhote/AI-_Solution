<?php
// testimonals.php — customer page showing static + DB portfolios (with features)
require_once __DIR__ . '/db_connection.php'; // must give $pdo (PDO) or $conn (mysqli)

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

/* ---------- helpers ---------- */
function column_exists($table, $column): bool {
  global $pdo, $conn;
  try {
    if (isset($pdo) && $pdo instanceof PDO) {
      $st = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
      $st->execute([$column]);
      return (bool)$st->fetch(PDO::FETCH_ASSOC);
    } elseif (isset($conn) && $conn instanceof mysqli) {
      $tbl = $conn->real_escape_string($table);
      $col = $conn->real_escape_string($column);
      $res = $conn->query("SHOW COLUMNS FROM `$tbl` LIKE '$col'");
      $ok  = $res && $res->num_rows > 0;
      $res && $res->free();
      return $ok;
    }
  } catch (Throwable $e) {}
  return false;
}

/* ---------- fetch portfolios ---------- */
$portfolios = [];
$featuresByPid = [];
$tableP  = 'portfolios';
$tablePF = 'portfolio_features';
$hasShow = column_exists($tableP, 'show_on_site');

try {
  if (isset($pdo) && $pdo instanceof PDO) {
    $sql = "
      SELECT id, title, summary
      FROM $tableP
      WHERE is_active = 1" . ($hasShow ? " AND show_on_site = 1" : "") . "
      ORDER BY sort_order ASC, id DESC
    ";
    $portfolios = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    if ($portfolios) {
      $ids = array_column($portfolios, 'id');
      $in  = implode(',', array_fill(0, count($ids), '?'));
      $stF = $pdo->prepare("
        SELECT portfolio_id, feature_text
        FROM $tablePF
        WHERE portfolio_id IN ($in)
        ORDER BY sort_order ASC, id ASC
      ");
      $stF->execute($ids);
      while ($r = $stF->fetch(PDO::FETCH_ASSOC)) {
        $pid = (int)$r['portfolio_id'];
        $featuresByPid[$pid][] = $r['feature_text'];
      }
    }
  } elseif (isset($conn) && $conn instanceof mysqli) {
    $sql = "
      SELECT id, title, summary
      FROM $tableP
      WHERE is_active = 1" . ($hasShow ? " AND show_on_site = 1" : "") . "
      ORDER BY sort_order ASC, id DESC
    ";
    if ($res = $conn->query($sql)) {
      while ($row = $res->fetch_assoc()) $portfolios[] = $row;
      $res->free();
    }

    if ($portfolios) {
      $ids = array_map('intval', array_column($portfolios, 'id'));
      $in  = implode(',', $ids);
      $sqlF = "
        SELECT portfolio_id, feature_text
        FROM $tablePF
        WHERE portfolio_id IN ($in)
        ORDER BY sort_order ASC, id ASC
      ";
      if ($resF = $conn->query($sqlF)) {
        while ($r = $resF->fetch_assoc()) {
          $pid = (int)$r['portfolio_id'];
          $featuresByPid[$pid][] = $r['feature_text'];
        }
        $resF->free();
      }
    }
  }
} catch (Throwable $e) {
  // optional: error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Testimonials</title>
<link rel="stylesheet" href="./style.css?v=2">
  <link rel="stylesheet" href="./testimonals.css?v=3">
</head>
<body>

<!-- ===== NAVIGATION ===== -->
<nav>
  <ul>
    <div class="logo">
      <img src="./ai.jpg" alt="AI-Solutions logo">
    </div>
  </ul>
  <ul>
    <li><a href="./index.php" class="links">Home</a></li>
    <li class="dropdown">
      <a href="./solutions.php" class="links" aria-haspopup="true" aria-expanded="false">Our Solutions</a>
      <ul class="dropdown-menu">
        <li><a href="./solutions.php" data-tab="ai">AI Virtual Assistant</a></li>
        <li><a href="./solutions1.php" data-tab="prototyping">Rapid Prototyping</a></li>
        <li><a href="./solutions2.php" data-tab="analytics">Analytics & Insights</a></li>
        <li><a href="./solutions3.php" data-tab="security">Security by Design</a></li>
      </ul>
    </li>
    <li><a href="./services.php" class="links">Services</a></li>
    <li><a href="./blogs.php" class="links">Blogs</a></li>
    <li><a href="events.php" class="links">Events</a></li>
    <li><a href="photos.php" class="links">Photos</a></li>
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

<div class="img_container">
  <img src="./rates.png" alt="AI-Solutions banner">
  <section class="part1">
    <p class="p1_one"><strong>AI Solution's Key to success with services</strong></p>
    <p class="p2_two">We harness AI to create smarter workplaces,<br> accelerate innovation, and empower employees.</p>
  </section>
</div>

<!-- ===== PAST PORTFOLIOS ===== -->


  <!-- ===== Portfolios ===== -->
  <section class="container section-pad">
    <div class="section-head">
      <h2>Past Portfolios</h2>
      <p class="muted">AI-powered solutions we’ve delivered across industries.</p>
    </div>

    <div class="grid portfolios">
      <!-- Static examples -->
      <article class="card portfolio-card">
        <h3>DocuMind</h3>
        <p>DocuMind is an AI-driven enterprise documentation assistant that
revolutionizes how organizations create, manage, and search knowledge. It
ensures employees spend less time hunting for information and more time
acting on it.
</p>
        <h4>Key Features</h4>
        <ul class="check-list">
          <li>Uses AI to auto-summarize and categorize large documents into concise
knowledge snippets</li>
          <li>Natural language query search (ask questions, get instant answers from
company docs).</li>
          <li>Real-time document translation into multiple languages.</li>
          <li>AI-powered compliance scanner to detect policy, legal, or security risks in
text.</li>
        </ul>
      </article>

      <article class="card portfolio-card">
        <h3>Log AI</h3>
        <p>An innovative inventory management solution powered by artificial intelligence,
designed to streamline and optimize inventory operations for businesses of all
sizes. With intelligent automation, real-time analytics, and predictive
capabilities, Log AI transforms how organizations manage stock, supply chains,
and operational logistics.</p>
        <h4>Key Features</h4>
        <ul class="check-list">
          <li>Automatically adjusts inventory levels based on demand forecasting and
sales trends.</li>
          <li>Tracks inventory across warehouses, stores, and delivery networks with
precision.
</li>
          <li>Seamlessly integrates with existing ERP, POS, and supply chain systems.</li>
          <li>Leverages AI to forecast future demand patterns and seasonal trends.</li>
        </ul>
      </article>

      <article class="card portfolio-card">
        <h3>FinSight</h3>
        <p>An AI-powered financial insights platform designed to help organizations track
expenses, forecast budgets, and optimize financial planning.
.</p>
        <h4>Key Features</h4>
        <ul class="check-list">
          <li>AI-based anomaly detection for unusual spending or fraud risks</li>
          <li>Predictive modeling for cash flow and revenue streams.</li>
          <li>Auto-categorization of expenses from receipts and invoices.</li>
          <li>Personalized budgeting recommendations for departments.</li>
          <li>Smart alerts for overspending, budget risks, or duplicate transactions.</li>

        </ul>
      </article>

      <article class="card portfolio-card">
        <h3>LaundriQ</h3>
        <p>LaundriQ is a cutting-edge AI-powered solution designed to redefine laundry
care by delivering unparalleled convenience, precision, and fabric protection.
Developed in collaboration with Samsung washing machines, LaundriQ
leverages advanced artificial intelligence to analyze fabric quality, optimize
detergent usage, and select the ideal washing mode for every load.</p>
        <h4>Key Features</h4>
        <ul class="check-list">
          <li>Uses AI to assess fabric type, condition, and quality in real-time</li>
          <li>Ensures gentle yet effective care tailored to each garment.</li>
          <li>Precisely calculates the required detergent amount based on load size,fabric sensitivity, and soil level.</li>
          <li>Minimizes water and energy usage through smart load assessment andcycle adjustments.</li>
          <li>Connects with Samsung SmartThings for remote control and monitoring.</li>

        </ul>
      </article>

      <article class="card portfolio-card">
        <h3>SafeOps</h3>
        <p>SafeOps is an AI-powered workplace safety and compliance monitoring solutionfor factories, warehouses, and offices.</p>
        <h4>Key Features</h4>
        <ul class="check-list">
          <li>Real-time camera monitoring with AI detecting unsafe behaviors (nohelmets, spills, hazards).</li>
          <li>Predictive risk analytics based on historical incidents.</li>
          <li>Automated compliance reporting for audits and regulators</li>
          <li>Personalized training suggestions for employees based on observed safetyhabits.</li>
          <li>Integration with IoT sensors for fire, gas leak, or equipment malfunctionalerts.</li>

        </ul>
      </article>

      <!-- Optional DB-driven cards -->
      <?php if ($portfolios): ?>
        <?php foreach ($portfolios as $p): ?>
          <article class="card portfolio-card dynamic">
            <h3><?= e($p['title']) ?></h3>
            <?php if (!empty($p['summary'])): ?>
              <p><?= nl2br(e($p['summary'])) ?></p>
            <?php endif; ?>
            <?php
              $pid = (int)$p['id'];
              $fl  = $featuresByPid[$pid] ?? [];
              if ($fl):
            ?>
              <h4>Key Features</h4>
              <ul class="check-list">
                <?php foreach ($fl as $ft): ?>
                  <li><?= e($ft) ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- ===== Customer Feedback (with photos) ===== -->
  <section class="container section-pad customer-feedback">
    <div class="section-head">
      <h2>What Our Customers Say</h2>
      <p class="muted">Verified feedback from real teams using our solutions.</p>
    </div>

    <div class="grid feedback-grid">
      <article class="card feedback-card">
        <div class="feedback-head">
          <img src="./samantha.jpg" alt="Samantha Lee" onerror="this.src='images/customers/_placeholder.jpg'">
          <div>
            <h3>Samantha Lee</h3>
            <p class="role">Operations Manager · Orion Tech</p>
            <div class="rating" aria-label="rating 4.7 out of 5">★ ★ ★ ★ ★ <span>4.7/5</span></div>
          </div>
        </div>
        <blockquote>DocuMind has completely changed how our teams handle
        documentation. Before, we wasted hours searching for information buried
        in long PDFs or emails. Now, with AI-driven summaries and instant
        answers, we save at least 30% of our time weekly. The compliance
        scanner also caught risks we would have easily missed. It feels like
        having a super-smart assistant that never forgets.</blockquote>
      </article>

      <article class="card feedback-card">
        <div class="feedback-head">
          <img src="./rajesh.jpg" alt="Rajesh Patel" onerror="this.src='images/customers/_placeholder.jpg'">
          <div>
            <h3>Rajesh Patel</h3>
            <p class="role">CFO · GreenWave Retail & Co.</p>
            <div class="rating" aria-label="rating 4.9 out of 5">★ ★ ★ ★ ★ <span>4.9/5</span></div>
          </div>
        </div>
        <blockquote>FinSight has given us unprecedented visibility into our company’s
finances. The predictive budgeting tool helped us forecast a seasonal dip
months before it hit, saving us from overspending. The AI-driven fraud
detection caught duplicate invoices that even our accountants overlooked.
It’s like having a CFO powered by AI working 24/7.</blockquote>
      </article>

      <article class="card feedback-card">
        <div class="feedback-head">
          <img src="./michael.png" alt="Michael Reed" onerror="this.src='images/customers/_placeholder.jpg'">
          <div>
            <h3>Michael Reed</h3>
            <p class="role">Supply Chain Manager · Vaux Brewery</p>
            <div class="rating" aria-label="rating 4.4 out of 5">★ ★ ★ ★ <span>4.4/5</span></div>
          </div>
        </div>
        <blockquote>Log AI has revolutionized our inventory management system. The
AI-driven optimization has helped us maintain perfect stock levels,
reducing both overstock and stockouts. The real-time tracking and
predictive analytics have made our supply chain more efficient, and we’re
now able to make data-driven decisions that save both time and money.
The seamless integration with our existing systems was a huge plus. Log
AI is a must-have for any business looking to streamline inventory
operations and stay ahead of the competition!</blockquote>
      </article>

      <article class="card feedback-card">
        <div class="feedback-head">
          <img src="./james.png" alt="James Lee" onerror="this.src='images/customers/_placeholder.jpg'">
          <div>
            <h3>James Lee</h3>
            <p class="role">Regional Manager · Samsung Electronics</p>
            <div class="rating" aria-label="rating 4.7 out of 5">★ ★ ★ ★ ★ <span>4.7/5</span></div>
          </div>
        </div>
        <blockquote>"LaundriQ is a groundbreaking innovation that perfectly complements
Samsung’s commitment to smart home solutions. As a regional manager,
I’ve seen firsthand how this AI-powered tool enhances the laundry
experience for our customers. By intelligently detecting fabric quality and
optimizing detergent usage, LaundriQ not only ensures superior cleaning
results but also supports eco-friendly practices. This collaboration with
LaundriQ aligns with our vision of creating smarter, more sustainable
home appliances. It’s truly a game-changer in the world of laundry care.</blockquote>
      </article>

      <article class="card feedback-card">
        <div class="feedback-head">
          <img src="./grant.png" alt="Michael Grant" onerror="this.src='images/customers/_placeholder.jpg'">
          <div>
            <h3>Michael Grant</h3>
            <p class="role">Plant Manager · Titan Manufacturing</p>
            <div class="rating">⭐⭐⭐⭐ <span class="score">4.4/5</span></div>


          </div>
        </div>
        <blockquote>"SafeOps has been a game-changer for our manufacturing unit. The AI
detects hazards in real-time and even predicts equipment failures that
could have cost us millions. Compliance reporting that used to take days is
now done in minutes. Our workplace has never been safer or more
efficient.</blockquote>
      </article>
    </div>
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
        <li><a href="solutions.php">Rapid Prototyping</a></li>
        <li><a href="solutions.php">Analytics & Insights</a></li>
        <li><a href="solutions.php">Security by Design</a></li>
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
    <p>© 2025 <span class="brand">AI-Solutions</span>. All rights reserved.</p>
  </div>
</footer>

  <!-- Dropdown behavior for nav -->
  <script>
  (function () {
    const dd = document.querySelector('nav .dropdown');
    if (!dd) return;
    const trigger = dd.querySelector('a.links');

    trigger.addEventListener('click', (e) => {
      if (!dd.classList.contains('open')) e.preventDefault();
      const open = !dd.classList.contains('open');
      dd.classList.toggle('open', open);
      trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    document.addEventListener('click', (e) => {
      if (!dd.contains(e.target)) {
        dd.classList.remove('open');
        trigger.setAttribute('aria-expanded','false');
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        dd.classList.remove('open');
        trigger.setAttribute('aria-expanded','false');
        trigger.focus();
      }
    });
  })();
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