<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../../koneksi.php';
header('Content-Type: application/json');

if (isset($_GET['no_machine'])) {
    $no_machine = strtolower($_GET['no_machine']);

    $query = "SELECT COUNT(*) AS count FROM db_laborat.master_mesin WHERE LOWER(no_machine) = ?";
    $result = sqlsrv_query($con, $query, [$no_machine]);

    if ($result === false) {
        $errors = sqlsrv_errors();
        echo json_encode([
            'status' => 'error',
            'message' => $errors ? $errors[0]['message'] : 'Gagal memeriksa data'
        ]);
        exit;
    }

    $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);

    if ($row && $row['count'] > 0) {
        echo json_encode(['status' => 'exists']);
    } else {
        echo json_encode(['status' => 'not_exists']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No. Machine tidak valid']);
}



