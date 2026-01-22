<?php
session_start();
include '../../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['no_resep'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Metode atau parameter tidak valid."]);
    exit;
}

$no_resep        = trim($_POST['no_resep']);
$dispensing_code = $_POST['dispensing_code'] ?? '';
$userDispensing  = $_SESSION['userLAB'] ?? '';

if (!$userDispensing) {
    echo json_encode([
        'success' => false,
        'message' => 'Session telah habis, silahkan login ulang terlebih dahulu!'
    ]);
    exit;
}

try {
    // ========================= BON RESEP =========================
    $bonSql = "
        SELECT id
        FROM db_laborat.tbl_preliminary_schedule
        WHERE no_resep = ?
          AND is_bonresep = 1
          AND pass_dispensing = 0
          AND status NOT IN ('ready', 'end')
    ";
    $stmtBon = sqlsrv_query($con, $bonSql, [$no_resep]);
    $bonRows = [];
    while ($stmtBon && ($r = sqlsrv_fetch_array($stmtBon, SQLSRV_FETCH_ASSOC))) {
        $bonRows[] = $r;
    }

    if (!empty($bonRows)) {
        $ids = array_column($bonRows, 'id');
        updateRowsBonResep($con, $ids, $userDispensing);
        echo json_encode([
            "success"       => true,
            "type"          => "bon_resep",
            "updated_ids"   => $ids,
            "updated_count" => count($ids),
            "message"       => "BON RESEP selesai (status=end, pass_dispensing=1)."
        ]);
        exit;
    }

    // ========================= NON BON RESEP =========================
    $query = "
        SELECT ps.id, ps.no_resep, ps.order_index, ps.status
        FROM db_laborat.tbl_preliminary_schedule ps
        LEFT JOIN db_laborat.master_suhu ms ON ps.code = ms.code
        WHERE 
            ps.status NOT IN ('ready')
            AND (
                (? = '' AND (COALESCE(ms.dispensing, '') NOT IN ('1', '2', '3')))
                OR ms.dispensing = ?
            )
            AND ps.pass_dispensing = 0
        ORDER BY ps.order_index ASC
    ";

    $stmt = sqlsrv_query($con, $query, [$dispensing_code, $dispensing_code]);
    $rows = [];
    while ($stmt && ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
        $rows[] = $r;
    }

    if (empty($rows)) {
        http_response_code(404);
        echo json_encode(["success" => false, "error" => "Tidak ada data ditemukan untuk kode dispensing."]);
        exit;
    }

    // Bagi data ke blok-blok isi 16 baris
    $rowsPerBlock = 16;
    $blocks = array_chunk($rows, $rowsPerBlock);

    // Cari blok aktif pertama yang masih scheduled
    $firstScheduledBlockIndex = null;
    foreach ($blocks as $index => $block) {
        foreach ($block as $row) {
            if ($row['status'] === 'scheduled') {
                $firstScheduledBlockIndex = $index;
                break 2;
            }
        }
    }

    if ($firstScheduledBlockIndex !== null) {
        $activeBlock = $blocks[$firstScheduledBlockIndex];
        $allowedNoResepList = array_column($activeBlock, 'no_resep');

        if (!in_array($no_resep, $allowedNoResepList)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error"   => "Silakan selesaikan blok sebelumnya terlebih dahulu (blok #" . ($firstScheduledBlockIndex + 1) . ")."
            ]);
            exit;
        }

        $updateIds = array_column(array_filter($activeBlock, function ($row) use ($no_resep) {
            return $row['no_resep'] === $no_resep && $row['status'] === 'scheduled';
        }), 'id');

        if (!empty($updateIds)) {
            updateRows($con, $updateIds, $userDispensing);

            echo json_encode([
                "success"       => true,
                "updated_ids"   => $updateIds,
                "block_index"   => $firstScheduledBlockIndex,
                "updated_count" => count($updateIds)
            ]);
            exit;
        }
    }

    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Semua blok untuk No. Resep ini sudah diproses."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}

// ================= Helper functions =================
function updateRows($con, array $ids, string $userDispensing): void {
    if (empty($ids)) return;
    $placeholders = implode(",", array_fill(0, count($ids), "?"));
    $params = array_merge([$userDispensing], $ids);
    $sql = "
        UPDATE db_laborat.tbl_preliminary_schedule 
        SET status = 'in_progress_dispensing',
            dispensing_start = GETDATE(),
            user_dispensing = ?
        WHERE id IN ($placeholders)
    ";
    sqlsrv_query($con, $sql, $params);
}

function updateRowsBonResep($con, array $ids, string $userDispensing): void {
    if (empty($ids)) return;
    $placeholders = implode(",", array_fill(0, count($ids), "?"));
    $params = array_merge([$userDispensing], $ids);
    $sql = "
        UPDATE db_laborat.tbl_preliminary_schedule
        SET status = 'end',
            pass_dispensing = 1,
            dispensing_start = GETDATE(),
            user_dispensing = ?
        WHERE id IN ($placeholders)
    ";
    sqlsrv_query($con, $sql, $params);
}
