<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set("display_errors", 1);

include "../../koneksi.php";

try {
    $sql = "
        SET NOCOUNT ON;

        WITH repeat_rows AS (
            SELECT
                tps.no_resep,
                tps.code,
                tps.status,
                ROW_NUMBER() OVER (
                    PARTITION BY tps.no_resep
                    ORDER BY tps.id DESC
                ) AS rn
            FROM db_laborat.tbl_preliminary_schedule tps
            WHERE
                tps.status = 'repeat'
                AND tps.is_old_cycle = 0
        )
        SELECT
            rr.no_resep,
            rr.status,
            ms.product_name
        FROM repeat_rows rr
        LEFT JOIN db_laborat.master_suhu ms
            ON CONVERT(VARCHAR(50), rr.code) = CONVERT(VARCHAR(50), ms.code)
        WHERE rr.rn = 1
        ORDER BY rr.no_resep ASC
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
