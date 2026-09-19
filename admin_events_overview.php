<?php
// admin_events_overview.php
declare(strict_types=1);

/* Always return JSON */
while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors','0'); ini_set('html_errors','0'); error_reporting(E_ALL);

try {
  /* ---- DB connection (try a few likely locations) ---- */
  $paths = [
    __DIR__ . '/ai_solution/db_connection.php',
    __DIR__ . '/db_connection.php',
    dirname(__DIR__) . '/ai_solution/db_connection.php'
  ];
  $ok = false;
  foreach ($paths as $p) {
    if (is_file($p)) { require $p; $ok = true; break; }
  }
  if (!$ok || !isset($pdo)) {
    throw new RuntimeException('db_connection.php not found, or $pdo is missing.');
  }
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  /* ---- sanity: required tables ---- */
  $q = $pdo->prepare("
    SELECT TABLE_NAME FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME IN ('calendar_event','event_attendee')
  ");
  $q->execute();
  $have = $q->fetchAll(PDO::FETCH_COLUMN);
  if (!in_array('calendar_event', $have, true) || !in_array('event_attendee', $have, true)) {
    echo json_encode([
      'ok'    => false,
      'error' => 'Required tables not found. Please create `calendar_event` and `event_attendee` (see setup SQL).'
    ]);
    exit;
  }

  /* ---- Params & range ---- */
  date_default_timezone_set('Asia/Kathmandu');
  $from = $_GET['from'] ?? (new DateTime('-60 days'))->format('Y-m-d');
  $to   = $_GET['to']   ?? (new DateTime('now'))->format('Y-m-d');
  $f = $from . ' 00:00:00';
  $t = $to   . ' 23:59:59';

  /* ---- 1) Events by organizer ---- */
  $st = $pdo->prepare("
    SELECT organizer, COUNT(*) AS c
    FROM calendar_event
    WHERE start_at BETWEEN :f AND :t
    GROUP BY organizer
    ORDER BY c DESC
  ");
  $st->execute([':f'=>$f, ':t'=>$t]);
  $orgL = []; $orgV = [];
  foreach ($st as $r) { $orgL[] = (string)$r['organizer']; $orgV[] = (int)$r['c']; }

  /* ---- 2) Status distribution + KPI tallies ---- */
  $st = $pdo->prepare("
    SELECT status, COUNT(*) AS c
    FROM calendar_event
    WHERE start_at BETWEEN :f AND :t
    GROUP BY status
  ");
  $st->execute([':f'=>$f, ':t'=>$t]);
  $stL = []; $stV = [];
  $total = 0; $completed = 0; $cancelled = 0; $pending = 0;
  foreach ($st as $r) {
    $status = (string)$r['status'];
    $count  = (int)$r['c'];
    $stL[]  = $status;
    $stV[]  = $count;
    $total += $count;
    if ($status === 'completed') { $completed += $count; }
    if ($status === 'cancelled') { $cancelled += $count; }
    if ($status === 'pending' || $status === 'scheduled') { $pending += $count; }
  }
  // success rate excludes pending/scheduled, avoid div-by-zero
  $considered   = max(1, $completed + $cancelled);
  $success_rate = $completed / $considered;
  $cancel_rate  = $total > 0 ? $cancelled / $total : 0.0;

  /* ---- 3) Top 5 invitees & their responses (FIXED ORDER BY) ---- */
  $st = $pdo->prepare("
    SELECT
      a.invitee,
      SUM(CASE WHEN a.response = 'accepted'    THEN 1 ELSE 0 END) AS accepted,
      SUM(CASE WHEN a.response = 'tentative'   THEN 1 ELSE 0 END) AS tentative,
      SUM(CASE WHEN a.response = 'no_response' THEN 1 ELSE 0 END) AS no_response,
      SUM(CASE WHEN a.response = 'declined'    THEN 1 ELSE 0 END) AS declined,
      COUNT(*) AS total
    FROM event_attendee a
    JOIN calendar_event e ON e.id = a.event_id
    WHERE e.start_at BETWEEN :f AND :t
    GROUP BY a.invitee
    ORDER BY total DESC
    LIMIT 5
  ");
  $st->execute([':f'=>$f, ':t'=>$t]);
  $invL = []; $acc = []; $ten = []; $nr = []; $dec = [];
  foreach ($st as $r) {
    $invL[] = (string)$r['invitee'];
    $acc[]  = (int)$r['accepted'];
    $ten[]  = (int)$r['tentative'];
    $nr[]   = (int)$r['no_response'];
    $dec[]  = (int)$r['declined'];
  }

  /* ---- 4) Venue counts (treemap) ---- */
  $st = $pdo->prepare("
    SELECT COALESCE(venue,'Unknown') AS venue, COUNT(*) AS c
    FROM calendar_event
    WHERE start_at BETWEEN :f AND :t
    GROUP BY venue
    ORDER BY c DESC
  ");
  $st->execute([':f'=>$f, ':t'=>$t]);
  $venues = [];
  foreach ($st as $r) {
    $venues[] = ['v' => ($r['venue'] ?: 'Unknown'), 'c' => (int)$r['c']];
  }

  /* ---- 5) KPIs ---- */
  $kpis = [
    'total'       => (int)$total,
    'successful'  => (int)$completed,
    'pending'     => (int)$pending,
    'successRate' => (float)$success_rate, // 0..1
    'cancelRate'  => (float)$cancel_rate   // 0..1
  ];

  /* ---- Output ---- */
  echo json_encode([
    'ok'         => true,
    'range'      => ['from'=>$from, 'to'=>$to],
    'organizers' => ['labels'=>$orgL, 'values'=>$orgV],
    'statuses'   => ['labels'=>$stL, 'values'=>$stV],
    'kpis'       => $kpis,
    'invitees'   => [
      'labels'      => $invL,
      'accepted'    => $acc,
      'tentative'   => $ten,
      'no_response' => $nr,
      'declined'    => $dec
    ],
    'venues'     => $venues
  ]);
} catch (Throwable $e) {
  http_response_code(200);
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
