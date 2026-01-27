<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";
session_start();

$ip_num = $_SERVER['REMOTE_ADDR'];
$time = date('Y-m-d H:i:s');

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

$benang_a = $_POST['benang_a'];
$Benang   = $_POST['Benang'];
$keterangan = $_POST['keterangan'] ?? '';

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
                      $_POST['matching_ke'], $_POST['howmany_Matching_ke'], $benang_a, $_POST['lebar_a'], $_POST['gramasi_a'],
                      $lr_format, $_POST['kadar_air'], $_POST['RC_Suhu'], $_POST['RCWaktu'], $_POST['soapingSuhu'], $_POST['soapingWaktu'],
                      $_POST['cie_wi'], $_POST['cie_tint'], $_POST['yellowness'], $_POST['Spektro_R'], $_POST['Done_Matching'], $keterangan,
                      $_SESSION['userLAB'], $time, $_POST['tside_c'], $_POST['tside_min'], $_POST['cside_c'], $_POST['cside_min'], $timer,
                      $_POST['kadar_air_true'], $_POST['koreksi_resep'], $_POST['koreksi_resep2'], $_POST['koreksi_resep3'], $_POST['koreksi_resep4'],
                      $_POST['koreksi_resep5'], $_POST['koreksi_resep6'], $_POST['koreksi_resep7'], $_POST['koreksi_resep8'], $_POST['final_matcher'],
                      $_POST['create_resep'], $_POST['acc_ulang_ok'], $_POST['acc_resep1'], $_POST['acc_resep2'], $_POST['colorist1'], $_POST['colorist2'],
                      $_POST['colorist3'], $_POST['colorist4'], $_POST['colorist5'], $_POST['colorist6'], $_POST['colorist7'], $_POST['colorist8'],
                      $_POST['Matcher'], $_POST['Group'], $_POST['bleaching_sh'], $_POST['bleaching_tm'], $second_lr_format, $_POST['id_status'], $_POST['idm']
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
                $_POST['cocok_warna'], $_POST['proses'], $_POST['item'], $_POST['no_warna'], $_POST['warna'],
                $_POST['Kain'], $Benang, $_POST['Lebar'], $_POST['Gramasi'], $_POST['Tgl_delivery'], $_POST['Order'],
                $_POST['QtyOrder'], $_POST['Buyer'], $_POST['recipe_code'], $_POST['id_matching']
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
