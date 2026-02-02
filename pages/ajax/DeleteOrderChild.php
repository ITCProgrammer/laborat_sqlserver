<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";
session_start();
$time = date('Y-m-d H:i:s');
$ip_num = $_SERVER['REMOTE_ADDR'];
$id = $_POST['id'] ?? '';
$idStatus = $_POST['id_status'] ?? '';

sqlsrv_query(
    $con,
    "DELETE FROM db_laborat.tbl_orderchild WHERE id = ?",
    [$id]
);
$LIB_SUCCSS = "LIB_SUCCSS";

$sqlNoResep = sqlsrv_query(
    $con,
    "SELECT idm FROM db_laborat.tbl_status_matching WHERE id = ?",
    [$idStatus]
);
$NoResep = sqlsrv_fetch_array($sqlNoResep, SQLSRV_FETCH_ASSOC);
sqlsrv_query(
    $con,
    "INSERT INTO db_laborat.log_status_matching (ids, status, info, do_by, do_at, ip_address)
     VALUES (?, ?, ?, ?, ?, ?)",
    [$NoResep['idm'], 'insert order child', 'Delete Order Child', $_SESSION['userLAB'], $time, $ip_num]
);

$response = array(
    'session' => $LIB_SUCCSS,
    'exp' => 'inserted'
);








echo json_encode($response);
