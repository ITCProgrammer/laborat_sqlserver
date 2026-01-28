<?php
ini_set("error_reporting", 1);
session_start();
include '../../koneksi.php';
$time = date('Y-m-d H:i:s');
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$Code = isset($_POST['Code']) ? $_POST['Code'] : '';
$Ket = isset($_POST['Ket']) ? $_POST['Ket'] : '';
$Code_New = isset($_POST['code_new']) ? $_POST['code_new'] : '';
$Product_Name = isset($_POST['Product_Name']) ? $_POST['Product_Name'] : '';
$liquid_powder = isset($_POST['liquid_powder']) ? $_POST['liquid_powder'] : '';
$is_active = isset($_POST['is_active']) ? $_POST['is_active'] : '';
$Product_Unit = isset($_POST['Product_Unit']) ? $_POST['Product_Unit'] : '';
$userLAB = isset($_SESSION['userLAB']) ? $_SESSION['userLAB'] : '';
$ip = isset($_SESSION['ip']) ? $_SESSION['ip'] : '';
$os = isset($_SESSION['os']) ? $_SESSION['os'] : '';

$updateSql = "UPDATE db_laborat.tbl_dyestuff SET 
                ket = ?,
                code = ?,
                code_new = ?,
                Product_Name = ?,
                liquid_powder = ?,
                is_active = ?,
                Product_Unit = ?,
                last_updated_at = ?,
                last_updated_by = ?
              WHERE id = ?";
$updateParams = [$Ket, $Code, $Code_New, $Product_Name, $liquid_powder, $is_active, $Product_Unit, $time, $userLAB, $id];
sqlsrv_query($con, $updateSql, $updateParams);

$logSql = "INSERT INTO db_laborat.tbl_log (what, what_do, do_by, do_at, ip, os, remark)
           VALUES (?, ?, ?, ?, ?, ?, ?)";
$logParams = [$id, 'UPDATE tbl_dyestuff', $userLAB, $time, $ip, $os, $Code];
sqlsrv_query($con, $logSql, $logParams);

$response = "LIB_SUCCSS";
echo json_encode($response);
