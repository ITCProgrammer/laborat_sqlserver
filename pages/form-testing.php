<!doctype html>
<html>

<head>
	<meta charset="utf-8">
	<title>Form Testing</title>
	<script>
		function uncheckAll() {
			var checkboxes = document.querySelectorAll('input[type="checkbox"]');
			checkboxes.forEach(function(checkbox) {
				checkbox.checked = false;
			});
		}
	</script>
</head>

<body>
	<?php
	ini_set("error_reporting", 1);
	session_start();
	include "koneksi.php";
	function nourut($str)
	{
		date_default_timezone_set('Asia/Jakarta');
		$bln = date("ym");
		$today = date("ymd");
		$sqlnotes = sqlsrv_query(
			$GLOBALS['con'],
			"SELECT TOP 1 no_counter FROM db_laborat.tbl_test_qc WHERE no_counter LIKE ? ORDER BY no_counter DESC",
			['%' . $bln . '%']
		);
		$dt = 0;
		if ($sqlnotes && ($rd = sqlsrv_fetch_array($sqlnotes, SQLSRV_FETCH_ASSOC))) {
			$dt = $rd['no_counter'];
			$strd = substr($dt, 6, 4);
			$Urutd = (int)$strd;
		} else {
			$Urutd = 0;
		}
		$Urutd = $Urutd + 1;
		$Nold = "";
		$nilaid = 4 - strlen($Urutd);
		for ($i = 1; $i <= $nilaid; $i++) {
			$Nold = $Nold . "0";
		}
		$no2 = $today . $Nold . $Urutd;
		//$no2 =$today.str_pad($Urutd, 4, "0",  STR_PAD_LEFT);
		return $no2;
	}

	$sqlNoCounter = sqlsrv_query($con, "SELECT TOP 1 no_counter FROM db_laborat.tbl_test_qc ORDER BY id DESC");
	$noCounter = $sqlNoCounter ? sqlsrv_fetch_array($sqlNoCounter, SQLSRV_FETCH_ASSOC) : null;
	$nourut = nourut($noCounter ? $noCounter['no_counter'] : '');
	$idR	= isset($_GET["idk"]) ? $_GET["idk"] : '';
	$sqlMatching = sqlsrv_query($con, "SELECT * FROM db_laborat.tbl_matching WHERE no_resep = ?", [$idR]);
	$dt	= $sqlMatching ? sqlsrv_fetch_array($sqlMatching, SQLSRV_FETCH_ASSOC) : null;
	$dt = $dt ?: [];
	$buyerTest = trim($dt['buyer'] ?? '') . " " . trim($dt['no_item'] ?? '');
	$hostSVR221 = "10.0.0.221";
	$usernameSVR221 = "sa";
	$passwordSVR221 = "Ind@taichen2024";
	$qc = "db_qc";
	$conQC = sqlsrv_connect($hostSVR221, [
		"Database" => $qc,
		"UID" => $usernameSVR221,
		"PWD" => $passwordSVR221,
		"CharacterSet" => "UTF-8"
	]);
	if (! $conQC) {
		exit("SQLSVR19 db_qc Connection failed");
	}

	$qMB = sqlsrv_query($conQC, "SELECT * FROM db_qc.tbl_masterbuyer_test WHERE buyer = ?", [$buyerTest]);
	$dMB = $qMB ? sqlsrv_fetch_array($qMB, SQLSRV_FETCH_ASSOC) : null;
	$detail2 = ($dMB && isset($dMB['colorfastness'])) ? explode(",", $dMB['colorfastness']) : [];
	$cek1 = $cek1 ?? 0;
	$r1 = $r1 ?? [];

	function jsAlertAndRedirect($message, $redirectUrl = '?p=Form-Testing')
	{
		$msg = json_encode($message, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		$url = json_encode($redirectUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
		echo "<script>alert($msg);window.location.href=$url;</script>";
	}

	function safeLen($value)
	{
		$val = (string)$value;
		return function_exists('mb_strlen') ? mb_strlen($val, 'UTF-8') : strlen($val);
	}

	function addLengthViolation(&$issues, $label, $value, $max)
	{
		$len = safeLen($value);
		if ($len > $max) {
			$issues[] = "$label terlalu panjang ($len/$max).";
		}
	}

	function formatSqlsrvErrorForAlert($errors)
	{
		if (!is_array($errors) || empty($errors)) {
			return 'Gagal menyimpan data. Silakan coba lagi.';
		}

		$primary = $errors[0];
		$code = isset($primary['code']) ? (int)$primary['code'] : 0;
		$message = isset($primary['message']) ? trim((string)$primary['message']) : '';

		if ($code === 2628) {
			if (preg_match("/column '([^']+)'/i", $message, $matches)) {
				return "Data terlalu panjang pada kolom {$matches[1]}. Mohon dipendekkan lalu simpan ulang.";
			}
			return 'Ada data yang melebihi batas panjang kolom database.';
		}

		if ($code === 2627) {
			return 'No Counter sudah terpakai (duplikat). Silakan refresh halaman lalu simpan ulang.';
		}

		return "SQL Error [$code]: $message";
	}

	?>
	<?php
	if (isset($_POST['simpan'])) {

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

		if (!$con) {
			jsAlertAndRedirect('Koneksi ke database laborat gagal. Cek server SQL lalu coba lagi.');
			exit;
		}

		$warna = trim((string)($_POST['warna'] ?? ''));
		$nowarna = trim((string)($_POST['nowarna'] ?? ''));
		$buyer = trim((string)($_POST['buyer'] ?? ''));
		$kain = trim((string)($_POST['jenis_kain'] ?? ''));
		$item = trim((string)($_POST['noitem'] ?? ''));
		$nama = trim((string)($_POST['nama'] ?? ''));
		$cck_warna = trim((string)($_POST['cck_warna'] ?? ''));
		$note_lab = trim((string)($_POST['note_lab'] ?? ''));
		$userLAB = $_SESSION['userLAB'] ?? '';
		$dyestuff = trim((string)($_POST['Dyestuff'] ?? ''));
		$suffix = trim((string)($_POST['suffix'] ?? ''));
		$status = trim((string)($_POST['sts'] ?? ''));
		$noResep = trim((string)($_POST['no_resep'] ?? ''));
		$jenMatching = (isset($_POST['jen_matching']) && is_array($_POST['jen_matching'])) ? $_POST['jen_matching'] : [];

		$chkc = '';
		if (!empty($_POST['colorfastness']) && is_array($_POST['colorfastness'])) {
			$chkc = implode(',', $_POST['colorfastness']);
		}

		$validationErrors = [];
		if ($dyestuff === '') {
			$validationErrors[] = 'Jenis Testing wajib dipilih.';
		}
		if (empty($jenMatching)) {
			$validationErrors[] = 'Treatment wajib dipilih minimal 1.';
		}
		if ($status === '') {
			$validationErrors[] = 'Status wajib dipilih.';
		}
		if ($nama === '') {
			$validationErrors[] = 'Nama Personil Testing wajib diisi.';
		}

		addLengthViolation($validationErrors, 'Jenis Testing', $dyestuff, 2);
		addLengthViolation($validationErrors, 'Suffix', $suffix, 20);
		addLengthViolation($validationErrors, 'Buyer', $buyer, 100);
		addLengthViolation($validationErrors, 'No Warna', $nowarna, 100);
		addLengthViolation($validationErrors, 'Nama Warna', $warna, 100);
		addLengthViolation($validationErrors, 'Jenis Kain', $kain, 500);
		addLengthViolation($validationErrors, 'Cocok Warna', $cck_warna, 100);
		addLengthViolation($validationErrors, 'No Item', $item, 20);
		addLengthViolation($validationErrors, 'Permintaan Testing', $chkc, 250);
		addLengthViolation($validationErrors, 'Nama Personil Testing', $nama, 100);
		addLengthViolation($validationErrors, 'Status', $status, 7);
		addLengthViolation($validationErrors, 'Note Lab', $note_lab, 200);
		addLengthViolation($validationErrors, 'User LAB', $userLAB, 100);

		if (!empty($validationErrors)) {
			jsAlertAndRedirect("Data belum bisa disimpan:\n- " . implode("\n- ", $validationErrors));
			exit;
		}

		sqlsrv_begin_transaction($con);

		$success = true;
		$errorMessage = '';

		if (!empty($jenMatching)) {
			$notrt = 1;

			foreach ($jenMatching as $index => $subject1) {
				$ktjen = trim((string)$subject1);
				$nocount = $noResep . "-" . $notrt;

				$rowValidation = [];
				addLengthViolation($rowValidation, 'No Counter', $nocount, 15);
				addLengthViolation($rowValidation, 'Treatment', $ktjen, 200);
				if (!empty($rowValidation)) {
					$success = false;
					$errorMessage = "Data belum bisa disimpan:\n- " . implode("\n- ", $rowValidation);
					break;
				}

				$checkDup = sqlsrv_query(
					$con,
					"SELECT COUNT(1) AS jml FROM db_laborat.tbl_test_qc WHERE RTRIM(no_counter) = RTRIM(?)",
					[$nocount]
				);
				if (!$checkDup) {
					$success = false;
					$errorMessage = formatSqlsrvErrorForAlert(sqlsrv_errors(SQLSRV_ERR_ERRORS));
					break;
				}
				$dupRow = sqlsrv_fetch_array($checkDup, SQLSRV_FETCH_ASSOC);
				if ($dupRow && (int)$dupRow['jml'] > 0) {
					$success = false;
					$errorMessage = "No Counter \"$nocount\" sudah ada. Refresh halaman lalu simpan ulang.";
					break;
				}

				// $qry = mysqli_query($con, "INSERT INTO tbl_test_qc (no_counter, treatment, jenis_testing, suffix, buyer, no_warna, warna, jenis_kain, no_item, permintaan_testing, nama_personil_test, tgl_buat, tgl_update, sts_laborat, sts_qc, sts, created_by)
	            //     VALUES ('$nocount', '$ktjen', '$_POST[Dyestuff]', '$_POST[suffix]', '$buyer', '$nowarna', '$warna', '$kain', '$item', '$chkc', '$nama', NOW(), NOW(), 'Open', 'Belum Terima Kain', '$_POST[sts]', '$_SESSION[userLAB]')");
	
				$qry = sqlsrv_query(
					$con,
					"INSERT INTO db_laborat.tbl_test_qc (no_counter, treatment, jenis_testing, suffix, buyer, no_warna, warna, jenis_kain, cocok_warna, no_item, permintaan_testing, nama_personil_test, tgl_buat, tgl_update, sts_laborat, sts_qc, sts, note_laborat, created_by)
	                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), GETDATE(), 'Open', 'Belum Terima Kain', ?, ?, ?)",
					[
						$nocount,
						$ktjen,
						$dyestuff,
						$suffix,
						$buyer,
						$nowarna,
						$warna,
						$kain,
						$cck_warna,
						$item,
						$chkc,
						$nama,
						$status,
						$note_lab,
						$userLAB
					]
				);


				if (!$qry) {
					$success = false;
					$errorMessage = formatSqlsrvErrorForAlert(sqlsrv_errors(SQLSRV_ERR_ERRORS));
					break; // Keluar dari loop jika salah satu query gagal
				}

				// Jika query berhasil, tambahkan juga ke log_qc_test
				$qry2 = sqlsrv_query(
					$con,
					"INSERT INTO db_laborat.log_qc_test (no_counter, status, info, do_by, do_at, ip_address)
	                 VALUES (?, 'Open', 'Kain diserahkan dari laborat', ?, GETDATE(), ?)",
					[$nocount, $userLAB, $ip_num]
				);

				if (!$qry2) {
					$success = false;
					$errorMessage = formatSqlsrvErrorForAlert(sqlsrv_errors(SQLSRV_ERR_ERRORS));
					break; // Keluar dari loop jika salah satu query gagal
				}

				$notrt++;
			}
		}

		// Commit transaksi jika semua operasi berhasil, rollback jika ada yang gagal
		if ($success) {
			sqlsrv_commit($con);
			echo "<script>alert('Data Tersimpan');window.location.href='?p=TestQCFinal';</script>";
		} else {
			sqlsrv_rollback($con);
			if ($errorMessage === '') {
				$errorMessage = 'Gagal menyimpan data. Silakan coba lagi.';
			}
			jsAlertAndRedirect($errorMessage);
		}
	}
	// if (isset($_POST['simpan'])) {
	// 	$ip_num = $_SERVER['REMOTE_ADDR'];
	// 	$warna = str_replace("'", "''", $_POST['warna']);
	// 	$nowarna = str_replace("'", "", $_POST['nowarna']);
	// 	$buyer = str_replace("'", "", $_POST['buyer']);
	// 	$kain = str_replace("'", "", $_POST['jenis_kain']);
	// 	$item = str_replace("'", "", $_POST['noitem']);
	// 	$nama = str_replace("'", "", $_POST['nama']);

	// 	$checkbox1 = $_POST['colorfastness'];

	// 	foreach ($checkbox1 as $chk1) {
	// 		$chkc .= $chk1 . ",";
	// 	}

	// 	if (isset($_POST["jen_matching"])) {
	// 		// Retrieving each selected option 
	// 		$notrt = 1;
	// 		foreach ($_POST['jen_matching'] as $index => $subject1) {
	// 			if ($index > 0) {
	// 				//	$ktjen = $ktjen . "," . $subject1;
	// 				$ktjen = $subject1;
	// 				$nocount = $_POST['no_resep'] . "-" . $notrt;
	// 				$qry = mysqli_query($con, "INSERT INTO tbl_test_qc SET
	// 				no_counter ='$nocount',
	// 				treatment = '$ktjen',
	// 				jenis_testing='$_POST[Dyestuff]',
	// 				suffix='$_POST[suffix]',
	// 				buyer ='$buyer',
	// 				no_warna='$nowarna',
	// 				warna='$warna',
	// 				jenis_kain='$kain',
	// 				no_item ='$item',
	// 				permintaan_testing ='$chkc',
	// 				nama_personil_test='$nama',
	// 				tgl_buat= now(),
	// 				tgl_update=now(),
	// 				sts_laborat='Open',
	// 				sts_qc='Belum Terima Kain',
	// 				sts='$_POST[sts]',
	// 				created_by = '$_SESSION[userLAB]'");

	// 				$qry2 = "INSERT INTO log_qc_test (no_counter, `status`, info, do_by, do_at, ip_address) 
	//     		 	VALUES ('$nocount', 'Open', 'Kain diserahkan dari laborat', '$userLAB', NOW(), '$ip_num')";
	// 			} else {
	// 				$ktjen = $subject1;
	// 				$nocount = $_POST['no_resep'] . "-" . $notrt;
	// 				$qry = mysqli_query($con, "INSERT INTO tbl_test_qc SET
	// 				no_counter ='$nocount',
	// 				treatment = '$ktjen',
	// 				jenis_testing='$_POST[Dyestuff]',
	// 				suffix='$_POST[suffix]',
	// 				buyer ='$buyer',
	// 				no_warna='$nowarna',
	// 				warna='$warna',
	// 				jenis_kain='$kain',
	// 				no_item ='$item',
	// 				permintaan_testing ='$chkc',
	// 				nama_personil_test='$nama',
	// 				tgl_buat= now(),
	// 				tgl_update=now(),
	// 				sts_laborat='Open',
	// 				sts_qc='Belum Terima Kain',
	// 				sts='$_POST[sts]',
	// 				created_by = '$_SESSION[userLAB]'");

	// 				$qry2 = "INSERT INTO log_qc_test (no_counter, `status`, info, do_by, do_at, ip_address) 
	//     		 	VALUES ('$nocount', 'Open', 'Kain diserahkan dari laborat', '$userLAB', NOW(), '$ip_num')";
	// 			}
	// 			$notrt++;
	// 		}
	// 	}


	// 	if ($qry) {
	// 		echo "<script>alert('Data Tersimpan');window.location.href='?p=TestQCFinal';</script>";
	// 	}
	// }
	?>
	<div class="row">
		<div class="col-md-12">
			<!-- Custom Tabs -->
			<div class="nav-tabs-custom">
				<ul class="nav nav-tabs">
					<li class="active"><a href="#tab_1" data-toggle="tab">Input Order</a></li>
				</ul>
				<div class="tab-content">
					<div class="tab-pane active" id="tab_1">
						<form class="form-horizontal" action="" method="post" enctype="multipart/form-data" name="form1">
							<div class="box-body">
								<div class="form-group">
									<label for="order" class="col-sm-2 control-label">Jenis Testing</label>
									<button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#staticBackdrop">
										...
									</button>
									<div class="col-sm-2">
											<select value="<?php echo isset($_GET['Dystf']) ? $_GET['Dystf'] : '' ?>" type="text" class="form-control select2" id="Dyestuff" name="Dyestuff" required>
											<option value="" selected disabled>Pilih Jenis Testing</option>
											<?php
											$sqlmstrcd = sqlsrv_query($con, "SELECT kode, value FROM db_laborat.tbl_mstrjnstesting ORDER BY kode ASC;");
											while ($sqlmstrcd && ($li = sqlsrv_fetch_array($sqlmstrcd, SQLSRV_FETCH_ASSOC))) { ?>
												<option value="<?php echo $li['value'] ?>" <?php if (isset($_GET['Dystf']) && $li['value'] == $_GET['Dystf']) {
																								echo 'selected';
																							} ?>><?php echo $li['kode'] ?></option>
											<?php } ?>
										</select>
									</div>
								</div>
								<input type="hidden" value="<?php echo $nourut; ?>" id="shadow_no_resep" name="shadow_no_resep">
								<div class=" form-group">
									<label for="no_resep" class="col-sm-2 control-label">Counter</label>
									<div class="col-sm-2">
										<input name="no_resep" type="text" class="form-control" id="no_resep" placeholder="No Resep" required readonly>
									</div>
								</div>
								<div class="form-group">
									<label for="suffix" class="col-sm-2 control-label">Suffix</label>
									<div class="col-sm-4">
										<input name="suffix" placeholder="Suffix ..." type="text" onkeyup="this.value = this.value.toUpperCase();" class="form-control suffixcuy" id="order" onchange="window.location='?p=Form-Testing&idk='+this.value+'&Dystf='+document.getElementById(`Dyestuff`).value" value="<?php if (isset($_GET['idk']) && $_GET['idk'] != "") {
																																																																															echo $_GET['idk'];
																																																																														} ?>" required>
									</div>
								</div>
								<div class=" form-group">
									<label for="jen_matching" class="col-sm-2 control-label">Treatment</label>
									<div class="col-sm-3">
										<select class="form-control select2" multiple="multiple" id="jen_matching" name="jen_matching[]" data-placeholder="Pilih Jenis Treatment" required>
											<!--<option selected disabled>Pilih...</option>-->
											<option <?php if (isset($_GET['jn_mcng']) && $_GET['jn_mcng'] == "non sublimasi / FIN") {
														echo "selected";
													} ?> value="non sublimasi / FIN">non sublimasi / FIN</option>
											<option <?php if (isset($_GET['jn_mcng']) && $_GET['jn_mcng'] == "sublimasi 110C") {
														echo "selected";
													} ?> value="sublimasi 110C">sublimasi 110'C</option>
											<option <?php if (isset($_GET['jn_mcng']) && $_GET['jn_mcng'] == "sublimasi 120C") {
														echo "selected";
													} ?> value="sublimasi 120C">sublimasi 120'C</option>
											<option <?php if (isset($_GET['jn_mcng']) && $_GET['jn_mcng'] == "sublimasi 130C") {
														echo "selected";
													} ?> value="sublimasi 130C">sublimasi 130'C</option>
											<option <?php if (isset($_GET['jn_mcng']) && $_GET['jn_mcng'] == "sublimasi 140C") {
														echo "selected";
													} ?> value="sublimasi 140C">sublimasi 140'C</option>
											<option <?php if (isset($_GET['jn_mcng']) && $_GET['jn_mcng'] == "FINISHING (cotton/ CVC)") {
														echo "selected";
													} ?> value="FINISHING (cotton/ CVC)">FINISHING (cotton/ CVC)</option>
											<option <?php if (isset($_GET['jn_mcng']) && $_GET['jn_mcng'] == "non WR") {
														echo "selected";
													} ?> value="non WR">non WR</option>
											<option <?php if (isset($_GET['jn_mcng']) && $_GET['jn_mcng'] == "WR") {
														echo "selected";
													} ?> value="WR">WR</option>
											<option <?php if (isset($_GET['jn_mcng']) && $_GET['jn_mcng'] == "non protx2") {
														echo "selected";
													} ?> value="non protx2">non protx2</option>
											<option <?php if (isset($_GET['jn_mcng']) && $_GET['jn_mcng'] == "protx2") {
														echo "selected";
													} ?> value="protx2">protx2</option>
										</select>
									</div>
								</div>
								<div id="echoing_the_choice">
									<div id="before_append">
										<div class=" form-group">
											<label for="order" class="col-sm-4 control-label" style="font-style: italic;"> Pilih Jenis Treatment untuk men-generate form...</label>
										</div>
									</div>
								</div>
							</div>
						</form>
						<!-- /.box-body -->
					</div>
					<!-- /.tab-pane -->

				</div>
				<!-- /.tab-content -->
			</div>
			<!-- nav-tabs-custom -->
		</div>
		<!-- /.col -->
	</div>
