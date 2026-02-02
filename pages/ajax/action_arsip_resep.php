<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";
session_start();
$time = date('Y-m-d H:i:s');
$arsip = isset($_POST['arsip']) ? $_POST['arsip'] : '';
$idStatus = isset($_POST['id_status']) ? $_POST['id_status'] : '';

sqlsrv_query(
    $con,
    "UPDATE db_laborat.tbl_status_matching SET status = ? WHERE id = ?",
    [$arsip, $idStatus]
);

$sqlNoResep = sqlsrv_query(
    $con,
    "SELECT idm FROM db_laborat.tbl_status_matching WHERE id = ?",
    [$idStatus]
);
$NoResep = sqlsrv_fetch_array($sqlNoResep, SQLSRV_FETCH_ASSOC);
$ip_num = $_SERVER['REMOTE_ADDR'];
sqlsrv_query(
    $con,
    "INSERT INTO db_laborat.log_status_matching (ids, status, info, do_by, do_at, ip_address)
     VALUES (?, ?, ?, ?, ?, ?)",
    [$NoResep['idm'], 'arsip', 'Resep di arsipkan', $_SESSION['userLAB'], $time, $ip_num]
);

$response = array(
    'session' => 'LIB_SUCCSS',
    'exp' => 'updated'
);
echo json_encode($response);
