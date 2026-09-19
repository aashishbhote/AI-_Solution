<?php
declare(strict_types=1);
require __DIR__ . '/db_connection.php'; // PDO connection

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// Fetch ONLY published services to append after the static list
$services = [];
try {
  $services = $pdo->query("
    SELECT id, title, slug, icon_emoji, category, short_desc, link_url
    FROM services
    WHERE is_active = 1
    ORDER BY sort_order ASC, title ASC
  ")->fetchAll(PDO::FETCH_ASSOC);

  $featuresStmt = $pdo->prepare("
    SELECT feature_text
    FROM service_features
    WHERE service_id = ?
    ORDER BY sort_order ASC, id ASC
  ");
} catch (Throwable $e) {
  $services = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Services</title>
<link rel="stylesheet" href="./style.css?v=2">
  <link rel="stylesheet" href="./services.css?v=2">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

  <!-- ===== NAV (unchanged) ===== -->
  <nav>
    <ul>
      <div class="logo">
        <img src="./ai.jpg" alt="AI-soultions logo">
      </div>
    </ul>
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
      <li><a href="./events.php" class="links"> Events</a></li>
      <li><a href="./photos.php" class="links">Photos</a></li>
      <li><a href="./testimonals.php" class="links">Portfolios</a></li>
                  <li><a href="./about.php" class="links">About us</a></li>

<li><a class="contact-btn" href="./contact.php">Contact</a></li>
    </ul>
  </nav>


  <script>
(function () {
  // Turn any URL or href into a comparable filename
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

      // If this link lives inside the dropdown, also activate the parent trigger
      const dd = a.closest('.dropdown');
      if (dd) dd.querySelector(':scope > a.links')?.classList.add('active');
    }

    // Optional: show active style immediately on click before navigation
    a.addEventListener('click', () => {
      document.querySelectorAll('nav a.active').forEach(x => x.classList.remove('active'));
      a.classList.add('active');
      const dd = a.closest('.dropdown');
      if (dd) dd.querySelector(':scope > a.links')?.classList.add('active');
    });
  });
})();
</script>
  <!-- ===== HERO (unchanged) ===== -->
  <div class="img_container">
    <img src="./services.png" alt="">
    <section class="part1">
      <p class="p1_one">Smarter <strong>Work</strong> , Faster <strong>Innovation</strong>, Better <strong>Experiences</strong></p>
      <p class="p2_two">We harness AI to create smarter workplaces,<br> accelerate innovation, and empower employees.</p>
    </section>
  </div>


  
  <!-- ===== Intro (unchanged) ===== -->
  <section class="hero">
    <div class="container hero-inner">
      <div class="hero-copy">
        <h1>Service Portfolio</h1>
        <p>We deliver practical, production-ready AI solutions—from virtual assistants to data analytics and secure, scalable platforms.</p>
        <div class="cta-row">
          <a href="#packages" class="btn">View Packages</a>
          <a href="#contact" class="btn btn--ghost">Request a Demo</a>
        </div>
      </div>
      <ul class="hero-highlights">
        <li>OWASP-aligned security</li>
        <li>Responsive & browser-independent</li>
        <li>Admin CMS for full control</li>
      </ul>
    </div>
  </section>

  <!-- ===== Services Grid ===== -->
  <section class="section" id="services">
    <div class="container">
      <div class="section-head">
        <h2>What we offer</h2>
        <p class="muted">Each service comes with clear deliverables, documentation, and optional post-launch support.</p>
      </div>

      <div class="service-grid">
        <!-- ========= YOUR STATIC CARDS (unchanged) ========= -->
        <article class="card service-item" data-category="ai">
          <div class="card-head">
            <div class="icon">🤖</div>
            <h3>AI Virtual Assistant</h3>
          </div>
          <p class="muted">FAQ/chat workflow, scripted intents, and analytics to understand user needs.</p>
          <ul class="feature-list">
            <li>Configurable intents & quick replies</li>
            <li>Embeddable widget for any page</li>
            <li>Conversation logs & insights</li>
          </ul>
          <div class="card-actions">
          </div>
          <div id="acc-ai" class="accordion" aria-hidden="true">
            <ul>
              <li>Discovery session & intent catalogue</li>
              <li>Brand-aligned chat UI (light/dark)</li>
              <li>Demo model or scripted bot (no PII)</li>
              <li>Admin toggle to enable/disable bot</li>
              <li>Usage dashboard (sessions, CSAT, FAQ hits)</li>
            </ul>
          </div>
        </article>

        <article class="card service-item" data-category="prototyping">
          <div class="card-head">
            <div class="icon">⚡</div>
            <h3>Affordable AI Prototyping</h3>
          </div>
          <p class="muted">Low-risk prototypes to validate ideas fast—perfect for tight budgets and timelines.</p>
          <ul class="feature-list">
            <li>Clickable UX flows or working PoCs</li>
            <li>Demo data only (no production access)</li>
            <li>Clear acceptance criteria & handover notes</li>
          </ul>
        </article>

        <article class="card service-item" data-category="dex">
          <div class="card-head">
            <div class="icon">🧭</div>
            <h3>Digital Employee Experience (DEX)</h3>
          </div>
          <p class="muted">Dashboards that surface friction in daily tools so teams fix issues before productivity drops.</p>
          <ul class="feature-list">
            <li>Journey mapping & pain-point logging</li>
            <li>Usage trends, SLA breaches, backlog insights</li>
            <li>CSV/Excel export for leadership reporting</li>
          </ul>
        </article>

        <article class="card service-item" data-category="incidents">
          <div class="card-head">
            <div class="icon">🛠️</div>
            <h3>Incident Prediction & Triage</h3>
          </div>
          <p class="muted">Early-warning signals, smart routing, and templates to reduce MTTR.</p>
          <ul class="feature-list">
            <li>Trigger rules and severity matrices</li>
            <li>Auto-assign & notify via email/webhooks</li>
            <li>Post-incident review checklist</li>
          </ul>
        </article>

        <article class="card service-item" data-category="kb">
          <div class="card-head">
            <div class="icon">📚</div>
            <h3>Knowledge Base Automation</h3>
          </div>
          <p class="muted">Keep FAQs, how-tos, and playbooks discoverable—assistant-aware and easy to maintain.</p>
          <ul class="feature-list">
            <li>Content models for FAQs/Policies/How-tos</li>
            <li>Auto-suggested updates from ticket trends</li>
            <li>Versioning & approval workflow</li>
          </ul>
        </article>

        <article class="card service-item" data-category="enquiries">
          <div class="card-head">
            <div class="icon">📨</div>
            <h3>Secure Enquiry Capture</h3>
          </div>
          <p class="muted">Contact form collects only required fields—no user accounts, no passwords.</p>
          <ul class="feature-list">
            <li>Name, email, phone, company, country, job title, job details</li>
            <li>Server-side validation & spam controls</li>
            <li>Auto-acknowledgement and export options</li>
          </ul>
        </article>

        <article class="card service-item" data-category="content">
          <div class="card-head">
            <div class="icon">🗞️</div>
            <h3>Content, Events & Gallery</h3>
          </div>
          <p class="muted">Promote your work with articles, testimonials, event timelines, and photo galleries.</p>
          <ul class="feature-list">
            <li>Blogs/news, ratings & testimonials</li>
            <li>Past & upcoming events timeline</li>
            <li>Responsive gallery with captions</li>
          </ul>
        </article>

        <article class="card service-item" data-category="admin">
          <div class="card-head">
            <div class="icon">🔐</div>
            <h3>Admin Portal & Analytics</h3>
          </div>
          <p class="muted">Password-protected admin for enquiries, content, and high-level usage analytics.</p>
          <ul class="feature-list">
            <li>Login/logout, password change</li>
            <li>Enquiry counts, sources, CSV export</li>
            <li>Manage blogs, photos, events</li>
          </ul>
        </article>

        <article class="card service-item" data-category="governance">
          <div class="card-head">
            <div class="icon">🔗</div>
            <h3>Integrations, Security & Compliance</h3>
          </div>
          <p class="muted">Connect safely with email, calendars, and webhooks; follow good security hygiene.</p>
          <ul class="feature-list">
            <li>REST hooks for notifications & exports</li>
            <li>Input validation, rate-limits, security headers</li>
            <li>PII-light defaults and content governance</li>
          </ul>
        </article>
        <!-- ========= /YOUR STATIC CARDS ========= -->

        <!-- ========= APPEND DB-PUBLISHED SERVICES (new ones you add in admin) ========= -->
        <?php if (!empty($services)): ?>
          <?php foreach ($services as $s): ?>
            <article class="card service-item" data-category="<?= e($s['category'] ?? '') ?>">
              <div class="card-head">
                <div class="icon"><?= e($s['icon_emoji'] ?: '🧩') ?></div>
                <h3><?= e($s['title']) ?></h3>
              </div>

              <?php if (!empty($s['short_desc'])): ?>
                <p class="muted"><?= e($s['short_desc']) ?></p>
              <?php endif; ?>

              <?php
                try {
                  $featuresStmt->execute([$s['id']]);
                  $features = $featuresStmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Throwable $e) { $features = []; }
                if ($features):
              ?>
                <ul class="feature-list">
                  <?php foreach ($features as $f): ?>
                    <li><?= e($f['feature_text']) ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>

              
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
        <!-- ========= /DB-PUBLISHED SERVICES ========= -->

      </div>
    </div>
  </section>



  <?php
