<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solutions</title>
</head>
<body>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="./style.css?v=2">
     <link rel="stylesheet" href="./solutions.css?=v3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <title>Rapid prototyping</title>
</head>
<body>
    <nav>
        <ul >
            <div class="logo">
                <img src="./ai.jpg" alt="AI-soultions logo">
            </div>
            
        </ul>
        <ul >
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

<script>
    // Dropdown behavior
    const dropdown = document.querySelector('nav .dropdown');
    if (dropdown) {
      const trigger = dropdown.querySelector('a.links');
      const menu = dropdown.querySelector('.dropdown-menu');

      trigger.addEventListener('click', (e) => {
        if (!dropdown.classList.contains('open')) e.preventDefault();
        const nowOpen = !dropdown.classList.contains('open');
        dropdown.classList.toggle('open', nowOpen);
        trigger.setAttribute('aria-expanded', nowOpen ? 'true' : 'false');
      });

      document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target)) {
          dropdown.classList.remove('open');
          trigger.setAttribute('aria-expanded', 'false');
        }
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          dropdown.classList.remove('open');
          trigger.setAttribute('aria-expanded', 'false');
          trigger.focus();
        }
      });
    }
  </script>
    <div class="aboutimg_container">
<img src="./analytics.jpg" alt="Analytics and Insights illustration" />        <section class="part1">
          <p class="p1_one">Analytics & Insights</p>
          <p class="p2_two">Turn data into decisions. Real-time dashboards track enquiries, workflows, and performance, revealing trends and bottlenecks so leaders can allocate resources and improve outcomes..</p>
        </section>
    </div>

 
    <section class="onboarding">
      <div class="copy1">
        <div class="copy">
          <h2>Real-Time Dashboards</h2>
          <p>
            AI-Solutions delivers dynamic, real-time dashboards that update instantly as new data flows into the system. These dashboards consolidate information from multiple sources such as employee activity, customer enquiries, and overall system performance. Leaders and managers gain immediate visibility into how their operations are performing at any given moment. This allows them to quickly identify issues, measure productivity, and make informed adjustments without waiting for manual reports. The result is a more responsive and agile decision-making process that keeps organizations ahead of potential problems.
          </p>
          
        </div>

        <div class="visual">
          <!-- replace with your phones image -->
          <img src="./analysis.png" alt="Mobile onboarding screens">
        </div>
      </div>


      <div class="copy2">
  <!-- IMAGE must be a sibling of .copy -->
  <div class="visual">
    <img src="./track.png" alt="Mobile onboarding screens">
  </div>

  <div class="copy">
    <h2>Workflow & Performance Tracking</h2>
    <p>
      Another important feature of AI-Solutions’ Analytics & Insights is workflow and performance tracking. The system analyzes each step of a business process from start to finish, giving leaders a clear view of how work moves through the organization. By doing this, it identifies delays, inefficiencies, redundancies, or underperforming areas that may be slowing down operations. These insights help organizations streamline processes, remove bottlenecks, and improve overall efficiency. This continuous monitoring ensures that resources are being used effectively and that employees remain focused on tasks that create the most value for the business.
    </p>
  </div>
</div>


      

      <div class="copy3">
        <div class="copy">
          <h2>Predictive Analytics </h2>
          <p>
            
            Moving beyond traditional reporting, AI-Solutions integrates predictive analytics powered by advanced AI models. Instead of only showing what has already happened, the system can forecast upcoming trends and likely outcomes. For example, it can predict peak enquiry times, anticipate workload surges, or even highlight potential system risks before they occur. This forward-looking approach helps organizations plan better, prepare resources in advance, and respond proactively rather than reactively. By combining historical data with machine learning, predictive analytics empowers leaders to make smarter strategic decisions that improve both short-term performance and long-term business outcomes.
          </p>
          
        </div>

        <div class="visual">
          <!-- replace with your phones image -->
          <img src="./predictive.png" alt="Mobile onboarding screens">
        </div>
      </div>

</section>



    <div class="solutioncase-card">
        <div class="head_details">
            <h3>USE CASE</h3>
            <h2> <i class="fa-brands fa-hire-a-helper">  Insight360</i> </h2>
            <p>
             Insight360 unifies operational metrics and conversation signals into a single view, giving teams clear visibility to act quickly and confidently.
            </p>
        </div>   
        <div class="numeric">
            <div><h1>100%</h1><p>Real-Time Tracking</p></div>
            <div><h1>30%</h1><p>Productivity Lift</p></div>
            <div><h1>50+</h1><p>Metrics Monitored</p></div> 
            <div><h1>1 View</h1><p>Unified Dashboard</p></div> 
        </div>
    </div>

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
            <li><a href="./photos.php" class="links">Photos</a></li>
        <li><a href="testimonials.php">Portfolios</a></li>
            <li><button class="contact-btn"><a href="./contact.php">Contact</a></button></li>

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

   

<script>
  // Mobile/click toggle for the nav dropdown
  const dropdown = document.querySelector('nav .dropdown');
  if (dropdown) {
    const trigger = dropdown.querySelector('a.links');
    const menu = dropdown.querySelector('.dropdown-menu');

    // Toggle on click
    trigger.addEventListener('click', (e) => {
      // If you still want the "Our Solutions" page to be navigable,
      // prevent only when menu is closed so first tap opens, second tap follows link.
      if (!dropdown.classList.contains('open')) {
        e.preventDefault();
      }
      const nowOpen = !dropdown.classList.contains('open');
      dropdown.classList.toggle('open', nowOpen);
      trigger.setAttribute('aria-expanded', nowOpen ? 'true' : 'false');
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
      if (!dropdown.contains(e.target)) {
        dropdown.classList.remove('open');
        trigger.setAttribute('aria-expanded','false');
      }
    });

    // Close on Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        dropdown.classList.remove('open');
        trigger.setAttribute('aria-expanded','false');
        trigger.focus();
      }
    });
  }
</script>
</body>
</html>