<?php
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . "/../../koneksi.php";

function toInt($v){ return ($v === '' || $v === null) ? 0 : (int)$v; }

$tgl   = $_POST['tgl']   ?? null;
$shift = $_POST['shift'] ?? null;

$ttl_kloter_poly   = toInt($_POST['ttl_kloter_poly']   ?? 0);
$ttl_kloter_cotton = toInt($_POST['ttl_kloter_cotton'] ?? 0);
$ttl_kloter_white  = toInt($_POST['ttl_kloter_white']  ?? 0);

$suffix_poly   = $_POST['suffix_poly']   ?? null;
$suffix_cotton = $_POST['suffix_cotton'] ?? null;
$suffix_white  = $_POST['suffix_white']  ?? null;

$botol = toInt($_POST['botol'] ?? 0);

$suffix_json = json_encode([
  "poly"   => $suffix_poly,
  "cotton" => $suffix_cotton,
  "white"  => $suffix_white,
], JSON_UNESCAPED_UNICODE);

$sql = "INSERT INTO db_laborat.summary_dispensing
        (tgl, shift, ttl_kloter_poly, ttl_kloter_cotton, ttl_kloter_white, suffix, botol)
        OUTPUT INSERTED.id
        VALUES (?,?,?,?,?,?,?)";

$params = [
  $tgl, $shift,
  $ttl_kloter_poly, $ttl_kloter_cotton, $ttl_kloter_white,
  $suffix_json, $botol
];

$stmt = sqlsrv_query($con, $sql, $params);
if(!$stmt){ echo json_encode(["ok"=>false,"message"=>sqlsrv_errors()]); exit; }
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$id = $row['id'] ?? null;

echo json_encode(["ok"=>true,"id"=>$id,"message"=>"Tersimpan"], JSON_UNESCAPED_UNICODE);
