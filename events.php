<?php
// events.php — Customer page (static + DB-driven)
require __DIR__ . '/db_connection.php'; // provides $pdo

// --- helpers ---
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function iso(?string $dt): string { return $dt ? gmdate('Y-m-d\TH:i:s\Z', strtotime($dt)) : ''; }
function dDate(?string $dt): string { return $dt ? date('j M Y', strtotime($dt)) : ''; }
function dTime(?string $start, ?string $end): string {
  if (!$start) return '';
  $s = strtotime($start); $e = $end ? strtotime($end) : $s;
  // Example: 14:00 – 15:00 BST/GMT (uses server TZ)
  $tz = date('T', $s);
  return date('H:i', $s) . ' – ' . date('H:i', $e) . ' ' . $tz;
}

// --- Fetch dynamic events ---
// Expected columns (adjust if your schema differs):
// id, title, description, location, start_datetime, end_datetime, type ('online'|'inperson'), is_active
$dynamicUpcoming = $dynamicPast = [];
try {
  $stmt = $pdo->query("
    SELECT id, title, description, location, start_datetime, end_datetime, type, is_active
    FROM events
    WHERE is_active = 1
    ORDER BY start_datetime ASC, id ASC
  ");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $now = time();
  foreach ($rows as $ev) {
    $endTs = $ev['end_datetime'] ? strtotime($ev['end_datetime']) : strtotime($ev['start_datetime']);
    if ($endTs >= $now) {
      $dynamicUpcoming[] = $ev;
    } else {
      $dynamicPast[] = $ev;
    }
  }
} catch (Throwable $e) {
  // If DB fails, just render static content
  $dynamicUpcoming = $dynamicPast = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="./style.css?v=2">
  <link rel="stylesheet" href="./events.css?v=3">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <title>Events</title>
</head>
<body>
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
          <li><a href="./solutions.php"  data-tab="ai"          role="menuitem">AI Virtual Assistant</a></li>
          <li><a href="./solutions1.php" data-tab="prototyping" role="menuitem">Rapid Prototyping</a></li>
          <li><a href="./solutions2.php" data-tab="analytics"   role="menuitem">Analytics & Insights</a></li>
          <li><a href="./solutions3.php" data-tab="security"    role="menuitem">Security by Design</a></li>
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
  <header class="page-hero">
    <div class="container">
      
      <h1>Events Timeline</h1>
      <p class="sub">Promotional events, webinars, and demos. No account required—RSVP via our contact form.</p>
    </div>
  </header>

  <!-- ===== Toolbar (Search / Filter) ===== -->
  <section class="toolbar">
    <div class="container toolbar-inner">
      <div class="search">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <input id="searchInput" type="search" placeholder="Search events (e.g. Sunderland, webinar, assistant)..." aria-label="Search events">
      </div>
      <div class="filters">
        <button class="tab is-active" data-filter="all">All</button>
        <button class="tab" data-filter="upcoming">Upcoming</button>
        <button class="tab" data-filter="past">Past</button>
      </div>
    </div>
  </section>

  <!-- ===== Upcoming Events ===== -->
  <section class="section" aria-labelledby="upcoming-heading">
    <div class="container">
      <div class="section-head">
        <h2 id="upcoming-heading">Upcoming</h2>
        <p class="muted">Join us to see affordable prototyping, assistant design patterns, and analytics in action.</p>
      </div>

      <div class="events-grid" id="upcoming">
        <!-- === STATIC upcoming events (keep yours) === -->
        <!-- Event card -->
        <article class="card event item" data-type="upcoming"
          data-title="AI-Solutions Demo Day — Sunderland"
          data-start="2025-09-27T10:00:00Z" data-end="2025-09-27T12:00:00Z"
          data-location="Sunderland Software City, UK">
          <div class="date">
            <time datetime="2025-09-27">27 Sep 2025</time>
            <span class="time">11:00 – 13:00 BST</span>
          </div>
          <div class="body">
            <h3>AI-Solutions Demo Day — Sunderland</h3>
            <p class="muted">Live walkthrough of our Rapid Prototyping workflow and AI Virtual Assistant with hands-on tryouts.</p>
            <ul class="highlights">
              <li>See a working assistant with quick replies & fallbacks</li>
              <li>Prototype station: try the demo with synthetic data</li>
              <li>Q&A + option to book a 1-to-1 consult</li>
            </ul>
            <div class="meta"><span class="pill pill--inperson">In person</span></div>
            <div class="actions">
              <a class="btn" href="./contact.php?subject=RSVP%3A%20Demo%20Day%20Sunderland">RSVP</a>
              <button class="btn btn--ghost add-ics" type="button">Add to Calendar (.ics)</button>
              <a class="btn btn--ghost google-link" target="_blank" rel="noopener">Google Calendar</a>
            </div>
          </div>
          <div class="loc"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Sunderland, UK</div>
        </article>

        <!-- Webinar: Designing Helpful Assistants -->
        <article class="card event item" data-type="upcoming"
          data-title="Webinar: Designing Helpful Assistants"
          data-start="2025-10-15T13:00:00Z" data-end="2025-10-15T14:00:00Z"
          data-location="Online (Zoom)">
          <div class="date">
            <time datetime="2025-10-15">15 Oct 2025</time>
            <span class="time">14:00 – 15:00 BST</span>
          </div>
          <div class="body">
            <h3>Webinar: Designing Helpful Assistants</h3>
            <p class="muted">Seven UX patterns that increase adoption and reduce first-response time.</p>
            <ul class="highlights">
              <li>Quick replies, intent fallbacks, and guided flows</li>
              <li>Live demo + checklist handout</li>
              <li>Open Q&A</li>
            </ul>
            <div class="meta"><span class="pill pill--online">Online</span></div>
            <div class="actions">
              <a class="btn" href="./contact.php?subject=RSVP%3A%20Webinar%20Assistant%20Design">RSVP</a>
              <button class="btn btn--ghost add-ics" type="button">Add to Calendar (.ics)</button>
              <a class="btn btn--ghost google-link" target="_blank" rel="noopener">Google Calendar</a>
            </div>
          </div>
          <div class="loc"><i class="fa-solid fa-globe" aria-hidden="true"></i> Online</div>
        </article>

        <!-- Workshop: Dashboards that Drive Action -->
        <article class="card event item" data-type="upcoming"
          data-title="Workshop: Building Dashboards that Drive Action"
          data-start="2025-10-30T14:00:00Z" data-end="2025-10-30T16:00:00Z"
          data-location="Online (Zoom)">
          <div class="date">
            <time datetime="2025-10-30">30 Oct 2025</time>
            <span class="time">14:00 – 16:00 GMT</span>
          </div>
          <div class="body">
            <h3>Workshop: Building Dashboards that Drive Action</h3>
            <p class="muted">Hands-on session to choose KPIs and design accessible, decision-ready charts.</p>
            <ul class="highlights">
              <li>Pick 5 starter KPIs for DEX/ops</li>
              <li>Chart do’s & don’ts (live critique)</li>
              <li>Set up scheduled exports</li>
            </ul>
            <div class="meta"><span class="pill pill--online">Online</span></div>
            <div class="actions">
              <a class="btn" href="./contact.php?subject=RSVP%3A%20Dashboards%20Workshop">RSVP</a>
              <button class="btn btn--ghost add-ics" type="button">Add to Calendar (.ics)</button>
              <a class="btn btn--ghost google-link" target="_blank" rel="noopener">Google Calendar</a>
            </div>
          </div>
          <div class="loc"><i class="fa-solid fa-globe" aria-hidden="true"></i> Online</div>
        </article>

        <!-- AI in Healthcare Roundtable — Newcastle -->
        <article class="card event item" data-type="upcoming"
          data-title="AI in Healthcare Roundtable — Newcastle"
          data-start="2025-11-05T10:00:00Z" data-end="2025-11-05T11:30:00Z"
          data-location="The Catalyst, Newcastle upon Tyne, UK">
          <div class="date">
            <time datetime="2025-11-05">5 Nov 2025</time>
            <span class="time">10:00 – 11:30 GMT</span>
          </div>
          <div class="body">
            <h3>AI in Healthcare Roundtable — Newcastle</h3>
            <p class="muted">Peer discussion on assistants and rapid prototyping in clinical support workflows.</p>
            <ul class="highlights">
              <li>Real use-cases (triage, knowledge surfacing)</li>
              <li>Privacy & governance considerations</li>
              <li>Networking with local leaders</li>
            </ul>
            <div class="meta"><span class="pill pill--inperson">In person</span></div>
            <div class="actions">
              <a class="btn" href="./contact.php?subject=RSVP%3A%20Healthcare%20Roundtable%20Newcastle">RSVP</a>
              <button class="btn btn--ghost add-ics" type="button">Add to Calendar (.ics)</button>
              <a class="btn btn--ghost google-link" target="_blank" rel="noopener">Google Calendar</a>
            </div>
          </div>
          <div class="loc"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Newcastle upon Tyne, UK</div>
        </article>

        <!-- === DYNAMIC upcoming events from DB === -->
        <?php foreach ($dynamicUpcoming as $ev): ?>
          <article class="card event item" data-type="upcoming"
            data-title="<?=h($ev['title'])?>"
            data-start="<?=iso($ev['start_datetime'])?>"
            data-end="<?=iso($ev['end_datetime'])?>"
            data-location="<?=h($ev['location'])?>">
            <div class="date">
              <time datetime="<?=h(date('Y-m-d', strtotime($ev['start_datetime'])))?>"><?=h(dDate($ev['start_datetime']))?></time>
              <span class="time"><?=h(dTime($ev['start_datetime'], $ev['end_datetime']))?></span>
            </div>
            <div class="body">
              <h3><?=h($ev['title'])?></h3>
              <?php if (!empty($ev['description'])): ?>
                <p class="muted"><?=h($ev['description'])?></p>
              <?php endif; ?>
              <div class="meta">
                <?php if (strtolower((string)$ev['type']) === 'inperson'): ?>
                  <span class="pill pill--inperson">In person</span>
                <?php else: ?>
                  <span class="pill pill--online">Online</span>
                <?php endif; ?>
              </div>
              <div class="actions">
                <a class="btn" href="./contact.php?subject=RSVP%3A%20<?=urlencode($ev['title'])?>">RSVP</a>
                <button class="btn btn--ghost add-ics" type="button">Add to Calendar (.ics)</button>
                <a class="btn btn--ghost google-link" target="_blank" rel="noopener">Google Calendar</a>
              </div>
            </div>
            <div class="loc">
              <?php if (strtolower((string)$ev['type']) === 'inperson'): ?>
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?=h($ev['location'])?>
              <?php else: ?>
                <i class="fa-solid fa-globe" aria-hidden="true"></i> Online
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ===== Past Events (Timeline) ===== -->
  <section class="section alt" aria-labelledby="past-heading">
    <div class="container">
      <div class="section-head">
        <h2 id="past-heading">Past</h2>
        <p class="muted">Concise highlights of previous sessions—no extra reading needed.</p>
      </div>

      <ol class="timeline" id="past">
        <!-- === STATIC past items (keep yours) === -->
        <!-- 2025 -->
        <li class="year">2025</li>

        <li class="titem item" data-type="past"
            data-title="Analytics that Matter — Live Session"
            data-start="2025-08-12T13:00:00Z" data-end="2025-08-12T14:00:00Z"
            data-location="Online">
          <div class="dot"></div>
          <div class="content">
            <time datetime="2025-08-12" class="tdate">12 Aug 2025</time>
            <h3>Analytics that Matter — Live Session</h3>
            <p class="muted">We reviewed five core DEX KPIs, showed unknown-intent triage, and set up scheduled CSV exports for stakeholders.</p>
          </div>
        </li>

        <li class="titem item" data-type="past"
            data-title="Security by Design — Tech Talk"
            data-start="2025-05-02T09:00:00Z" data-end="2025-05-02T10:00:00Z"
            data-location="Online">
          <div class="dot"></div>
          <div class="content">
            <time datetime="2025-05-02" class="tdate">2 May 2025</time>
            <h3>Security by Design — Tech Talk</h3>
            <p class="muted">Quick wins for small teams: Argon2/bcrypt hashing, CSRF tokens, strict CSP, audit logs, and tested backups.</p>
          </div>
        </li>

        <li class="titem item" data-type="past"
            data-title="Rapid Prototyping — Case Study"
            data-start="2025-06-18T12:00:00Z" data-end="2025-06-18T13:00:00Z"
            data-location="Online">
          <div class="dot"></div>
          <div class="content">
            <time datetime="2025-06-18" class="tdate">18 Jun 2025</time>
            <h3>Rapid Prototyping — Case Study</h3>
            <p class="muted">Shared a two-week PoC that proved value with synthetic data, clear acceptance criteria, and a go/no-go review.</p>
          </div>
        </li>

        <li class="titem item" data-type="past"
            data-title="Community Meetup — Sunderland"
            data-start="2025-04-10T16:00:00Z" data-end="2025-04-10T17:30:00Z"
            data-location="Sunderland Software City, UK">
          <div class="dot"></div>
          <div class="content">
            <time datetime="2025-04-10" class="tdate">10 Apr 2025</time>
            <h3>Community Meetup — Sunderland</h3>
            <p class="muted">Hands-on demos of our assistant patterns and a lively Q&A on safe, affordable prototyping.</p>
          </div>
        </li>

        <li class="titem item" data-type="past"
            data-title="Webinar: Prototyping with Synthetic Data"
            data-start="2025-03-05T13:00:00Z" data-end="2025-03-05T14:00:00Z"
            data-location="Online">
          <div class="dot"></div>
          <div class="content">
            <time datetime="2025-03-05" class="tdate">5 Mar 2025</time>
            <h3>Webinar: Prototyping with Synthetic Data</h3>
            <p class="muted">Explained how to mirror production formats without PII, plus templates for success metrics and evaluation.</p>
          </div>
        </li>

        <li class="titem item" data-type="past"
            data-title="Security Clinic for SMEs"
            data-start="2025-02-14T10:00:00Z" data-end="2025-02-14T11:00:00Z"
            data-location="Online">
          <div class="dot"></div>
          <div class="content">
            <time datetime="2025-02-14" class="tdate">14 Feb 2025</time>
            <h3>Security Clinic for SMEs</h3>
            <p class="muted">Step-by-step hardening for admin panels: session safety, rate-limiting, headers, and logging.</p>
          </div>
        </li>

        <li class="titem item" data-type="past"
            data-title="Assistant Adoption Patterns — Brown Bag"
            data-start="2025-01-22T12:00:00Z" data-end="2025-01-22T13:00:00Z"
            data-location="Online">
          <div class="dot"></div>
          <div class="content">
            <time datetime="2025-01-22" class="tdate">22 Jan 2025</time>
            <h3>Assistant Adoption Patterns — Brown Bag</h3>
            <p class="muted">Seven UI/UX patterns (quick replies, guided flows) that boosted adoption and cut first-response time.</p>
          </div>
        </li>

        <!-- 2024 -->
        <li class="year">2024</li>

        <li class="titem item" data-type="past"
            data-title="Analytics Bootcamp — North East"
            data-start="2024-11-18T09:30:00Z" data-end="2024-11-18T11:00:00Z"
            data-location="The Catalyst, Newcastle upon Tyne, UK">
          <div class="dot"></div>
          <div class="content">
            <time datetime="2024-11-18" class="tdate">18 Nov 2024</time>
            <h3>Analytics Bootcamp — North East</h3>
            <p class="muted">Picked outcome-driven KPIs, critiqued real dashboards, and configured alerts & exports.</p>
          </div>
        </li>

        <li class="titem item" data-type="past"
            data-title="Product Discovery Workshop"
            data-start="2024-09-30T13:00:00Z" data-end="2024-09-30T15:00:00Z"
            data-location="Online">
          <div class="dot"></div>
          <div class="content">
            <time datetime="2024-09-30" class="tdate">30 Sep 2024</time>
            <h3>Product Discovery Workshop</h3>
            <p class="muted">From problem framing to a two-week validation plan with clear acceptance criteria.</p>
          </div>
        </li>

        <li class="titem item" data-type="past"
            data-title="University Talk: Practical AI Assistants"
            data-start="2024-06-20T15:00:00Z" data-end="2024-06-20T16:00:00Z"
            data-location="University of Sunderland, UK">
          <div class="dot"></div>
          <div class="content">
            <time datetime="2024-06-20" class="tdate">20 Jun 2024</time>
            <h3>University Talk: Practical AI Assistants</h3>
            <p class="muted">Guest lecture on building privacy-first assistants and measurable pilots.</p>
          </div>
        </li>

        <!-- === DYNAMIC past events (grouped by year, newest year first) === -->
        <?php
          if (!empty($dynamicPast)) {
            // Sort by start desc
            usort($dynamicPast, fn($a,$b) => strtotime($b['start_datetime']) <=> strtotime($a['start_datetime']));
            $yearPrinted = [];
            foreach ($dynamicPast as $ev):
              $y = (int)date('Y', strtotime($ev['start_datetime']));
              if (!isset($yearPrinted[$y])) {
                echo '<li class="year">'. $y .'</li>';
                $yearPrinted[$y] = true;
              }
        ?>
          <li class="titem item" data-type="past"
              data-title="<?=h($ev['title'])?>"
              data-start="<?=iso($ev['start_datetime'])?>"
              data-end="<?=iso($ev['end_datetime'])?>"
              data-location="<?=h($ev['location'])?>">
            <div class="dot"></div>
            <div class="content">
              <time class="tdate" datetime="<?=h(date('Y-m-d', strtotime($ev['start_datetime'])))?>"><?=h(dDate($ev['start_datetime']))?></time>
              <h3><?=h($ev['title'])?></h3>
              <p class="muted">
                Completed session<?= $ev['location'] ? ' — ' . h($ev['location']) : '' ?>.
              </p>
              <div class="meta">
                <?php if (strtolower((string)$ev['type']) === 'inperson'): ?>
                  <i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?=h($ev['location'])?>
                <?php else: ?>
                  <i class="fa-solid fa-globe" aria-hidden="true"></i> Online
                <?php endif; ?>
              </div>
            </div>
          </li>
        <?php
            endforeach;
          }
        ?>
      </ol>
    </div>
  </section>

  <section class="section" id="timeline-all" aria-labelledby="timeline-heading">
    <div class="container">
      <div class="section-head">
        <h2 id="timeline-heading">Full Timeline</h2>
        <p class="muted">All events in one place—upcoming and past.</p>
      </div>
      <ol class="timeline" id="timelineAll"></ol>
    </div>
  </section>

  <script>
  // Build a combined timeline from the existing sections
  (function buildFullTimeline(){
    const container = document.getElementById('timelineAll');
    if (!container) return;

    const items = [];
    // Collect events from "Upcoming" cards
    document.querySelectorAll('#upcoming .event').forEach(ev => {
      const start = new Date(ev.dataset.start);
      const end   = new Date(ev.dataset.end || ev.dataset.start);
      items.push({
        start, end,
        title: ev.dataset.title || ev.querySelector('h3')?.textContent?.trim() || 'Event',
        location: ev.dataset.location || '',
        type: ev.querySelector('.pill--inperson') ? 'inperson' : 'online',
        status: 'upcoming'
      });
    });

    // Collect events from "Past" timeline items
    document.querySelectorAll('#past .titem').forEach(li => {
      const start = new Date(li.dataset.start);
      const end   = new Date(li.dataset.end || li.dataset.start);
      items.push({
        start, end,
        title: li.dataset.title || li.querySelector('h3')?.textContent?.trim() || 'Event',
        location: li.dataset.location || '',
        type: (li.dataset.location && li.dataset.location.toLowerCase().includes('online')) ? 'online' : 'inperson',
        status: 'past'
      });
    });

    // Sort newest first
    items.sort((a,b) => b.start - a.start);

    // Render grouped by year
    let currentYear = null;
    const fmt = d => d.toLocaleDateString(undefined, { day:'2-digit', month:'short', year:'numeric' });

    items.forEach(it => {
      const y = it.start.getUTCFullYear();
      if (y !== currentYear){
        const yearLi = document.createElement('li');
        yearLi.className = 'year';
        yearLi.textContent = y;
        container.appendChild(yearLi);
        currentYear = y;
      }

      const li = document.createElement('li');
      li.className = 'titem item';
      li.dataset.type = it.status;         // 'upcoming' | 'past'
      li.dataset.year = String(y);

      const isOnline = it.type === 'online';
      const icon = isOnline
        ? '<i class="fa-solid fa-globe" aria-hidden="true"></i> Online'
        : `<i class="fa-solid fa-location-dot" aria-hidden="true"></i> ${it.location || ''}`;

      const summary = it.status === 'upcoming'
        ? `Coming up: ${it.location || (isOnline ? 'Online' : '')}. Add to your calendar above.`
        : `Completed session${it.location ? ' — ' + it.location : ''}.`;

      li.innerHTML = `
        <div class="dot"></div>
        <div class="content">
          <time class="tdate" datetime="${it.start.toISOString().slice(0,10)}">${fmt(it.start)}</time>
          <h3>${it.title}</h3>
          <p class="muted">${summary}</p>
          <div class="meta">${icon}</div>
        </div>
      `;
      container.appendChild(li);
    });

    if (typeof applyFilters === 'function') applyFilters();
  })();
  </script>

  <!-- ===== Event Photos ===== -->
  

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
          <li><a href="./blogs.php">Blogs</a></li>
          <li><a href="./events.php">Events</a></li>
          <li><a href="./testimonials.php">Testimonials</a></li>
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

  <!-- ===== JS: filters, search, calendar links (.ics + Google) ===== -->
  <script>
    document.getElementById('year').textContent = new Date().getFullYear();

    // Search / Filter tabs
    const tabs = document.querySelectorAll('.tab');
    const searchInput = document.getElementById('searchInput');
    function applyFilters() {
      const filter = document.querySelector('.tab.is-active').dataset.filter;
      const q = (searchInput.value || '').toLowerCase().trim();
      document.querySelectorAll('.item').forEach(card => {
        const type = card.dataset.type; // 'upcoming' or 'past'
        const text = card.textContent.toLowerCase();
        const visibleByType = (filter === 'all') || (filter === type);
        const visibleBySearch = (!q || text.includes(q));
        card.style.display = (visibleByType && visibleBySearch) ? '' : 'none';
      });
    }
    tabs.forEach(t => t.addEventListener('click', () => {
      tabs.forEach(x => x.classList.remove('is-active'));
      t.classList.add('is-active');
      applyFilters();
    }));
    searchInput.addEventListener('input', applyFilters);
    applyFilters();

    // Calendar helpers
    function pad(n){ return String(n).padStart(2,'0'); }
    function toICSDate(dt){
      return dt.getUTCFullYear()
        + pad(dt.getUTCMonth()+1)
        + pad(dt.getUTCDate()) + 'T'
        + pad(dt.getUTCHours())
        + pad(dt.getUTCMinutes())
        + pad(dt.getUTCSeconds()) + 'Z';
    }
    function makeICS(ev){
      const title = ev.dataset.title;
      const start = new Date(ev.dataset.start);
      const end = new Date(ev.dataset.end || ev.dataset.start);
      const loc = ev.dataset.location || '';
      const desc = 'Event by AI-Solutions: https://example.com/events';
      const ics =
`BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//AI-Solutions//Events//EN
CALSCALE:GREGORIAN
METHOD:PUBLISH
BEGIN:VEVENT
UID:${Date.now()}-${Math.random().toString(36).slice(2)}@ai-solutions
DTSTAMP:${toICSDate(new Date())}
DTSTART:${toICSDate(start)}
DTEND:${toICSDate(end)}
SUMMARY:${title}
DESCRIPTION:${desc}
LOCATION:${loc}
END:VEVENT
END:VCALENDAR`;
      const blob = new Blob([ics], {type: 'text/calendar;charset=utf-8'});
      return URL.createObjectURL(blob);
    }
    function makeGoogleLink(ev){
      const title = encodeURIComponent(ev.dataset.title);
      const start = new Date(ev.dataset.start);
      const end   = new Date(ev.dataset.end || ev.dataset.start);
      const dates = toICSDate(start) + '/' + toICSDate(end);
      const loc   = encodeURIComponent(ev.dataset.location || '');
      const details = encodeURIComponent('Event by AI-Solutions');
      return `https://www.google.com/calendar/render?action=TEMPLATE&text=${title}&dates=${dates}&details=${details}&location=${loc}&sf=true&output=xml`;
    }
    document.querySelectorAll('.event').forEach(ev => {
      const g = ev.querySelector('.google-link');
      if (g) g.href = makeGoogleLink(ev);
      const icsBtn = ev.querySelector('.add-ics');
      if (icsBtn) icsBtn.addEventListener('click', () => {
        const url = makeICS(ev);
        const a = document.createElement('a');
        a.href = url; a.download = (ev.dataset.title || 'event') + '.ics';
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(()=>URL.revokeObjectURL(url), 2500);
      });
    });
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
