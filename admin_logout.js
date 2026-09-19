/* assets/logout.js */
(function(){
  // --- styles ---
  const css = `
    .logout-modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;
      background:rgba(2,6,23,.55);z-index:10000;padding:16px}
    .logout-modal[aria-hidden="false"]{display:flex}
    .logout-panel{width:min(420px,92vw);background:#fff;color:#0f172a;border-radius:16px;padding:22px;
      text-align:center;box-shadow:0 24px 64px rgba(2,6,23,.35);animation:pop .15s ease}
    @keyframes pop{from{transform:scale(.98);opacity:0}to{transform:scale(1);opacity:1}}
    .logout-icon{font-size:28px;margin-bottom:6px}
    .logout-actions{display:flex;gap:10px;justify-content:center;margin-top:14px}
    .btn{border:0;border-radius:10px;padding:10px 14px;cursor:pointer;font-weight:600}
    .btn-danger{background:#ef4444;color:#fff}.btn-danger:hover{filter:brightness(.95)}
    .btn-ghost{background:#e5e7eb;color:#111827}.btn-ghost:hover{filter:brightness(.97)}
  `;
  const style = document.createElement('style');
  style.textContent = css;
  document.head.appendChild(style);

  // --- modal ---
  const modal = document.createElement('div');
  modal.className = 'logout-modal';
  modal.setAttribute('aria-hidden','true');
  modal.setAttribute('role','dialog');
  modal.setAttribute('aria-labelledby','logout-title');
  modal.setAttribute('aria-modal','true');
  modal.innerHTML = `
    <div class="logout-panel" role="document" tabindex="-1">
      <div class="logout-icon" aria-hidden="true">⚠️</div>
      <h3 id="logout-title">Log out?</h3>
      <p>Are you sure you want to log out now?</p>
      <div class="logout-actions">
        <button type="button" class="btn btn-danger" id="logoutYes">Yes, log me out</button>
        <button type="button" class="btn btn-ghost"  id="logoutNo">Cancel</button>
      </div>
    </div>
  `;
  document.body.appendChild(modal);

  const panel = modal.querySelector('.logout-panel');
  const yes   = modal.querySelector('#logoutYes');
  const no    = modal.querySelector('#logoutNo');

  let targetHref = 'admin_logout.php';
  let submitForm = null;
  let lastFocus  = null;

  function openModal(){
    lastFocus = document.activeElement;
    modal.setAttribute('aria-hidden','false');
    setTimeout(()=>panel.focus(),0);
    document.addEventListener('keydown', onKeyDown);
  }
  function closeModal(){
    modal.setAttribute('aria-hidden','true');
    document.removeEventListener('keydown', onKeyDown);
    submitForm = null;
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }
  function onKeyDown(e){ if (e.key === 'Escape') closeModal(); }

  // Catch ANY .logout click (link or button)
  document.addEventListener('click', (e)=>{
    const el = e.target.closest('.logout');
    if (!el) return;

    e.preventDefault();

    submitForm = el.closest('form#logoutForm') || null;

    if (el.tagName === 'A' && el.getAttribute('href')) {
      targetHref = el.getAttribute('href');               // e.g., admin_logout.php
    } else if (el.dataset && el.dataset.logout) {
      targetHref = el.dataset.logout;
    } else if (submitForm) {
      targetHref = submitForm.getAttribute('action') || 'admin_logout.php';
    } else {
      targetHref = 'admin_logout.php';
    }

    openModal();
  });

  yes.addEventListener('click', ()=>{
    if (submitForm) submitForm.submit();     // POST form case
    else window.location.href = targetHref;  // typical <a href="admin_logout.php">
  });

  no.addEventListener('click', closeModal);

  // Close on backdrop click
  modal.addEventListener('click', (e)=>{ if (e.target === modal) closeModal(); });
})();
