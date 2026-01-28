<?php
ini_set("error_reporting", 1);
session_start();
include("../koneksi.php");
$time = date('Y-m-d H:i:s');
if ($_POST) {
    extract($_POST);
    $Proses_desc = isset($_POST['Proses_desc']) ? strtoupper($_POST['Proses_desc']) : '';
    $is_active = isset($_POST['is_active']) ? $_POST['is_active'] : '';
    $userLAB = isset($_SESSION['userLAB']) ? $_SESSION['userLAB'] : '';
    $ip = isset($_SESSION['ip']) ? $_SESSION['ip'] : '';
    $os = isset($_SESSION['os']) ? $_SESSION['os'] : '';

    $insertSql = "INSERT INTO db_laborat.master_proses
        (nama_proses, is_active, created_at, created_by)
        VALUES (?, ?, ?, ?)";
    $insertParams = [$Proses_desc, $is_active, $time, $userLAB];
    sqlsrv_query($con, $insertSql, $insertParams);

    $logSql = "INSERT INTO db_laborat.tbl_log (what, what_do, do_by, do_at, ip, os, remark)
               VALUES (?, ?, ?, ?, ?, ?, ?)";
    $logParams = [$Proses_desc, 'INSERT INTO master_proses', $userLAB, $time, $ip, $os, 'Insert new Proses'];
    sqlsrv_query($con, $logSql, $logParams);
    echo " <script>window.location='?p=Manage-Proses';</script>";
}
