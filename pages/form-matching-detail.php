<?php
ini_set("error_reporting", 1);
session_start();
include "koneksi.php";
// Ambil id matching
$r1 = sqlsrv_fetch_array(
	sqlsrv_query(
		$con,
		"SELECT TOP 1 id FROM db_laborat.tbl_matching WHERE no_resep = ?",
		[$_GET['noresep']]
	),
	SQLSRV_FETCH_ASSOC
);
if ($_GET['id'] != "") {
	$id = $_GET['id'];
} else {
	$id = $r1['id'];
}
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
				document.getElementById('btnCetak').addEventListener('click', function(e) {
					e.preventDefault();
					var noresep = "<?php echo $_GET['noresep']; ?>";
					var xhr = new XMLHttpRequest();
					xhr.open("POST", "", true);
					xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
					xhr.onreadystatechange = function() {
						if (xhr.readyState === 4 && xhr.status === 200) {
							// Optionally handle response
							window.open("pages/cetak/matching.php?idkk=" + noresep, "_blank");
						}
					};
					xhr.send("print_matching=1&noresep=" + encodeURIComponent(noresep));
				});
			</script>
			<?php
			if (isset($_POST['print_matching'])) {
				$no_resep = $_POST['noresep'];
				$time = date('Y-m-d H:i:s');
				$ip_num = $_SERVER['REMOTE_ADDR'];
				// Cek jika dua huruf depan $no_resep adalah DR
				if (substr($no_resep, 0, 2) === 'DR') {
					$no_resep_a = $no_resep . '-A';
					$no_resep_b = $no_resep . '-B';

					sqlsrv_query($con, "INSERT INTO db_laborat.log_status_matching (ids, status, info, do_by, do_at, ip_address)
						VALUES (?, 'Create No.resep', 'generate no resep DR-A', ?, ?, ?)", [$no_resep_a, $_SESSION['userLAB'], $time, $ip_num]);
					sqlsrv_query($con, "INSERT INTO db_laborat.log_status_matching (ids, status, info, do_by, do_at, ip_address)
						VALUES (?, 'Create No.resep', 'generate no resep DR-B', ?, ?, ?)", [$no_resep_b, $_SESSION['userLAB'], $time, $ip_num]);

					$url = "http://10.0.0.121:8080/api/v1/document/create";
					$payload_a = json_encode([
						"doc_number" => $no_resep_a,
						"ip_address" => '10.0.6.225'
					]);
					$ch_a = curl_init($url);
					curl_setopt($ch_a, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch_a, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
					curl_setopt($ch_a, CURLOPT_POST, true);
					curl_setopt($ch_a, CURLOPT_POSTFIELDS, $payload_a);
					$response_a = curl_exec($ch_a);
					$error_a = curl_error($ch_a);
					curl_close($ch_a);

					$payload_b = json_encode([
						"doc_number" => $no_resep_b,
						"ip_address" => '10.0.6.225'
					]);
					$ch_b = curl_init($url);
					curl_setopt($ch_b, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch_b, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
					curl_setopt($ch_b, CURLOPT_POST, true);
					curl_setopt($ch_b, CURLOPT_POSTFIELDS, $payload_b);
					$response_b = curl_exec($ch_b);
					$error_b = curl_error($ch_b);
					curl_close($ch_b);

					if ($error_a) {
						$logMessageA = "CURL Error: " . addslashes($error_a);
						$logSuccessA = 0;
					} else {
						$resultA = json_decode($response_a, true);
						$logMessageA = addslashes($resultA['message'] ?? 'Unknown response');
						$logSuccessA = isset($resultA['success']) && $resultA['success'] ? 1 : 0;
					}
					sqlsrv_query($con, "INSERT INTO db_laborat.log_printing (no_resep, ip_address, success, message, response_raw, created_at, created_by)
						VALUES (?, ?, ?, ?, ?, GETDATE(), ?)", [$no_resep_a, $ip_num, $logSuccessA, $logMessageA, $response_a, $_SESSION['userLAB']]);

					if ($error_b) {
						$logMessageB = "CURL Error: " . addslashes($error_b);
						$logSuccessB = 0;
					} else {
						$resultB = json_decode($response_b, true);
						$logMessageB = addslashes($resultB['message'] ?? 'Unknown response');
						$logSuccessB = isset($resultB['success']) && $resultB['success'] ? 1 : 0;
					}
					sqlsrv_query($con, "INSERT INTO db_laborat.log_printing (no_resep, ip_address, success, message, response_raw, created_at, created_by)
						VALUES (?, ?, ?, ?, ?, GETDATE(), ?)", [$no_resep_b, $ip_num, $logSuccessB, $logMessageB, $response_b, $_SESSION['userLAB']]);
					// Tidak perlu alert di sini, karena sudah di-handle di JS
				} else {
					sqlsrv_query($con, "INSERT INTO db_laborat.log_status_matching (ids, status, info, do_by, do_at, ip_address)
						VALUES (?, 'Create No.resep', 'generate no resep', ?, ?, ?)", [$no_resep, $_SESSION['userLAB'], $time, $ip_num]);

					$url = "http://10.0.0.121:8080/api/v1/document/create";
					$payload = json_encode([
						"doc_number" => $no_resep,
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
						$logMessage = "CURL Error: " . addslashes($error);
						$logSuccess = 0;
					} else {
						$result = json_decode($response, true);
						$logMessage = addslashes($result['message'] ?? 'Unknown response');
						$logSuccess = isset($result['success']) && $result['success'] ? 1 : 0;
					}
					sqlsrv_query($con, "INSERT INTO db_laborat.log_printing (no_resep, ip_address, success, message, response_raw, created_at, created_by)
						VALUES (?, ?, ?, ?, ?, GETDATE(), ?)", [$no_resep, $ip_num, $logSuccess, $logMessage, $response, $_SESSION['userLAB']]);
				}
				exit;
			}
			?>
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
