<?php
ini_set("error_reporting", 1);
session_start();
include "koneksi.php";
$time = date('Y-m-d H:i:s');
$dbAuth = $con; // koneksi SQL Server db_laborat
if (! $dbAuth) {
	die('Koneksi login (db_laborat) gagal.');
}
$ip = $_SERVER['REMOTE_ADDR'];
$os = $_SERVER['HTTP_USER_AGENT'];
// var_dump($_SESSION);
// die;
?>
<?php
if ($_POST) { //login user
	$username = trim($_POST['username'] ?? '');
	$password = trim($_POST['password'] ?? '');

	$sql = sqlsrv_query($dbAuth, "SELECT TOP 1 * FROM db_laborat.tbl_user WHERE username = ? AND password = ?", [$username, $password]);
	if ($sql && ($r = sqlsrv_fetch_array($sql, SQLSRV_FETCH_ASSOC))) {
		$_SESSION['userLAB'] = $username;
		$_SESSION['passLAB'] = $password;
		$_SESSION['id'] = $r['id'];
		$_SESSION['lvlLAB'] = $r['level'];
		$_SESSION['statusLAB'] = $r['status'];
		$_SESSION['mamberLAB'] = $r['mamber'];
		$_SESSION['fotoLAB'] = $r['foto'];
		$_SESSION['jabatanLAB'] = $r['jabatan'];
		$_SESSION['os'] = $os;
		$_SESSION['ip'] = $ip;
		$_SESSION['pic_printrfid'] = $r['pic_printrfid'];
		$_SESSION['role_cycletime'] = $r['pic_cycletime'];
		// 1 == admin
		// 2 == spv
		// 3 == user
		//login_validate();
		sqlsrv_query($dbAuth, "INSERT INTO db_laborat.tbl_log (what, what_do, do_by, do_at, ip, os, remark) VALUES ('login', 'login into laborat', ?, ?, ?, ?, ?)", [
			$_SESSION['userLAB'],
			$time,
			$ip,
			$os,
			$_SESSION['jabatanLAB']
		]);
		if ($sql) {
			sqlsrv_free_stmt($sql);
		}
		echo "<script>window.location='index1.php?p=Home';</script>";
	} else {
		if ($sql) {
			sqlsrv_free_stmt($sql);
		}
		echo "<script>alert('Login Gagal!! $username');window.location='index.php';</script>";
	}
} elseif (isset($_GET['act']) && $_GET['act'] == "logout") { //logout user
	sqlsrv_query($dbAuth, "INSERT INTO db_laborat.tbl_log (what, what_do, do_by, do_at, ip, os, remark) VALUES ('Logout', 'Logout from laborat', ?, ?, ?, ?, ?)", [
		$_SESSION['userLAB'] ?? '',
		$time,
		$ip,
		$os,
		$_SESSION['jabatanLAB'] ?? ''
	]);
	session_destroy();
	echo "<script>window.location='login';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<title>Login Laborat</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!--===============================================================================================-->
	<link rel="icon" type="image/png" href="login_assets/images/icons/ITTI_Logo index.ico" />
	<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="login_assets/vendor/bootstrap/css/bootstrap.min.css">
	<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="login_assets/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="login_assets/fonts/iconic/css/material-design-iconic-font.min.css">
	<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="login_assets/vendor/animate/animate.css">
	<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="login_assets/vendor/css-hamburgers/hamburgers.min.css">
	<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="login_assets/vendor/animsition/css/animsition.min.css">
	<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="login_assets/vendor/select2/select2.min.css">
	<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="login_assets/vendor/daterangepicker/daterangepicker.css">
	<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="login_assets/css/util.css">
	<link rel="stylesheet" type="text/css" href="login_assets/css/main.css">
	<!--===============================================================================================-->
</head>

<body>

	<div class="limiter">
		<div class="container-login100" style="background-image: url('login_assets/images/247191809.jpg');">
			<div class="wrap-login100">
				<form class="login100-form validate-form" method="POST" action="">
					<span class="login100-form-logo">
						<img src="login_assets/logo-itti.png" alt="" width="80%" style="border-radius: 50%;">
					</span>

					<span class="login100-form-title p-b-34 p-t-27">
						LABORAT RESEP
					</span>

					<div class="wrap-input100 validate-input" data-validate="Enter username">
						<input class="input100" type="text" name="username" placeholder="Username" autofocus>
						<span class="focus-input100" data-placeholder="&#xf207;"></span>
					</div>

					<div class="wrap-input100 validate-input" data-validate="Enter password">
						<input class="input100" type="password" name="password" placeholder="Password">
						<span class="focus-input100" data-placeholder="&#xf191;"></span>
					</div>

					<div class="container-login100-form-btn">
						<button class="login100-form-btn" type="submit">
							Login
						</button>
					</div>
					</br>
					</br>
					<div class="container-login100-form-btn">
						<a href="stock_opname/index.php" target="_blank" class="btn btn-light btn-sm text-center"><i class="fa fa-barcode" aria-hidden="true"></i> Stock Opname GK</a>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!--===============================================================================================-->
	<script src="login_assets/vendor/jquery/jquery-3.2.1.min.js"></script>
	<!--===============================================================================================-->
	<script src="login_assets/vendor/animsition/js/animsition.min.js"></script>
	<!--===============================================================================================-->
	<script src="login_assets/vendor/bootstrap/js/popper.js"></script>
	<script src="login_assets/vendor/bootstrap/js/bootstrap.min.js"></script>
	<!--===============================================================================================-->
	<script src="login_assets/vendor/select2/select2.min.js"></script>
	<!--===============================================================================================-->
	<script src="login_assets/vendor/daterangepicker/moment.min.js"></script>
	<script src="login_assets/vendor/daterangepicker/daterangepicker.js"></script>
	<!--===============================================================================================-->
	<script src="login_assets/vendor/countdowntime/countdowntime.js"></script>
	<!--===============================================================================================-->
	<script src="login_assets/js/main.js"></script>

</body>

</html>
