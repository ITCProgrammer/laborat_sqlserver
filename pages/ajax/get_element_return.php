<?php
header('Content-Type: application/json');
require_once '../../koneksi.php'; // sesuaikan

$response = [
    'success' => false,
    'data' => null,
    'message' => 'Unknown error'
];

// --- Validate input ---
if (!isset($_POST['element_id']) || empty($_POST['element_id'])) {
    echo json_encode(['success' => false, 'data' => null, 'message' => 'element_id required']);
    exit;
}

$element_id = $_POST['element_id'];

// --- Ambil data element untuk direturn---
 $queryElement = " SELECT TOP 1
        b.NUMBERID as element_id, 
        b.ELEMENTSCODE as element_code,
        b.BASEPRIMARYQUANTITYUNIT as curr_qty,
        ISNULL(bt.used_stock, 0) AS used_stock,
        (b.BASEPRIMARYQUANTITYUNIT + ISNULL(bt.used_stock, 0) / 1000) AS initial_stock
    FROM db_laborat.balance b
    LEFT JOIN (
        SELECT element_id, SUM(qty) AS used_stock
        FROM db_laborat.balance_transactions
        GROUP BY element_id
    ) bt ON bt.element_id = b.NUMBERID
    WHERE b.NUMBERID = ?
";

$stmt = sqlsrv_prepare($con, $queryElement, [$element_id]);
if (!$stmt) {
    $errors = sqlsrv_errors();
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Prepare failed: ' . ($errors ? $errors[0]['message'] : 'unknown error')]);
    exit;
}

if (!sqlsrv_execute($stmt)) {
    $errors = sqlsrv_errors();
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Execute failed: ' . ($errors ? $errors[0]['message'] : 'unknown error')]);
    exit;
}

if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // fetch associated resep list for this element
    $noResepList = [];
    $stmt2 = sqlsrv_query($con, "SELECT DISTINCT no_resep FROM db_laborat.tbl_resep_element WHERE element_id = ?", [$element_id]);
    if ($stmt2) {
        while ($rr = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC)) {
            $noResepList[] = $rr['no_resep'];
        }
        sqlsrv_free_stmt($stmt2);
    }

    $response['success'] = true;
    $response['data'] = [
        'element_id' => $row['element_id'],
        'element_code' => $row['element_code'],
        'curr_qty' => floatval($row['curr_qty']),
        'initial_stock' => floatval($row['initial_stock']),
        'no_resep_list' => $noResepList
    ];
    $response['message'] = 'Success';
} else {
    $response['message'] = 'Data not found';
}

sqlsrv_free_stmt($stmt);
echo json_encode($response);
exit;
