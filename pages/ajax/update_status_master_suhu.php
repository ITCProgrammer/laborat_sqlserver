<?php
include '../../koneksi.php';

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$status = isset($_POST['status']) ? intval($_POST['status']) : 1;

if ($id <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ID tidak valid'
    ]);
    exit;
}

$query = "UPDATE db_laborat.master_suhu SET status = ? WHERE id = ?";
$params = [$status, $id];
$stmt = sqlsrv_prepare($con, $query, $params);

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
        'message' => 'Status berhasil diperbarui'
    ]);
} else {
    $errors = sqlsrv_errors();
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal memperbarui status: ' . ($errors ? $errors[0]['message'] : 'unknown error')
    ]);
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($con);
