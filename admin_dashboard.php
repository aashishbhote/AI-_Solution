<?php
// admin_dashboard.php
declare(strict_types=1);
require __DIR__ . '/admin_auth.php'; // starts session, checks login, sets CSRF

if (!function_exists('e')) {
  function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}
$active = basename($_SERVER['PHP_SELF']);

$adminName  = $_SESSION['admin_name']  ?? 'Purnima Mali';
$adminEmail = $_SESSION['admin_email'] ?? 'malipurnima2058@gmail.com';

$adminTz = $_SESSION['admin_tz'] ?? 'UTC';
$validTzs = timezone_identifiers_list();
if (!in_array($adminTz, $validTzs, true)) $adminTz = 'UTC';

// Server-rendered fallback (shows a time even if JS is off)
$tzNow = (new DateTime('now', new DateTimeZone($adminTz)))->format('D, M j Y · h:i:s A');


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
  <title>Admin • Dashboard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <?php require __DIR__.'/admin_head_snippet.php'; ?>
  <link rel="stylesheet" href="./admin-theme.css?v=1">
  <link rel="stylesheet" href="./admin_dashboard.css?v=2">
</head>
<body>

  <!-- ===== Sidebar (admin-nav) ===== -->
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
        <li><a href="admin_portfolios.php" class="<?= $active==='admin_portfolios.php'?'active':'' ?>">Portfolios</a></li>
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
        <li><a href="testimonals.php" target="_blank" rel="noopener">Portfolios<span class="ext" aria-hidden="true">↗</span></a></li>
        <li><a href="contact.php"   target="_blank" rel="noopener">Contact<span class="ext" aria-hidden="true">↗</span></a></li>
      </ul>
    </div>

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

  <!-- ===== Content ===== -->
  <div class="wrap">
    <div class="card">
      <h2>Welcome, <?= e($adminName) ?>!</h2>
      <p class="muted">You are logged in as <strong><?= e($adminEmail) ?></strong>.</p>
      <p class="muted">
    Time zone: <strong><?= e($adminTz) ?></strong>
    <span aria-hidden="true">•</span>
    <span id="tzClock" data-tz="<?= e($adminTz) ?>"><?= e($tzNow) ?></span>
  </p>
    </div>

    <!-- ===== Enquiries & Customers (Analytics only) ===== -->
    <div class="grid-analytics">
      <article class="card">
        <h3>Enquiries over time</h3>
        <canvas id="enquiriesOverTime" height="130"></canvas>
      </article>

      <article class="card">
        <h3>Unread vs Read</h3>
        <canvas id="readSplit" height="130"></canvas>
      </article>

      <article class="card">
        <h3>Top companies by enquiries</h3>
        <canvas id="topCompanies" height="130"></canvas>
      </article>

      <article class="card">
        <h3>Monthly totals</h3>
        <div id="kpis" class="kpi-row"></div>
        <div id="monthlyTotals" class="mini-table"></div>
      </article>
      <!-- ===== Events Analytics (stacked) ===== -->
  


    </div>
  </div>
  <!-- ===== Events Analytics (stacked) ===== -->
<div class="grid-two">
  <!-- Stacked (smaller) -->
  <article class="card">
    <div class="card-head">
      <h3>Events by Type × Channel</h3>
      <small class="muted">last 30 days</small>
    </div>
    <div class="chart-sm">
      <canvas id="eventsByTypeStacked"></canvas>
    </div>
  </article>

  <!-- Day-wise (make short) -->
  <article class="card">
  <div class="card-head">
    <h3>Day-wise Events (Organic vs Other)</h3>
    <select id="dwEvent" class="mini-select"></select>
  </div>
  <!-- was class="chart-xs" -->
  <div class="chart-h" style="--h: 240px">
    <canvas id="eventsDayWise"></canvas>
  </div>
</article>

</div>
<!-- EVENTS ORGANIZER DASHBOARD -->
 <div class="events-overview">
  <!-- your overview cards (Successful/Pending + 4 charts) -->


