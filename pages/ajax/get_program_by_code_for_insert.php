<?php
include "../../koneksi.php";

header('Content-Type: application/json');


if (isset($_GET['code']) && isset($_GET['machine'])) {
    $code = $_GET['code'];
    $machine = $_GET['machine'];

    $groupQuery = sqlsrv_query($con, "SELECT TOP 1 id_group FROM db_laborat.tbl_preliminary_schedule WHERE no_machine = ?", [$machine]);
    
    if ($groupQuery && ($groupRow = sqlsrv_fetch_array($groupQuery, SQLSRV_FETCH_ASSOC))) {
        $id_group = $groupRow['id_group'];

        $checkCodeQuery = sqlsrv_query($con, "SELECT TOP 1 product_name FROM db_laborat.master_suhu WHERE code = ? AND [group] = ?", [$code, $id_group]);

        if ($checkCodeQuery && ($codeRow = sqlsrv_fetch_array($checkCodeQuery, SQLSRV_FETCH_ASSOC))) {
            echo json_encode([
                'status' => 'success',
                'product_name' => $codeRow['product_name'],
                'group' => $id_group
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Kode tidak diperbolehkan untuk mesin ini'
            ]);
        }

    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Mesin tidak ditemukan dalam preliminary schedule'
        ]);
    }

} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Parameter code dan machine wajib diisi'
    ]);
}

