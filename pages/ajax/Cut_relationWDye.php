<?php
ini_set("error_reporting", 1);
session_start();
include '../../koneksi.php';

$delete = '';
$time = date('Y-m-d H:i:s');
sqlsrv_query(
    $con,
    "UPDATE db_dying.tbl_hasilcelup SET rcode = ? WHERE id = ?",
    [$delete, $_POST['id'] ?? '']
);

sqlsrv_query(
    $con,
    "INSERT INTO db_laborat.tbl_log (what, what_do, do_by, do_at, ip, os, remark)
     VALUES (?, ?, ?, ?, ?, ?, ?)",
    [
        $_POST['id'] ?? '',
        'UPDATE db_dying.tbl_hasilcelup rcode',
        $_SESSION['userLAB'] ?? '',
        $time,
        $_SESSION['ip'] ?? '',
        $_SESSION['os'] ?? '',
        'Delete Relation'
    ]
);

$response = "LIB_SUCCSS";
echo json_encode($response);
