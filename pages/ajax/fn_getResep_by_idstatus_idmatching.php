<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";

$id_status = $_POST['id_status'];
$id_matching = $_POST['id_matching'];

$stmt = sqlsrv_query($con,"SELECT flag,kode,nama,conc1,conc2,conc3,conc4,conc5,conc6,conc7,conc8,conc9,conc10
                           FROM db_laborat.tbl_matching_detail
                           WHERE id_matching = ? AND id_status = ?
                           ORDER BY flag ASC", [$id_matching, $id_status]);

$responce = [];
$w = 1;
while ($li = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $norm = function($v){
        return (floatval($v) == 0) ? 'bg-black text-black' : floatval($v);
    };
    $responce[$w] = array(
        $li['flag'],
        $li['kode'],
        $norm($li['conc1']),
        $norm($li['conc2']),
        $norm($li['conc3']),
        $norm($li['conc4']),
        $norm($li['conc5']),
        $norm($li['conc6']),
        $norm($li['conc7']),
        $norm($li['conc8']),
        $norm($li['conc9']),
        $norm($li['conc10']),
        $li['nama']
    );
    $w++;
}

echo json_encode($responce);
?>
