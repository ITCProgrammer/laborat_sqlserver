<?php
ini_set("error_reporting", 1);
session_start();
include '../../koneksi.php';

$time = date('Y-m-d H:i:s');
sqlsrv_query(
    $con,
    "UPDATE db_dying.tbl_hasilcelup SET k_resep = ? WHERE id = ?",
    [$_POST['value'] ?? '', $_POST['pk'] ?? '']
);

sqlsrv_query(
    $con,
    "INSERT INTO db_laborat.tbl_log (what, what_do, do_by, do_at, ip, os, remark)
     VALUES (?, ?, ?, ?, ?, ?, ?)",
    [
        $_POST['pk'] ?? '',
        'UPDATE db_dying.tbl_hasilcelup k_resep',
        $_SESSION['userLAB'] ?? '',
        $time,
        $_SESSION['ip'] ?? '',
        $_SESSION['os'] ?? '',
        $_POST['value'] ?? ''
    ]
);

$response = "LIB_SUCCSS";
echo json_encode($response);
