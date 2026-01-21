<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";
session_start();
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

$rcode = $_POST['rcode'];
$temp_code1 = $_POST['temp_code'] ?? null;
$temp_code2 = $_POST['temp_code2'] ?? null;

if (! $con) {
    echo json_encode(['session' => 'ERROR', 'message' => 'Koneksi SQL Server gagal']);
    exit;
}

function json_sql_error($context) {
    $err = sqlsrv_errors();
    echo json_encode([
        'session' => 'ERROR',
        'context' => $context,
        'errors' => $err,
    ]);
    exit;
}

// update temp_code
if ($temp_code1) {
    if (substr($rcode, 0, 2) === 'DR') {
        $ok = sqlsrv_query($con, "UPDATE db_laborat.tbl_matching SET temp_code = ?, temp_code2 = ? WHERE no_resep = ?", [$temp_code1, $temp_code2, $rcode]);
        if (! $ok) json_sql_error('update temp_code DR');
    } else {
        $ok = sqlsrv_query($con, "UPDATE db_laborat.tbl_matching SET temp_code = ? WHERE no_resep = ?", [$temp_code1, $rcode]);
        if (! $ok) json_sql_error('update temp_code');
    }
}

$ok = sqlsrv_query($con,"UPDATE db_laborat.tbl_matching SET status_bagi = 'siap bagi' WHERE no_resep = ?", [$rcode]);
if (! $ok) json_sql_error('update status_bagi');

$ok = sqlsrv_query($con,"INSERT INTO db_laborat.log_status_matching (ids, status, info, do_by, do_at, ip_address) VALUES (?, ?, ?, ?, ?, ?)", [
    $rcode,
    'siap bagi',
    'changed status_bagi to siap bagi',
    $_SESSION['userLAB'] ?? '',
    $time,
    $ip_num
]);
if (! $ok) json_sql_error('insert log_status_matching');

$response = array(
    'session' => 'LIB_SUCCSS',
    'exp' => 'updated',
    'ip_address' => $ip
);
echo json_encode($response);