</body>
<!-- Modal -->
<div class="modal fade" id="staticBackdrop" data-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title text-center" id="staticBackdropLabel">Rincian Kode</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div class="container-fluid bg-light">
					<table id="tablee" class="display compact nowrap" style="width:100%">
						<thead>
							<th>No.</th>
							<th>Kode</th>
							<th class="text-center">Keterangan</th>
						</thead>
						<tbody>
							<?php
							$i = 1;
							$sqlmstrcd = sqlsrv_query($con, "SELECT kode, keterangan FROM db_laborat.tbl_mstrjnstesting;");
							while ($sqlmstrcd && ($title = sqlsrv_fetch_array($sqlmstrcd, SQLSRV_FETCH_ASSOC))) {
								echo '<tr><td>' . $i++ . '.</td>
									<td>' . $title['kode'] . '</td>
									<td>' . $title['keterangan'] . '</td></tr>';
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-info" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>
<!-- end modal -->
<div style="display: none;" id="hidding-choice">

</div>
<!--/////////////////////////////////////////////////////////////// inputanTest -->
<div id="inputanTest" style="display: none;">

	<div class="form-group">
		<label for="buyer" class="col-sm-2 control-label">Buyer</label>
		<div class="col-sm-8">
			<input name="buyer" type="text" class="form-control" id="buyer" placeholder="buyer" value="<?= isset($dt['langganan']) ? $dt['langganan'] : ''; ?>">
		</div>
	</div>
	<div class="form-group">
		<label for="nowarna" class="col-sm-2 control-label">No Warna</label>
		<div class="col-sm-6">
			<input name="nowarna" type="text" class="form-control" id="nowarna" placeholder="No Warna" value="<?php if ($cek1 > 0) {
																													echo $r1['color'] ?? '';
																												} else {
																													echo $dt['no_warna'] ?? '';
																												} ?>">
		</div>
	</div>
	<div class="form-group">
		<label for="warna" class="col-sm-2 control-label">Nama Warna</label>
		<div class="col-sm-6">
			<input name="warna" type="text" class="form-control" id="warna" placeholder="Nama Warna" value="<?php if ($cek1 > 0) {
																												echo $r1['color'] ?? '';
																											} else {
																												echo $dt['warna'] ?? '';
																											} ?>">
		</div>
	</div>
	<div class="form-group">
		<label for="noitem" class="col-sm-2 control-label">Item</label>
		<div class="col-sm-6">
			<input name="noitem" type="text" class="form-control" id="noitem" placeholder="No Item" value="<?php if ($cek1 > 0) {
																												echo $r1['colorno'] ?? '';
																											} else {
																												echo $dt['no_item'] ?? '';
																											} ?>">
		</div>
	</div>
	<div class="form-group">
		<label for="jenis_kain" class="col-sm-2 control-label">Jenis Kain</label>
		<div class="col-sm-8">
			<input name="jenis_kain" type="text" class="form-control" id="jenis_kain" placeholder="Jenis Kain" value="<?php if ($cek1 > 0) {
																															echo htmlentities($r1['description'] ?? '', ENT_QUOTES);
																														} else {
																															echo $dt['jenis_kain'] ?? '';
																														} ?>">
		</div>
	</div>

	<div class="form-group">
		<label for="warna" class="col-sm-2 control-label">Cocok Warna</label>
		<div class="col-sm-6">
			<input name="cck_warna" type="text" class="form-control" id="cck_warna" placeholder="Cocok Warna" value="<?= isset($dt['cocok_warna']) ? $dt['cocok_warna'] : ''; ?>">
		</div>
	</div>

	<div class="form-group">
		<label for="nama" class="col-sm-2 control-label">Nama Personil Testing</label>
		<div class="col-sm-6">
			<input name="nama" type="text" class="form-control" id="nama" placeholder="nama" value="" required>
		</div>
	</div>
	<div class="form-group">
		<label for="permintaan_testing" class="col-sm-2 control-label">Permintaan Testing</label>
		<div class="col-sm-2">
			<label><input type="checkbox" class="minimal" name="colorfastness[]" value="WASHING" <?php if (in_array("WASHING", $detail2)) {
																										echo "checked";
																									} ?>> Washing Fastness
			</label>
			<br>
			<label><input type="checkbox" class="minimal" name="colorfastness[]" value="PERSPIRATION ACID" <?php if (in_array("PERSPIRATION ACID", $detail2)) {
																												echo "checked";
																											} ?>> Perpiration Fastness ACID
			</label>
			<br>
			<label><input type="checkbox" class="minimal" name="colorfastness[]" value="PERSPIRATION ALKALINE" <?php if (in_array("PERSPIRATION ACID", $detail2)) {
																													echo "checked";
																												} ?>> Perpiration Fastness ALKALINE
			</label>
			<br>
			<label><input type="checkbox" class="minimal" name="colorfastness[]" value="WATER" <?php if (in_array("WATER", $detail2)) {
																									echo "checked";
																								} ?>> Water Fastness
			</label>

			<br>
			<label><input type="checkbox" class="minimal" name="colorfastness[]" value="CROCKING" <?php if (in_array("CROCKING", $detail2)) {
																										echo "checked";
																									} ?>> Crocking Fastness
			</label>
			<br>
			<label><input type="checkbox" class="minimal" name="colorfastness[]" value="COLOR MIGRATION-OVEN TEST" <?php if (in_array("COLOR MIGRATION-OVEN TEST", $detail2)) {
																														echo "checked";
																													} ?>> Color Migration - Oven Test
			</label>
			<br>
			<label><input type="checkbox" class="minimal" name="colorfastness[]" value="COLOR MIGRATION" <?php if (in_array("COLOR MIGRATION", $detail2)) {
																												echo "checked";
																											} ?>> Color Migration Fastness
				<br>
				<label><input type="checkbox" class="minimal" name="colorfastness[]" value="CHLORIN & NON-CHLORIN" <?php if (in_array("CHLORIN & NON-CHLORIN", $detail2)) {
																														echo "checked";
																													} ?>> Chlorin &amp; Non-Chlorin
				</label>
				<br>
				<label><input type="checkbox" class="minimal" name="colorfastness[]" value="BLEEDING" <?php if (in_array("BLEEDING", $detail2)) {
																												echo "checked";
																											} ?>> Bleeding
				</label>
				<br>
				<label><input type="checkbox" class="minimal" name="colorfastness[]" value="PHENOLIC YELLOWING" <?php if (in_array("PHENOLIC YELLOWING", $detail2)) {
																													echo "checked";
																												} ?>> Phenolic Yellowing
				</label>

		</div>
		<div class="col-sm-2">
			<label><input type="checkbox" class="minimal" name="colorfastness[]" value="LIGHT" <?php if (in_array("LIGHT", $detail2)) {
																									echo "checked";
																								} ?>> Light Fastness
			</label> <br>
			<label><input type="checkbox" class="minimal" name="colorfastness[]" value="LIGHT PERSPIRATION" <?php if (in_array("LIGHT PERSPIRATION", $detail2)) {
																												echo "checked";
																											} ?>> Light Perspiration
			</label> <br>
			<label><input type="checkbox" class="minimal" name="colorfastness[]" value="PH" <?php if (in_array("PH", $detail2)) {
																								echo "checked";
																							} ?>> PH3 &amp; PH4
			</label> <br>
			<label><input type="checkbox" class="minimal" name="colorfastness[]" value="SUHU" <?php if (in_array("SUHU", $detail2)) {
																									echo "checked";
																								} ?>> SUHU 30'C &amp; 40'C
			</label> <br>
			<label><input type="checkbox" class="minimal" name="colorfastness[]" value="APPEARANCE AFTER WASH" <?php if (in_array("APPEARANCE AFTER WASH", $detail2)) {
																									echo "checked";
																								} ?>> Appearance After Wash
			</label> <br>
		</div>
	</div>
	<div class="form-group">
		<label for="sts" class="col-sm-2 control-label"></label>
		<div class="col-sm-6">
			<a href="#" class="btn btn-xs btn-danger" onclick="uncheckAll();">Full Test</a>
		</div>
	</div>
	<div class="form-group">
		<label for="sts" class="col-sm-2 control-label">Status</label>
		<div class="col-sm-6">
			<select class="form-control select2" id="sts" name="sts" required>
				<option value="" selected disabled>Pilih status</option>
				<option value="normal">Normal</option>
				<option value="urgent">Urgent</option>
				<option value="request">Request</option>
			</select>
		</div>
	</div>

	<div class="form-group">
		<label for="note_lab" class="col-sm-2 control-label">Note Lab</label>
		<div class="col-sm-6">
			<input name="note_lab" type="text" class="form-control" id="note_lab" placeholder="Note Lab" value="" required>
		</div>
	</div>
	<div class="box-footer">
		<div class="col-sm-2">
			<button type="submit" class="btn btn-block btn-social btn-linkedin" name="simpan" style="width: 80%">Simpan <i class="fa fa-save"></i></button>
		</div>
	</div>
</div>



<script>
	$(document).ready(function() {
		$('.datepicker').datepicker({
			autoclose: true,
			format: 'yyyy-mm-dd',
			todayHighlight: true,
		})

		if ($('.form-control.suffixcuy').val().length >= 2) {


			$("#echoing_the_choice").children(":first").appendTo('#hidding-choice');
			$('#inputanTest').appendTo('#echoing_the_choice');
			$("#inputanTest").show()

		}

		let antrian = $('#shadow_no_resep').val();
		var no_resep_fix = antrian + $(this).find(":selected").val();
		$('#no_resep').val(no_resep_fix);

		$('#Dyestuff').change(function() {
			var Q = $('#shadow_no_resep').val();
			var no_resep_fix = Q + $(this).find(":selected").val();
			$('#no_resep').val(no_resep_fix);
		})

		$('#jen_matching').change(function() {
			if ($(this).find(":selected").val() != '') {
				$("#echoing_the_choice").children(":first").appendTo('#hidding-choice');
				$('#inputanTest').appendTo('#echoing_the_choice');
				$("#inputanTest").show()
			}
		})

	});
</script>

</html>
