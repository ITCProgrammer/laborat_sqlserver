<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";
session_start();
if (! $con) {
    http_response_code(500);
    echo json_encode(['error' => 'Koneksi SQL Server gagal']);
    exit;
}
$time = date('Y-m-d H:i:s');
$ip_num = $_SERVER['REMOTE_ADDR'];
$id = $_POST['id'] ?? '';
$idMatching = $_POST['id_matching'] ?? '';

sqlsrv_query($con, "DELETE FROM db_laborat.tbl_orderchild WHERE id = ?", [$id]);
$LIB_SUCCSS = "LIB_SUCCSS";

$sqlNoResep = sqlsrv_query($con, "SELECT no_resep from db_laborat.tbl_matching where id = ?", [$idMatching]);
$NoResep = sqlsrv_fetch_array($sqlNoResep, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($sqlNoResep);
$noResepVal = $NoResep['no_resep'] ?? '';

sqlsrv_query($con, "INSERT into db_laborat.log_status_matching (ids, status, info, do_by, do_at, ip_address) VALUES (?, ?, ?, ?, GETDATE(), ?)", [
    $noResepVal,
    'insert order child',
    'Delete Order Child',
    $_SESSION['userLAB'],
    $ip_num
]);

$response = array(
    'session' => $LIB_SUCCSS,
    'exp' => 'inserted'
);








echo json_encode($response);
