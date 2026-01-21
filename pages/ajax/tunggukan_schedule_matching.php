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
$ip = get_client_ip();
$ip_num = $_SERVER['REMOTE_ADDR'];

$update = sqlsrv_query($con, "UPDATE db_laborat.tbl_matching SET 
    status_bagi = 'tunggu',
    note = ?
    WHERE no_resep = ?", [
    $_POST['why'],
    $_POST['rcode']
]);

if (! $update) {
    http_response_code(500);
    echo json_encode(['error' => sqlsrv_errors()]);
    exit;
}

sqlsrv_query($con, "INSERT INTO db_laborat.log_status_matching (ids, status, info, do_by, do_at, ip_address) VALUES (?, ?, ?, ?, GETDATE(), ?)", [
    $_POST['rcode'],
    'tunggu',
    'changed status_bagi to tunggu',
    $_SESSION['userLAB'],
    $ip_num
]);

$response = array(
    'session' => 'LIB_SUCCSS',
    'exp' => 'updated',
    'ip_address' => $ip
);
echo json_encode($response);