<article class="card span-3">
  <div class="kpi-row">
    <div class="kpi">
      <div class="kpi-label">Successful</div>
      <div class="kpi-value" id="kpi-successful">--</div>
    </div>
    <div class="kpi">
      <div class="kpi-label">Pending</div>
      <div class="kpi-value" id="kpi-pending">--</div>
    </div>
    <div class="kpi">
      <div class="kpi-label">Success Rate</div>
      <div class="kpi-value" id="kpi-success-rate">--</div>
    </div>
    <div class="kpi">
      <div class="kpi-label">Cancellation Rate</div>
      <div class="kpi-value" id="kpi-cancel-rate">--</div>
    </div>
  </div>
  <div class="chart-xs"><canvas id="gaugeSuccess"></canvas></div>
  <div class="chart-xs" style="margin-top:10px;"><canvas id="gaugeCancel"></canvas></div>
</article>

<article class="card span-5">
  <div class="card-head"><h3>Events by Organizer</h3></div>
  <div class="chart-md"><canvas id="byOrganizer"></canvas></div>
</article>

<article class="card span-4">
  <div class="card-head"><h3>Events by Status</h3></div>
  <div class="chart-lg"><canvas id="statusDonut"></canvas></div>
</article>

<article class="card span-7">
  <div class="card-head"><h3>Top 5 Invitees and Their Responses</h3></div>
  <div class="chart-md"><canvas id="inviteeResponses"></canvas></div>
</article>

<article class="card span-5">
  <div class="card-head"><h3>Events by Venue</h3></div>
  <div class="chart-md"><canvas id="venueTreemap"></canvas></div>
</article>

</div>




  <!-- Sidebar & account dropdown JS -->
  <script>
    (function(){
      const sidebar = document.getElementById('adminNav');
      const localToggle  = sidebar?.querySelector('.toggle');
      const btn  = document.getElementById('accountBtn');
      const menu = document.getElementById('accountMenu');

      function toggleSidebar(){
        const open = sidebar.classList.toggle('open');
        localToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
      }
      localToggle?.addEventListener('click', toggleSidebar);

      function closeMenu(){
        if (!menu || !btn) return;
        menu.classList.remove('open');
        btn.setAttribute('aria-expanded','false');
      }
      btn?.addEventListener('click', (e)=>{
        e.stopPropagation();
        const willOpen = !menu.classList.contains('open');
        document.querySelectorAll('.account-menu.open').forEach(m => m.classList.remove('open'));
        if (willOpen){ menu.classList.add('open'); btn.setAttribute('aria-expanded','true'); }
        else { closeMenu(); }
      });
      document.addEventListener('click', (e)=>{ if (menu && btn && !menu.contains(e.target) && e.target !== btn) closeMenu(); });
      document.addEventListener('keydown', (e)=>{ if (e.key==='Escape') closeMenu(); });
    })();
  </script>

  <!-- Charts for Enquiries -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <!-- Treemap plugin for Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-treemap@2"></script>

  
  <script>
  (async function(){
    try {
      const res = await fetch('admin_enquiries_analytics.php?months=6', {cache:'no-store'});
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || 'No data');

      // 1) Over time (line)
      new Chart(document.getElementById('enquiriesOverTime'), {
        type: 'line',
        data: {
          labels: data.overTime.labels,
          datasets: [{ label: 'Enquiries', data: data.overTime.data, tension:.3, fill:true }]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, ticks: { precision:0 } } }
        }
      });

      // 2) Read vs Unread (doughnut)
      new Chart(document.getElementById('readSplit'), {
        type: 'doughnut',
        data: {
          labels: ['Unread','Read'],
          datasets: [{ data: [data.readSplit.unread, data.readSplit.read] }]
        },
        options: {
          responsive: true,
          plugins: { legend: { position: 'bottom' } },
          cutout: '60%'
        }
      });

      // 3) Top companies (bar)
      new Chart(document.getElementById('topCompanies'), {
        type: 'bar',
        data: {
          labels: data.topCompanies.labels,
          datasets: [{ label: 'Enquiries', data: data.topCompanies.data }]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, ticks: { precision:0 } } }
        }
      });

      // KPIs
      const kpis = document.getElementById('kpis');
      if (kpis && data.kpis) {
        kpis.innerHTML = `
          <div class="kpi"><div class="kpi-label">This month</div><div class="kpi-value">${data.kpis.thisMonth}</div></div>
          <div class="kpi"><div class="kpi-label">Last month</div><div class="kpi-value">${data.kpis.lastMonth}</div></div>
          <div class="kpi"><div class="kpi-label">Unread</div><div class="kpi-value">${data.kpis.unread}</div></div>
          <div class="kpi"><div class="kpi-label">Read</div><div class="kpi-value">${data.kpis.read}</div></div>
        `;
      }

      // Monthly totals table
      const mt = document.getElementById('monthlyTotals');
      if (mt && data.byMonth) {
        mt.innerHTML = `
          <div class="row head"><span>Month</span><span>Enquiries</span></div>
          ${data.byMonth.map(r => `
            <div class="row">
              <span>${r.label}</span>
              <strong>${r.count}</strong>
            </div>
          `).join('')}
        `;
      }
    } catch (e) {
      console.error(e);
      document.querySelectorAll('.grid-analytics .card').forEach(c => {
        if (!c.querySelector('canvas')) return;
        const err = document.createElement('p');
        err.style.color = '#b91c1c';
        err.textContent = 'Failed to load analytics.';
        c.appendChild(err);
      });
    }
  })();
  </script>


