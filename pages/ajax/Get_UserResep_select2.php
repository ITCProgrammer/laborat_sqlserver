<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";

$search = isset($_GET['search']) ? $_GET['search'] : '';
if ($search == "") {
    $sql = sqlsrv_query($con,"SELECT id, nama, is_active FROM db_laborat.tbl_user_resep where is_active = 'TRUE'");
} else {
    $like = '%'.$search.'%';
    $sql = sqlsrv_query($con,"SELECT id, nama, is_active FROM db_laborat.tbl_user_resep where is_active = 'TRUE' and nama like ?", [$like]);
}
$list = array();
if ($sql) {
    while ($row = sqlsrv_fetch_array($sql, SQLSRV_FETCH_ASSOC)) {
        $list[] = ['id' => $row['nama'], 'text' => $row['nama']];
    }
}
echo json_encode($list);
