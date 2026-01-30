<?php
include "../../koneksi.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $today = date('Y-m-d H:i:s');
    $salesorder = trim($_POST['salesorder'] ?? '');
    $orderline  = trim($_POST['orderline'] ?? '');
    $warna      = trim($_POST['warna'] ?? '');
    $benang     = trim($_POST['benang'] ?? '');
    $po         = trim($_POST['po_greige'] ?? '');
    $pic        = trim($_POST['pic_check'] ?? '');
    $status     = trim($_POST['status_bonorder'] ?? '');
    $user       = trim($_POST['user'] ?? '');
    $ip         = trim($_POST['ip'] ?? '');

    if (empty($pic) || empty($status)) {
        echo "PIC dan Status harus dipilih!";
        exit;
    }

    // Cek apakah data sudah ada berdasarkan unique key
    $checkSql = "SELECT TOP 1 1 FROM db_laborat.status_matching_bon_order
                 WHERE salesorder = ?
                   AND orderline = ?
                   AND po_greige = ?";
    $checkResult = sqlsrv_query($con, $checkSql, [$salesorder, $orderline, $po]);

    if ($checkResult && sqlsrv_fetch_array($checkResult, SQLSRV_FETCH_ASSOC)) {
        // Data sudah ada -> lakukan update
        $updateSql = "UPDATE db_laborat.status_matching_bon_order SET 
                        pic_check = ?,
                        status_bonorder = ?
                      WHERE salesorder = ?
                        AND orderline = ?
                        AND warna = ?
                        AND po_greige = ?";

        $insertLog = "INSERT INTO db_laborat.tbl_log_history_matching 
                      (salesorder, orderline, warna, po_greige, benang, values_pic, values_status, ip_update, user_update, date_update, process)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'update')";                
        if (sqlsrv_query($con, $updateSql, [$pic, $status, $salesorder, $orderline, $warna, $po])
            && sqlsrv_query($con, $insertLog, [$salesorder, $orderline, $warna, $po, $benang, $pic, $status, $ip, $user, $today])) {
            echo "Data berhasil diupdate!";
        } else {
            $errors = sqlsrv_errors();
            echo "Gagal update: " . ($errors ? $errors[0]['message'] : 'unknown error');
        }
    } else {
        // Data belum ada -> lakukan insert
        $insertSql = "INSERT INTO db_laborat.status_matching_bon_order 
                      (salesorder, orderline, warna, benang, po_greige, pic_check, status_bonorder)
                      VALUES (?, ?, ?, ?, ?, ?, ?)";

        $insertLog = "INSERT INTO db_laborat.tbl_log_history_matching 
                      (salesorder, orderline, warna, po_greige, benang, values_pic, values_status, ip_update, user_update, date_update, process)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'insert')";
        if (sqlsrv_query($con, $insertSql, [$salesorder, $orderline, $warna, $benang, $po, $pic, $status])
            && sqlsrv_query($con, $insertLog, [$salesorder, $orderline, $warna, $po, $benang, $pic, $status, $ip, $user, $today])) {
            echo "Data berhasil disimpan!";
        } else {
            $errors = sqlsrv_errors();
            echo "Gagal simpan: " . ($errors ? $errors[0]['message'] : 'unknown error');
        }
    }
}
?>
