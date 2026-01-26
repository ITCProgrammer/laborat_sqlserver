<?php
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . "/../../koneksi.php";

$from = isset($_GET['from']) ? trim($_GET['from']) : '';
$to   = isset($_GET['to'])   ? trim($_GET['to'])   : '';

$valid = function($d){ return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d); };

if ($from !== '' && $to !== '' && $valid($from) && $valid($to) && $from > $to){
  $tmp=$from; $from=$to; $to=$tmp;
}
if ($from === '' || !$valid($from) || $to === '' || !$valid($to)){
  $to   = date('Y-m-d');
  $from = date('Y-m-d', strtotime($to.' -30 days'));
}

$sql = "SELECT id,tgl,shift,jumlah,suffix,ket
        FROM db_laborat.summary_darkroom
        WHERE tgl BETWEEN ? AND ?
        ORDER BY tgl DESC, id DESC";

$stmt = sqlsrv_query($con, $sql, [$from, $to]);
if ($stmt === false){ echo json_encode(["ok"=>false,"message"=>sqlsrv_errors()]); exit; }

function suffix_to_string($v){
  // Terima array/string → rapikan jadi string spasi-tunggal
  if (is_array($v)) $v = implode(' ', $v);
  return trim(preg_replace('/\s+/', ' ', (string)$v));
}

$data = [];
$row = null;
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
  $row['jumlah'] = (int)$row['jumlah'];
  if ($row['tgl'] instanceof DateTimeInterface){
    $row['tgl'] = $row['tgl']->format('Y-m-d');
  }

  // Decode JSON → ambil key "all" (fallback: "list" / array / string)
  $sfx = '';
  if (!empty($row['suffix'])){
    $j = json_decode($row['suffix'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($j)){
      if (array_key_exists('all', $j))       $sfx = suffix_to_string($j['all']);
      elseif (array_key_exists('list', $j))  $sfx = suffix_to_string($j['list']);
      else                                   $sfx = suffix_to_string($j); 
    }else{
      // sudah string JSON tapi bukan object/array → kirim apa adanya
      $sfx = suffix_to_string($row['suffix']);
    }
  }
  $row['suffix'] = $sfx;

  $data[] = $row;
}
sqlsrv_free_stmt($stmt);

echo json_encode(["ok"=>true, "data"=>$data], JSON_UNESCAPED_UNICODE);
