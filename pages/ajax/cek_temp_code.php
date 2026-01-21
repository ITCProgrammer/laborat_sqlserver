<?php
include "../../koneksi.php";
header('Content-Type: application/json');

$rcode = $_POST['rcode'] ?? '';
$response = ['needInput' => false, 'isDR' => false, 'error' => null];

if (!$con) {
    $response['error'] = 'Koneksi SQL Server gagal';
    echo json_encode($response);
    exit;
}
if (empty($rcode)) {
    $response['error'] = 'Kode resep kosong';
    echo json_encode($response);
    exit;
}

$isDR = substr($rcode, 0, 2) === 'DR';
$response['isDR'] = $isDR;

if ($isDR) {
    $stmt = sqlsrv_query($con, "SELECT temp_code, temp_code2 FROM db_laborat.tbl_matching WHERE no_resep = ?", [$rcode]);
    if ($stmt) {
        $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($data && (empty($data['temp_code']) || empty($data['temp_code2']))) {
            $response['needInput'] = true;
        }
        sqlsrv_free_stmt($stmt);
    } else {
        $response['error'] = sqlsrv_errors();
    }
} else {
    $stmt = sqlsrv_query($con, "SELECT temp_code FROM db_laborat.tbl_matching WHERE no_resep = ?", [$rcode]);
    if ($stmt) {
        $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($data && empty($data['temp_code'])) {
            $response['needInput'] = true;
        }
        sqlsrv_free_stmt($stmt);
    } else {
        $response['error'] = sqlsrv_errors();
    }
}

echo json_encode($response);
