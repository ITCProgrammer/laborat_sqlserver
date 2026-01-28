<?php
ini_set("error_reporting", 1);
session_start();
include '../../koneksi.php';
$time = date('Y-m-d H:i:s');

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$isActive = isset($_POST['is_active']) ? $_POST['is_active'] : '';

$query = "UPDATE db_laborat.master_proses SET is_active = ? WHERE id = ?";
sqlsrv_query($con, $query, [$isActive, $id]);
$response = "LIB_SUCCSS";
echo json_encode($response);
