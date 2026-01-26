<?php
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . "/../../koneksi.php";

$id = (int)($_POST['id'] ?? 0);
if ($id<=0){ echo json_encode(["ok"=>false,"message"=>"ID tidak valid"]); exit; }

$stmt = sqlsrv_query($con, "DELETE FROM db_laborat.summary_dispensing WHERE id=?", [$id]);
if($stmt === false){ echo json_encode(["ok"=>false,"message"=>sqlsrv_errors()]); exit; }

echo json_encode(["ok"=>true,"message"=>"Dihapus"]);
