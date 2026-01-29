<?php
ini_set("error_reporting", 1);
session_start();
include "../../koneksi.php";

$ids = isset($_POST['cek']) && is_array($_POST['cek']) ? $_POST['cek'] : [];
$id_list = array_values(array_filter(array_map('intval', $ids)));
$rows = [];

if (!empty($id_list)) {
	$placeholders = implode(', ', array_fill(0, count($id_list), '?'));
	$sql = "SELECT * FROM db_laborat.tbl_test_qc
	        WHERE sts_laborat <> 'Approved Full' AND id IN ($placeholders)
	        ORDER BY id ASC";
	$stmt = sqlsrv_query($con, $sql, $id_list);

	if (!$stmt) {
		$errors = sqlsrv_errors();
		echo 'Error executing query: ' . ($errors ? $errors[0]['message'] : 'unknown error');
		exit;
	}

	while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
		$rows[] = $row;
	}
	sqlsrv_free_stmt($stmt);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Form Permintaan</title>
	<style>
		body,
		td,
		th {
			font-family: sans-serif, Roman, serif;
			font-size: 12px;
		}

		td {
			border-top: 0px #000000 solid;
			border-bottom: 0px #000000 solid;
			border-left: 0px #000000 solid;
			border-right: 0px #000000 solid;
		}

		body {
			padding-left: 1.6cm;
			padding-top: 0.2cm;
		}
	</style>
</head>


<body>
	<table width="100%" border="0" style="width: 7in;">
		<tbody>
			<?php
			$count = count($rows);
			for ($i = 0; $i < $count; $i++) :
				if ($i == 3) {
					echo '<tr>';
				}
				if ($i < 6) :
			?>
					<td align="left" valign="top" style="height: 1.6in;">
						<table width="100%" border="0" class="table-list1" style="width: 2.3in;">
							<tr>
								<td colspan="3" style="border-top:0px #000000 solid; border-bottom:0px #000000 solid; border-left:0px #000000 solid; border-right:0px #000000 solid; ">
									<div style="font-size: 9px;"><strong>FORM PERMINTAAN TEST LABORATORY</strong></div>
								</td>
							</tr>
							<tr>
								<td colspan="3" style="border-top:0px #000000 solid; border-bottom:0px #000000 solid; border-left:0px #000000 solid; border-right:0px #000000 solid;">
									<div style="font-size: 8px;">BUYER : <?php echo isset($rows[$i]['buyer']) ? $rows[$i]['buyer'] : ''; ?></div>
								</td>
							</tr>
							<tr>
								<td colspan="3" style="border-top:0px #000000 solid; border-bottom:0px #000000 solid; border-left:0px #000000 solid; border-right:0px #000000 solid;">
									<div style="font-size: 8px;">COCOK WARNA : <?php
									$cocokWarna = isset($rows[$i]['cocok_warna']) ? $rows[$i]['cocok_warna'] : '';
									if ($cocokWarna === null) {
										$cocokWarna = '';
									}
									echo strtoupper($cocokWarna);
									?>
									</div>
								</td>
							</tr>
							<tr>
								<td colspan="3" style="border-top:0px #000000 solid; border-bottom:0px #000000 solid; border-left:0px #000000 solid; border-right:0px #000000 solid;">
									<div style="font-size: 8px;">STATUS : <?php echo isset($rows[$i]['sts']) ? strtoupper($rows[$i]['sts']) : ''; ?>
									</div>
								</td>
							</tr>
							<tr>
								<td colspan="3" style="border-top:0px #000000 solid; border-bottom:0px #000000 solid; border-left:0px #000000 solid; border-right:0px #000000 solid;">
									<div style="font-size: 8px;">TANGGAL : <?php
									$tglUpdate = isset($rows[$i]['tgl_update']) ? $rows[$i]['tgl_update'] : '';
									if ($tglUpdate instanceof DateTimeInterface) {
										$tglUpdate = $tglUpdate->format('Y-m-d H:i:s');
									} elseif ($tglUpdate === null) {
										$tglUpdate = '';
									}
									echo $tglUpdate !== '' ? date('d-m-Y H:i', strtotime(substr($tglUpdate, 0, 18))) : '';
									?></div>
								</td>
							</tr>
							<tr>
								<td colspan="3" style="border-top:0px #000000 solid; border-bottom:0px #000000 solid; border-left:0px #000000 solid; border-right:0px #000000 solid;">
									<div style="font-size: 8px;">NAMA WARNA : <?php echo isset($rows[$i]['warna']) ? $rows[$i]['warna'] : ''; ?></div>
								</td>
							</tr>
							<tr>
								<td colspan="3" style="border-top:0px #000000 solid; border-bottom:0px #000000 solid; border-left:0px #000000 solid; border-right:0px #000000 solid;">
									<div style="font-size: 8px;">NOMER WARNA : <?php echo isset($rows[$i]['no_warna']) ? $rows[$i]['no_warna'] : ''; ?></div>
								</td>
							</tr>
							<tr>
								<td colspan="3" style="border-top:0px #000000 solid; border-bottom:0px #000000 solid; border-left:0px #000000 solid; border-right:0px #000000 solid;">
									<div style="font-size: 8px;">QR CODE : <?php echo isset($rows[$i]['no_counter']) ? $rows[$i]['no_counter'] : ''; ?></div>
								</td>
							</tr>
							<tr>
								<td colspan="3" style="border-top:0px #000000 solid; border-bottom:0px #000000 solid; border-left:0px #000000 solid; border-right:0px #000000 solid;">
									<div style="font-size: 8px;">SUFFIX : <?php echo isset($rows[$i]['suffix']) ? $rows[$i]['suffix'] : ''; ?></div>
								</td>
							</tr>
							<tr>
								<td colspan="3" style="border-top:0px #000000 solid; border-bottom:0px #000000 solid; border-left:0px #000000 solid; border-right:0px #000000 solid;">
									<div style="font-size: 8px;">PERM TESTING : <?php echo isset($rows[$i]['permintaan_testing']) && ($rows[$i]['permintaan_testing'] == null || $rows[$i]['permintaan_testing'] == '') ? "FULL TEST" : $rows[$i]['permintaan_testing']; ?></div>
								</td>
							</tr>
							<tr>
								<td colspan="3" style="border-top:0px #000000 solid; border-bottom:0px #000000 solid; border-left:0px #000000 solid; border-right:0px #000000 solid;">
									<div style="font-size: 8px;">NOTE LAB : <?php echo isset($rows[$i]['note_laborat']) ? $rows[$i]['note_laborat'] : ''; ?></div>
								</td>
							</tr>
						</table>
					</td>

			<?php
				endif;
			endfor;

			?>
			<?php
			$count = count($rows);
			if ($count == 2) :  ?>
				<td align="left" valign="top" style="height: 1.6in;">
					<table width="100%" border="0" class="table-list1" style="width: 2.3in;">
					</table>
				</td>
			<?php endif ?>
		</tbody>
	</table>
</body>


</html>
