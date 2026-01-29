<?php
ini_set("error_reporting", 1);
session_start();
include("../koneksi.php");
if ($_POST) {
	extract($_POST);
	$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
	$user = isset($_POST['username']) ? $_POST['username'] : '';
	$level = isset($_POST['level']) ? $_POST['level'] : '';
	$status = isset($_POST['status']) ? $_POST['status'] : '';
	$thn = isset($_POST['thn']) ? $_POST['thn'] : '';
	$jabatan = isset($_POST['jabatan']) ? $_POST['jabatan'] : '';
	$roles = isset($_POST['roles']) ? implode(';', $_POST['roles']) : '';
	$userLAB = isset($_SESSION['userLAB']) ? $_SESSION['userLAB'] : '';
	$ip = isset($_SESSION['ip']) ? $_SESSION['ip'] : '';
	$os = isset($_SESSION['os']) ? $_SESSION['os'] : '';

	if (empty($user)) {
		echo "<script>alert('Username tidak boleh kosong!');window.location='?p=user';</script>";
		exit;
	}

	// Jika password diisi, cek konfirmasi dan update password
	if (!empty($_POST['password'])) {
		$pass = $_POST['password'];
		$repass = $_POST['re_password'];
		if ($pass != $repass) {
			echo "<script>alert('Not Match Re-New Password!!');window.location='?p=user';</script>";
			exit;
		}
		$updatePassword = ", password = ?";
		$updatePasswordParams = [$pass];
	} else {
		$updatePassword = "";
		$updatePasswordParams = [];
	}

	$updateSql = "UPDATE db_laborat.tbl_user SET 
			username = ?
			$updatePassword,
			level = ?,
			status = ?,
			mamber = ?,
			jabatan = ?,
			pic_cycletime = ?
			WHERE id = ?";
	$updateParams = array_merge([$user], $updatePasswordParams, [$level, $status, $thn, $jabatan, $roles, $id]);
	sqlsrv_query($con, $updateSql, $updateParams);

	$time = date('Y-m-d H:i:s');
	$logSql = "INSERT INTO db_laborat.tbl_log (what, what_do, do_by, do_at, ip, os, remark)
	           VALUES (?, ?, ?, ?, ?, ?, ?)";
	$logParams = [$id, 'UPDATE table tbl_user', $userLAB, $time, $ip, $os, 'edit user'];
	sqlsrv_query($con, $logSql, $logParams);
	echo "<script>window.location='?p=user';</script>";
}
