<?php
include "../../koneksi.php";
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $query = "DELETE FROM db_laborat.master_suhu WHERE id = ?";
    $stmt = sqlsrv_prepare($con, $query, [$id]);

    if (!$stmt) {
        $errors = sqlsrv_errors();
        echo json_encode([
            'status' => 'error',
            'message' => 'Prepare statement gagal: ' . ($errors ? $errors[0]['message'] : 'unknown error')
        ]);
        exit;
    }

    if (sqlsrv_execute($stmt)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Data berhasil dihapus.'
        ]);
    } else {
        $errors = sqlsrv_errors();
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal menghapus data: ' . ($errors ? $errors[0]['message'] : 'unknown error')
        ]);
    }
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($con);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Permintaan tidak valid.'
    ]);
}
