<?php
ini_set("error_reporting", 1);
session_start();
include("../koneksi.php");
$date = date('Y-m-d H:i:s');
if ($_POST) {
    extract($_POST);
    $nama = isset($_POST['nama']) ? $_POST['nama'] : '';
    $sts = isset($_POST['sts']) ? $_POST['sts'] : '';
    $userLAB = isset($_SESSION['userLAB']) ? $_SESSION['userLAB'] : '';
    $ip = isset($_SESSION['ip']) ? $_SESSION['ip'] : '';
    $os = isset($_SESSION['os']) ? $_SESSION['os'] : '';

    $insertSql = "INSERT INTO db_laborat.tbl_user_resep (nama, is_active, created_at, created_by)
                  VALUES (?, ?, ?, ?)";
    $insertParams = [$nama, $sts, $date, $userLAB];
    sqlsrv_query($con, $insertSql, $insertParams);

    $logSql = "INSERT INTO db_laborat.tbl_log (what, what_do, do_by, do_at, ip, os, remark)
               VALUES (?, ?, ?, ?, ?, ?, ?)";
    $logParams = [$nama, 'INSERT INTO tbl_user_resep', $userLAB, $date, $ip, $os, "Insert new user_resep $nama"];
    sqlsrv_query($con, $logSql, $logParams);
    echo " <script>window.location='?p=UserResep';</script>";
}
