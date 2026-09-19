<?php
// admin_events_analytics.php — uses ai_solution/db_connection.php
declare(strict_types=1);

/* JSON-only output + error hardening */
while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors','0'); ini_set('html_errors','0'); error_reporting(E_ALL);
set_error_handler(function($sev,$msg,$file,$line){
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>"PHP: $msg",'at'=>basename($file).":$line"]); exit;
});
register_shutdown_function(function(){
  $e = error_get_last();
  if ($e && in_array($e['type'], [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR])) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>"Fatal: {$e['message']}",'at'=>basename($e['file']).":{$e['line']}"]);
  }
});

/* ---- include your DB connection ----
   Adjust the fallback paths if your file lives elsewhere. */
$tried = [];
$paths = [
  __DIR__ . '/db_connection.php',
  __DIR__ . '/ai_solution/db_connection.php',
  dirname(__DIR__) . '/ai_solution/db_connection.php',
];
$ok = false;
foreach ($paths as $p) {
  $tried[] = $p;
  if (is_file($p)) { require $p; $ok = true; break; }
}
if (!$ok) {
  echo json_encode(['ok'=>false,'error'=>'db_connection.php not found','tried'=>$tried]); exit;
}
if (!isset($pdo) || !($pdo instanceof PDO)) {
  echo json_encode(['ok'=>false,'error'=>'$pdo not defined by db_connection.php']); exit;
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
  // Auto-detect table name; create demo table/rows if requested
  $table = null;
  $q = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('event','events')");
  $names = $q->fetchAll(PDO::FETCH_COLUMN);
  if (in_array('event',$names,true))      $table = 'event';
  elseif (in_array('events',$names,true)) $table = 'events';

  // Optional: seed table if not present -> /admin_events_analytics.php?seed=1
  if (!$table && isset($_GET['seed'])) {
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS `event` (
        id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id      BIGINT UNSIGNED NULL,
        event_type   VARCHAR(64)     NOT NULL,
        channel      VARCHAR(64)     NULL,
        source       VARCHAR(64)     NULL,
        offer_name   VARCHAR(64)     NULL,
        occurred_at  DATETIME        NOT NULL,
        PRIMARY KEY (id),
        INDEX idx_event_occurred_at (occurred_at),
        INDEX idx_event_type (event_type),
        INDEX idx_event_channel (channel)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $pdo->exec("
      INSERT INTO `event` (user_id,event_type,channel,source,offer_name,occurred_at) VALUES
      (1,'Home','Channel 1','Organic',NULL,               NOW() - INTERVAL 7 DAY),
      (1,'Registration','Channel 1','Organic','Welcome Offer', NOW() - INTERVAL 7 DAY + INTERVAL 10 MINUTE),
      (2,'Home','Channel 2','Paid',NULL,                  NOW() - INTERVAL 6 DAY),
      (2,'ContestList','Channel 2','Paid','Special Offer',NOW() - INTERVAL 6 DAY + INTERVAL 1 HOUR),
      (2,'Contest_Play','Channel 2','Paid','Special Offer',NOW() - INTERVAL 6 DAY + INTERVAL 70 MINUTE),
      (3,'Home','Channel 3','Referral',NULL,              NOW() - INTERVAL 5 DAY),
      (3,'Share','Channel 3','Referral','Champion Offer', NOW() - INTERVAL 5 DAY + INTERVAL 2 HOUR),
      (4,'Home','Channel 4','Organic',NULL,               NOW() - INTERVAL 4 DAY),
      (4,'Contest_Summary','Channel 4','Organic','Exclusive Offer', NOW() - INTERVAL 4 DAY + INTERVAL 45 MINUTE),
      (5,'Home','Channel 2','Paid','One Time Offer',      NOW() - INTERVAL 3 DAY),
      (5,'Registration','Channel 2','Paid','One Time Offer', NOW() - INTERVAL 3 DAY + INTERVAL 6 MINUTE),
      (6,'Home','Channel 1','Organic',NULL,               NOW() - INTERVAL 2 DAY),
      (6,'ContestList','Channel 1','Organic',NULL,        NOW() - INTERVAL 2 DAY + INTERVAL 40 MINUTE),
      (7,'Home','Channel 3','Organic',NULL,               NOW() - INTERVAL 1 DAY),
      (7,'Registration','Channel 3','Organic','Welcome Offer', NOW() - INTERVAL 1 DAY + INTERVAL 5 MINUTE);
    ");
    $table = 'event';
  }

  if (!$table) {
    echo json_encode(['ok'=>false,'error'=>'No `event` or `events` table found. Hit this endpoint once with `?seed=1` to create demo data.']);
    exit;
  }

  // Inputs (default last 30 days)
  date_default_timezone_set('Asia/Kathmandu');
  $from = $_GET['from'] ?? (new DateTime('-30 days'))->format('Y-m-d');
  $to   = $_GET['to']   ?? (new DateTime('now'))     ->format('Y-m-d');
  $fromStart = "$from 00:00:00";
  $toEnd     = "$to 23:59:59";

  // Labels = distinct event types
  $st = $pdo->prepare("SELECT DISTINCT event_type
                         FROM `$table`
                        WHERE occurred_at BETWEEN :f AND :t
                        ORDER BY event_type");
  $st->execute([':f'=>$fromStart, ':t'=>$toEnd]);
  $types = $st->fetchAll(PDO::FETCH_COLUMN);
  if (!$types) { echo json_encode(['ok'=>true,'labels'=>[],'datasets'=>[],'total'=>0,'table'=>$table]); exit; }

  // Counts by type × channel
  $st = $pdo->prepare("SELECT event_type, COALESCE(channel,'Unknown') AS channel, COUNT(*) c
                         FROM `$table`
                        WHERE occurred_at BETWEEN :f AND :t
                        GROUP BY event_type, channel");
  $st->execute([':f'=>$fromStart, ':t'=>$toEnd]);
  $rows = $st->fetchAll();

  $channels = [];
  foreach ($rows as $r) $channels[$r['channel']] = true;
  $channels = array_keys($channels); sort($channels, SORT_NATURAL);

  $series = [];
  foreach ($channels as $ch) $series[$ch] = array_fill(0, count($types), 0);
  foreach ($rows as $r) {
    $i = array_search($r['event_type'], $types, true);
    if ($i !== false) $series[$r['channel']][$i] = (int)$r['c'];
  }

  $datasets = [];
  $total = 0;
  foreach ($series as $ch=>$vals) {
    $datasets[] = ['label'=>$ch,'data'=>$vals];
    foreach ($vals as $v) $total += $v;
  }

  echo json_encode(['ok'=>true,'labels'=>$types,'datasets'=>$datasets,'total'=>$total,'table'=>$table]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
