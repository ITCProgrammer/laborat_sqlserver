<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";
session_start();
$time = date('Y-m-d H:i:s');

function get_client_ip()
{
    foreach (['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','HTTP_X_FORWARDED','HTTP_FORWARDED_FOR','HTTP_FORWARDED','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) return $_SERVER[$k];
    }
    return 'UNKNOWN';
}
$ip = get_client_ip();

$fail = function($ctx){
    echo json_encode(['session'=>'ERROR','ctx'=>$ctx,'sqlsrv'=>sqlsrv_errors()]);
    exit;
};

$stmt = sqlsrv_query($con,"SELECT TOP 1 id, no_order, jenis_matching from db_laborat.tbl_matching where no_resep = ?", [$_POST['idm']]);
if(!$stmt) $fail('get_matching');
$data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if(!sqlsrv_query($con,"INSERT INTO db_laborat.historical_delete_matching
    (no_matching,id_matching,id_status,jenis_matching,ip_adress,delete_at,delete_by,why_delete,no_order)
    VALUES (?,?,?,?,?,?,?,?,?)",
    [$_POST['idm'], $_POST['id_matching'], $_POST['id_status'], $data['jenis_matching'], $ip, $time, $_SESSION['userLAB'], $_POST['why_batal'], $_POST['no_order']])) $fail('insert_history');

if(!sqlsrv_query($con,"DELETE from db_laborat.tbl_matching_detail where id_matching=? and id_status=?", [$_POST['id_matching'], $_POST['id_status']])) $fail('del_detail');
if(!sqlsrv_query($con,"DELETE from db_laborat.tbl_status_matching where id=?", [$_POST['id_status']])) $fail('del_status');
if(!sqlsrv_query($con,"DELETE from db_laborat.tbl_matching where no_resep=?", [$_POST['idm']])) $fail('del_matching');

$ip_num = $_SERVER['REMOTE_ADDR'];
if(!sqlsrv_query($con,"INSERT INTO db_laborat.log_status_matching (ids,status,info,do_by,do_at,ip_address)
            VALUES (?,?,?,?,?,?)",
            [$_POST['idm'], 'deleted', $_POST['why_batal'], $_SESSION['userLAB'], $time, $ip_num])) $fail('insert_log');

echo json_encode([
    'session' => 'LIB_SUCCSS',
    'exp' => 'updated',
    'ip_address' => $ip
]);
?>
