<?php
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=LAB_RecapKoreksiResep11" . date('Y-m-d') . ".xls"); //ganti nama sesuai keperluan
header("Pragma: no-cache");
header("Expires: 0");
// disini script laporan anda
?>
<?php
ini_set("error_reporting", 1);
include '../../koneksi.php';

$start_date = date('Y-m-d', strtotime("-1 days"));
$end_date = date('Y-m-d');

$start = $start_date . " 23:00:00";
$end = $end_date . " 23:00:00";
?>
<table>
	<tr>
		<th>Nama</th>
		<th>Matching Ulang</th>
		<th>Perbaikan</th>
		<th>L/D</th>
		<th>Matching Development</th>
		<th>Total</th>
	</tr>
	<?php
	function get_val($start, $end, $jenis, $colorist)
	{
		global $con;
		$sql = "SELECT
									SUM(CASE WHEN a.koreksi_resep = ? THEN 0.5 ELSE 0 END +
										CASE WHEN a.koreksi_resep2 = ? THEN 0.5 ELSE 0 END +
										CASE WHEN a.koreksi_resep3 = ? THEN 0.5 ELSE 0 END +
										CASE WHEN a.koreksi_resep4 = ? THEN 0.5 ELSE 0 END +
										CASE WHEN a.koreksi_resep5 = ? THEN 0.5 ELSE 0 END +
										CASE WHEN a.koreksi_resep6 = ? THEN 0.5 ELSE 0 END +
										CASE WHEN a.koreksi_resep7 = ? THEN 0.5 ELSE 0 END +
										CASE WHEN a.koreksi_resep8 = ? THEN 0.5 ELSE 0 END) AS total_value 
								FROM
									db_laborat.tbl_status_matching a
									JOIN db_laborat.tbl_matching b ON a.idm = b.no_resep 
								WHERE
									a.approve_at >= ?
									AND a.approve_at < ?
									AND b.jenis_matching = ?
									AND (? IN (a.koreksi_resep, a.koreksi_resep2, a.koreksi_resep3, a.koreksi_resep4,
														a.koreksi_resep5, a.koreksi_resep6, a.koreksi_resep7, a.koreksi_resep8))
									AND a.status = 'selesai'";
		$params = array_fill(0, 8, $colorist);
		$params[] = $start;
		$params[] = $end;
		$params[] = $jenis;
		$params[] = $colorist;
		$stmt = sqlsrv_query($con, $sql, $params);
		$data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

		return $data['total_value'] ?? 0;
	}
	$alll = 0;
	$colorist = sqlsrv_query($con, "SELECT * FROM db_laborat.tbl_colorist WHERE is_active= 'TRUE' ");
	while ($clrst = sqlsrv_fetch_array($colorist, SQLSRV_FETCH_ASSOC)) { ?>
		<tr>
			<td><?php echo $clrst['nama'] ?></td>
			<td><?php $mu2 = get_val($start, $end, 'Matching Ulang', $clrst['nama']) + get_val($start, $end, 'Matching Ulang NOW', $clrst['nama']);
				echo $mu2; ?> </td>
			<td><?php $mp2 = get_val($start, $end, 'Perbaikan', $clrst['nama']) + get_val($start, $end, 'Perbaikan NOW', $clrst['nama']);
				echo $mp2; ?> </td>
			<td><?php $ld2 = get_val($start, $end, 'L/D', $clrst['nama']) + get_val($start, $end, 'LD NOW', $clrst['nama']);
				echo $ld2; ?> </td>
			<td><?php $md2 = get_val($start, $end, 'Matching Development', $clrst['nama']) + 0;
				echo $md2; ?> </td>
			<td><?php $totall = $mu2 + $mp2 + $ld2 + $md2;
				echo number_format($totall, 2); ?></td>
			<?php $alll += $totall; ?>
		</tr>
	<?php } ?>
</table>
