<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include "../../koneksi.php";

$no_resep = trim($_GET['no_resep'] ?? '');
$response = ['success' => false, 'codes' => []];

if ($no_resep) {
    $no_resep_base    = $no_resep;
    $matching_column  = 'temp_code';

    if (str_ends_with($no_resep, '-A')) {
        $no_resep_base = substr($no_resep, 0, -2);
        // kolom tetap 'temp_code'
    } elseif (str_ends_with($no_resep, '-B')) {
        $no_resep_base   = substr($no_resep, 0, -2);
        $matching_column = 'temp_code2';
    }

    $query = "
        SELECT DISTINCT code AS code FROM db_laborat.tbl_preliminary_schedule 
        WHERE no_resep = ? AND status = 'repeat' AND code IS NOT NULL AND code <> '-'
        UNION
        SELECT DISTINCT $matching_column AS code FROM db_laborat.tbl_matching 
        WHERE no_resep = ? AND $matching_column IS NOT NULL AND $matching_column <> '-'
    ";

    $stmt = sqlsrv_prepare($con, $query, [$no_resep, $no_resep_base]);
    if ($stmt && sqlsrv_execute($stmt)) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (!isset($row['code'])) {
                continue;
            }
            $codeVal = trim((string)$row['code']);
            if ($codeVal === '' || $codeVal === '-') {
                continue; // abaikan kode kosong atau placeholder
            }
            $response['codes'][] = $codeVal;
        }
        if (!empty($response['codes'])) {
            $response['success'] = true;
        }
    } else {
        $response['error'] = sqlsrv_errors();
    }
}

header('Content-Type: application/json');
echo json_encode($response);
