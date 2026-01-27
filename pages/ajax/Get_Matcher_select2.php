<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";

$search = isset($_GET['search']) ? $_GET['search'] : '';
if ($search == "") {
    $sql = sqlsrv_query($con,"SELECT id, nama, [status] FROM db_laborat.tbl_matcher where [status] = 'Aktif' order by id desc");
} else {
    $like = '%'.$search.'%';
    $sql = sqlsrv_query($con,"SELECT id, nama, [status] FROM db_laborat.tbl_matcher where [status] = 'Aktif' and nama like ?", [$like]);
}
$list = array();
if ($sql) {
    while ($row = sqlsrv_fetch_array($sql, SQLSRV_FETCH_ASSOC)) {
        $list[] = ["id" => $row['nama'], "text" => $row['nama']];
    }
}
echo json_encode($list);
