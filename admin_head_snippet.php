<?php /* Admin theme boot + design tokens (load this in <head> on every admin page) */ ?>
<script>
/* Boot: read saved prefs and apply BEFORE CSS to avoid flash */
(function () {
  var ls   = localStorage;
  var html = document.documentElement;

  var theme   = ls.getItem('admin.theme')   || html.getAttribute('data-theme') || 'auto';
  var accent  = ls.getItem('admin.accent')  || '#2563eb';
  var density = ls.getItem('admin.density') || 'comfortable';
  var rm      = ls.getItem('admin.rm') || '0';

  function resolve(t){
    return t === 'auto'
      ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
      : t;
  }

  html.setAttribute('data-theme', theme);
  html.setAttribute('data-resolved-theme', resolve(theme));
  html.setAttribute('data-density', density);
  if (rm === '1') html.setAttribute('data-reduced-motion','1');
  html.style.setProperty('--accent', accent);
})();
</script>

<style>
/* ==== Design tokens (light by default) ==== */
:root{
  --accent: #2563eb;

  --text:   #0f172a;           /* slate-900 */
  --muted:  #64748b;           /* slate-500/600 */
  --border: rgba(15,23,42,.12);

  --card: #ffffff;
  --page: #f8fafc;

  --ring: 0 0 0 3px color-mix(in lab, var(--accent) 35%, transparent);
  --shadow-1: 0 12px 36px -16px rgba(2,6,23,.15);
}

/* Dark (works for explicit dark OR auto+resolved=dark) */
:root[data-theme="dark"],
:root[data-theme="auto"][data-resolved-theme="dark"]{
  --text:   #e5e7eb;           /* slate-200 */
  --muted:  #9aa4b2;           /* slate-400 */
  --border: rgba(255,255,255,.12);

  --card: #0f172a;             /* slate-900 */
  --page: #0b1220;             /* deep navy */

  --shadow-1: 0 20px 60px -20px rgba(0,0,0,.5);
}

/* Optional compact density */
:root[data-density="compact"]{
  /* use these if you want denser spacing in your CSS */
  --space-1: 6px;
  --space-2: 8px;
}

/* Respect reduced motion */
:root[data-reduced-motion="1"]{
  --motion: 0s !important;
}

/* Generic focus color */
*:focus{ outline-color: color-mix(in lab, var(--accent) 50%, transparent); }
</style>
