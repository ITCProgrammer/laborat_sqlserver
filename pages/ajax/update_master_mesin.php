<?php
include "../../koneksi.php";
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int) $_POST['id'];
    $no_machine = trim($_POST['no_machine']);
    $suhu = $_POST['suhu'] !== '' ? $_POST['suhu'] . '°C' : null;
    $program = $_POST['program'];
    $keterangan = $_POST['keterangan'];

    // Cek apakah no_machine sudah digunakan oleh mesin lain
    $checkQuery = "SELECT id FROM db_laborat.master_mesin WHERE no_machine = ? AND id != ?";
    $checkStmt = sqlsrv_prepare($con, $checkQuery, [$no_machine, $id]);

    if (!$checkStmt) {
        $errors = sqlsrv_errors();
        echo json_encode([
            "error" => $errors ? $errors[0]['message'] : "Gagal menyiapkan pengecekan data"
        ]);
        exit;
    }

    if (!sqlsrv_execute($checkStmt)) {
        $errors = sqlsrv_errors();
        echo json_encode([
            "error" => $errors ? $errors[0]['message'] : "Gagal mengeksekusi pengecekan data"
        ]);
        sqlsrv_free_stmt($checkStmt);
        exit;
    }

    if (sqlsrv_has_rows($checkStmt)) {
        echo json_encode(["error" => "No Machine sudah digunakan."]);
    } else {
        // Lanjutkan update
        $query = "UPDATE db_laborat.master_mesin SET no_machine = ?, suhu = ?, program = ?, keterangan = ? WHERE id = ?";
        $stmt = sqlsrv_prepare($con, $query, [$no_machine, $suhu, $program, $keterangan, $id]);

        if (!$stmt) {
            $errors = sqlsrv_errors();
            echo json_encode([
                "error" => $errors ? $errors[0]['message'] : "Gagal menyiapkan update"
            ]);
            sqlsrv_free_stmt($checkStmt);
            exit;
        }

        if (sqlsrv_execute($stmt)) {
            echo json_encode(["success" => true]);
        } else {
            $errors = sqlsrv_errors();
            echo json_encode([
                "error" => $errors ? $errors[0]['message'] : "Gagal update data"
            ]);
        }

        sqlsrv_free_stmt($stmt);
    }

    sqlsrv_free_stmt($checkStmt);
    sqlsrv_close($con);
} else {
    echo json_encode(["error" => "Terjadi kesalahan."]);
}
