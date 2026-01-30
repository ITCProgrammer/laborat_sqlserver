<?php
session_start();
include "../../koneksi.php";
header('Content-Type: application/json');

try {
    $element_id = trim($_POST['element_id'] ?? '');
    if (!$element_id) throw new Exception('element_id required');

    $decosub01 = trim($_POST['decosub01'] ?? "");
    $decosub02 = trim($_POST['decosub02'] ?? "");
    $decosub03 = trim($_POST['decosub03'] ?? "");
    $decosub04 = trim($_POST['decosub04'] ?? "");

    $warehouse_zone_code     = trim($_POST['warehouse_zone_code'] ?? "");
    $warehouse_location_code = trim($_POST['warehouse_location_code'] ?? "");

    $quality_level_code = trim($_POST['quality_level_code'] ?? "");
    $lot_code           = trim($_POST['lot_code'] ?? "");
    $project_code       = trim($_POST['project_code'] ?? "");
    $g_b                = trim($_POST['g_b'] ?? "");

    $primary_qty   = floatval($_POST['primary_quantity'] ?? 0);
    $secondary_qty = floatval($_POST['secondary_quantity'] ?? 0);

    $UPDATEDBY = $_SESSION['userLAB'] ?? 'anonymous';

    // Do not update quantity fields here — quantities must remain unchanged during edit
    $quality_level_code = ($quality_level_code === '' || !is_numeric($quality_level_code))
        ? null
        : (int) $quality_level_code;
    $element_id = is_numeric($element_id) ? (int) $element_id : $element_id;

    $sql = "UPDATE db_laborat.balance SET
        DECOSUBCODE01 = ?,
        DECOSUBCODE02 = ?,
        DECOSUBCODE03 = ?,
        DECOSUBCODE04 = ?,
        WHSLOCATIONWAREHOUSEZONECODE = ?,
        WAREHOUSELOCATIONCODE = ?,
        QUALITYLEVELCODE = ?,
        LOTCODE = ?,
        PROJECTCODE = ?,
        G_B = ?,
        LASTUPDATEDATETIME = GETDATE(),
        LASTUPDATEDATETIMEUTC = GETDATE()
        WHERE NUMBERID = ?";

    $stmt = sqlsrv_prepare($con, $sql, [
        $decosub01,
        $decosub02,
        $decosub03,
        $decosub04,
        $warehouse_zone_code,
        $warehouse_location_code,
        $quality_level_code,
        $lot_code,
        $project_code,
        $g_b,
        $element_id
    ]);
    if (!$stmt) {
        $errors = sqlsrv_errors();
        throw new Exception('Prepare failed: ' . ($errors ? $errors[0]['message'] : 'unknown error'));
    }

    $ok = sqlsrv_execute($stmt);
    if (!$ok) {
        $errors = sqlsrv_errors();
        throw new Exception('Execute failed: ' . ($errors ? $errors[0]['message'] : 'unknown error'));
    }

    sqlsrv_free_stmt($stmt);

    echo json_encode(['status' => 'success', 'message' => 'Element updated']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

?>
