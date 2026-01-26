<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set("display_errors", 1);

include __DIR__ . "/../../koneksi.php";

try {
    $statuses = [
        'end',
    ];

    $statusList = "'" . implode("','", $statuses) . "'";
    
    // ambil rcode dari GET (karena kita kirim sebagai query string)
    $rcode = isset($_GET['rcode']) ? trim($_GET['rcode']) : '';
    // siapkan filter tambahan (optional)
    $filterResep = '';
    $params = [];
    if ($rcode !== '') {
        $filterResep = "AND tps.no_resep LIKE ?";
        $params[] = "%{$rcode}%";
    }

    $sql = "SELECT TOP 100
                    tps.*, 
                    ms.product_name,
                    ms.suhu,
                    ms.waktu,
                    ms.dispensing,
                    tsm.grp,
                    tm.warna,
                    CASE 
                        WHEN LEFT(tps.no_resep, 2) = 'DR' 
                            THEN LEFT(tps.no_resep, CHARINDEX('-', tps.no_resep + '-')-1)
                        ELSE tps.no_resep
                    END AS no_resep_cutting
            FROM db_laborat.tbl_preliminary_schedule tps
            INNER JOIN (
                    SELECT MIN(id) AS id
                    FROM db_laborat.tbl_preliminary_schedule
                    WHERE status IN ($statusList)
                    GROUP BY no_resep
            ) AS sub 
                    ON tps.id = sub.id
            LEFT JOIN db_laborat.master_suhu ms 
                    ON LTRIM(RTRIM(tps.code)) = LTRIM(RTRIM(ms.code))
            LEFT JOIN db_laborat.tbl_matching tm
                    ON (
                            CASE 
                                    WHEN LEFT(tps.no_resep, 2) = 'DR' 
                                    THEN LEFT(tps.no_resep, CHARINDEX('-', tps.no_resep + '-')-1)
                                    ELSE tps.no_resep
                            END
                    ) = tm.no_resep
            LEFT JOIN db_laborat.tbl_status_matching tsm 
                    ON (
                            CASE 
                                    WHEN LEFT(tps.no_resep, 2) = 'DR' 
                                    THEN LEFT(tps.no_resep, CHARINDEX('-', tps.no_resep + '-')-1)
                                    ELSE tps.no_resep
                            END
                    ) = tsm.idm
            WHERE 1=1 {$filterResep}
            ORDER BY tps.id ASC";

    $result = sqlsrv_query($con, $sql, $params);
    if ($result === false) {
        throw new Exception(json_encode(sqlsrv_errors()));
    }

    // prepared statement untuk ambil status terakhir di log_status_matching
    $stmtLog = sqlsrv_prepare(
        $con,
        "SELECT TOP 1 status FROM db_laborat.log_status_matching WHERE ids = ? ORDER BY id DESC",
        [&$cuttingParam]
    );

    $data = [];
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $no_resep_cutting = $row['no_resep_cutting'];

        $lastStatus = null;

        if ($stmtLog) {
            $cuttingParam = $no_resep_cutting;
            $exec = sqlsrv_execute($stmtLog);
            if ($exec && ($r2 = sqlsrv_fetch_array($stmtLog, SQLSRV_FETCH_ASSOC))) {
                $lastStatus = $r2['status'];
            }
            // rewind resultset for next loop
            sqlsrv_free_stmt($stmtLog);
            $stmtLog = sqlsrv_prepare(
                $con,
                "SELECT TOP 1 status FROM db_laborat.log_status_matching WHERE ids = ? ORDER BY id DESC",
                [&$cuttingParam]
            );
        }

        // tambahin field baru, misal namanya 'status_log'
        $row['info'] = $lastStatus; // bisa null kalau tidak ada
        
        $data[] = $row;
    }

    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
