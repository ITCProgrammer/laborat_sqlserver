<?php
include __DIR__ . '/../../koneksi.php';
header('Content-Type: application/json');

$noResep = $_GET['no_resep'] ?? '';

if ($noResep === '') {
    echo json_encode(["valid" => false, "error" => "No. Resep kosong."]);
    exit;
}

$stmt = sqlsrv_query(
    $con,
    "SELECT COUNT(*) AS total 
     FROM db_laborat.tbl_preliminary_schedule 
     WHERE no_resep = ? AND status = 'end' AND is_old_cycle = 0",
    [$noResep]
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["valid" => false, "error" => sqlsrv_errors()]);
    exit;
}
$data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo json_encode(["valid" => ($data['total'] ?? 0) > 0]);
