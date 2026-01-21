<?php
include '../../koneksi.php';

$no_resep = $_GET['no_resep'] ?? '';
$data = ['temp_code' => '', 'temp_code2' => ''];

if (!$con || $no_resep === '') {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

$stmt = sqlsrv_query($con, "SELECT TOP 1 temp_code, temp_code2 FROM db_laborat.tbl_matching WHERE no_resep = ?", [$no_resep]);

if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
    $data['temp_code'] = $row['temp_code'] ?? '';
    $data['temp_code2'] = $row['temp_code2'] ?? '';
    sqlsrv_free_stmt($stmt);
}

header('Content-Type: application/json');
echo json_encode($data);
?>
