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
function get_client_ip()
{
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if (isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if (isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if (isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}
$sql = sqlsrv_query($con,"SELECT TOP 1 id, no_order, jenis_matching from db_laborat.tbl_matching where no_resep = ?", [$_POST['rcode']]);
$data = sqlsrv_fetch_array($sql, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($sql);
$ip = get_client_ip();
sqlsrv_query($con,"INSERT INTO db_laborat.historical_delete_matching
    (no_matching, id_matching, id_status, jenis_matching, ip_adress, delete_at, delete_by, why_delete, no_order)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", [
    $_POST['rcode'],
    $data['id'],
    $_POST['rcode'],
    $data['jenis_matching'],
    $ip,
    $time,
    $_SESSION['userLAB'],
    'deleted from data matching',
    $data['no_order']
]);
sqlsrv_query($con,"DELETE from db_laborat.tbl_matching where no_resep=?", [$_POST['rcode']]);
sqlsrv_query($con,"DELETE from db_laborat.tbl_status_matching where idm=?", [$_POST['rcode']]);
sqlsrv_query($con,"DELETE from db_laborat.tbl_matching_detail where id_matching=?", [$data['id']]);

$ip_num = $_SERVER['REMOTE_ADDR'];
sqlsrv_query($con,"INSERT INTO db_laborat.log_status_matching
            (ids, status, info, do_by, do_at, ip_address) VALUES (?, ?, ?, ?, GETDATE(), ?)", [
    $_POST['rcode'],
    'deleted',
    'deleted from data matching',
    $_SESSION['userLAB'],
    $ip_num
]);

$response = array(
    'session' => 'LIB_SUCCSS',
    'exp' => 'updated',
    'ip_address' => $ip
);
echo json_encode($response);
