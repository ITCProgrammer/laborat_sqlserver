<?php
ini_set("error_reporting", 1);
session_start();
include "koneksi.php";

$time = date('Y-m-d H:i:s');
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$sts = isset($_POST['sts']) ? $_POST['sts'] : '';
$userLAB = isset($_SESSION['userLAB']) ? $_SESSION['userLAB'] : '';
$ip = isset($_SESSION['ip']) ? $_SESSION['ip'] : '';
$os = isset($_SESSION['os']) ? $_SESSION['os'] : '';

sqlsrv_query($con, "UPDATE db_laborat.tbl_colorist SET is_active = ? WHERE id = ?", [$sts, $id]);
sqlsrv_query(
    $con,
    "INSERT INTO db_laborat.tbl_log (what, what_do, do_by, do_at, ip, os, remark)
     VALUES (?, ?, ?, ?, ?, ?, ?)",
    [$id, 'EDIT tbl_colorist', $userLAB, $time, $ip, $os, 'EDIT colorist']
);

echo " <script>window.location='?p=Colorist';</script>";
