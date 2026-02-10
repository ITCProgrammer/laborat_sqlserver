<?php
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=LAB_RecapColorist11" . date('Y-m-d') . ".xls"); //ganti nama sesuai keperluan
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
	function get_value($start, $end, $jenis, $colorist)
	{
		global $con;
		$sql = "SELECT
							SUM(CASE WHEN a.colorist1 = ? THEN 0.5 ELSE 0 END + 
								CASE WHEN a.colorist2 = ? THEN 0.5 ELSE 0 END +
								CASE WHEN a.colorist3 = ? THEN 0.5 ELSE 0 END +
								CASE WHEN a.colorist4 = ? THEN 0.5 ELSE 0 END +
								CASE WHEN a.colorist5 = ? THEN 0.5 ELSE 0 END +
								CASE WHEN a.colorist6 = ? THEN 0.5 ELSE 0 END +
								CASE WHEN a.colorist7 = ? THEN 0.5 ELSE 0 END +
								CASE WHEN a.colorist8 = ? THEN 0.5 ELSE 0 END) AS total_value 
						FROM
							db_laborat.tbl_status_matching a
							JOIN db_laborat.tbl_matching b ON a.idm = b.no_resep 
						WHERE
							a.approve_at >= ?
							AND a.approve_at < ?
							AND b.jenis_matching = ?
							AND (? IN (a.colorist1, a.colorist2, a.colorist3, a.colorist4,
												a.colorist5, a.colorist6, a.colorist7, a.colorist8))
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
	$colorist = sqlsrv_query($con, "SELECT * FROM db_laborat.tbl_colorist WHERE is_active = 'TRUE'");
	?>
	<?php
	$all = 0;
	while ($clrst = sqlsrv_fetch_array($colorist, SQLSRV_FETCH_ASSOC)) { ?>
		<tr>
			<td><?php echo $clrst['nama'] ?></td>
			<td><?php $mu = get_value($start, $end, 'Matching Ulang', $clrst['nama']) + get_value($start, $end, 'Matching Ulang NOW', $clrst['nama']);
				echo $mu; ?> </td>
			<td><?php $mp = get_value($start, $end, 'Perbaikan', $clrst['nama']) + get_value($start, $end, 'Perbaikan NOW', $clrst['nama']);
				echo $mp; ?> </td>
			<td><?php $ld = get_value($start, $end, 'L/D', $clrst['nama']) + get_value($start, $end, 'LD NOW', $clrst['nama']);
				echo $ld; ?> </td>
			<td><?php $md = get_value($start, $end, 'Matching Development', $clrst['nama']);
				echo $md; ?> </td>
			<td><?php $total = $mu + $mp + $ld + $md;
				echo $total ?></td>
			<?php $all += $total; ?>
		</tr>
	<?php } ?>
</table>
