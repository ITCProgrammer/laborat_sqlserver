<?php
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . "/../../koneksi.php";

date_default_timezone_set('Asia/Jakarta');
$today  = date('Y-m-d');
$last30 = date('Y-m-d', strtotime('-30 days', strtotime($today)));

$from = $_GET['from'] ?? '';
$to   = $_GET['to']   ?? '';

$valid = function($d){ return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d); };

if ($from && $to && $valid($from) && $valid($to) && $from > $to){ [$from,$to] = [$to,$from]; }
if ((!$from || !$valid($from)) && (!$to || !$valid($to))) { $from=$last30; $to=$today; }

$where=[]; $params=[];
if ($from && $valid($from)){ $where[]="tgl >= ?"; $params[]=$from; }
if ($to   && $valid($to)){   $where[]="tgl <= ?"; $params[]=$to; }

$sql = "SELECT id,tgl,shift,
               ttl_kloter_poly, ttl_kloter_cotton, ttl_kloter_white,
               suffix, botol
        FROM db_laborat.summary_dispensing".
        ($where? " WHERE ".implode(" AND ",$where):"").
        " ORDER BY tgl DESC, id DESC";

$stmt = sqlsrv_query($con, $sql, $params);
if($stmt === false){ echo json_encode(["ok"=>false,"message"=>sqlsrv_errors()]); exit; }

$data=[];
while($row=sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
  //   if (isset($row['jam']) && $row['jam']!==null){ $row['jam']=substr($row['jam'],0,5); }
  $sx = json_decode($row['suffix'] ?? 'null', true) ?: [];
  $row['suffix_poly']   = $sx['poly']   ?? null;
  $row['suffix_cotton'] = $sx['cotton'] ?? null;
  $row['suffix_white']  = $sx['white']  ?? null;
  unset($row['suffix']);
  if ($row['tgl'] instanceof DateTimeInterface){
    $row['tgl'] = $row['tgl']->format('Y-m-d');
  }
  $data[]=$row;
}

echo json_encode(["ok"=>true,"data"=>$data]);
