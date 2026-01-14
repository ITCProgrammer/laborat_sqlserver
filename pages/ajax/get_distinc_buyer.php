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
    SELECT buyer
    FROM (
        SELECT buyer, MAX(id) AS max_id
        FROM db_laborat.vpot_lampbuy
        WHERE (? = '' OR buyer LIKE ?)
        GROUP BY buyer
    ) AS b
    ORDER BY b.max_id DESC
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
        'id'   => $row['buyer'],
        'text' => $row['buyer'],
    ];
}
sqlsrv_free_stmt($stmt);

echo json_encode($rows);
