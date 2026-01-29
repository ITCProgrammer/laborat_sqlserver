<?php
header('Content-Type: application/json');
include "../../koneksi.php";

$response = ['success' => false, 'data' => null, 'message' => 'Unknown error'];

// accept element_id via GET or POST
$element_id = $_GET['element_id'] ?? $_POST['element_id'] ?? null;
if (!$element_id) {
    echo json_encode(['success' => false, 'message' => 'element_id required']);
    exit;
}

$sql = "SELECT TOP 1
    NUMBERID as element_id,
    ELEMENTSCODE as element_code,
    DECOSUBCODE01 as decosub01,
    DECOSUBCODE02 as decosub02,
    DECOSUBCODE03 as decosub03,
    DECOSUBCODE04 as decosub04,
    WHSLOCATIONWAREHOUSEZONECODE as warehouse_zone_code,
    WAREHOUSELOCATIONCODE as warehouse_location_code,
    QUALITYLEVELCODE as quality_level_code,
    LOTCODE as lot_code,
    PROJECTCODE as project_code,
    G_B as g_b,
    BASEPRIMARYQUANTITYUNIT as primary_qty,
    BASESECONDARYQUANTITYUNIT as secondary_qty
    FROM db_laborat.balance WHERE NUMBERID = ?";

$stmt = sqlsrv_prepare($con, $sql, [$element_id]);
if (!$stmt) {
    $errors = sqlsrv_errors();
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . ($errors ? $errors[0]['message'] : 'unknown error')]);
    exit;
}

if (!sqlsrv_execute($stmt)) {
    $errors = sqlsrv_errors();
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . ($errors ? $errors[0]['message'] : 'unknown error')]);
    exit;
}

if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // build a human-friendly item text
    $item_text = trim(($row['decosub01'] ?? '') . ' ' . ($row['decosub02'] ?? '') . ' ' . ($row['decosub03'] ?? '') . ' ' . ($row['decosub04'] ?? ''));

    $row['item_text'] = $item_text;
    // item_id not strictly available; we can use concatenated code if needed
    $row['item_id'] = trim(($row['decosub01'] ?? '') . ($row['decosub02'] ?? '') . ($row['decosub03'] ?? '') . ($row['decosub04'] ?? ''));

    $response['success'] = true;
    $response['data'] = $row;
    $response['message'] = 'OK';
} else {
    $response['message'] = 'Not found';
}

sqlsrv_free_stmt($stmt);
echo json_encode($response);
exit;

?>
