<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";
session_start();

$ip_num = $_SERVER['REMOTE_ADDR'];
$time = date('Y-m-d H:i:s');

$num = function($key){
    $v = $_POST[$key] ?? null;
    if ($v === '' || $v === null) return null;
    return (float)$v;
};
$str = function($key){
    return $_POST[$key] ?? '';
};
$dateVal = function($key){
    $v = $_POST[$key] ?? null;
    if ($v === '' || $v === null) return null;
    // Pastikan format yyyy-mm-dd
    $ts = strtotime($v);
    if ($ts === false) return null;
    return date('Y-m-d', $ts);
};

$fail = function($ctx){
    $err = print_r(sqlsrv_errors(), true);
    header('Content-Type: application/json');
    echo json_encode(['status'=>'error','ctx'=>$ctx,'sqlsrv'=>$err]);
    exit;
};

$awal  = strtotime($_POST['tgl_buat_status']);
$akhir = strtotime($time);
$diff  = $akhir - $awal;
$hari  = floor($diff / (60 * 60 * 24));
$jam   = floor(($diff - ($hari * (60 * 60 * 24))) / (60 * 60));
$menit = ($diff - ($hari * (60 * 60 * 24))) - (($jam) * (60 * 60));
$timer =  $hari . ' Hari, ' . $jam .  ' Jam, ' . floor($menit / 60) . ' Menit';

$benang_a = $str('benang_a');
$Benang   = $str('Benang');
$keterangan = $str('keterangan');

$second_lr_format = !empty($_POST['second_lr']) ? preg_replace('/\s*:\s*/', ':', $_POST['second_lr']) : "0:0";
$lr_format        = !empty($_POST['l_R']) ? preg_replace('/\s*:\s*/', ':', $_POST['l_R']) : null;

// hapus detail lama
if(!sqlsrv_query($con, "DELETE from db_laborat.tbl_matching_detail where id_matching = ? and id_status = ?", [$_POST['id_matching'], $_POST['id_status']])) $fail('hapus_detail');

// update status matching selesai
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
                    status = 'selesai',
                    cie_wi = ?,
                    cie_tint = ?,
                    yellowness = ?,
                    spektro_r = ?,
                    done_matching = ?,
                    ket = ?,
                    selesai_by = ?,
                    selesai_at = ?,
                    tside_c = ?,
                    tside_min = ?,
                    cside_c = ?,
                    cside_min= ?,
                    timer = ?,
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
                      $str('matching_ke'), $str('howmany_Matching_ke'), $benang_a, $num('lebar_a'), $num('gramasi_a'),
                      $lr_format, $str('kadar_air'), $str('RC_Suhu'), $str('RCWaktu'), $str('soapingSuhu'), $str('soapingWaktu'),
                      $str('cie_wi'), $str('cie_tint'), $str('yellowness'), $str('Spektro_R'), $dateVal('Done_Matching'), $keterangan,
                      $_SESSION['userLAB'], $time, $str('tside_c'), $str('tside_min'), $str('cside_c'), $str('cside_min'), $timer,
                      $str('kadar_air_true'), $str('koreksi_resep'), $str('koreksi_resep2'), $str('koreksi_resep3'), $str('koreksi_resep4'),
                      $str('koreksi_resep5'), $str('koreksi_resep6'), $str('koreksi_resep7'), $str('koreksi_resep8'), $str('final_matcher'),
                      $str('create_resep'), $str('acc_ulang_ok'), $str('acc_resep1'), $str('acc_resep2'), $str('colorist1'), $str('colorist2'),
                      $str('colorist3'), $str('colorist4'), $str('colorist5'), $str('colorist6'), $str('colorist7'), $str('colorist8'),
                      $str('Matcher'), $str('Group'), $str('bleaching_sh'), $str('bleaching_tm'), $second_lr_format, $_POST['id_status'], $_POST['idm']
                    ])) $fail('update_status_matching');

// update master matching
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
                $str('cocok_warna'), $str('proses'), $str('item'), $str('no_warna'), $str('warna'),
                $str('Kain'), $Benang, $num('Lebar'), $num('Gramasi'), $dateVal('Tgl_delivery'), $str('Order'),
                $str('QtyOrder'), $str('Buyer'), $str('recipe_code'), $_POST['id_matching']
                ])) $fail('update_matching');

// log
if(!sqlsrv_query($con, "INSERT into db_laborat.log_status_matching (ids,status,info,do_by,do_at,ip_address) 
                VALUES (?,?,?,?,?,?)",
                [$_POST['idm'], 'selesai', 'not yet approved', $_SESSION['userLAB'], $time, $ip_num])) $fail('insert_log');

$response = array(
    'session' => 'LIB_SUCCSS',
    'exp' => 'updated'
);
echo json_encode($response);
?>
