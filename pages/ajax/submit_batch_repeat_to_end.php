<?php
session_start();
include __DIR__ . '/../../koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userLAB'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Session telah habis, silahkan login ulang terlebih dahulu!'
    ]);
    exit;
}

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Data tidak valid."]);
    exit;
}

$endList = $data['end'] ?? [];
if (!is_array($endList) || empty($endList)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Tidak ada data yang dikirim."]);
    exit;
}

sqlsrv_begin_transaction($con);

try {
    foreach ($endList as $noResep) {
        processUpdate($con, $noResep, 'repeat', 'end');
    }

    sqlsrv_commit($con);

    echo json_encode([
        "success" => true,
        "message" => "Semua data berhasil diproses."
    ]);
} catch (Exception $e) {
    sqlsrv_rollback($con);
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Gagal memproses batch: " . $e->getMessage()
    ]);
}

function processUpdate($con, $noResep, $expectedStatus, $newStatus){
    $userRepeatEnd = $_SESSION['userLAB'] ?? '';

    $stmt = sqlsrv_query(
        $con,
        "SELECT status
         FROM db_laborat.tbl_preliminary_schedule
         WHERE no_resep = ? AND is_old_cycle = 0",
        [$noResep]
    );

    if (!$stmt || !$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        throw new Exception("No. Resep $noResep tidak ditemukan.");
    }

    if (($row['status'] ?? '') !== $expectedStatus) {
        throw new Exception("Status No. Resep $noResep tidak sesuai ({$row['status']}).");
    }

    // Koreksi repeat -> end: hanya ubah status agar tidak menambah komponen perhitungan point.
    $update = sqlsrv_query(
        $con,
        "UPDATE db_laborat.tbl_preliminary_schedule
         SET status = ?, repeat_to_end = ?, time_repeat_to_end = GETDATE()
         WHERE no_resep = ? AND is_old_cycle = 0",
        [$newStatus, $userRepeatEnd, $noResep]
    );

    if (!$update) {
        throw new Exception("Update gagal untuk $noResep: " . json_encode(sqlsrv_errors()));
    }
}
?>