<script>
(async function(){
  try {
    const res = await fetch('admin_events_analytics.php', {cache:'no-store'});
    // Helpful debug: if endpoint sends HTML, this will throw before JSON.parse
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); }
    catch { throw new Error('Endpoint did not return JSON:\n' + text.slice(0,180)); }

    if (!data.ok) throw new Error(data.error || 'Failed loading events data');

    const el = document.getElementById('eventsByTypeStacked');
    if (!el) return;

    if ((data.total || 0) === 0) {
      const ctx = el.getContext('2d');
      ctx.fillStyle = '#9aa4b2';
      ctx.font = '14px system-ui';
      ctx.textAlign = 'center';
      ctx.fillText('No events in the selected range', el.width/2, el.height/2);
      return;
    }

    const palette = ['#60a5fa','#f472b6','#fbbf24','#34d399','#a78bfa','#f87171','#f59e0b','#22d3ee'];
    const datasets = data.datasets.map((ds,i)=>({
      ...ds,
      backgroundColor: palette[i % palette.length],
      borderWidth: 0
    }));

    new Chart(el, {
      type: 'bar',
      data: { labels: data.labels, datasets },
      options: {
        indexAxis: 'y',
        responsive: true,
        scales: {
          x: { stacked: true, beginAtZero: true, ticks: { callback:v=>Intl.NumberFormat().format(v) } },
          y: { stacked: true }
        },
        plugins: {
          legend: { position: 'bottom' }
        }
      }
    });
  } catch (e) {
    console.error(e);
    const el = document.getElementById('eventsByTypeStacked');
    if (el) {
      const ctx = el.getContext('2d');
      ctx.fillStyle = '#b91c1c';
      ctx.font = '12px monospace';
      ctx.textAlign = 'center';
      ctx.fillText((String(e.message || e)).slice(0,160), el.width/2, el.height/2);
    }
  }
})();
</script>


