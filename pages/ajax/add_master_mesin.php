<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../../koneksi.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $no_machine = trim($_POST['no_machine']);
    $suhu = trim($_POST['suhu']);
    $program = trim($_POST['program']);
    $keterangan = trim($_POST['keterangan']);

    if (!empty($suhu)) {
        $suhu = $suhu . '°C';
    }

    $query = "INSERT INTO db_laborat.master_mesin (no_machine, suhu, program, keterangan) VALUES (?, ?, ?, ?)";
    $params = [$no_machine, $suhu, $program, $keterangan];
    $stmt = sqlsrv_prepare($con, $query, $params);

    if (!$stmt) {
        $errors = sqlsrv_errors();
        echo json_encode([
            'status' => 'error',
            'message' => 'Prepare statement gagal: ' . ($errors ? $errors[0]['message'] : 'unknown error')
        ]);
        exit;
    }

    $success = sqlsrv_execute($stmt);

    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil ditambahkan!']);
    } else {
        $errors = sqlsrv_errors();
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal menyimpan data: ' . ($errors ? $errors[0]['message'] : 'unknown error')
        ]);
    }

    sqlsrv_free_stmt($stmt);
}

