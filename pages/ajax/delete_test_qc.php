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

$ip_num = get_client_ip();

$success = true;

sqlsrv_begin_transaction($con);


$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$no_counter = isset($_POST['no_counter']) ? $_POST['no_counter'] : '';
$userLAB = isset($_SESSION['userLAB']) ? $_SESSION['userLAB'] : '';

$query_delete = "UPDATE db_laborat.tbl_test_qc SET deleted_at = GETDATE() WHERE id = ?";
$result_delete = sqlsrv_query($con, $query_delete, [$id]);


if (!$result_delete) {
    $success = false;
}


$log_info = "Menghapus test $no_counter";


$query_log = "INSERT INTO db_laborat.log_qc_test (no_counter, status, info, do_by, do_at, ip_address)
                  VALUES (?, 'Open', ?, ?, GETDATE(), ?)";
$result_log = sqlsrv_query($con, $query_log, [$no_counter, $log_info, $userLAB, $ip_num]);


if (!$result_log) {
    $success = false;
}

if ($success) {
    sqlsrv_commit($con);

    $response = array(
        'session' => 'LIB_SUCCSS',
        'exp' => 'updated',
    );
    echo json_encode($response);
} else {
    sqlsrv_rollback($con);
    $response = array(
        'session' => 'LIB_FAILED',
        'exp' => 'updated',
    );
    echo json_encode($response);
}