// ---------- FETCH EXTRA PACKAGES (not the built-ins) ----------
if (!function_exists('e')) {
  function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}

$packages = [];
try {
  $packages = $pdo->query("
    SELECT id, title, price_text, badge_label, cta_label, cta_href, is_featured
    FROM packages
    WHERE is_active = 1
      AND title NOT IN ('Starter','Growth','Enterprise')  -- keep originals as static
    ORDER BY sort_order ASC, id ASC
  ")->fetchAll();

  $pkgFeatStmt = $pdo->prepare("
    SELECT feature_text
    FROM package_features
    WHERE package_id = ?
    ORDER BY sort_order ASC, id ASC
  ");
} catch (Throwable $e) {
  $packages = [];
}
?>

<!-- ===== Packages (built-ins first, DB extras after) ===== -->
<section class="section alt" id="packages">
  <div class="container">
    <div class="section-head">
      <h2>Packages</h2>
      <p class="muted">Pick a starting point—each package can be tailored to your needs and budget.</p>
    </div>

    <div class="plans">
      <!-- 1) Your three existing static packages (unchanged) -->
      <div class="plan">
        <h3>Starter</h3>
        <p class="price">£4,900</p>
        <ul>
          <li>Landing + About + Contact</li>
          <li>Demo chatbot widget</li>
          <li>Basic CMS (blogs & gallery)</li>
          <li>Email enquiries</li>
        </ul>
        <a href="#contact" class="btn btn--block">Get Started</a>
      </div>

      <div class="plan plan--featured" aria-label="Recommended">
        <div class="badge">Popular</div>
        <h3>Growth</h3>
        <p class="price">£12,500</p>
        <ul>
          <li>All Starter features</li>
          <li>Events timeline & portfolio</li>
          <li>Analytics dashboard</li>
          <li>Staging + production setup</li>
        </ul>
        <a href="#contact" class="btn btn--block">Choose Growth</a>
      </div>

      <div class="plan">
        <h3>Enterprise</h3>
        <p class="price">Custom</p>
        <ul>
          <li>SSO, roles & audit logs</li>
          <li>Custom integrations</li>
          <li>Uptime SLA & training</li>
          <li>Dedicated support</li>
        </ul>
        <a href="#contact" class="btn btn--block">Talk to Sales</a>
      </div>

      <!-- 2) Append admin-added packages from DB -->
      <?php if (!empty($packages)): ?>
        <?php foreach ($packages as $p): ?>
          <div class="plan <?= $p['is_featured'] ? 'plan--featured' : '' ?>">
            <?php if (!empty($p['badge_label'])): ?>
              <div class="badge"><?= e($p['badge_label']) ?></div>
            <?php endif; ?>

            <h3><?= e($p['title']) ?></h3>
            <p class="price"><?= e($p['price_text']) ?></p>

            <ul>
              <?php
                $pkgFeatStmt->execute([$p['id']]);
                foreach ($pkgFeatStmt->fetchAll() as $f):
              ?>
                <li><?= e($f['feature_text']) ?></li>
              <?php endforeach; ?>
            </ul>

            <?php if (!empty($p['cta_label'])): ?>
              <a href="<?= e($p['cta_href'] ?: '#contact') ?>" class="btn btn--block">
                <?= e($p['cta_label']) ?>
              </a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

    </div>
  </div>
