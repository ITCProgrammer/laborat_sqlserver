<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set("display_errors", 1);

include "../../koneksi.php"; // pastikan file ini hanya koneksi

try {
    $sql = "SELECT
                tps.*,
                ms.product_name,
                tm.jenis_matching,
                tpse.element_id,
                bal.ELEMENTSCODE AS element_code,
                tpse.qty AS element_qty,
                tm.for_forecast as for_forecast
            FROM
                db_laborat.tbl_preliminary_schedule tps
                LEFT JOIN db_laborat.master_suhu ms ON tps.code = ms.code 
                LEFT JOIN db_laborat.tbl_matching tm ON 
                    CASE WHEN LEFT(tps.no_resep, 2) = 'DR' 
                        THEN LEFT(tps.no_resep, LEN(tps.no_resep) - 2)
                        ELSE tps.no_resep
                    END = tm.no_resep
                LEFT JOIN db_laborat.tbl_preliminary_schedule_element tpse ON tps.id = tpse.tbl_preliminary_schedule_id 
                -- Hindari error konversi nvarchar->numeric: samakan tipe jadi varchar
                LEFT JOIN db_laborat.balance bal ON CONVERT(VARCHAR(50), tpse.element_id) = CONVERT(VARCHAR(50), bal.numberid)
            WHERE
                tps.STATUS = 'ready' 
            ORDER BY
                tps.id DESC";

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
