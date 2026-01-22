<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set("display_errors", 1);

include "../../koneksi.php";

try {
    $statuses = ['repeat'];
    $statusList = "'" . implode("','", $statuses) . "'";

    $sql = "
        SELECT 
            tps.id,
            tps.no_resep,
            tps.code,
            tps.no_machine,
            tps.status,
            tps.is_old_data,
            tps.is_old_cycle,
            tps.is_test,
            tps.is_bonresep,
            tps.order_index,
            tps.user_scheduled,
            ms.product_name,
            ms.suhu,
            ms.waktu,
            ms.dispensing
        FROM db_laborat.tbl_preliminary_schedule tps
        INNER JOIN (
            SELECT MIN(id) AS id
            FROM db_laborat.tbl_preliminary_schedule
            WHERE status IN ($statusList) AND is_old_cycle = 0
            GROUP BY no_resep
        ) AS sub ON tps.id = sub.id
        LEFT JOIN db_laborat.master_suhu ms 
            ON CONVERT(VARCHAR(50), tps.code) = CONVERT(VARCHAR(50), ms.code)
        ORDER BY 
            CASE WHEN tps.status = 'repeat' THEN 1 ELSE 0 END DESC,
            tps.id ASC
    ";

    $result = sqlsrv_query($con, $sql);
    if (!$result) {
        throw new Exception(print_r(sqlsrv_errors(), true));
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
