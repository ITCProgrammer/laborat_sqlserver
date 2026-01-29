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
$sql = "SELECT no_resep,
           COUNT(*) AS trx_count,
           SUM(qty) AS total_qty,
           MAX(created_at) AS last_date
    FROM db_laborat.balance_transactions
    WHERE element_id = ? AND action = 'Preliminary-Cycle'
    GROUP BY no_resep
    ORDER BY last_date DESC";

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
    $lastDate = $row['last_date'];
    if ($lastDate instanceof DateTimeInterface) {
        $lastDate = $lastDate->format('Y-m-d H:i:s');
    } elseif ($lastDate === null) {
        $lastDate = '';
    }
    $data[] = [
        'no_resep' => $row['no_resep'],
        'trx_count' => intval($row['trx_count']),
        'total_qty' => floatval($row['total_qty']),
        'last_date' => $lastDate,
    ];
}
sqlsrv_free_stmt($stmt);
echo json_encode(['data' => $data]);
exit;
