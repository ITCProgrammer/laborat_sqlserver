<?php
session_start();
include __DIR__ . '/../../koneksi.php';

header('Content-Type: application/json');

$userDarkroomStart = $_SESSION['userLAB'] ?? '';

if (!$userDarkroomStart) {
    echo json_encode([
        'success' => false,
        'message' => 'Session telah habis, silahkan login ulang terlebih dahulu!'
    ]);
    exit;
}

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Data tidak valid."]);
    exit;
}

$allNoResep = array_merge(
    $data['repeat'] ?? [],
    $data['end'] ?? [],
    $data['progress'] ?? []
);

if (empty($allNoResep)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Tidak ada data yang dikirim."]);
    exit;
}

sqlsrv_begin_transaction($con);

try {
    foreach ($data['repeat'] ?? [] as $no_resep) {
        processUpdate($con, $no_resep, ['in_progress_dyeing', 'stop_dyeing'], 'repeat', $userDarkroomStart);
    }

    foreach ($data['end'] ?? [] as $no_resep) {
        processUpdate($con, $no_resep, ['in_progress_dyeing', 'stop_dyeing'], 'end', $userDarkroomStart, true);
    }

    foreach ($data['progress'] ?? [] as $no_resep) {
        processUpdate($con, $no_resep, ['in_progress_dyeing', 'stop_dyeing'], 'in_progress_darkroom', $userDarkroomStart);
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
        "message" => "Gagal memproses batch: " . $e->getMessage()
    ]);
}

function processUpdate($con, $no_resep, $expected_statuses, $new_status, $userDarkroomStart, $update_end_time = false) {
    $stmt = sqlsrv_query($con, "SELECT status FROM db_laborat.tbl_preliminary_schedule WHERE no_resep = ? AND is_old_cycle = 0", [$no_resep]);
    if (!$stmt || !$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        throw new Exception("No. Resep $no_resep tidak ditemukan.");
    }

    // Cek apakah status sekarang termasuk yang diizinkan
    if (!in_array($row['status'], (array)$expected_statuses)) {
        throw new Exception("Status No. Resep $no_resep tidak sesuai ({$row['status']}).");
    }

    if ($update_end_time) {
        $update = sqlsrv_query(
            $con,
            "UPDATE db_laborat.tbl_preliminary_schedule 
             SET status = ?, sekali_celup = GETDATE(), darkroom_start = GETDATE(), user_darkroom_start = ?
             WHERE no_resep = ? AND is_old_cycle = 0",
            [$new_status, $userDarkroomStart, $no_resep]
        );
    } else {
        $update = sqlsrv_query(
            $con,
            "UPDATE db_laborat.tbl_preliminary_schedule 
             SET status = ?, darkroom_start = GETDATE(), user_darkroom_start = ?
             WHERE no_resep = ? AND is_old_cycle = 0",
            [$new_status, $userDarkroomStart, $no_resep]
        );
    }

    if (!$update) {
        throw new Exception("Update gagal untuk $no_resep: " . json_encode(sqlsrv_errors()));
    }
}
