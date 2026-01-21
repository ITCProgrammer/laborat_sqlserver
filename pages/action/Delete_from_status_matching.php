<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";
if (! $con) {
    die('Koneksi SQL Server gagal.');
}
sqlsrv_query($con,"DELETE FROM db_laborat.tbl_status_matching WHERE id = ?", [$_POST['id']]);
echo "<script>location.href='../../index1.php?p=Schedule-Matching'</script>";
