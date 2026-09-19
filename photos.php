<?php
// photos.php — Customer section gallery
declare(strict_types=1);

require __DIR__ . '/db_connection.php';

if (!function_exists('e')) {
  function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
  }
}

/* Fetch published photos (table has: id, filename, caption, is_active, created_at) */
try {
  $stmt = $pdo->query("
    SELECT 
      id,
      filename AS fname,
      caption,
      is_active,
      created_at
    FROM photos
    WHERE (is_active = 1 OR is_active IS NULL)
    ORDER BY COALESCE(created_at, id) DESC
  ");
  $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $photos = [];
}

/* Helper to build correct src */
function photo_src(?string $fname): string {
  if (!$fname) return '';
  $fname = ltrim($fname);
  // If someone ever stored a full URL, honor it
  if (preg_match('#^https?://#i', $fname)) return $fname;
  // If a relative path was stored (e.g., "uploads/photos/abc.jpg"), keep it
  if (strpos($fname, 'uploads/') === 0) return $fname;
  // Otherwise, assume it's just the filename saved by admin_photos.php
  return 'uploads/photos/' . $fname;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./style.css?=v2">
  <link rel="stylesheet" href="./events.css?=v3">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <title>Event Photos</title>
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
        <a href="./solutions.php" class="links">Our Solutions</a>
        <ul class="dropdown-menu">
          <li><a href="./solutions.php">AI Virtual Assistant</a></li>
          <li><a href="./solutions1.php">Rapid Prototyping</a></li>
          <li><a href="./solutions2.php">Analytics & Insights</a></li>
          <li><a href="./solutions3.php">Security by Design</a></li>
        </ul>
      </li>
      <li><a href="./services.php" class="links">Services</a></li>
      <li><a href="./blogs.php" class="links">Blogs</a></li>
      <li><a href="./events.php" class="links">Events</a></li>
      <li><a href="./photos.php" class="links active">Photos</a></li>
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

  <section class="section" id="gallery" aria-labelledby="gallery-heading">
    <div class="container">
      <div class="section-head">
        <h2 id="gallery-heading">Event Photos</h2>
        <p class="muted">Snapshots from recent demos and sessions.</p>
      </div>

      <div class="gallery-grid">
        <!-- Static photos -->
        <figure><img src="./images1.png" alt="Demo day audience"></figure>
        <figure><img src="./images2.png" alt="Prototype walkthrough"></figure>
        <figure><img src="./images3.png" alt="Assistant Q&A"></figure>
        <figure><img src="./images4.png" alt="Assistant Q&A"></figure>
        <figure><img src="./images5.png" alt="Assistant Q&A"></figure>
        <figure><img src="./images6.png" alt="Assistant Q&A"></figure>
        <figure><img src="./images7.png" alt="Assistant Q&A"></figure>
        <figure><img src="./images8.png" alt="Assistant Q&A"></figure>
        <figure><img src="./images9.png" alt="Assistant Q&A"></figure>
        <figure><img src="./images10.png" alt="Assistant Q&A"></figure>
        <figure><img src="./images11.png" alt="Assistant Q&A"></figure>
        <figure><img src="./images12.png" alt="Assistant Q&A"></figure>

        <!-- Dynamic photos from DB -->
        <?php foreach ($photos as $p): 
              $src = photo_src($p['fname'] ?? '');
              if (!$src) continue; ?>
          <figure>
            <img src="<?= e($src) ?>"
                 alt="<?= e($p['caption'] ?: 'Event photo') ?>"
                 loading="lazy">
            <?php if (!empty($p['caption'])): ?>
              <figcaption><?= e($p['caption']) ?></figcaption>
            <?php endif; ?>
          </figure>
        <?php endforeach; ?>
      </div>
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

  <script>
    document.getElementById('year').textContent = new Date().getFullYear();
  </script>
</body>
</html>
