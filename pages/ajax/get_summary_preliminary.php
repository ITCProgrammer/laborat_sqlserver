<?php
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . "/../../koneksi.php";

/* --- default: 30 hari terakhir (zona Asia/Jakarta) --- */
date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');
$last30 = date('Y-m-d', strtotime('-30 days', strtotime($today)));

$from = isset($_GET['from']) ? trim($_GET['from']) : '';
$to   = isset($_GET['to'])   ? trim($_GET['to'])   : '';

$valid = function($d){
  return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
};

/* tukar kalau from > to */
if ($from !== '' && $to !== '' && $valid($from) && $valid($to) && $from > $to){
  $tmp = $from; $from = $to; $to = $tmp;
}

$where = [];
$params = [];

/* jika user tidak kirim from/to, pakai default 30 hari terakhir */
if (($from === '' || !$valid($from)) && ($to === '' || !$valid($to))) {
  $from = $last30;
  $to   = $today;
}

if ($from !== '' && $valid($from)){
  $where[] = "tgl >= ?";
  $params[] = $from;
}
if ($to !== '' && $valid($to)){
  $where[] = "tgl <= ?";
  $params[] = $to;
}

$sql = "SELECT * FROM db_laborat.summary_preliminary";
if (!empty($where)){
  $sql .= " WHERE ".implode(" AND ", $where);
}
$sql .= " ORDER BY tgl DESC, jam DESC, id DESC";

$stmt = sqlsrv_query($con, $sql, $params);
if ($stmt === false){
  echo json_encode(["ok"=>false, "message"=>sqlsrv_errors()]); exit;
}

$data = [];
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
  if (isset($row['tgl']) && $row['tgl'] instanceof DateTimeInterface){
    $row['tgl'] = $row['tgl']->format('Y-m-d');
  }
  
  if (isset($row['jam']) && $row['jam'] instanceof DateTimeInterface){
    $row['jam'] = $row['jam']->format('H:i');
  }
  $data[] = $row;
}

echo json_encode(["ok"=>true,"data"=>$data]);
