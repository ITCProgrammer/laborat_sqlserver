<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";
session_start();

$time = date('Y-m-d H:i:s');
$user = $_SESSION['userLAB'] ?? 'unknown';

$val = function($key) {
    return isset($_POST[$key]) && $_POST[$key] !== '' ? $_POST[$key] : null;
};

$id_status   = $val('id_status');
$id_matching = $val('id_matching');

// ambil resep (no_resep) berdasarkan id_status
$resep = null;
if ($id_status) {
    $stmtR = sqlsrv_query($con, "SELECT idm FROM db_laborat.tbl_status_matching WHERE id = ?", [$id_status]);
    if ($stmtR) {
        $rowR = sqlsrv_fetch_array($stmtR, SQLSRV_FETCH_ASSOC);
        $resep = $rowR['idm'] ?? null;
    }
}
if (!$resep) {
    header('Content-Type: application/json');
    echo json_encode([
        'session' => 'ERROR',
        'exp' => 'resep_missing',
        'ctx' => 'Insert_dataTableResep_toDB',
        'sqlsrv' => sqlsrv_errors()
    ]);
    exit;
}

// siapkan nilai conc/time/doby (10 slot)
$conc = [];
$dt   = [];
$doby = [];
for ($i = 0; $i <= 9; $i++) {
    $idx = $i === 0 ? '' : $i; // conc, conc1, conc2...
    $cKey = $i === 0 ? 'conc' : 'conc'.$i;
    if ($val($cKey) !== null) {
        $conc[$i] = $val($cKey);
        $dt[$i]   = $time;
        $doby[$i] = $user;
    } else {
        $conc[$i] = null;
        $dt[$i]   = null;
        $doby[$i] = null;
    }
}

$nama = $val('desc_code');

$sql = "INSERT INTO db_laborat.tbl_matching_detail
            (flag,id_matching,id_status,resep,kode,nama,
             conc1,conc2,conc3,conc4,conc5,conc6,conc7,conc8,conc9,conc10,
             time_1,time_2,time_3,time_4,time_5,time_6,time_7,time_8,time_9,time_10,
             doby1,doby2,doby3,doby4,doby5,doby6,doby7,doby8,doby9,doby10,
             remark,inserted_at,inserted_by)
        VALUES (?,?,?,?,?,?,
                ?,?,?,?,?,?,?,?,?,?,
                ?,?,?,?,?,?,?,?,?,?,
                ?,?,?,?,?,?,?,?,?,?,
                ?,?,?)";

$params = [
    $val('flag'), $id_matching, $id_status, $resep, $val('code'), $nama,
    $conc[0], $conc[1], $conc[2], $conc[3], $conc[4], $conc[5], $conc[6], $conc[7], $conc[8], $conc[9],
    $dt[0], $dt[1], $dt[2], $dt[3], $dt[4], $dt[5], $dt[6], $dt[7], $dt[8], $dt[9],
    $doby[0], $doby[1], $doby[2], $doby[3], $doby[4], $doby[5], $doby[6], $doby[7], $doby[8], $doby[9],
    $val('keterangan'), $time, $user
];

$stmt = sqlsrv_query($con, $sql, $params);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode([
        'session' => 'ERROR',
        'exp' => 'insert_failed',
        'ctx' => 'Insert_dataTableResep_toDB',
        'sqlsrv' => sqlsrv_errors(),
        'params' => $params
    ]);
    exit;
}

header('Content-Type: application/json');
echo json_encode([
    'session' => 'LIB_SUCCSS',
    'exp' => 'inserted'
]);
?>
