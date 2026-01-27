<?php
ini_set("error_reporting", 1);
include __DIR__ . "/../../koneksi.php";
session_start();
$time = date('Y-m-d H:i:s');

$newStatus = $_POST['newStatus'] ?? '';
$idStatus  = $_POST['id_status'] ?? '';
$idm       = $_POST['idm'] ?? '';
$userLab   = $_SESSION['userLAB'] ?? '';
$ip_num    = $_SERVER['REMOTE_ADDR'] ?? '';

sqlsrv_query($con, "UPDATE db_laborat.tbl_status_matching SET kt_status = ? WHERE id = ?", [$newStatus, $idStatus]);
sqlsrv_query($con, "INSERT INTO db_laborat.log_status_matching (ids, status, info, do_by, do_at, ip_address)
                    VALUES (?, 'Change ket status to', ?, ?, ?, ?)",
                    [$idm, $newStatus, $userLab, $time, $ip_num]);

$response = array(
    'session' => 'LIB_SUCCSS',
    'exp' => 'updated'
);
echo json_encode($response);
