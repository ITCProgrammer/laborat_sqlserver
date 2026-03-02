<?php
ini_set("error_reporting", 1);
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
include "koneksi.php";

if (!function_exists('qcf_print_api_send')) {
	function qcf_print_api_send($docNumber)
	{
		$url = "http://10.0.0.121:8080/api/v1/document/create";
		$payload = json_encode([
			"doc_number" => $docNumber,
			"ip_address" => '10.0.6.225'
		]);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		$response = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);

		if ($error) {
			return [
				'success' => 0,
				'message' => "CURL Error: " . $error,
				'response' => $response
			];
		}

		$result = json_decode($response, true);
		$ok = isset($result['success']) && $result['success'] ? 1 : 0;
		$msg = isset($result['message']) ? (string)$result['message'] : 'Unknown response';

		return [
			'success' => $ok,
			'message' => $msg,
			'response' => $response
		];
	}
}

if (!function_exists('qcf_has_recent_print_log')) {
	function qcf_has_recent_print_log($conn, $docNumber, $seconds = 8)
	{
		$stmt = sqlsrv_query(
			$conn,
			"SELECT TOP 1 1 AS found
			 FROM db_laborat.log_printing
			 WHERE no_resep = ?
			   AND created_at >= DATEADD(SECOND, ?, GETDATE())",
			[$docNumber, -abs((int)$seconds)]
		);
		if (! $stmt) {
			return false;
		}
		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		sqlsrv_free_stmt($stmt);
		return $row ? true : false;
	}
}

if (isset($_POST['print_matching'])) {
	header('Content-Type: application/json; charset=utf-8');
	if (!isset($_SESSION['userLAB']) || !isset($_SESSION['passLAB'])) {
		http_response_code(401);
		echo json_encode([
			'ok' => false,
			'message' => 'Session login habis, silakan login ulang'
		]);
		exit;
	}

	$no_resep = strtoupper(trim((string)($_POST['noresep'] ?? '')));
	$time = date('Y-m-d H:i:s');
	$ip_num = $_SERVER['REMOTE_ADDR'];
	$createdBy = $_SESSION['userLAB'] ?? '';
	$isDr = (substr($no_resep, 0, 2) === 'DR');
	$targetDocs = $isDr ? [$no_resep . '-A', $no_resep . '-B'] : [$no_resep];

	if ($no_resep === '') {
		echo json_encode([
			'ok' => false,
			'message' => 'No resep kosong'
		]);
		exit;
	}

	$printed = 0;
	$skipped = 0;
	$failed = 0;

	foreach ($targetDocs as $docNo) {
		// Anti duplicate request: request yang sama dalam 8 detik akan diabaikan.
		if (qcf_has_recent_print_log($con, $docNo, 8)) {
			$skipped++;
			continue;
		}

		$info = ($docNo === ($no_resep . '-A')) ? 'generate no resep DR-A' : (($docNo === ($no_resep . '-B')) ? 'generate no resep DR-B' : 'generate no resep');
		sqlsrv_query(
			$con,
			"INSERT INTO db_laborat.log_status_matching (ids, status, info, do_by, do_at, ip_address)
			 VALUES (?, 'Create No.resep', ?, ?, ?, ?)",
			[$docNo, $info, $createdBy, $time, $ip_num]
		);

		$api = qcf_print_api_send($docNo);
		$logSuccess = (int)$api['success'];
		$logMessage = addslashes((string)$api['message']);
		$responseRaw = isset($api['response']) ? $api['response'] : null;

		sqlsrv_query(
			$con,
			"INSERT INTO db_laborat.log_printing (no_resep, ip_address, success, message, response_raw, created_at, created_by)
			 VALUES (?, ?, ?, ?, ?, GETDATE(), ?)",
			[$docNo, $ip_num, $logSuccess, $logMessage, $responseRaw, $createdBy]
		);

		if ($logSuccess) {
			$printed++;
		} else {
			$failed++;
		}
	}

	$ok = ($failed === 0) && (($printed + $skipped) > 0);
	$message = 'Perintah print berhasil dikirim';
	if ($failed > 0) {
		$message = 'Sebagian print gagal dikirim';
	} elseif ($printed === 0 && $skipped > 0) {
		$message = 'Permintaan duplikat terdeteksi, proses print kedua diabaikan';
	}

	echo json_encode([
		'ok' => $ok,
		'printed' => $printed,
		'skipped' => $skipped,
		'failed' => $failed,
		'message' => $message
	]);
	exit;
}
// Ambil id matching
$noResep = isset($_GET['noresep']) ? $_GET['noresep'] : '';
$r1 = sqlsrv_fetch_array(
	sqlsrv_query(
		$con,
		"SELECT TOP 1 id FROM db_laborat.tbl_matching WHERE no_resep = ?",
		[$noResep]
	),
	SQLSRV_FETCH_ASSOC
);

