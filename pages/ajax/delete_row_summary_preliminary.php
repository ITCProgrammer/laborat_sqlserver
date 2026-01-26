<?php
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . "/../../koneksi.php";

if (!isset($_POST['id']) || $_POST['id']===''){ echo json_encode(["ok"=>false,"message"=>"ID kosong"]); exit; }
$id = (int)$_POST['id'];

$stmt = sqlsrv_query($con, "DELETE FROM db_laborat.summary_preliminary WHERE id=?", [$id]);
if($stmt === false){ echo json_encode(["ok"=>false,"message"=>sqlsrv_errors()]); exit; }

echo json_encode(["ok"=>true,"message"=>"Terhapus (ID $id)"]);
