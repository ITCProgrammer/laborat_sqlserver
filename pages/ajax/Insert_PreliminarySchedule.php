<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";

session_start();

$no_resep         = trim(htmlspecialchars($_POST['no_resep'] ?? ''));
$bottle_qty_1     = (int)($_POST['bottle_qty_1'] ?? 0);
$bottle_qty_2     = (int)($_POST['bottle_qty_2'] ?? 0); // belum dipakai, tetap diterima
$bottle_qty_test  = (int)($_POST['bottle_qty_test'] ?? 0);
$temp_1           = trim(htmlspecialchars($_POST['temp_1'] ?? ''));
$temp_2           = trim(htmlspecialchars($_POST['temp_2'] ?? '')); // tidak dipakai, tetap diterima
$username         = $_SESSION['userLAB'] ?? null;
$is_bonresep      = isset($_POST['is_bonresep']) ? (int)$_POST['is_bonresep'] : 0;

// Element balance
$element_id       = trim(htmlspecialchars($_POST['element'] ?? ''));
$kain_qty         = (int)($_POST['kain_qty'] ?? 0);
$kain_qty_test    = (int)($_POST['kain_qty_test'] ?? 0);

if (!$username) {
    echo json_encode(['success' => false, 'message' => 'Session telah habis, silahkan login ulang terlebih dahulu!']);
    exit;
}

if ($bottle_qty_1 > 10 || $bottle_qty_2 > 10 || $bottle_qty_test > 10) {
    echo json_encode(['success' => false, 'message' => 'Qty tidak boleh lebih dari 10!']);
    exit;
}

// Helper hitung cepat
$countByStatus = function(string $statusList) use ($con, $no_resep) {
    $sql = "SELECT COUNT(*) AS total FROM db_laborat.tbl_preliminary_schedule WHERE no_resep = ? AND status IN ($statusList)";
    $stmt = sqlsrv_query($con, $sql, [$no_resep]);
    $row  = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;
    return $row ? (int)$row['total'] : 0;
};

if ($countByStatus("'ready'") > 0) { echo json_encode(['success'=>false,'message'=>'Data sudah diinput!']); exit; }
if ($countByStatus("'end'") > 0)   { echo json_encode(['success'=>false,'message'=>'ERROR - data suffix di END!']); exit; }
if ($countByStatus("'hold'") > 0)  { echo json_encode(['success'=>false,'message'=>'ERROR - data suffix di HOLD!']); exit; }
if ($countByStatus("'scheduled','in_progress_dispensing','in_progress_dyeing','stop_dyeing','in_progress_darkroom'") > 0) {
    echo json_encode(['success'=>false,'message'=>'No. resep ini sedang dalam proses!']); exit;
}

$insertedCount = 0;

if (!sqlsrv_begin_transaction($con)) {
    echo json_encode(['success' => false, 'message' => 'Gagal mulai transaksi', 'error' => sqlsrv_errors()]); exit;
}

try {
    // tandai data repeat lama
    $stmtRepeat = sqlsrv_query($con,
        "SELECT COUNT(*) AS cnt FROM db_laborat.tbl_preliminary_schedule WHERE no_resep = ? AND status = 'repeat'",
        [$no_resep]
    );
    $rowRepeat = $stmtRepeat ? sqlsrv_fetch_array($stmtRepeat, SQLSRV_FETCH_ASSOC) : null;
    if ($rowRepeat && $rowRepeat['cnt'] > 0) {
        sqlsrv_query($con,
            "UPDATE db_laborat.tbl_preliminary_schedule SET is_old_cycle = 1 WHERE no_resep = ? AND status = 'repeat'",
            [$no_resep]
        );
    }

    // Insert parent + ambil ID
    $sqlInsertParent = "
        INSERT INTO db_laborat.tbl_preliminary_schedule (no_resep, code, username, is_test, is_bonresep)
        VALUES (?, ?, ?, ?, ?);
        SELECT SCOPE_IDENTITY() AS id;
    ";
    // Insert child
    $sqlInsertChild = "
        INSERT INTO db_laborat.tbl_preliminary_schedule_element (tbl_preliminary_schedule_id, element_id, qty, created_at)
        VALUES (?, ?, ?, GETDATE())
    ";

    // Insert test bottles
    for ($i = 0; $i < $bottle_qty_test; $i++) {
        $paramsParent = [$no_resep, $temp_1, $username, 1, $is_bonresep];
        $stmtParent = sqlsrv_query($con, $sqlInsertParent, $paramsParent);
        if (!$stmtParent || !sqlsrv_next_result($stmtParent)) {
            throw new Exception("Insert schedule (test) gagal: " . print_r(sqlsrv_errors(), true));
        }
        $rowId = sqlsrv_fetch_array($stmtParent, SQLSRV_FETCH_ASSOC);
        $lastId = $rowId['id'] ?? null;
        if (!$lastId) {
            throw new Exception("Gagal mendapatkan ID schedule (test)");
        }
        $insertedCount++;

        $stmtChild = sqlsrv_query($con, $sqlInsertChild, [$lastId, $element_id, $kain_qty_test]);
        if (!$stmtChild) {
            throw new Exception("Insert element (test) gagal: " . print_r(sqlsrv_errors(), true));
        }
    }

    // Insert normal bottles (qty_1)
    for ($i = 0; $i < $bottle_qty_1; $i++) {
        $paramsParent = [$no_resep, $temp_1, $username, 0, $is_bonresep];
        $stmtParent = sqlsrv_query($con, $sqlInsertParent, $paramsParent);
        if (!$stmtParent || !sqlsrv_next_result($stmtParent)) {
            throw new Exception("Insert schedule gagal: " . print_r(sqlsrv_errors(), true));
        }
        $rowId = sqlsrv_fetch_array($stmtParent, SQLSRV_FETCH_ASSOC);
        $lastId = $rowId['id'] ?? null;
        if (!$lastId) {
            throw new Exception("Gagal mendapatkan ID schedule");
        }
        $insertedCount++;

        $stmtChild = sqlsrv_query($con, $sqlInsertChild, [$lastId, $element_id, $kain_qty]);
        if (!$stmtChild) {
            throw new Exception("Insert element gagal: " . print_r(sqlsrv_errors(), true));
        }
    }

    if ($insertedCount > 0) {
        sqlsrv_commit($con);
        echo json_encode(['success' => true, 'message' => "Berhasil menyimpan $insertedCount data."]);
    } else {
        sqlsrv_rollback($con);
        echo json_encode(['success' => false, 'message' => 'Tidak ada data yang disimpan.']);
    }
} catch (Exception $e) {
    sqlsrv_rollback($con);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server.',
        'error'   => $e->getMessage()
    ]);
}
?>
