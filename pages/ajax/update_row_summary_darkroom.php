<?php
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . "/../../koneksi.php";

function toInt($v){ return ($v === '' || $v === null) ? 0 : (int)$v; }
function norm_suffix($s){
  $arr = preg_split('/[,\s;]+/', (string)$s, -1, PREG_SPLIT_NO_EMPTY);
  $arr = array_values(array_unique(array_map('trim', $arr)));
  return implode(' ', $arr);
}

$id     = (int)($_POST['id'] ?? 0);
$tgl    = $_POST['tgl']   ?? '';
$shift  = $_POST['shift'] ?? null;
$jumlah = toInt($_POST['jumlah'] ?? 0);
$suffix_str = norm_suffix($_POST['suffix'] ?? '');
$ket    = $_POST['ket'] ?? null;

if ($id<=0){ echo json_encode(["ok"=>false,"message"=>"ID tidak valid"]); exit; }
if ($tgl === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)){
  echo json_encode(["ok"=>false,"message"=>"TGL tidak valid"]); exit;
}
if ($jumlah <= 0 && $suffix_str !== ''){
  $jumlah = count(preg_split('/\s+/', $suffix_str));
}

$suffix_json = json_encode(["all" => $suffix_str], JSON_UNESCAPED_UNICODE);

$sql = "UPDATE db_laborat.summary_darkroom
        SET tgl=?, shift=?, jumlah=?, suffix=?, ket=?
        WHERE id=?";
$stmt = sqlsrv_query($con, $sql, [$tgl, $shift, $jumlah, $suffix_json, $ket, $id]);
if(!$stmt){ echo json_encode(["ok"=>false,"message"=>sqlsrv_errors()]); exit; }

echo json_encode(["ok"=>true,"message"=>"Update selesai"], JSON_UNESCAPED_UNICODE);
