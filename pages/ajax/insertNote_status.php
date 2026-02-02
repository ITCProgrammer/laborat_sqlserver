<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";
session_start();
$time = date('Y-m-d H:i:s');
$ip_num = $_SERVER['REMOTE_ADDR'];
$idMatching = $_POST['id_matching'] ?? '';
$idStatus = $_POST['id_status'] ?? '';
$rcode = $_POST['Rcode'] ?? '';
$noteStatus = $_POST['note_status'] ?? '';

sqlsrv_query(
    $con,
    "INSERT INTO db_laborat.tbl_notestatus (id_matching, id_status, r_code, note)
     VALUES (?, ?, ?, ?)",
    [$idMatching, $idStatus, $rcode, $noteStatus]
);
$LIB_SUCCSS = "LIB_SUCCSS";

$response = array(
    'session' => $LIB_SUCCSS,
    'exp' => 'inserted'
);
echo json_encode($response);
