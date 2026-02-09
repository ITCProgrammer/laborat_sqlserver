<?php
ini_set("error_reporting", 1);
session_start();
include "../../koneksi.php";

$fail = function($ctx){
    $errs = sqlsrv_errors();
    $msg = '';
    if (is_array($errs) && isset($errs[0]['message'])) {
        $msg = $errs[0]['message'];
    }
    echo json_encode([
        'status' => 'error',
        'ctx' => $ctx,
        'message' => $msg,
        'sqlsrv' => $errs
    ]);
    exit;
};

$time   = date('Y-m-d H:i:s');
$ip_num = $_SERVER['REMOTE_ADDR'];

$benang_a   = $_POST['benang_a'] ?? '';
$Benang     = $_POST['Benang'] ?? '';
$keterangan = $_POST['keterangan'] ?? '';

// Normalisasi angka agar tidak gagal konversi ke numeric di SQL Server
$norm_num = function ($val) {
    $val = trim((string)$val);
    if ($val === '' || $val === '-' || strcasecmp($val, 'null') === 0 || strcasecmp($val, 'undefined') === 0 || strcasecmp($val, 'nan') === 0) {
        return null;
    }
    // ubah format 1:6 / 1,6 menjadi 1.6
    $val = str_replace([',', ':', ' '], ['.', '.', ''], $val);
    return $val;
};
$is_num = function ($val) {
    return ($val === null || $val === '') ? true : preg_match('/^-?\d+(\.\d+)?$/', $val);
};

$second_lr_format = $norm_num($_POST['second_lr'] ?? '');
$lr_format        = $norm_num($_POST['l_R'] ?? '');

// Validasi numeric sebelum query
$numericFields = [
    'matching_ke' => $norm_num($_POST['matching_ke'] ?? null),
    'howmany_Matching_ke' => $norm_num($_POST['howmany_Matching_ke'] ?? null),
    'lebar_a' => $norm_num($_POST['lebar_a'] ?? null),
    'gramasi_a' => $norm_num($_POST['gramasi_a'] ?? null),
    'kadar_air' => $norm_num($_POST['kadar_air'] ?? null),
    'RC_Suhu' => $norm_num($_POST['RC_Suhu'] ?? null),
    'RCWaktu' => $norm_num($_POST['RCWaktu'] ?? null),
    'soapingSuhu' => $norm_num($_POST['soapingSuhu'] ?? null),
    'soapingWaktu' => $norm_num($_POST['soapingWaktu'] ?? null),
    'cie_wi' => $norm_num($_POST['cie_wi'] ?? null),
    'cie_tint' => $norm_num($_POST['cie_tint'] ?? null),
    'yellowness' => $norm_num($_POST['yellowness'] ?? null),
    'Spektro_R' => $norm_num($_POST['Spektro_R'] ?? null),
    'tside_c' => $norm_num($_POST['tside_c'] ?? null),
    'tside_min' => $norm_num($_POST['tside_min'] ?? null),
    'cside_c' => $norm_num($_POST['cside_c'] ?? null),
    'cside_min' => $norm_num($_POST['cside_min'] ?? null),
    'kadar_air_true' => $norm_num($_POST['kadar_air_true'] ?? null),
    'bleaching_sh' => $norm_num($_POST['bleaching_sh'] ?? null),
    'bleaching_tm' => $norm_num($_POST['bleaching_tm'] ?? null),
    'lr' => $lr_format,
    'second_lr' => $second_lr_format
];
$invalid = [];
foreach ($numericFields as $k => $v) {
    if (!$is_num($v)) {
        $invalid[$k] = $v;
    }
}
if (!empty($invalid)) {
    echo json_encode([
        'status' => 'error',
        'ctx' => 'invalid_numeric',
        'message' => 'Input numeric tidak valid: ' . implode(', ', array_keys($invalid)),
        'invalid' => $invalid
    ]);
    exit;
}

// hapus detail lama
if(!sqlsrv_query($con, "DELETE FROM db_laborat.tbl_matching_detail WHERE id_matching = ? AND id_status = ?", [$_POST['id_matching'], $_POST['id_status']])) $fail('hapus_detail');

