<?php
session_start();
include __DIR__ . '/../../koneksi.php';

$sql = "SELECT ms.[group], tps.no_resep, tps.no_machine
        FROM db_laborat.tbl_preliminary_schedule tps
        JOIN db_laborat.master_suhu ms ON LTRIM(RTRIM(tps.code)) = LTRIM(RTRIM(ms.code))
        WHERE tps.status IN ('scheduled', 'in_progress_dispensing', 'in_progress_dyeing', 'in_progress_darkroom', 'ok')
        ORDER BY ms.[group], tps.no_resep";
$result = sqlsrv_query($con, $sql);

$schedules = [];
while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    $schedules[$row['group']][] = $row['no_resep'];
}

echo json_encode($schedules);