<script>
(async function daywiseBlock(){
  const sel = document.getElementById('dwEvent');
  const cv  = document.getElementById('eventsDayWise');
  let chart;

  async function load(eventType='All'){
    const url = new URL('admin_events_daywise.php', location.href);
    url.searchParams.set('days', '10');
    url.searchParams.set('eventType', eventType);
    const res = await fetch(url, {cache:'no-store'});
    const data = await res.json();
    if (!data.ok) throw new Error(data.error || 'Failed to load day-wise data');

    // fill dropdown once (prepend "All")
    if (!sel.options.length){
      const all = ['All', ...(data.eventTypes || [])];
      sel.innerHTML = all.map(x=>`<option ${x===data.selected?'selected':''}>${x}</option>`).join('');
    }

    // draw/update
    chart?.destroy();
    chart = new Chart(cv, {
      type:'bar',
      data:{
        labels: data.labels,
        datasets:[
          { label:'Organic', data:data.organic, backgroundColor:'#34d399' },
          { label:'Other',   data:data.other,   backgroundColor:'#94a3b8' }
        ]
      },
      options:{
        maintainAspectRatio:false,
        responsive:true,
        scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } },
        plugins:{ legend:{ position:'bottom' } }
      }
    });
  }

  sel.addEventListener('change', ()=>load(sel.value));
  load().catch(err=>{
    const ctx = cv.getContext('2d');
    ctx.fillStyle = '#b91c1c'; ctx.font = '12px monospace'; ctx.textAlign='center';
    ctx.fillText(String(err.message || err).slice(0,180), cv.width/2, cv.height/2);
    console.error(err);
  });
})();
</script>