$id = isset($_GET['id']) && $_GET['id'] !== '' ? $_GET['id'] : ($r1['id'] ?? '');
if (isset($_POST['save'])) {
	$kode = $_POST['kode'];
	$jns = $_POST['jenis'];
	$dyes = str_replace("'", "''", $_POST['dyes']);
	$lab = str_replace("'", "''", $_POST['lab']);
	$aktual = str_replace("'", "''", $_POST['aktual']);

	$qry = sqlsrv_query(
		$con,
		"INSERT INTO db_laborat.tbl_matching_detail (id_matching, kode, nama, lab, jenis)
		 VALUES (?, ?, ?, ?, ?)",
		[$id, $kode, $dyes, $lab, $jns]
	);
	if ($qry) {
		echo "<script>alert('Data Tersimpan');</script>";
		echo "<script>window.location.href='?p=Form-Matching-Detail&noresep=$_GET[noresep]&id=$_GET[id]';</script>";
	} else {
		echo "There's been a problem: ";
		print_r(sqlsrv_errors());
	}
}
?>

<style>
    #loading-overlay {
        position: fixed;
        z-index: 9999;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    #loading-overlay .loader-box {
        text-align: center;
        color: #fff;
        font-size: 16px;
    }
    #loading-overlay .spinner {
        border: 6px solid #f3f3f3;
        border-top: 6px solid #3498db;
        border-radius: 50%;
        width: 60px;
        height: 60px;
        margin: 0 auto 10px;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        0%   { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div id="loading-overlay">
    <div class="loader-box">
        <div class="spinner"></div>
        <div>Memuat data, harap tunggu...</div>
    </div>
</div>

<div class="box box-info">
	<form class="form-horizontal" action="" method="post" enctype="multipart/form-data" name="form1">
		<div class="box-header with-border">
			<h3 class="box-title">Form Matching Detail Dyes &amp; Chemical</h3>
			<div class="box-tools pull-right">
				<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
			</div>
		</div>
		<?php
		//$sqlsvr=mssql_query("SELECT * from PRODUCT WHERE ProductCode='$_GET[kd]'");
		//$dt=mssql_fetch_array($sqlsvr);
		?>
		<div class="box-body">
			<!-- 
			<div class="form-group">
				<label for="order" class="col-sm-2 control-label">No Resep</label>
				<div class="col-sm-2">
					<input name="no_resep" type="text" class="form-control" id="no_resep" value="<?php echo $_GET['noresep']; ?>" placeholder="No Resep">
				</div>
			</div>
			<div class="form-group">
				<label for="order" class="col-sm-2 control-label">Kode</label>
				<div class="col-sm-3">
					<input name="kode" type="text" class="form-control" id="kode" onChange="window.location='?p=Form-Matching-Detail&noresep=<?php echo $_GET['noresep']; ?>&id=<?php echo $_GET['id']; ?>&kd='+this.value" value="<?php echo $_GET['kd']; ?>" placeholder="Kode" required>
				</div>
			</div>
			<div class="form-group">
				<label for="dyes" class="col-sm-2 control-label">Dyes &amp; Chemical</label>
				<div class="col-sm-8">
					<input name="dyes" type="text" class="form-control" id="dyes" placeholder="Dyes &amp; Chemical" value="<?php echo trim($dt['ProductName']); ?>">
				</div>
			</div>
			<div class="form-group">
				<label for="lab" class="col-sm-2 control-label">Lab</label>
				<div class="col-sm-3">
					<input name="lab" type="text" class="form-control" id="lab" value="" placeholder="Lab">
				</div>
			</div>
			<div class="form-group">
				<label for="jenis" class="col-sm-2 control-label">&nbsp;</label>
				<div class="col-sm-3">
					<select name="jenis" class="form-control" id="jenis">
						<option value="">Pilih</option>
						<option value="Polyester">Polyester</option>
						<option value="Cotton">Cotton</option>

					</select>
				</div>
			</div> -->

		</div>
		<div class="box-footer">
			<!-- <div class="col-sm-2">
				<button type="submit" class="btn btn-block btn-social btn-linkedin" name="save" style="width: 80%">Simpan <i class="fa fa-save"></i></button>
			</div> -->
			<a href="pages/cetak/matching.php?idkk=<?php echo $_GET['noresep']; ?>" class="btn btn-danger pull-right" target="_blank" id="btnCetak"><span class="fa fa-print"></span> Cetak</a>
			<script>
				(function () {
					var btn = document.getElementById('btnCetak');
					if (!btn) return;

					var inFlight = false;
					btn.addEventListener('click', function(e) {
						e.preventDefault();
						if (inFlight) return;

						inFlight = true;
						btn.classList.add('disabled');
						btn.style.pointerEvents = 'none';
						btn.style.opacity = '0.7';
						btn.innerHTML = '<span class="fa fa-spinner fa-spin"></span> Proses...';

						var noresep = "<?php echo $_GET['noresep']; ?>";
						fetch("pages/form-matching-detail.php", {
							method: "POST",
							headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
							body: "print_matching=1&noresep=" + encodeURIComponent(noresep)
						})
						.then(function(res) { return res.text(); })
						.then(function(text) {
							var resp = {};
							try { resp = JSON.parse(text); } catch (e) {}

							if (resp && resp.ok) {
								window.open("pages/cetak/matching.php?idkk=" + noresep, "_blank");
								return;
							}

							var msg = (resp && resp.message) ? resp.message : ((text || '').trim() || 'Gagal kirim perintah print RFID');
							alert(msg);
						})
						.catch(function() {
							alert('Gagal kirim perintah print RFID');
						})
						.finally(function() {
							inFlight = false;
							btn.classList.remove('disabled');
							btn.style.pointerEvents = '';
							btn.style.opacity = '';
							btn.innerHTML = '<span class="fa fa-print"></span> Cetak';
						});
					});
				})();
			</script>
		</div>
		<!-- /.box-footer -->


	</form>
</div>
<div class="row">
	<div class="col-xs-12">
		<div class="box">
			<div class="box-header with-border">

			</div>
			<div class="box-body">
				<table id="example2" class="table table-bordered table-hover display" width="100%">
					<thead class="bg-green">
						<tr>
							<th width="37">
								<div align="center">No</div>
							</th>
							<th width="131">
								<div align="center">Kode</div>
							</th>
							<th width="516">
								<div align="center">Dyes &amp; Chemical</div>
							</th>
							<th width="266">
								<div align="center">Lab</div>
							</th>
							<th width="241">
								<div align="center">#</div>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$sql = sqlsrv_query($con, "SELECT * FROM db_laborat.tbl_matching_detail a
							INNER JOIN db_laborat.tbl_matching b ON b.id = a.id_matching
							WHERE b.no_resep = ?", [$_GET['noresep']]);
						$no = 0;
						$col = 0;
						while ($r = sqlsrv_fetch_array($sql, SQLSRV_FETCH_ASSOC)) {
							$no++;
							$bgcolor = ($col++ & 1) ? 'gainsboro' : 'antiquewhite'; ?>
							<tr bgcolor="<?php echo $bgcolor; ?>">
								<td align="center">
									<?php echo $no; ?>
								</td>
								<td align="center">
									<?php echo $r['kode']; ?>
								</td>
								<td align="center">
									<?php echo $r['nama']; ?>
								</td>
								<td>
									<?php echo $r['lab']; ?>
								</td>
								<td align="center">
									<?php echo $r['jenis']; ?>
								</td>
							</tr>
						<?php
						} ?>
					</tbody>

				</table>
			</div>
		</div>
	</div>
</div>

<script>
    $(window).on('load', function () {
        setTimeout(function () {
            $('#loading-overlay').fadeOut(300);
        }, 700);
    });
</script>
