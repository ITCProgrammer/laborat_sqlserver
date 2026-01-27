<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";
session_start();
$time = date('Y-m-d H:i:s');
$ip_num = $_SERVER['REMOTE_ADDR'];

$fail = function($ctx){
    echo json_encode(['session'=>'ERROR','ctx'=>$ctx,'sqlsrv'=>sqlsrv_errors()]);
    exit;
};

if(!sqlsrv_query($con,"UPDATE db_laborat.tbl_status_matching SET
            status='revisi',
            revisi_at=?,
            revisi_by=?
            where id = ?", [$time, $_SESSION['userLAB'], $_POST['id_status']])) $fail('update_status');

$sqlNoResep = sqlsrv_query($con,"SELECT idm from db_laborat.tbl_status_matching where id = ?", [$_POST['id_status']]);
if(!$sqlNoResep) $fail('get_idm');
$NoResep = sqlsrv_fetch_array($sqlNoResep, SQLSRV_FETCH_ASSOC);

if(!sqlsrv_query($con,"INSERT INTO db_laborat.log_status_matching (ids,status,info,do_by,do_at,ip_address)
            VALUES (?,?,?,?,?,?)",
            [$NoResep['idm'], 'revisi', 'Back to matcher', $_SESSION['userLAB'], $time, $ip_num])) $fail('insert_log');

echo json_encode([
    'session' => 'LIB_SUCCSS_HOLD',
    'exp' => 'updated'
]);
