<?php
date_default_timezone_set('Asia/Jakarta');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

function noteConnectionFailure($label, $error)
{
    error_log(sprintf('[%s] DB connection failed: %s', $label, $error ?: 'unknown error'));
}

$con_db_dyeing    = mysqli_connect("10.0.0.10","dit","4dm1n","db_dying");

$hostSVR19     = "10.0.0.221";
$usernameSVR19 = "sa";
$passwordSVR19 = "Ind@taichen2024";
$nowprd        = "nowprd";
$nowprdd       = [
    "Database" => $nowprd,
    "UID" => $usernameSVR19,
    "PWD" => $passwordSVR19,
    "CharacterSet" => "UTF-8"
];
$con_nowprd = sqlsrv_connect($hostSVR19, $nowprdd);

$cona = mysqli_connect("10.0.0.10","dit","4dm1n","db_adm");

$hostname="10.0.0.21";
// $database = "NOWTEST"; // SERVER NOW 20
$database = "NOWPRD"; // SERVER NOW 22
$user = "db2admin";
$passworddb2 = "Sunkam@24809";
$port="25000";
$conn_string = "DRIVER={IBM ODBC DB2 DRIVER}; HOSTNAME=$hostname; PORT=$port; PROTOCOL=TCPIP; UID=$user; PWD=$passworddb2; DATABASE=$database;";
$conn1 = db2_pconnect($conn_string,'', '');

$hostLabSqlsrv = "10.0.0.221";
$dbLabSqlsrv   = "db_laborat";
$con = sqlsrv_connect($hostLabSqlsrv, [
    "Database" => $dbLabSqlsrv,
    "UID"      => $usernameSVR19,
    "PWD"      => $passwordSVR19,
    "LoginTimeout" => 2,
    "CharacterSet" => "UTF-8",
]);
if (! $con) {
    noteConnectionFailure($dbLabSqlsrv, print_r(sqlsrv_errors(), true));
}

// Tutup koneksi saat eksekusi selesai (tidak wajib, tapi eksplisit).
register_shutdown_function(function () use (&$con, &$con_db_dyeing, &$cona, &$con_nowprd) {
    foreach ([$con_db_dyeing, $cona] as $mysqliConn) {
        if ($mysqliConn instanceof mysqli) {
            $mysqliConn->close();
        } elseif ($mysqliConn) {
            mysqli_close($mysqliConn);
        }
    }

    if ($con_nowprd) {
        sqlsrv_close($con_nowprd);
    }
    if ($con) {
        sqlsrv_close($con);
    }
    // $conn1 adalah pconnect DB2, dibiarkan agar tetap persistent.
});
