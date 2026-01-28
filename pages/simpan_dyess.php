<?php
ini_set("error_reporting", 1);
session_start();
include("../koneksi.php");
$time = date('Y-m-d H:i:s');
if ($_POST) {
    extract($_POST);
    $ket = isset($_POST['ket']) ? $_POST['ket'] : '';
    $Code = isset($_POST['Code']) ? $_POST['Code'] : '';
	$Code_New = isset($_POST['Code_new']) ? $_POST['Code_new'] : '';
    $Product_Name = isset($_POST['Product_name']) ? $_POST['Product_name'] : '';
    $liquid_powder = isset($_POST['liquid_powder']) ? $_POST['liquid_powder'] : '';
    $Product_Unit = isset($_POST['Product_Unit']) ? $_POST['Product_Unit'] : '';
    $is_active = isset($_POST['is_active']) ? $_POST['is_active'] : '';
    $userLAB = isset($_SESSION['userLAB']) ? $_SESSION['userLAB'] : '';
    $ip = isset($_SESSION['ip']) ? $_SESSION['ip'] : '';
    $os = isset($_SESSION['os']) ? $_SESSION['os'] : '';

    $insertSql = "INSERT INTO db_laborat.tbl_dyestuff
        (ket, code, code_new, Product_Name, liquid_powder, Product_Unit, is_active, created_at, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insertParams = [$ket, $Code, $Code_New, $Product_Name, $liquid_powder, $Product_Unit, $is_active, $time, $userLAB];
    sqlsrv_query($con, $insertSql, $insertParams);

    $logSql = "INSERT INTO db_laborat.tbl_log (what, what_do, do_by, do_at, ip, os, remark)
               VALUES (?, ?, ?, ?, ?, ?, ?)";
    $logParams = [$Code, 'INSERT INTO tbl_dyestuff', $userLAB, $time, $ip, $os, $Product_Name];
    sqlsrv_query($con, $logSql, $logParams);

    echo " <script>window.location='?p=Manage-Dyestuff';</script>";
}
