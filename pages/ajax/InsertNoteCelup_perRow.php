<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";
session_start();

sqlsrv_query(
    $con,
    "INSERT INTO db_laborat.tbl_note_celup (kk, jenis_note, note, created_at, created_by)
     VALUES (?, ?, ?, GETDATE(), ?)",
    [$_POST['kk'] ?? '', $_POST['jenis_note'] ?? '', $_POST['note'] ?? '', $_SESSION['userLAB'] ?? '']
);

$SQL_rcode = sqlsrv_query(
    $con,
    "SELECT TOP (1) idm FROM db_laborat.tbl_status_matching WHERE id = ?",
    [$_POST['id_status'] ?? '']
);
$rcode_ = sqlsrv_fetch_array($SQL_rcode, SQLSRV_FETCH_ASSOC);
$ip_num = $_SERVER['REMOTE_ADDR'];
sqlsrv_query(
    $con,
    "INSERT INTO db_laborat.log_status_matching (ids, status, info, do_by, do_at, ip_address)
     VALUES (?, ?, ?, ?, GETDATE(), ?)",
    [$rcode_['idm'] ?? '', 'selesai', 'add note ' . ($_POST['kk'] ?? ''), $_SESSION['userLAB'] ?? '', $ip_num]
);
$response = array(
    'session' => 'LIB_SUCCSS',
    'exp' => $rcode_['idm'] ?? ''
);
echo json_encode($response);
