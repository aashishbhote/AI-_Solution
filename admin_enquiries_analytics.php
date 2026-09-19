<?php
// admin_enquiries_analytics.php
// JSON analytics for the dashboard (works with either PDO $pdo or mysqli $conn)

declare(strict_types=1);
header('Content-Type: application/json');

require __DIR__ . '/db_connection.php';   // must define $pdo (PDO) or $conn (mysqli)

// --- DB adapter detection ---
$isPDO    = (isset($pdo) && $pdo instanceof PDO);
$isMySQLi = (isset($conn) && $conn instanceof mysqli);

if (!$isPDO && !$isMySQLi) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'No DB connection found. Ensure db_connection.php sets $pdo or $conn.']);
  exit;
}

// ---- helpers ----
function tableHasCol($db, bool $isPDO, string $table, string $col): bool {
  $sql = "SHOW COLUMNS FROM `$table` LIKE '$col'";
  if ($isPDO) {
    $st = $db->query($sql);
    return (bool)$st->fetch(PDO::FETCH_ASSOC);
  } else {
    $rs = $db->query($sql);
    $ok = $rs && $rs->num_rows > 0;
    $rs && $rs->free();
    return $ok;
  }
}

// ---- config ----
$months = max(1, (int)($_GET['months'] ?? 6));
$table  = 'contact_submissions';

// Detect date field we should use (your table already has submission_date)
if ($isPDO) {
  $hasSubmissionDate = tableHasCol($pdo, true,  $table, 'submission_date');
  $hasCreatedAt      = tableHasCol($pdo, true,  $table, 'created_at');
} else {
  $hasSubmissionDate = tableHasCol($conn, false, $table, 'submission_date');
  $hasCreatedAt      = tableHasCol($conn, false, $table, 'created_at');
}
$dateField = $hasSubmissionDate ? 'submission_date' : ($hasCreatedAt ? 'created_at' : null);
if ($dateField === null) {
  // last resort: fake zero-filled series and counts by reading all rows w/o date
  $labels = []; $data = [];
  $start = new DateTime('first day of -' . ($months - 1) . ' month 00:00:00');
  $cursor = clone $start;
  for ($i=0; $i<$months; $i++) { $labels[] = $cursor->format('M Y'); $data[] = 0; $cursor->modify('+1 month'); }
  $read = $unread = 0; $topLabels=[]; $topData=[];
  echo json_encode([
    'ok' => true,
    'dateField' => null,
    'range' => $months.'m',
    'overTime' => ['labels'=>$labels,'data'=>$data,'from'=>null,'to'=>null],
    'readSplit' => ['unread'=>$unread,'read'=>$read],
    'topCompanies' => ['labels'=>$topLabels,'data'=>$topData],
    'byMonth' => array_map(fn($i)=>['label'=>$labels[$i],'count'=>$data[$i]], array_keys($labels)),
    'kpis' => ['total'=>0,'thisMonth'=>0,'lastMonth'=>0,'unread'=>0,'read'=>0],
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

/* ===== time window: first day of N-1 months ago .. last day of this month ===== */
$start = new DateTime('first day of -' . ($months - 1) . ' month 00:00:00');
$end   = new DateTime('last day of this month 23:59:59');
$startStr = $start->format('Y-m-d H:i:s');
$endStr   = $end->format('Y-m-d H:i:s');

/* ===== Enquiries over time (zero-filled) ===== */
$raw = []; // ym => count
$sqlOver = "
  SELECT DATE_FORMAT($dateField,'%Y-%m') AS ym, COUNT(*) AS c
  FROM `$table`
  WHERE `$dateField` BETWEEN ? AND ?
  GROUP BY ym
  ORDER BY ym ASC
";
if ($isPDO) {
  $st = $pdo->prepare($sqlOver);
  $st->execute([$startStr, $endStr]);
  while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $raw[$r['ym']] = (int)$r['c'];
  }
} else {
  $st = $conn->prepare($sqlOver);
  $st->bind_param('ss', $startStr, $endStr);
  $st->execute();
  $res = $st->get_result();
  while ($r = $res->fetch_assoc()) {
    $raw[$r['ym']] = (int)$r['c'];
  }
  $res && $res->free(); $st->close();
}

// Build labels/data
$labels = []; $data = [];
$cursor = clone $start;
for ($i=0; $i<$months; $i++) {
  $ym = $cursor->format('Y-m');
  $labels[] = $cursor->format('M Y');
  $data[]   = $raw[$ym] ?? 0;
  $cursor->modify('+1 month');
}

/* ===== Read vs Unread ===== */
$read = $unread = 0;
$sqlRU = "SELECT is_read, COUNT(*) c FROM `$table` GROUP BY is_read";
if ($isPDO) {
  foreach ($pdo->query($sqlRU) as $r) {
    ((int)$r['is_read'] === 1) ? $read = (int)$r['c'] : $unread = (int)$r['c'];
  }
} else {
  if ($rs = $conn->query($sqlRU)) {
    while ($r = $rs->fetch_assoc()) {
      ((int)$r['is_read'] === 1) ? $read = (int)$r['c'] : $unread = (int)$r['c'];
    }
    $rs->free();
  }
}

/* ===== Top companies ===== */
$sqlTop = "
  SELECT COALESCE(NULLIF(TRIM(company),''), '(no company)') AS label, COUNT(*) AS c
  FROM `$table`
  GROUP BY label
  ORDER BY c DESC
  LIMIT 8
";
$topLabels = []; $topData = [];
if ($isPDO) {
  foreach ($pdo->query($sqlTop) as $r) {
    $topLabels[] = $r['label'];
    $topData[]   = (int)$r['c'];
  }
} else {
  if ($rs = $conn->query($sqlTop)) {
    while ($r = $rs->fetch_assoc()) {
      $topLabels[] = $r['label'];
      $topData[]   = (int)$r['c'];
    }
    $rs->free();
  }
}

/* ===== KPIs + monthly table ===== */
$total = array_sum($data);
$thisMonth = $data ? $data[count($data)-1] : 0;
$lastMonth = (count($data) >= 2) ? $data[count($data)-2] : 0;

echo json_encode([
  'ok' => true,
  'dateField' => $dateField,
  'range' => $months.'m',
  'overTime' => [
    'labels' => $labels,
    'data'   => $data,
    'from'   => $startStr,
    'to'     => $endStr,
  ],
  'readSplit' => [
    'unread' => $unread,
    'read'   => $read,
  ],
  'topCompanies' => [
    'labels' => $topLabels,
    'data'   => $topData,
  ],
  'byMonth' => array_map(function($i) use ($labels, $data) {
    return ['label'=>$labels[$i], 'count'=>$data[$i]];
  }, array_keys($labels)),
  'kpis' => [
    'total'      => $total,
    'thisMonth'  => $thisMonth,
    'lastMonth'  => $lastMonth,
    'unread'     => $unread,
    'read'       => $read,
  ],
], JSON_UNESCAPED_UNICODE);
