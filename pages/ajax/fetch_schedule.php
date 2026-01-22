<?php
session_start();
include '../../koneksi.php';

$sql = "SELECT
            CASE
                WHEN tps.is_bonresep = 1 THEN 'BON_RESEP'
                ELSE ms.[group]
            END AS [group],
            tps.no_resep
        FROM
            db_laborat.tbl_preliminary_schedule tps
        LEFT JOIN db_laborat.master_suhu ms ON tps.code = ms.code 
        LEFT JOIN db_laborat.tbl_matching ON 
            CASE WHEN LEFT(tps.no_resep, 2) = 'DR' 
                THEN LEFT(tps.no_resep, LEN(tps.no_resep) - 2)
                ELSE tps.no_resep
            END = db_laborat.tbl_matching.no_resep
        WHERE
            tps.STATUS = 'ready' 
        ORDER BY
            CASE 
                WHEN db_laborat.tbl_matching.jenis_matching IN ('LD', 'LD NOW') THEN 1
                WHEN db_laborat.tbl_matching.jenis_matching IN ('Matching Ulang', 'Matching Ulang NOW', 'Matching Development', 'Perbaikan' , 'Perbaikan NOW') THEN 2
                ELSE 3
            END,
            CASE 
                WHEN ISNULL(tps.order_index,0) > 0 THEN 0 
                ELSE 1 
            END, 
            tps.order_index ASC,
            ms.suhu DESC, 
            ms.waktu DESC, 
            tps.no_resep ASC";
$result = sqlsrv_query($con, $sql);

$schedules = [];
while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    $schedules[$row['group']][] = $row['no_resep'];
}

echo json_encode($schedules);
