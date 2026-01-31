<?php
include "../../koneksi.php";

if (!$con) {
    http_response_code(500);
    echo "Gagal koneksi DB";
    exit;
}

$id_gantikain = $_POST['id_gantikain'];
$pic_lab = $_POST['pic_lab'];
$status_lab = $_POST['status_lab'];

// Cek apakah sudah ada
$q = sqlsrv_query($con, "SELECT TOP 1 id FROM db_laborat.status_matching_ganti_kain WHERE id_gantikain = '$id_gantikain'");
$exists = $q ? sqlsrv_fetch_array($q, SQLSRV_FETCH_ASSOC) : null;
if ($exists) {
    $result = sqlsrv_query($con, "UPDATE db_laborat.status_matching_ganti_kain SET pic_lab='$pic_lab', status_lab='$status_lab', updated_at=GETDATE() WHERE id_gantikain='$id_gantikain'");
} else {
    $result = sqlsrv_query($con, "INSERT INTO db_laborat.status_matching_ganti_kain (id_gantikain, pic_lab, status_lab) VALUES ('$id_gantikain', '$pic_lab', '$status_lab')");
}

if ($result) {
    echo "Sukses";
} else {
    http_response_code(500);
    echo "Gagal simpan";
}
