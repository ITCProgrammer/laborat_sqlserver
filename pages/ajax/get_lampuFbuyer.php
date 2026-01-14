<?php
ini_set("error_reporting", 1);
header('Content-Type: application/json');
include "../../koneksi.php";

$buyer = isset($_POST['buyer']) ? $_POST['buyer'] : '';

if (! $con) {
    http_response_code(500);
    echo json_encode(['error' => 'Koneksi SQL Server db_laborat gagal']);
    exit;
}

$stmt = sqlsrv_query(
    $con,
    "SELECT lampu FROM db_laborat.vpot_lampbuy WHERE buyer = ? ORDER BY flag",
    [$buyer],
    ['Scrollable' => SQLSRV_CURSOR_KEYSET]
);

if (! $stmt) {
    http_response_code(500);
    echo json_encode(['error' => sqlsrv_errors()]);
    exit;
}

$data = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $data[] = [$row['lampu']];
}
sqlsrv_free_stmt($stmt);

echo json_encode($data);
