<?php
header('Content-Type: application/json');
require_once '../../koneksi.php';

$element_id = null;
if (isset($_GET['element_id'])) {
    $element_id = $_GET['element_id'];
} elseif (isset($_POST['element_id'])) {
    $element_id = $_POST['element_id'];
}

if (!$element_id) {
    echo json_encode(['data' => [], 'message' => 'element_id required']);
    exit;
}

// Query grouped by no_resep with conditional aggregates for Preliminary and Waste
$sql = "SELECT qty,
            created_at
    FROM db_laborat.balance_transactions
    WHERE element_id = ? AND action = 'Waste'
    ORDER BY created_at DESC";

$stmt = sqlsrv_prepare($con, $sql, [$element_id]);
if (!$stmt) {
    $errors = sqlsrv_errors();
    echo json_encode(['data' => [], 'message' => 'Prepare failed: ' . ($errors ? $errors[0]['message'] : 'unknown error')]);
    exit;
}

if (!sqlsrv_execute($stmt)) {
    $errors = sqlsrv_errors();
    echo json_encode(['data' => [], 'message' => 'Execute failed: ' . ($errors ? $errors[0]['message'] : 'unknown error')]);
    exit;
}
$data = [];
$res = $stmt;
while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
    $createdAt = $row['created_at'];
    if ($createdAt instanceof DateTimeInterface) {
        $createdAt = $createdAt->format('Y-m-d H:i:s');
    } elseif ($createdAt === null) {
        $createdAt = '';
    }
    $data[] = [
        'qty' => floatval($row['qty']),
        'created_at' => $createdAt,
    ];
}
sqlsrv_free_stmt($stmt);
echo json_encode(['data' => $data]);
exit;
