<?php
ini_set("error_reporting", 1);
session_start();
include '../../koneksi.php';

// Fetch data from master_suhu table
$query = "SELECT * FROM db_laborat.master_mesin";
$result = sqlsrv_query($con, $query);

$data = [];
if ($result !== false) {
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $data[] = $row;
    }
}

echo json_encode(['data' => $data]);
?>
