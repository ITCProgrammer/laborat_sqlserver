<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set("display_errors", 1);

include __DIR__ . "/../../koneksi.php";

try {
    $statuses = [
        'in_progress_darkroom',
    ];

    $statusList = "'" . implode("','", $statuses) . "'";
    
    $sql = "
        SELECT 
            tps.*, 
            ms.product_name,
            ms.suhu,
            ms.waktu,
            ms.dispensing,
            tsm.grp,
            tm.warna
        FROM db_laborat.tbl_preliminary_schedule tps
        INNER JOIN (
            SELECT MIN(id) AS id
            FROM db_laborat.tbl_preliminary_schedule
            WHERE status IN ($statusList)
            GROUP BY no_resep
        ) AS sub ON tps.id = sub.id
        LEFT JOIN db_laborat.master_suhu ms ON LTRIM(RTRIM(tps.code)) = LTRIM(RTRIM(ms.code))
        LEFT JOIN db_laborat.tbl_matching tm
                ON (
                    CASE 
                        WHEN LEFT(tps.no_resep, 2) = 'DR' THEN LEFT(tps.no_resep, LEN(tps.no_resep) - 2)
                        ELSE tps.no_resep
                    END
                ) = tm.no_resep
        LEFT JOIN db_laborat.tbl_status_matching tsm 
                ON (
                    CASE 
                        WHEN LEFT(tps.no_resep, 2) = 'DR' THEN LEFT(tps.no_resep, LEN(tps.no_resep) - 2)
                        ELSE tps.no_resep
                    END
                ) = tsm.idm
        ORDER BY 
            CASE WHEN tps.status = 'in_progress_darkroom' THEN 1 ELSE 0 END DESC,
            tps.id ASC";

    $result = sqlsrv_query($con, $sql);
    if ($result === false) {
        throw new Exception(json_encode(sqlsrv_errors()));
    }

    $data = [];
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $data[] = $row;
    }

    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
