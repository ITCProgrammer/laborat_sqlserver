<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";
session_start();
$time = date('Y-m-d H:i:s');
$awal  = strtotime($_POST['tgl_buat_status']);
$akhir = strtotime(date('Y-m-d H:i:s'));
$benang_a = str_replace("'", "''", $_POST['benang_a'] ?? '');
function v($key, $default = '') {
    return $_POST[$key] ?? $default;
}
function n($key) {
    $val = $_POST[$key] ?? null;
    return ($val === '' || $val === null) ? null : $val;
}
$paramsStatus = [
    n('matching_ke'),
    n('howmany_Matching_ke'),
    $benang_a,
    n('lebar_a'),
    n('gramasi_a'),
    v('l_R'),
    n('kadar_air'),
    n('RC_Suhu'),
    n('RCWaktu'),
    n('soapingSuhu'),
    n('soapingWaktu'),
    n('cie_wi'),
    n('cie_tint'),
    n('yellowness'),
    v('Spektro_R'),
    v('keterangan'),
    n('tside_c'),
    n('tside_min'),
    n('cside_c'),
    n('cside_min'),
    n('kadar_air_true'),
    $time,
    $_SESSION['userLAB'] ?? '',
    n('bleaching_sh'),
    n('bleaching_tm'),
    v('second_lr'),
    v('remark_dye'),
    v('id_status'),
    v('idm')
];
$ok = sqlsrv_query(
    $con,
    "UPDATE db_laborat.tbl_status_matching SET
        percobaan_ke = ?,
        howmany_percobaan_ke = ?,
        benang_aktual = ?,
        lebar_aktual = ?,
        gramasi_aktual = ?,
        lr = ?,
        ph = ?,
        rc_sh = ?,
        rc_tm = ?,
        soaping_sh = ?,
        soaping_tm = ?,
        cie_wi = ?,
        cie_tint = ?,
        yellowness = ?,
        spektro_r = ?,
        ket = ?,
        tside_c = ?,
        tside_min = ?,
        cside_c = ?,
        cside_min = ?,
        kadar_air = ?,
        edited_at = ?,
        edited_by = ?,
        bleaching_sh = ?,
        bleaching_tm = ?,
        second_lr = ?,
        remark_dye = ?
     WHERE id = ? AND idm = ?",
    $paramsStatus
);
if (! $ok) {
    echo json_encode(['session' => 'ERROR', 'errors' => sqlsrv_errors()]);
    exit;
}
$ok = sqlsrv_query(
    $con,
    "UPDATE db_laborat.tbl_matching SET recipe_code = ? WHERE id = ?",
    [$_POST['recipe_code'] ?? '', $_POST['id_tblmatching'] ?? '']
);
if (! $ok) {
    echo json_encode(['session' => 'ERROR', 'errors' => sqlsrv_errors()]);
    exit;
}
$ip_num = $_SERVER['REMOTE_ADDR'];
$ok = sqlsrv_query(
    $con,
    "INSERT INTO db_laborat.log_status_matching (ids, status, info, do_by, do_at, ip_address)
     VALUES (?, ?, ?, ?, ?, ?)",
    [$_POST['idm'] ?? '', 'selesai', 'modifikasi resep', $_SESSION['userLAB'] ?? '', $time, $ip_num]
);
if (! $ok) {
    echo json_encode(['session' => 'ERROR', 'errors' => sqlsrv_errors()]);
    exit;
}

$response = array(
    'session' => 'LIB_SUCCSS',
    'exp' => 'updated'
);
echo json_encode($response);
