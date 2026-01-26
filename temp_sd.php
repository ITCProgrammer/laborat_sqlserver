<?php
include 'koneksi.php';
$sql = "SELECT COUNT(*) AS c FROM db_laborat.summary_dispensing";
$stmt = sqlsrv_query($con, $sql);
if(!$stmt){ var_dump(sqlsrv_errors()); exit; }
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
var_dump($row);
?>
