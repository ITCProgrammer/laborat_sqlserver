<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";
session_start();

$idStatus = $_POST['id_status'] ?? '';
$jenisNote = $_POST['jenis_note'] ?? '';
$note = $_POST['note'] ?? '';
sqlsrv_query(
    $con,
    "INSERT INTO db_laborat.tbl_note_celup (id_status, jenis_note, note, created_at, created_by)
     VALUES (?, ?, ?, GETDATE(), ?)",
    [$idStatus, $jenisNote, $note, $_SESSION['userLAB']]
);

$SQL_rcode = sqlsrv_query(
    $con,
    "SELECT TOP (1) idm FROM db_laborat.tbl_status_matching WHERE id = ?",
    [$idStatus]
);
$rcode_ = sqlsrv_fetch_array($SQL_rcode, SQLSRV_FETCH_ASSOC);
$ip_num = $_SERVER['REMOTE_ADDR'];
sqlsrv_query(
    $con,
    "INSERT INTO db_laborat.log_status_matching (ids, status, info, do_by, do_at, ip_address)
     VALUES (?, ?, ?, ?, GETDATE(), ?)",
    [$rcode_['idm'], 'selesai', 'Menambahkan Note Pada hasil celup', $_SESSION['userLAB'], $ip_num]
);
$response = array(
    'session' => 'LIB_SUCCSS',
    'exp' => $rcode_['idm'] ?? ''
);
echo json_encode($response);
