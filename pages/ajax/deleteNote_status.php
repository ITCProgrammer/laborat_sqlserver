<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";
session_start();
$time = date('Y-m-d H:i:s');
$ip_num = $_SERVER['REMOTE_ADDR'];
$idMatching = $_POST['id_matching'] ?? '';
$idStatus = $_POST['id_status'] ?? '';
$rcode = $_POST['Rcode'] ?? '';

sqlsrv_query(
    $con,
    "DELETE FROM db_laborat.tbl_notestatus
     WHERE id_matching = ? AND id_status = ? AND r_code = ?",
    [$idMatching, $idStatus, $rcode]
);
$LIB_SUCCSS = "LIB_SUCCSS";

$response = array(
    'session' => $LIB_SUCCSS,
    'exp' => 'inserted'
);
echo json_encode($response);
