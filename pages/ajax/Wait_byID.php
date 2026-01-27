<?php
ini_set("error_reporting", 1);
include __DIR__ . "/../../koneksi.php";
session_start();
$time = date('Y-m-d H:i:s');

$idStatus = $_POST['id_status'] ?? '';
$why      = $_POST['why'] ?? '';
$user     = $_SESSION['userLAB'] ?? '';
$ip_num   = $_SERVER['REMOTE_ADDR'] ?? '';

$sqlNoResep = sqlsrv_query($con, "SELECT idm FROM db_laborat.tbl_status_matching WHERE id = ?", [$idStatus]);
$NoResep = sqlsrv_fetch_array($sqlNoResep, SQLSRV_FETCH_ASSOC);
$idm = $NoResep['idm'] ?? '';

sqlsrv_query($con, "INSERT INTO db_laborat.log_status_matching (ids, status, info, do_by, do_at, ip_address)
                    VALUES (?, 'tunggu', ?, ?, ?, ?)", [$idm, $why, $user, $time, $ip_num]);

sqlsrv_query($con, "UPDATE db_laborat.tbl_matching SET status_bagi = 'tunggu', note = ? WHERE no_resep = ?", [$why, $idm]);

sqlsrv_query($con, "DELETE FROM db_laborat.tbl_status_matching WHERE id = ?", [$idStatus]);
sqlsrv_query($con, "DELETE FROM db_laborat.tbl_matching_detail WHERE id_status = ?", [$idStatus]);


$response = array(
    'session' => 'LIB_SUCCSS',
    'exp' => 'updated'
);
echo json_encode($response);
