<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../../koneksi.php';
header('Content-Type: application/json');

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    $query = "SELECT TOP 1 code FROM db_laborat.master_suhu WHERE code = ?";
    $params = [$code];
    $result = sqlsrv_query($con, $query, $params);

    $duplicate = false;

    if ($result !== false) {
        if (sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
            $duplicate = true;
        }

        if ($duplicate) {
            echo json_encode(['status' => 'exists']);
        } else {
            echo json_encode(['status' => 'not_exists']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Query gagal: ' . print_r(sqlsrv_errors(), true)]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Product name tidak valid']);
}