</section>


  <!-- ===== Case Studies (unchanged) ===== -->
  <section class="section" id="cases">
    <div class="container">
      <div class="section-head">
        <h2>Selected case studies</h2>
        <p class="muted">A snapshot of outcomes achieved with similar clients.</p>
      </div>
      <div class="cases">
        <article class="case">
          <h3>Retail FAQ Assistant</h3>
          <p>Deployed a scripted bot to deflect repetitive “order status” queries; first-response time cut by 60%.</p>
        </article>
        <article class="case">
          <h3>Partner Portal Revamp</h3>
          <p>Migrated legacy CMS to a modern stack with role-based access and improved Lighthouse scores by 40%.</p>
        </article>
        <article class="case">
          <h3>Ops Analytics Dashboard</h3>
          <p>Unified events, blogs, and enquiries into a single dashboard; weekly export flow reduced to minutes.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- ===== FAQ (unchanged) ===== -->
   <section class="section alt" id="faq">
    <div class="container">
      <div class="section-head"><h2>FAQs</h2></div>
      <div class="faq">
        <details>
          <summary>Can the virtual assistant run without collecting personal data?</summary>
          <p>
            Yes. By default we configure the assistant in a <strong>privacy-first “demo mode”</strong>
            that avoids processing personally identifiable information (PII). In this mode the bot only
            stores <em>non-identifying telemetry</em> so you can measure usefulness without risking privacy.
          </p>
          <span class="faq-subhead">What we track by default (non-PII):</span>
          <ul>
            <li><strong>Timestamp</strong> of the session and a random session ID (rotated frequently).</li>
            <li><strong>High-level intent names</strong> (e.g., “Leave policy”, “Reset password”), not full message text.</li>
            <li><strong>Outcome flags</strong> (resolved / hand-off / unknown) and basic counts (e.g., clicks on suggested replies).</li>
            <li><strong>Device & page context</strong> (browser family, page URL path) without exact IP or precise location.</li>
          </ul>
          <span class="faq-subhead">What we explicitly avoid in demo mode:</span>
          <ul>
            <li>Names, emails, phone numbers, employee IDs, or free-text logs that could identify a person.</li>
            <li>Raw conversation transcripts saved to the database (only aggregate analytics are stored).</li>
            <li>Long-term identifiers; all IDs are short-lived and rotated.</li>
          </ul>
          <span class="faq-subhead">Data minimisation & retention:</span>
          <p>We keep only aggregates needed for dashboards and <em>auto-purge</em> raw events after a short window (e.g., 30–60 days). You can tighten or disable logging entirely.</p>
          <span class="faq-subhead">If you later need production data:</span>
          <p>We can enable opt-in fields (e.g., email) with consent wording, masking, and access controls. A Data Processing Addendum (DPA) and updated retention settings are recommended in that case.</p>
        </details>

        <details>
          <summary>Will the admin panel be secure?</summary>
          <p>Yes—security follows practical OWASP guidance with least-privilege defaults and hardened sessions.</p>
          <span class="faq-subhead">Baseline controls:</span>
          <ul>
            <li><strong>Authentication:</strong> hashed passwords using <code>Argon2id</code> (or <code>bcrypt</code> fallback), strong rules, lockout, optional 2FA.</li>
            <li><strong>Session safety:</strong> HTTP-only, Secure, SameSite cookies; short lifetime + idle timeout.</li>
            <li><strong>CSRF protection:</strong> synchroniser tokens on all state-changing requests; per-form nonces.</li>
            <li><strong>Input validation & DB safety:</strong> centralised validation/allow-lists; prepared statements/ORM to prevent SQLi/XSS.</li>
            <li><strong>Headers & TLS:</strong> HSTS, CSP (nonces), X-Frame-Options, X-Content-Type-Options, Referrer-Policy; HTTPS only.</li>
            <li><strong>Authorisation:</strong> roles (Admin/Editor/Viewer) with feature-level permissions.</li>
            <li><strong>Rate limiting:</strong> IP/user throttling and CAPTCHA on suspicious patterns.</li>
            <li><strong>Audit trails:</strong> read-only logs for logins, content changes, exports (tamper-evident, timestamped).</li>
            <li><strong>Backups & recovery:</strong> encrypted backups, tested restores, least-privilege service credentials.</li>
          </ul>
          <span class="faq-subhead">Operational hygiene:</span>
          <p>Automated dependency updates and vulnerability scans are enabled, and secrets are kept out of the codebase (environment variables or a secrets manager).</p>
        </details>

        <details>
          <summary>Can we add or remove modules later?</summary>
          <p>Absolutely. The platform is <strong>modular</strong>, so features can be enabled, disabled, or extended without a rebuild.</p>
          <span class="faq-subhead">How it works:</span>
          <ul>
            <li><strong>Feature toggles:</strong> switch modules (Blogs, Events, Gallery, Assistant, Analytics) on/off via Admin → Settings.</li>
            <li><strong>Decoupled content models:</strong> each module has its own tables/APIs to reduce cross-impact.</li>
            <li><strong>Graceful degradation:</strong> when a module is off, its routes and nav items hide automatically.</li>
            <li><strong>Safe migrations:</strong> schema changes via versioned migrations with rollback support.</li>
            <li><strong>Data retention:</strong> disable a module while keeping data archived/exported (CSV/JSON) or hidden.</li>
          </ul>
          <span class="faq-subhead">Bespoke extensions:</span>
          <p>Need HRIS integration, SSO, or custom dashboards later? We add them as separate modules with their own permissions and settings so upgrade paths remain clean.</p>
        </details>
      </div>
    </div>
  </section>

  <!-- ===== Contact CTA (unchanged) ===== -->
  <section class="cta" id="contact">
    <div class="container cta-inner">
      <h2>Ready to discuss your project?</h2>
      <p>Tell us your goals and we’ll propose the most effective path—within your budget.</p>
      <a href="./contact.php" class="btn btn--lg">Contact Us</a>
    </div>
  </section>

  <!-- ===== FOOTER (unchanged) ===== -->
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

  <!-- ===== Minimal JS (accordion + optional mobile nav toggle if you have it) ===== -->
  <script>
    (function(){
      // Accordion toggles (only affects static fallback card with data-accordion attr)
      document.querySelectorAll('[data-accordion]').forEach(btn => {
        const sel = btn.getAttribute('data-accordion');
        const target = sel ? document.querySelector(sel) : null;
        btn.addEventListener('click', () => {
          if (!target) return;
          const open = target.classList.contains('open');
          document.querySelectorAll('.accordion').forEach(a => { a.classList.remove('open'); a.setAttribute('aria-hidden','true'); });
          if (!open) { target.classList.add('open'); target.setAttribute('aria-hidden','false'); btn.setAttribute('aria-expanded','true'); }
          else { btn.setAttribute('aria-expanded','false'); }
        });
      });

      // (Optional) dropdown open/close logic if you need JS support beyond CSS :hover
      const dropdown = document.querySelector('nav .dropdown');
      if (dropdown){
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
      }
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