// update status matching
if(!sqlsrv_query($con, "UPDATE db_laborat.tbl_status_matching SET
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
                    status = 'hold',
                    cie_wi = ?,
                    cie_tint = ?,
                    yellowness = ?,
                    spektro_r = ?,
                    done_matching = ?,
                    ket = ?,
                    hold_by = ?,
                    hold_at = ?,
                    tside_c = ?,
                    tside_min = ?,
                    cside_c = ?,
                    cside_min= ?,
                    kadar_air= ?,
                    koreksi_resep= ?,
                    koreksi_resep2= ?,
                    koreksi_resep3= ?,
                    koreksi_resep4= ?,
                    koreksi_resep5= ?,
                    koreksi_resep6= ?, 
                    koreksi_resep7= ?,
                    koreksi_resep8= ?,
                    final_matcher= ?,
                    create_resep = ?,
                    acc_ulang_ok = ?,
                    acc_resep1 = ?,
                    acc_resep2 = ?,
                    colorist1 = ?,
                    colorist2 = ?,
                    colorist3 = ?,
                    colorist4 = ?,
                    colorist5 = ?,
                    colorist6 = ?,
                    colorist7 = ?,
                    colorist8 = ?,
                    matcher = ?,
                    grp=?,
                    bleaching_sh=?,
                    bleaching_tm=?,
                    second_lr=?
                    where id = ? and idm = ?", [
                      $numericFields['matching_ke'], $numericFields['howmany_Matching_ke'], $benang_a, $numericFields['lebar_a'], $numericFields['gramasi_a'],
                      $numericFields['lr'], $numericFields['kadar_air'], $numericFields['RC_Suhu'], $numericFields['RCWaktu'], $numericFields['soapingSuhu'], $numericFields['soapingWaktu'],
                      $numericFields['cie_wi'], $numericFields['cie_tint'], $numericFields['yellowness'], $numericFields['Spektro_R'], $_POST['Done_Matching'], $keterangan,
                      $_SESSION['userLAB'], $time, $numericFields['tside_c'], $numericFields['tside_min'], $numericFields['cside_c'], $numericFields['cside_min'],
                      $numericFields['kadar_air_true'],
                      $_POST['koreksi_resep'] ?? null,
                      $_POST['koreksi_resep2'] ?? null,
                      $_POST['koreksi_resep3'] ?? null,
                      $_POST['koreksi_resep4'] ?? null,
                      $_POST['koreksi_resep5'] ?? null,
                      $_POST['koreksi_resep6'] ?? null,
                      $_POST['koreksi_resep7'] ?? null,
                      $_POST['koreksi_resep8'] ?? null,
                      $_POST['final_matcher'] ?? null,
                      $_POST['create_resep'] ?? null,
                      $_POST['acc_ulang_ok'] ?? null,
                      $_POST['acc_resep1'] ?? null,
                      $_POST['acc_resep2'] ?? null,
                      $_POST['colorist1'] ?? null,
                      $_POST['colorist2'] ?? null,
                      $_POST['colorist3'] ?? null,
                      $_POST['colorist4'] ?? null,
                      $_POST['colorist5'] ?? null,
                      $_POST['colorist6'] ?? null,
                      $_POST['colorist7'] ?? null,
                      $_POST['colorist8'] ?? null,
                      $_POST['Matcher'] ?? null,
                      $_POST['Group'] ?? null,
                      $numericFields['bleaching_sh'],
                      $numericFields['bleaching_tm'],
                      $numericFields['second_lr'],
                      $_POST['id_status'] ?? null,
                      $_POST['idm'] ?? null
                    ])) $fail('update_status_matching');

// update matching
if(!sqlsrv_query($con, "UPDATE db_laborat.tbl_matching SET 
                        cocok_warna = ?,
                        proses=?,
                        no_item=?,
                        no_warna=?,
                        warna=?,
                        jenis_kain=?,
                        benang=?,
                        lebar=?,
                        gramasi=?,
                        tgl_delivery=?,
                        no_order=?,
                        qty_order=?,
                        buyer=?,
                        recipe_code = ?
                        where id = ?", [
                        $_POST['cocok_warna'], $_POST['proses'], $_POST['item'], $_POST['no_warna'], $_POST['warna'], $_POST['Kain'], $Benang,
                        $_POST['Lebar'], $_POST['Gramasi'], $_POST['Tgl_delivery'], $_POST['Order'], $_POST['QtyOrder'], $_POST['Buyer'], $_POST['recipe_code'], $_POST['id_matching']
                        ])) $fail('update_matching');

// catat log
if(!sqlsrv_query($con, "INSERT into db_laborat.log_status_matching (ids,status,info,do_by,do_at,ip_address) 
                VALUES (?,?,?,?,?,?)",
                [$_POST['idm'],'hold','Save & Pause',$_SESSION['userLAB'],$time,$ip_num])) $fail('insert_log');

$response = array(
  'session' => 'LIB_SUCCSS_HOLD',
  'exp' => 'updated'
);
echo json_encode($response);
?>
