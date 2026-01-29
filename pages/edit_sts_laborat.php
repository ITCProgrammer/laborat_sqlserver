<?php
ini_set("error_reporting", 1);
session_start();
include("../koneksi.php");

if ($_POST) {

	function get_client_ip()
	{
		$ipaddress = '';
		if (isset($_SERVER['HTTP_CLIENT_IP']))
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if (isset($_SERVER['HTTP_X_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		else if (isset($_SERVER['HTTP_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		else if (isset($_SERVER['REMOTE_ADDR']))
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		else
			$ipaddress = 'UNKNOWN';
		return $ipaddress;
	}

	$ip_num = get_client_ip();

	extract($_POST);
	$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
	$sts_laborat = isset($_POST['sts_laborat']) ? $_POST['sts_laborat'] : '';
	$no_counter = isset($_POST['no_counter']) ? $_POST['no_counter'] : '';
	$userLAB = isset($_SESSION['userLAB']) ? $_SESSION['userLAB'] : '';

	$success = true;

	sqlsrv_begin_transaction($con);

	if ($sts_laborat == "Waiting Approval Full") {
		$stsqc = "Kain OK";
		$stslaborat = "Approved Full";
	} elseif ($sts_laborat == "Waiting Approval Parsial") {
		$stsqc = "Kain OK Sebagian";
		$stslaborat = "Approved Parsial";
	}

	$query_update = "UPDATE db_laborat.tbl_test_qc SET 
                sts_laborat = ?,
                sts_qc = ?
                WHERE id = ?";

	$result_update = sqlsrv_query($con, $query_update, [$stslaborat, $stsqc, $id]);

	if (!$result_update) {
		$success = false;
	}

	$query_log = "INSERT INTO db_laborat.log_qc_test (no_counter, status, info, do_by, do_at, ip_address)
                   VALUES (?, ?, 'Sudah approve dari laborat', ?, GETDATE(), ?)";

	$result_log = sqlsrv_query($con, $query_log, [$no_counter, $stslaborat, $userLAB, $ip_num]);

	if (!$result_log) {
		$success = false;
	}

	if ($success) {
		sqlsrv_commit($con);
		echo "<script>window.location='?p=ApprovedTestReport';</script>";
	} else {
		sqlsrv_rollback($con);
		echo "<script>window.location='?p=ApprovedTestReport';</script>";
	}
}
