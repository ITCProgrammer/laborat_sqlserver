<?php
ini_set("error_reporting", 1);
include __DIR__ . "/../../koneksi.php";
session_start();
$time = date('Y-m-d H:i:s');
$ip_num = $_SERVER['REMOTE_ADDR'];
$idStatus = $_POST['id_status'] ?? '';
$user = $_SESSION['userLAB'] ?? '';

sqlsrv_query($con, "UPDATE db_laborat.tbl_status_matching SET status='hold' WHERE id = ?", [$idStatus]);
$sqlNoResep = sqlsrv_query($con, "SELECT idm FROM db_laborat.tbl_status_matching WHERE id = ?", [$idStatus]);
$NoResep = sqlsrv_fetch_array($sqlNoResep, SQLSRV_FETCH_ASSOC);
$idm = $NoResep['idm'] ?? '';
sqlsrv_query($con, "INSERT INTO db_laborat.log_status_matching (ids, status, info, do_by, do_at, ip_address)
            VALUES (?, 'hold', 'lanjutkan after wait', ?, ?, ?)", [$idm, $user, $time, $ip_num]);

$response = array(
    'session' => 'LIB_SUCCSS',
    'exp' => 'updated'
);
echo json_encode($response);
