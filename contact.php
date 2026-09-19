<?php
require_once __DIR__ . "/db_connection.php"; // must define $pdo = new PDO(...)

// Guard: ensure $pdo exists and is a PDO instance
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Database connection not initialized. Make sure db_connection.php defines \$pdo (PDO).");
}

$name = $company = $email = $phone = $message = $country = $jobTitle = $jobDetails = "";
$consentChecked = false;
$errors = [];
$successMsg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Gather inputs
    $name       = trim($_POST['name'] ?? '');
    $company    = trim($_POST['company'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $country    = trim($_POST['country'] ?? '');
    $jobTitle   = trim($_POST['job_title'] ?? '');
    $jobDetails = trim($_POST['job_details'] ?? '');
    $message    = trim($_POST['message'] ?? '');   

    $consentChecked = isset($_POST['consent']);

    // Server-side validation
    if ($name === "") $errors['name'] = "Full name is required.";
    if ($company === "") $errors['company'] = "Company name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Valid email is required.";

    // Phone: allow any formatting but require 7–15 digits overall
    $digits = preg_replace('/\D+/', '', $phone);
    if ($digits === "" || strlen($digits) < 7 || strlen($digits) > 15) {
        $errors['phone'] = "Phone must contain 7–15 digits.";
    }

    if ($country === "")    $errors['country'] = "Country is required.";
    if ($jobTitle === "")   $errors['job_title'] = "Job title is required.";
    if ($jobDetails === "") $errors['job_details'] = "Job details are required.";

    if ($message === "") $errors['message'] = "Message is required.";
    if (!$consentChecked) $errors['consent'] = "You must give consent.";

    // reCAPTCHA verification
    $captchaResponse = $_POST['g-recaptcha-response'] ?? '';
    if ($captchaResponse === '') {
        $errors['captcha'] = "Please complete the reCAPTCHA.";
    } else {
        // TODO: move secret into env or config
        $secret = "6LeRzrsrAAAAAH4Dq_iDHQcqXOsW9bCGinnJR-dQ";
        $verifyUrl = "https://www.google.com/recaptcha/api/siteverify";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $verifyUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'secret'   => $secret,
                'response' => $captchaResponse,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $captchaVerify = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($captchaVerify === false) {
            $errors['captcha'] = "reCAPTCHA check failed. Please try again.";
        } else {
            $captchaResult = json_decode($captchaVerify, true);
            if (!($captchaResult['success'] ?? false)) {
                $errors['captcha'] = "reCAPTCHA verification failed.";
            }
        }
    }

    // Insert if no errors
    if (!$errors) {
        try {
           $stmt = $pdo->prepare(
  "INSERT INTO contact_submissions
   (name, company, email, phone, country, job_title, job_details, message, consent)
   VALUES (:name, :company, :email, :phone, :country, :job_title, :job_details, :message, :consent)"
);

            $stmt->execute([
    ':name'        => $name,
    ':company'     => $company,
    ':email'       => $email,
    ':phone'       => $phone,
    ':country'     => $country,
    ':job_title'   => $jobTitle,
    ':job_details' => $jobDetails,
    ':message'     => $message,
    ':consent'     => $consentChecked ? 1 : 0,
            ]);

            $successMsg = "✅ Thank you! Your message has been submitted.";
$name = $company = $email = $phone = $message = $country = $jobTitle = $jobDetails = "";
$consentChecked = false;


        } catch (Throwable $e) {
            // error_log($e->getMessage()); // optional
            $errors['server'] = "Database error. Please try later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>contact</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="./style.css?v=2">
  <!-- If your file is actually 'contact.css', fix the filename below -->
  <link rel="stylesheet" href="./conatct.css?v=3">
  <style>
    .error { color: #d9534f; font-size: 14px; margin-top: 6px; }
    .success-box { color: #0a7a0a; padding: 10px 0; }
    .error-box { color: #b00020; padding: 10px 0; }
    .form-group { margin-bottom: 12px; }
    .form-check { margin: 12px 0; }
    .submit-btn { cursor: pointer; }
    nav .links.active { text-decoration: underline; }
    nav .dropdown.open > .dropdown-menu { display:block; }
  </style>
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
      <li><a class="contact-btn links" href="./contact.php">Contact</a></li>
    </ul>
  </nav>

  <script>
  // Active link + dropdown active
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

  <div class="contact_container">
    <img src="./company.jpg" alt="">
    <section class="contact_header">
      <p class="contact">Contact</p>
      <h1 class="heading">Get in touch</h1>
      <p class="p1">Connect with our team of experts for demos, requirement analysis and estimates.</p>
    </section>
  </div>

  <header class="page-hero">
    <div class="container">
      <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="./index.php">Home</a> <span>/</span> <span>Contact</span>
      </nav>
    </div>
  </header>

  <div class="main_form">
    <div class="form_container">
      <!-- Errors or success -->
      <?php if (!empty($errors)): ?>
        <div class="error-box" role="alert" aria-live="polite">
          <?php foreach ($errors as $err): ?>
            <p><?php echo htmlspecialchars($err); ?></p>
          <?php endforeach; ?>
        </div>
      <?php elseif (!empty($successMsg)): ?>
        <div class="success-box" role="status" aria-live="polite">
          <p><?php echo htmlspecialchars($successMsg); ?></p>
        </div>
      <?php endif; ?>

      <form id="contactForm" action="contact.php" method="POST" autocomplete="off" novalidate>
        <div class="form-group">
          <label for="name">Full Name</label>
          <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($name); ?>">
          <?php if (!empty($errors['name'])): ?><div class="error"><?php echo htmlspecialchars($errors['name']); ?></div><?php endif; ?>
        </div>

        <div class="form-group">
          <label for="company">Company Name</label>
          <input type="text" id="company" name="company" required value="<?php echo htmlspecialchars($company); ?>">
          <?php if (!empty($errors['company'])): ?><div class="error"><?php echo htmlspecialchars($errors['company']); ?></div><?php endif; ?>
        </div>

        <div class="form-group">
          <label for="email">Company Email</label>
          <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">
          <?php if (!empty($errors['email'])): ?><div class="error"><?php echo htmlspecialchars($errors['email']); ?></div><?php endif; ?>
        </div>

        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input type="tel" id="phone" name="phone" required value="<?php echo htmlspecialchars($phone); ?>">
          <?php if (!empty($errors['phone'])): ?><div class="error"><?php echo htmlspecialchars($errors['phone']); ?></div><?php endif; ?>
        </div>

        <div class="form-group">
  <label for="country">Country</label>
  <input type="text" id="country" name="country" required
         value="<?php echo htmlspecialchars($country); ?>">
  <?php if (!empty($errors['country'])): ?>
    <div class="error"><?php echo htmlspecialchars($errors['country']); ?></div>
  <?php endif; ?>
</div>

<div class="form-group">
  <label for="job_title">Job Title</label>
  <input type="text" id="job_title" name="job_title" required
         value="<?php echo htmlspecialchars($jobTitle); ?>">
  <?php if (!empty($errors['job_title'])): ?>
    <div class="error"><?php echo htmlspecialchars($errors['job_title']); ?></div>
  <?php endif; ?>
</div>

<div class="form-group">
  <label for="job_details">Job Details</label>
  <textarea id="job_details" name="job_details" required><?php
    echo htmlspecialchars($jobDetails);
  ?></textarea>
  <?php if (!empty($errors['job_details'])): ?>
    <div class="error"><?php echo htmlspecialchars($errors['job_details']); ?></div>
  <?php endif; ?>
</div>

        <div class="form-group">
          <label for="message">Message</label>
          <textarea id="message" name="message" required><?php echo htmlspecialchars($message); ?></textarea>
          <?php if (!empty($errors['message'])): ?><div class="error"><?php echo htmlspecialchars($errors['message']); ?></div><?php endif; ?>
        </div>

        <div class="form-check">
          <input type="checkbox" id="consent" name="consent" <?php echo $consentChecked ? 'checked' : ''; ?> required>
          <label for="consent">I allow AI Solutions to store and utilize this information to contact me.</label>
          <?php if (!empty($errors['consent'])): ?><div class="error"><?php echo htmlspecialchars($errors['consent']); ?></div><?php endif; ?>
        </div>

        <!-- Google reCAPTCHA -->
        <div class="g-recaptcha" data-sitekey="6LeRzrsrAAAAACGcCqCjrOOeLNfu4nQvx8zXvPRW"></div>
        <?php if (!empty($errors['captcha'])): ?><div class="error"><?php echo htmlspecialchars($errors['captcha']); ?></div><?php endif; ?>

        <button type="submit" class="submit-btn">Submit</button>
      </form>
    </div>

    <div class="detail_container">
      <h1>Head Office</h1>
      <p>AI SOlutions</p>
      <p>AI solutions Building, Sanepa</p>
      <p>lalitpur Nepal</p>
      <p>T: +9779819206024</p>
      <p>E: info@aisolution.com</p>
      <div class="icon_container">
        <a href="#"><i class="fab fa-facebook-f" aria-label="Facebook"></i></a>
        <a href="#"><i class="fab fa-linkedin-in" aria-label="LinkedIn"></i></a>
        <a href="#"><i class="fab fa-youtube" aria-label="YouTube"></i></a>
      </div>
    </div>
  </div>

  <!-- Google reCAPTCHA script -->
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>

  <script>
  // Mobile/click toggle for the nav dropdown
  const dropdown = document.querySelector('nav .dropdown');
  if (dropdown) {
    const trigger = dropdown.querySelector('a.links');
    const menu = dropdown.querySelector('.dropdown-menu');

    trigger.addEventListener('click', (e) => {
      if (!dropdown.classList.contains('open')) {
        e.preventDefault(); // first tap opens menu
      }
      const nowOpen = !dropdown.classList.contains('open');
      dropdown.classList.toggle('open', nowOpen);
      trigger.setAttribute('aria-expanded', nowOpen ? 'true' : 'false');
    });

    document.addEventListener('click', (e) => {
      if (!dropdown.contains(e.target)) {
        dropdown.classList.remove('open');
        trigger.setAttribute('aria-expanded','false');
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        dropdown.classList.remove('open');
        trigger.setAttribute('aria-expanded','false');
        trigger.focus();
      }
    });
  }
  </script>

  <!-- ===== Pro AI Chat (drop-in) — single copy ===== -->
  <script>
  (function(){
    const ENDPOINT = 'ai/ai-chat-widget.php'; // adjust if different

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
    .ai-row.user .ai-bubble{ background: linear-gradient(135deg, #1f6feb, #2563eb); }
    .ai-row.bot .ai-bubble{ background: linear-gradient(135deg, #243253, #2b3a67); }
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
    @media (max-width: 480px){
      #aiChatPanel{ right:12px; bottom:84px; width:calc(100vw - 24px); height:60vh }
      #aiFab{ right:12px; bottom:12px }
    }`;

    const style = document.createElement('style');
    style.id = 'ai-chat-embed-styles';
    style.textContent = css;
    document.head.appendChild(style);

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

    const msgsEl = panel.querySelector('#aiMsgs');
    const form   = panel.querySelector('#aiForm');
    const input  = panel.querySelector('#aiInput');
    const sendBtn= panel.querySelector('#aiSend');
    const minBtn = panel.querySelector('#aiMinBtn');
    const closeBtn = panel.querySelector('#aiCloseBtn');
    const badge  = fab.querySelector('.ai-badge');

    let open = false;
    const history = [];

    function nowTime(){
      const d = new Date();
      return d.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
    }
    function addMsg(role, text, isTyping=false){
      const row = document.createElement('div');
      row.className = `ai-row ${role}`;
      const bubble = document.createElement('div');
      bubble.className = 'ai-bubble';
      if (isTyping){ bubble.innerHTML = '<span class="ai-typing" aria-label="Assistant is typing"><i></i><i></i><i></i></span>'; }
      else { bubble.textContent = text; }
      const time = document.createElement('span');
      time.className = 'ai-time';
      time.textContent = nowTime();
      bubble.appendChild(time);
      row.appendChild(bubble);
      msgsEl.appendChild(row);
      msgsEl.scrollTop = msgsEl.scrollHeight;
      return {row, bubble};
    }
    function openPanel(){ panel.style.display = 'block'; open = true; badge.style.display = 'none'; setTimeout(()=>input.focus(),0); }
    function closePanel(){ panel.style.display = 'none'; open = false; }

    fab.addEventListener('click', openPanel);
    minBtn.addEventListener('click', closePanel);
    closeBtn.addEventListener('click', closePanel);
    document.addEventListener('keydown', (e)=>{ if(e.key==='Escape' && open) closePanel(); });

    addMsg('bot', 'Hi! How can I help you today?');

    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const text = input.value.trim();
      if(!text) return;
      input.value = ''; input.focus();
      history.push({role:'user', content:text});
      addMsg('user', text);
      sendBtn.disabled = true;
      const typing = addMsg('bot', '', true);

      try{
        const res = await fetch(ENDPOINT, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify({messages: history})
        });
        const data = await res.json();
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
