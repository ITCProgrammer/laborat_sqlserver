<?php
header('Content-Type: application/json');

include "../../koneksi.php";

$id = $_POST['id'] ?? '';
$element_qty = $_POST['element_qty'] ?? '';

// Validasi
if (!$id || $element_qty === '') {
    echo json_encode([
        'success' => false,
        'message' => 'ID dan element_qty wajib diisi'
    ]);
    exit;
}

// Validasi qty tidak negatif
$qty = floatval($element_qty);
if ($qty < 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Qty tidak boleh negatif'
    ]);
    exit;
}

// Update ke database
$updateQuery = "UPDATE db_laborat.tbl_preliminary_schedule_element SET qty = ? WHERE tbl_preliminary_schedule_id = ?";
$stmt = sqlsrv_query($con, $updateQuery, [$qty, $id]);

if ($stmt) {
    echo json_encode([
        'success' => true,
        'message' => 'Qty berhasil diupdate'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal update database',
        'error'   => sqlsrv_errors()
    ]);
}
?>
