<?php
session_start();
include __DIR__ . '/../../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['no_resep'])) {
    $no_resep    = trim($_POST['no_resep']);
    $userDyeing  = $_SESSION['userLAB'] ?? '';
    $isForceStop = isset($_POST['force_stop']) && $_POST['force_stop'] === 'true';

    if (!$userDyeing) {
        echo json_encode([
            'success' => false,
            'message' => 'Session telah habis, silahkan login ulang terlebih dahulu!'
        ]);
        exit;
    }
    
    if ($isForceStop) {
        $stmtSD = sqlsrv_query(
            $con,
            "UPDATE db_laborat.tbl_preliminary_schedule 
             SET status = 'stop_dyeing'
             WHERE no_resep = ? AND is_old_cycle = 0",
            [$no_resep]
        );

        if ($stmtSD) {
            echo json_encode(["success" => true, "new_status" => 'stop_dyeing']);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "error" => sqlsrv_errors()]);
        }
        exit;
    }

    $stmt = sqlsrv_query(
        $con,
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN status = 'in_progress_dispensing' THEN 1 ELSE 0 END) AS matching
         FROM db_laborat.tbl_preliminary_schedule
         WHERE no_resep = ? AND is_old_cycle = 0",
        [$no_resep]
    );

    if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
        $total = (int)$row['total'];
        $matching = (int)$row['matching'];

        if ($total > 0 && $total === $matching) {
            $next_status = 'in_progress_dyeing';

            $update = sqlsrv_query(
                $con,
                "UPDATE db_laborat.tbl_preliminary_schedule 
                 SET status = ?, dyeing_start = GETDATE(), user_dyeing = ?
                 WHERE no_resep = ? AND is_old_cycle = 0",
                [$next_status, $userDyeing, $no_resep]
            );

            if ($update) {
                echo json_encode(["success" => true, "new_status" => $next_status]);
                resetOrderIndexIfDone($con);
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "error" => sqlsrv_errors()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "error" => "Status tidak valid."]);
        }
    } else {
        http_response_code(404);
        echo json_encode(["success" => false, "error" => "Data tidak ditemukan."]);
    }
}

function resetOrderIndexIfDone($con): void {
    $codes = ['1', '2', '3'];

    foreach ($codes as $code) {
        $stmt = sqlsrv_query(
            $con,
            "SELECT COUNT(*) AS cnt
             FROM db_laborat.tbl_preliminary_schedule ps
             LEFT JOIN db_laborat.master_suhu ms ON LTRIM(RTRIM(ps.code)) = LTRIM(RTRIM(ms.code))
             WHERE ms.dispensing = ? AND ps.status IN ('scheduled', 'in_progress_dispensing')",
            [$code]
        );
        if (!$stmt) continue;
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $count = (int)($row['cnt'] ?? 0);

        if ((int)$count === 0) {
            sqlsrv_query(
                $con,
                "UPDATE ps
                 SET ps.order_index = NULL, ps.pass_dispensing = 1
                 FROM db_laborat.tbl_preliminary_schedule ps
                 LEFT JOIN db_laborat.master_suhu ms ON LTRIM(RTRIM(ps.code)) = LTRIM(RTRIM(ms.code))
                 WHERE ms.dispensing = ? AND ps.status <> 'ready'",
                [$code]
            );
        }
    }
}
?>
