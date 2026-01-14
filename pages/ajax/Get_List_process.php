<?php
ini_set("error_reporting", 1);
header('Content-Type: application/json');
include "../../koneksi.php";

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (! $con) {
    http_response_code(500);
    echo json_encode(['error' => 'Koneksi SQL Server db_laborat gagal']);
    exit;
}

$sql = "
    SELECT id, nama_proses
    FROM db_laborat.master_proses
    WHERE is_active = 'TRUE'
      AND (? = '' OR nama_proses LIKE ?)
    ORDER BY TRY_CONVERT(INT, id) DESC, id DESC
";
$params = [$search, '%' . $search . '%'];

$stmt = sqlsrv_query($con, $sql, $params, ['Scrollable' => SQLSRV_CURSOR_KEYSET]);
if (! $stmt) {
    http_response_code(500);
    echo json_encode(['error' => sqlsrv_errors()]);
    exit;
}

$rows = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $rows[] = [
        'id'   => $row['nama_proses'],
        'text' => $row['nama_proses'],
    ];
}
sqlsrv_free_stmt($stmt);

echo json_encode($rows);
