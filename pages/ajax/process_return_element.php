<?php
header('Content-Type: application/json');
require_once '../../koneksi.php';

// Simple JSON response helper
function res($success, $message = '', $data = null) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

if (!isset($_POST['element_id']) || !isset($_POST['qty_return']) 
    // || !isset($_POST['no_resep'])
) {
    res(false, 'element_id, qty_return and no_resep are required');
}

$element_id = $_POST['element_id'];
$qty_return_raw = $_POST['qty_return'];
// $no_resep = $_POST['no_resep'];

// sanitize and validate qty
if (!is_numeric($qty_return_raw)) {
    res(false, 'qty_return must be a number');
}
$qty_return = floatval($qty_return_raw);
if ($qty_return < 0) {
    res(false, 'qty_return cannot be a (-) decimal');
}

// Begin transaction
sqlsrv_begin_transaction($con);
try {
    // 1) Fetch current qty from balance
    $sql = "SELECT TOP 1 BASEPRIMARYQUANTITYUNIT FROM db_laborat.balance WHERE NUMBERID = ?";
    $stmt = sqlsrv_prepare($con, $sql, [$element_id]);
    if (!$stmt) {
        $errors = sqlsrv_errors();
        throw new Exception('Prepare failed (select): ' . ($errors ? $errors[0]['message'] : 'unknown error'));
    }
    if (!sqlsrv_execute($stmt)) {
        $errors = sqlsrv_errors();
        throw new Exception('Execute failed (select): ' . ($errors ? $errors[0]['message'] : 'unknown error'));
    }
    if (!$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        throw new Exception('Element not found');
    }
    $curr_qty = floatval($row['BASEPRIMARYQUANTITYUNIT']);
    sqlsrv_free_stmt($stmt);

    // 2) Overwrite qty (user requested overwrite, not subtract)
    $new_qty = $qty_return;

    // 3) Update balance table (overwrite)
    $sql = "UPDATE db_laborat.balance SET BASEPRIMARYQUANTITYUNIT = ?, LASTUPDATEDATETIME = GETDATE() WHERE NUMBERID = ?";
    $stmt = sqlsrv_prepare($con, $sql, [$new_qty, $element_id]);
    if (!$stmt) {
        $errors = sqlsrv_errors();
        throw new Exception('Prepare failed (update): ' . ($errors ? $errors[0]['message'] : 'unknown error'));
    }
    if (!sqlsrv_execute($stmt)) {
        $errors = sqlsrv_errors();
        throw new Exception('Update failed: ' . ($errors ? $errors[0]['message'] : 'unknown error'));
    }
    sqlsrv_free_stmt($stmt);

    // 5) Insert into balance_transactions with action 'Waste'
    $qty_waste_kg = $curr_qty - $new_qty;
    $qty_waste_gr = $qty_waste_kg * 1000;

    $qty_before = $curr_qty;
    $qty_after = $new_qty;
    $action = 'Waste';
    $uom = 'gr';
    $uom_balance = 'kg';
    $no_resep = NULL;

    $sql = "INSERT INTO db_laborat.balance_transactions (element_id, no_resep, action, uom, qty, uom_balance, qty_element_before, qty_element_after, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, GETDATE())";
    $stmt = sqlsrv_prepare($con, $sql, [$element_id, $no_resep, $action, $uom, $qty_waste_gr, $uom_balance, $qty_before, $qty_after]);
    if (!$stmt) {
        $errors = sqlsrv_errors();
        throw new Exception('Prepare failed (insert transaction): ' . ($errors ? $errors[0]['message'] : 'unknown error'));
    }
    if (!sqlsrv_execute($stmt)) {
        $errors = sqlsrv_errors();
        throw new Exception('Insert transaction failed: ' . ($errors ? $errors[0]['message'] : 'unknown error'));
    }
    sqlsrv_free_stmt($stmt);

    // 6) Delete row(s) in tbl_resep_element for that element_id AND specific no_resep
    $sql = "DELETE FROM db_laborat.tbl_resep_element WHERE element_id = ?";
    $stmt = sqlsrv_prepare($con, $sql, [$element_id]);
    if (!$stmt) {
        $errors = sqlsrv_errors();
        throw new Exception('Prepare failed (delete): ' . ($errors ? $errors[0]['message'] : 'unknown error'));
    }
    if (!sqlsrv_execute($stmt)) {
        $errors = sqlsrv_errors();
        throw new Exception('Delete failed: ' . ($errors ? $errors[0]['message'] : 'unknown error'));
    }
    $affected = sqlsrv_rows_affected($stmt);
    sqlsrv_free_stmt($stmt);

    // Commit
    sqlsrv_commit($con);

    res(true, 'Return processed (overwrite)', ['new_qty' => $new_qty, 'deleted_rows' => $affected]);

} catch (Exception $e) {
    sqlsrv_rollback($con);
    res(false, $e->getMessage());
}
