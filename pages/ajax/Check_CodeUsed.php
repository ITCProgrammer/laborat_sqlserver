<?php
header('Content-Type: application/json');
include '../../koneksi.php';

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

$response = ['used' => false, 'error' => false];

// Ambil code berdasarkan ID
$getCode = sqlsrv_query($con, "SELECT code FROM db_laborat.master_suhu WHERE id = ?", [$id]);

if (!$getCode || sqlsrv_has_rows($getCode) === false) {
    $response['error'] = true;
    $errors = sqlsrv_errors();
    $response['message'] = $errors ? $errors[0]['message'] : 'Data tidak ditemukan.';
    echo json_encode($response);
    exit;
}

$row = sqlsrv_fetch_array($getCode, SQLSRV_FETCH_ASSOC);
$code = $row['code'];
sqlsrv_free_stmt($getCode);

// Cek apakah code digunakan di tbl_preliminary_schedule
$check = sqlsrv_query(
    $con,
    "SELECT COUNT(*) as total FROM db_laborat.tbl_preliminary_schedule WHERE code = ?",
    [$code]
);

if (!$check) {
    $response['error'] = true;
    $errors = sqlsrv_errors();
    $response['message'] = $errors ? $errors[0]['message'] : 'Gagal memeriksa data.';
    echo json_encode($response);
    exit;
}

$data = sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($check);

if ($data['total'] > 0) {
    $response['used'] = true;
}

echo json_encode($response);
sqlsrv_close($con);
