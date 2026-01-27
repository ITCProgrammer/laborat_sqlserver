<?php
    ini_set("error_reporting", 1);
    include "../../koneksi.php";
    session_start();
    $time = date('Y-m-d H:i:s');

    $idm        = $_GET['idm'] ?? '';
    $setting    = $_POST['setting'] ?? '';
    $value      = $_POST['value'] ?? null;

    // Validasi kolom yang boleh di-update
    $allowed = ['suhu_chamber', 'warna_flourescent'];
    if (!$idm || !in_array($setting, $allowed)) {
        exit('Invalid request');
    }

    // 1. Ambil no_resep dari tbl_status_matching
    $stmt = sqlsrv_query($con, "SELECT idm AS no_resep FROM db_laborat.tbl_status_matching WHERE id = ?", [$idm]);
    if(!$stmt){ echo "ERR-GET-RESEP:".print_r(sqlsrv_errors(),true); exit; }
    $row  = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $no_resep = $row['no_resep'] ?? '';

    if ($no_resep) {
        // 2. Update tbl_matching pakai no_resep
        $update = sqlsrv_query($con, "UPDATE db_laborat.tbl_matching SET $setting = ? WHERE no_resep = ?", [$value, $no_resep]);
        if (!$update) { echo "ERR-UPDATE:".print_r(sqlsrv_errors(),true); exit; }
        // 3. Log perubahan
        $ip_num = $_SERVER['REMOTE_ADDR'];
        if(!sqlsrv_query($con, "INSERT INTO db_laborat.log_status_matching (ids,status,info,do_by,do_at,ip_address)
                                VALUES (?,?,?,?,?,?)",
                                [$no_resep, 'selesai', "Perubahan $setting menjadi $value", $_SESSION['userLAB'], $time, $ip_num])){
            echo "ERR-LOG:".print_r(sqlsrv_errors(),true); exit;
        }
        echo 'OK';
    } else {
        echo 'ERROR';
    }

    sqlsrv_close($con);
?>
