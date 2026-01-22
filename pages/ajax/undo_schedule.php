<?php
include '../../koneksi.php';

$response = ['success' => false];

$query = "UPDATE db_laborat.tbl_is_scheduling SET is_scheduling = 0";

$stmt = sqlsrv_query($con, $query);

if ($stmt) {
    $response['success'] = true;
}

header('Content-Type: application/json');
echo json_encode($response);
