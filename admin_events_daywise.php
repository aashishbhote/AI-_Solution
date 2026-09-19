<?php
// admin_events_daywise.php
declare(strict_types=1);

/* JSON-only output + simple error handling */
while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors','0'); ini_set('html_errors','0'); error_reporting(E_ALL);
set_error_handler(function($s,$m,$f,$l){ http_response_code(500); echo json_encode(['ok'=>false,'error'=>"PHP: $m",'at'=>basename($f).":$l"]); exit; });

/* include your PDO */
$paths = [
  __DIR__.'/ai_solution/db_connection.php',
  __DIR__.'/db_connection.php',
  dirname(__DIR__).'/ai_solution/db_connection.php'
];
$ok=false; foreach($paths as $p){ if (is_file($p)){ require $p; $ok=true; break; } }
if (!$ok || !isset($pdo)) { echo json_encode(['ok'=>false,'error'=>'db_connection.php not found or $pdo missing']); exit; }
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* inputs */
date_default_timezone_set('Asia/Kathmandu');
$days = max(1, min(90, (int)($_GET['days'] ?? 10)));  // 1..90 days
$eventType = trim($_GET['eventType'] ?? 'All');       // 'All' or specific type

/* which table: event or events */
$tq = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES
                   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('event','events')");
$names = $tq->fetchAll(PDO::FETCH_COLUMN);
$table = in_array('event',$names,true) ? 'event' : (in_array('events',$names,true) ? 'events' : null);
if (!$table) { echo json_encode(['ok'=>false,'error'=>'No `event` or `events` table found']); exit; }

$from = (new DateTime("-$days days"))->format('Y-m-d 00:00:00');
$to   = (new DateTime('now'))        ->format('Y-m-d 23:59:59');

/* event types for the dropdown */
$types = $pdo->query("SELECT DISTINCT event_type FROM `$table` ORDER BY event_type")->fetchAll(PDO::FETCH_COLUMN);

/* build WHERE */
$where = "occurred_at BETWEEN :f AND :t";
$params = [':f'=>$from, ':t'=>$to];
if ($eventType !== '' && strtolower($eventType) !== 'all') {
  $where .= " AND event_type = :et";
  $params[':et'] = $eventType;
}

/* query */
$sql = "SELECT DATE(occurred_at) d,
               SUM(CASE WHEN source='Organic' THEN 1 ELSE 0 END) AS org,
               SUM(CASE WHEN source <> 'Organic' OR source IS NULL THEN 1 ELSE 0 END) AS oth
        FROM `$table`
        WHERE $where
        GROUP BY DATE(occurred_at)
        ORDER BY d ASC";
$st = $pdo->prepare($sql); $st->execute($params);
$labels=[]; $organic=[]; $other=[];
foreach ($st as $r){ $labels[]=$r['d']; $organic[]=(int)$r['org']; $other[]=(int)$r['oth']; }

echo json_encode([
  'ok'=>true,
  'labels'=>$labels,
  'organic'=>$organic,
  'other'=>$other,
  'eventTypes'=>$types,
  'selected'=>$eventType ?: 'All'
]);
