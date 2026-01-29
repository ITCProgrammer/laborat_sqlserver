<?php
ini_set("error_reporting", 1);
session_start();
include("../koneksi.php");
if ($_POST) {
	extract($_POST);
	$time = date('Y-m-d H:i:s');
	$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
	$nama = isset($_POST['nama']) ? $_POST['nama'] : '';
	$sts = isset($_POST['sts']) ? $_POST['sts'] : '';
	$userLAB = isset($_SESSION['userLAB']) ? $_SESSION['userLAB'] : '';
	$ip = isset($_SESSION['ip']) ? $_SESSION['ip'] : '';
	$os = isset($_SESSION['os']) ? $_SESSION['os'] : '';

	$updateSql = "UPDATE db_laborat.tbl_matcher SET nama = ?, status = ? WHERE id = ?";
	$updateParams = [$nama, $sts, $id];
	sqlsrv_query($con, $updateSql, $updateParams);

	$logSql = "INSERT INTO db_laborat.tbl_log (what, what_do, do_by, do_at, ip, os, remark)
	           VALUES (?, ?, ?, ?, ?, ?, ?)";
	$logParams = [$id, 'UPDATE tbl_matcher', $userLAB, $time, $ip, $os, 'Edit matcher'];
	sqlsrv_query($con, $logSql, $logParams);


	echo " <script>window.location='?p=Matcher';</script>";
}