<script>
(() => {
  // plugin to write % in the middle of the semi-gauges
  const centerText = {
    id: 'centerText',
    afterDraw(chart, args, opts) {
      if (!opts || typeof opts.text !== 'string') return;
      const {ctx} = chart;
      ctx.save();
      ctx.font = '600 18px system-ui, Roboto, Arial';
      ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--ink') || '#0f172a';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText(opts.text, chart.width/2, chart.height/2);
      ctx.restore();
    }
  };
  Chart.register(centerText);

  const fmtPct = (x)=> (Math.round(x*10000)/100).toFixed(2) + '%';
  const paintError = (msg) => {
    document.querySelectorAll('#byOrganizer,#statusDonut,#inviteeResponses,#venueTreemap,#gaugeSuccess,#gaugeCancel')
      .forEach(cv => {
        const ctx = cv.getContext('2d');
        ctx.clearRect(0,0,cv.width,cv.height);
        ctx.fillStyle = '#b91c1c';
        ctx.font = '12px monospace';
        ctx.textAlign = 'center';
        ctx.fillText(String(msg).slice(0,180), cv.width/2, cv.height/2);
      });
  };

  fetch('admin_events_overview.php', { cache: 'no-store' })
    .then(async r => {
      const t = await r.text();
      try { return JSON.parse(t); }
      catch { throw new Error("admin_events_overview.php returned non-JSON:\n" + t.slice(0,200)); }
    })
    .then(data => {
      if (!data.ok) throw new Error(data.error || 'Failed to load');

      // ===== KPIs =====
      document.getElementById('kpi-successful').textContent   = data.kpis.successful;
      document.getElementById('kpi-pending').textContent      = data.kpis.pending;
      document.getElementById('kpi-success-rate').textContent = fmtPct(data.kpis.successRate);
      document.getElementById('kpi-cancel-rate').textContent  = fmtPct(data.kpis.cancelRate);

      // ===== Gauges (semi-doughnuts) =====
      new Chart(document.getElementById('gaugeSuccess'), {
        type: 'doughnut',
        data: { labels:['Success','Remainder'],
          datasets:[{ data:[data.kpis.successRate, 1-data.kpis.successRate], backgroundColor:['#22c55e','#e5e7eb'] }] },
        options: { rotation:-Math.PI, circumference:Math.PI, cutout:'70%',
          plugins:{ legend:{display:false}, centerText:{ text: fmtPct(data.kpis.successRate) } },
          responsive:true, maintainAspectRatio:false
        }
      });

      new Chart(document.getElementById('gaugeCancel'), {
        type: 'doughnut',
        data: { labels:['Cancelled','Other'],
          datasets:[{ data:[data.kpis.cancelRate, 1-data.kpis.cancelRate], backgroundColor:['#ef4444','#e5e7eb'] }] },
        options: { rotation:-Math.PI, circumference:Math.PI, cutout:'70%',
          plugins:{ legend:{display:false}, centerText:{ text: fmtPct(data.kpis.cancelRate) } },
          responsive:true, maintainAspectRatio:false
        }
      });

      // If you have no rows, show a friendly note instead of a blank board
      const total = (data.kpis && data.kpis.total) || 0;
      if (!total) {
        paintError('No events found in the selected range.');
        return;
      }

      // ===== By organizer (horizontal bar) =====
      new Chart(document.getElementById('byOrganizer'), {
        type:'bar',
        data:{ labels:data.organizers.labels,
          datasets:[{ label:'Events', data:data.organizers.values, backgroundColor:'#60a5fa' }] },
        options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false,
          scales:{ x:{ beginAtZero:true, grid:{color:'rgba(15,23,42,.06)'} },
                   y:{ grid:{color:'rgba(15,23,42,.06)'} } },
          plugins:{ legend:{ display:false } }
        }
      });

      // ===== Status donut =====
      new Chart(document.getElementById('statusDonut'), {
        type:'doughnut',
        data:{ labels: data.statuses.labels.map(s=>s[0]?.toUpperCase()+s.slice(1)),
          datasets:[{ data:data.statuses.values, backgroundColor:['#22c55e','#fbbf24','#94a3b8','#ef4444'] }] },
        options:{ responsive:true, maintainAspectRatio:false, cutout:'55%',
          plugins:{ legend:{ position:'bottom' } } }
      });

      // ===== Invitees (stacked horizontal) =====
      new Chart(document.getElementById('inviteeResponses'), {
        type:'bar',
        data:{
          labels:data.invitees.labels,
          datasets:[
            { label:'Accepted',    data:data.invitees.accepted,    backgroundColor:'#22c55e' },
            { label:'Tentative',   data:data.invitees.tentative,   backgroundColor:'#fbbf24' },
            { label:'No Response', data:data.invitees.no_response, backgroundColor:'#94a3b8' },
            { label:'Declined',    data:data.invitees.declined,    backgroundColor:'#ef4444' }
          ]
        },
        options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false,
          scales:{ x:{ stacked:true, beginAtZero:true, grid:{color:'rgba(15,23,42,.06)'} },
                   y:{ stacked:true, grid:{color:'rgba(15,23,42,.06)'} } },
          plugins:{ legend:{ position:'bottom' } }
        }
      });

      // ===== Venue treemap =====
      new Chart(document.getElementById('venueTreemap'), {
        type: 'treemap',
        data: {
          datasets: [{
            tree: data.venues.map(v => ({category:v.v, value:v.c})),
            key: 'value', groups: ['category'], spacing: 1,
            borderWidth: 1, borderColor: 'rgba(15,23,42,.12)',
            backgroundColor: ctx => {
              const i = ctx.dataIndex, colors =
                ['#60a5fa','#a78bfa','#34d399','#f59e0b','#f472b6','#22d3ee','#f87171','#84cc16'];
              return colors[i % colors.length];
            },
            labels: { display: true, formatter: c => `${c.raw.g} (${c.raw.v})` }
          }]
        },
        options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } } }
      });

    })
    .catch(err => {
      console.error(err);
      paintError(err.message || err);
    });
})();
</script>
<script>
(function(){
  const el = document.getElementById('tzClock'); if (!el) return;
  const tz = el.dataset.tz || 'UTC';
  function tick(){
    el.textContent = new Date().toLocaleString([], {
      timeZone: tz, weekday:'short', month:'short', day:'numeric', year:'numeric',
      hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:true
    });
  }
  tick(); setInterval(tick, 1000);
})();
</script>


  <script src="./admin_logout.js?v=1"></script>
</body>
</html>
