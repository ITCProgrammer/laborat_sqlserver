<?php
ini_set("error_reporting", 1);
include "../../../koneksi.php";

$search = $_GET['search'] ?? '';
$sql = sqlsrv_query(
    $con,
    "SELECT code FROM db_laborat.tbl_dyestuff WHERE code LIKE ? AND is_active = 'TRUE' ORDER BY id ASC",
    ['%' . $search . '%']
);
$list = array();
if ($sql) {
    $key = 0;
    while ($row = sqlsrv_fetch_array($sql, SQLSRV_FETCH_ASSOC)) {
        $list[$key]['id'] = $row['code'];
        $list[$key]['text'] = $row['code'];
        $key++;
    }
}

if (!empty($list)) {
    echo json_encode($list);
} else {
    echo "Keyword tidak cocok!";
}
