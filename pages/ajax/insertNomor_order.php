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

$idMatching = $_POST['id_matching'] ?? '';
$idStatus   = $_POST['id_status'] ?? '';
$rcode      = $_POST['Rcode'] ?? '';
$noOrder    = $_POST['no_order'] ?? '';
$lot        = $_POST['lot'] ?? '';
$benang     = str_replace("'", "''", $_POST['addt_benang'] ?? '');

// cari flag max
$Sql_Cek_Flag = sqlsrv_query($con,"SELECT max(flag) as maxflag FROM db_laborat.tbl_orderchild WHERE id_matching = ?", [$idMatching]);
$row_data = sqlsrv_fetch_array($Sql_Cek_Flag, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($Sql_Cek_Flag);
$flag = ($row_data && $row_data['maxflag'] !== null) ? (intval($row_data['maxflag']) + 1) : 1;

// cek duplikasi
$Sql_Cek_Duplikasi = sqlsrv_query($con,"SELECT TOP 1 id FROM db_laborat.tbl_orderchild WHERE id_matching = ? AND [order] = ?", [$idMatching, $noOrder]);
$sql_row = $Sql_Cek_Duplikasi ? sqlsrv_num_rows($Sql_Cek_Duplikasi) : 0;
sqlsrv_free_stmt($Sql_Cek_Duplikasi);

// tetap insert (logic sama), hanya sekali blok
sqlsrv_query($con,"INSERT INTO db_laborat.tbl_orderchild
        (flag, id_matching, id_status, r_code, [order], lot, jenis_benang, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", [
    $flag,
    $idMatching,
    $idStatus,
    $rcode,
    $noOrder,
    $lot,
    $benang,
    $_SESSION['userLAB'],
    $time
]);
$LIB_SUCCSS = "LIB_SUCCSS";

$sqlNoResep = sqlsrv_query($con,"SELECT idm from db_laborat.tbl_status_matching where id = ?", [$idStatus]);
$NoResep = sqlsrv_fetch_array($sqlNoResep, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($sqlNoResep);
$noResepVal = $NoResep['idm'] ?? '';

sqlsrv_query($con,"INSERT into db_laborat.log_status_matching
        (ids, status, info, do_by, do_at, ip_address) VALUES (?, ?, ?, ?, GETDATE(), ?)", [
    $noResepVal,
    'insert order child',
    $noOrder,
    $_SESSION['userLAB'],
    $ip_num
]);

$response = array(
    'session' => $LIB_SUCCSS,
    'exp' => 'inserted'
);
echo json_encode($response);
