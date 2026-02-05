<?php
header('Content-Type: application/json');

// koneksi ke DB
include "../../koneksi.php"; 
if (! $con) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Koneksi SQL Server gagal']);
    exit;
}

// Ambil POST data
$no_resep   = $_POST['no_resep'] ?? '';
$element_code = $_POST['element_code'] ?? '';

// Validasi basic
if ($no_resep === '' || $element_code === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'no_resep dan element_id wajib diisi'
    ]);
    exit;
}

$sqlGetId = "SELECT NUMBERID FROM db_laborat.balance WHERE ELEMENTSCODE = ?";
$stmt = sqlsrv_query($con, $sqlGetId, [$element_code]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmt);

if (!$row) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Element Code tidak ditemukan di tabel balance.'
    ]);
    exit;
}

$element_id = $row['NUMBERID'];


// Cek balance: pastikan record balance ada dan qty (BASEPRIMARYQUANTITYUNIT) > 0
$checkBalanceQuery = "SELECT TOP 1 BASEPRIMARYQUANTITYUNIT FROM db_laborat.balance WHERE NUMBERID = ?";
$stmt = sqlsrv_query($con, $checkBalanceQuery, [$element_id]);
$balanceRow = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmt);

if (!$balanceRow) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Element tidak ditemukan di tabel balance.'
    ]);
    exit;
}

$qty = floatval($balanceRow['BASEPRIMARYQUANTITYUNIT']);
if ($qty <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Balance untuk element ini tidak mencukupi (qty <= 0).'
    ]);
    exit;
}

// 1. Cek apakah data sudah ada
$checkQuery = "SELECT COUNT(*) AS total FROM db_laborat.tbl_resep_element WHERE no_resep = ?";
$stmt = sqlsrv_query($con, $checkQuery, [$no_resep]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmt);

if ($row['total'] > 0) {
    // 2. UPDATE jika sudah ada
    $updateQuery = "UPDATE db_laborat.tbl_resep_element 
        SET element_id = ?, element_code = ?
        WHERE no_resep = ?";
    $stmt = sqlsrv_query($con, $updateQuery, [$element_id, $element_code, $no_resep]);

    if ($stmt) {
        echo json_encode([
            'status' => 'success',
            'mode' => 'update',
            'message' => 'Data berhasil diupdate.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => print_r(sqlsrv_errors(), true)
        ]);
    }

} else {
    // 3. INSERT jika belum ada
    $insertQuery = "INSERT INTO db_laborat.tbl_resep_element (no_resep, element_id, element_code) VALUES (?, ?, ?)";
    $stmt = sqlsrv_query($con, $insertQuery, [$no_resep, $element_id, $element_code]);

    if ($stmt) {
        echo json_encode([
            'status' => 'success',
            'mode' => 'insert',
            'message' => 'Data berhasil ditambahkan.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => print_r(sqlsrv_errors(), true)
        ]);
    }
}
