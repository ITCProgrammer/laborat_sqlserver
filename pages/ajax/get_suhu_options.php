<?php
include '../../koneksi.php';

header('Content-Type: text/html; charset=UTF-8');

$getDyestuff = $_GET['Dystf'] ?? null;
$getJnsMtcg = $_GET['jnsMtcg'] ?? null;

$where = "1";

if ($getDyestuff) {
	if ($getDyestuff === 'DR') {
		$where = "dispensing IN (1,2,3)";

		if (in_array($getJnsMtcg, ['L/D', 'LD NOW'])) {
            echo '<option value="-">-</option>';
        }
	} elseif ($getDyestuff === 'CD') {
		$where = "dispensing = 1";
	} elseif ($getDyestuff === 'OB') {
		$where = "dispensing = 3";
	} else {
		$char = strtoupper(substr($getDyestuff, 0, 1));
		switch ($char) {
			case 'D':
			case 'A': $where = "dispensing = 1"; break;
			case 'R': $where = "dispensing = 2"; break;
			default:  $where = "1";
		}
	}

	$query = "SELECT * FROM db_laborat.master_suhu WHERE $where AND status = 1 ORDER BY TRY_CONVERT(int, suhu) ASC, waktu ASC";
	$result = sqlsrv_query($con, $query);

	while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
		$info = '';
		if ($row['program'] == 1) $info = 'KONSTAN';
		elseif ($row['program'] == 2) $info = 'RAISING';
		else $info = '-';

		if ($row['dyeing'] == 1) $info .= ' - POLY';
		elseif ($row['dyeing'] == 2) $info .= ' - COTTON';

		if ($row['dispensing'] == 1) $info .= ' - POLY';
		elseif ($row['dispensing'] == 2) $info .= ' - COTTON';
		elseif ($row['dispensing'] == 3) $info .= ' - WHITE';

		// Tampilkan apa adanya (tanpa htmlspecialchars) agar simbol ° tidak berubah
		echo '<option value="' . $row['code'] . '">' .
		     $row['product_name'] . ' (' . $info . ')</option>';
	}
}
?>
