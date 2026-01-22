<?php
header('Content-Type: application/json');
include "../../koneksi.php";

try {
    $sql = "
        SELECT
            tps.*,
            ms.product_name,
            ms.suhu,
            ms.waktu,
            ms.dispensing,
            tm.jenis_matching,
            br.min_order_index
        FROM db_laborat.tbl_preliminary_schedule AS tps
        LEFT JOIN db_laborat.master_suhu AS ms
            ON tps.code = ms.code
        LEFT JOIN db_laborat.tbl_matching AS tm
            ON (
                CASE
                    WHEN LEFT(tps.no_resep, 2) = 'DR'
                        THEN LEFT(tps.no_resep, LEN(tps.no_resep) - 2)
                    ELSE tps.no_resep
                END
            ) = tm.no_resep
        LEFT JOIN (
            SELECT
                no_resep,
                MIN(order_index) AS min_order_index
            FROM db_laborat.tbl_preliminary_schedule
            WHERE is_bonresep = 1
            GROUP BY no_resep
        ) AS br
            ON br.no_resep = tps.no_resep
        WHERE tps.status NOT IN ('ready')
        ORDER BY
            CASE
                WHEN tm.jenis_matching IN ('LD', 'LD NOW') THEN 1
                WHEN tm.jenis_matching IN ('Matching Ulang', 'Matching Ulang NOW', 'Matching Development', 'Perbaikan', 'Perbaikan NOW') THEN 2
                ELSE 3
            END,
            CASE
                WHEN tps.order_index > 0 THEN 0
                ELSE 1
            END,
            CASE
                WHEN tps.is_bonresep = 1 THEN COALESCE(br.min_order_index, 999999999)
                ELSE tps.order_index
            END ASC,
            CASE
                WHEN tps.is_bonresep = 1 THEN tps.no_resep
                ELSE ''
            END ASC,
            tps.order_index ASC,
            ms.suhu DESC,
            ms.waktu DESC,
            tps.no_resep,
            tps.no_machine ASC,
            tps.is_old_data ASC";

    $result = sqlsrv_query($con, $sql);
    if (!$result) {
        throw new Exception(print_r(sqlsrv_errors(), true));
    }

    $data = [];
    $usedIndexes = [];

    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        if ((int)$row['order_index'] > 0) {
            $usedIndexes[] = (int)$row['order_index'];
        }
        if ((int)$row['pass_dispensing'] == 0 || (int)$row['order_index'] > 0) {
            $data[] = $row;
        }
    }

    // Isi order_index yang masih 0 dengan nilai unik berikutnya
    $nextIndex = 1;
    foreach ($data as &$row) {
        if ((int)$row['order_index'] === 0) {
            while (in_array($nextIndex, $usedIndexes)) {
                $nextIndex++;
            }
            $id = (int)$row['id'];
            sqlsrv_query($con, "UPDATE db_laborat.tbl_preliminary_schedule SET order_index = ? WHERE id = ? AND pass_dispensing = 0", [$nextIndex, $id]);
            $row['order_index'] = $nextIndex;
            $usedIndexes[] = $nextIndex;
            $nextIndex++;
        }
    }
    unset($row);

    // Group data per dispensing code
    $grouped = ['1' => [], '2' => [], '3' => []];
    foreach ($data as $row) {
        $code = $row['dispensing'] ?? '';
        if (in_array($code, ['1', '2', '3'])) {
            $grouped[$code][] = $row;
        }
    }

    $finalData = [];
    $rowsPerCycle = 16;
    foreach ($grouped as $dispCode => $items) {
        usort($items, fn($a, $b) => $a['order_index'] - $b['order_index']);
        $rowCounter = 0;
        $cycleCounter = 1;
        foreach ($items as &$item) {
            $rowCounter++;
            $item['rowNumber'] = $rowCounter;
            $item['cycleNumber'] = $cycleCounter;
            if ($rowCounter >= $rowsPerCycle) {
                $cycleCounter++;
                $rowCounter = 0;
            }
            $finalData[] = $item;
        }
    }

    echo json_encode($finalData);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
