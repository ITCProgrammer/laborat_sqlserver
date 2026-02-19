<?php
ini_set("error_reporting", 1);
session_start();
include("../koneksi.php");
if ($_POST) {
	extract($_POST);
	$time = date('Y-m-d H:i:s');
	$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
	$user = isset($_POST['username']) ? $_POST['username'] : '';
	$pass = isset($_POST['password']) ? $_POST['password'] : '';
	$repass = isset($_POST['re_password']) ? $_POST['re_password'] : '';
	$level = isset($_POST['level']) ? $_POST['level'] : '';
		$status = isset($_POST['status']) ? $_POST['status'] : '';
		$jabatan = isset($_POST['jabatan']) ? $_POST['jabatan'] : '';
		$level_jabatan = isset($_POST['level_jabatan']) ? $_POST['level_jabatan'] : '';
		$thn = isset($_POST['thn']) ? $_POST['thn'] : '';
		$roles = isset($_POST['roles']) ? implode(';', $_POST['roles']) : '';
	$userLAB = isset($_SESSION['userLAB']) ? $_SESSION['userLAB'] : '';
	$ip = isset($_SESSION['ip']) ? $_SESSION['ip'] : '';
	$os = isset($_SESSION['os']) ? $_SESSION['os'] : '';

	$datauser = sqlsrv_query($con, "SELECT COUNT(*) AS jml FROM db_laborat.tbl_user WHERE username = ?", [$user]);
	$row = $datauser ? sqlsrv_fetch_array($datauser, SQLSRV_FETCH_ASSOC) : null;
	if ($datauser) {
		sqlsrv_free_stmt($datauser);
	}
	if ($row && (int) $row['jml'] > 0) {
		echo " <script>alert('Someone already has this username!');window.location='?p=user';</script>";
	} else if ($pass != $repass) {
		echo " <script>alert('Not Match Re-New Password!');window.location='?p=user';</script>";
	} else {
			$insertSql = "INSERT INTO db_laborat.tbl_user
				(username, password, level, status, foto, jabatan, level_jabatan, mamber, pic_cycletime)
				VALUES (?, ?, ?, ?, 'avatar.png', ?, ?, ?, ?)";
			$insertParams = [$user, $pass, $level, $status, $jabatan, $level_jabatan, $thn, $roles];
		sqlsrv_query($con, $insertSql, $insertParams);

		$logSql = "INSERT INTO db_laborat.tbl_log (what, what_do, do_by, do_at, ip, os, remark)
		           VALUES (?, ?, ?, ?, ?, ?, ?)";
		$logParams = [$user, 'INSERT INTO tbl_user', $userLAB, $time, $ip, $os, 'New user'];
		sqlsrv_query($con, $logSql, $logParams);
		echo " <script>window.location='?p=user';</script>";
	}
}
