<?php
include "../../koneksi.php";

header('Content-Type: application/json');

if (!isset($_GET['code'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Parameter code wajib diisi']);
    exit;
}

$code  = trim($_GET['code']);
$query = "SELECT TOP 1 product_name FROM db_laborat.master_suhu WHERE code = ?";
$stmt  = sqlsrv_query($con, $query, [$code]);

if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
    echo json_encode([
        'status'        => 'success',
        'product_name'  => $row['product_name']
    ]);
} else {
    $err = sqlsrv_errors();
    echo json_encode([
        'status'  => 'error',
        'message' => 'Kode tidak ditemukan',
        'detail'  => $err
    ]);
}
