<?php
include "../../koneksi.php";
header('Content-Type: application/json');

if (isset($_POST['id'])) {
    $id = (int) $_POST['id'];
    $query = "DELETE FROM db_laborat.tbl_preliminary_schedule WHERE id = ?";
    $stmt  = sqlsrv_query($con, $query, [$id]);
    if ($stmt) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => sqlsrv_errors()]);
    }
} else {
    echo json_encode(['status' => 'invalid_request']);
}
