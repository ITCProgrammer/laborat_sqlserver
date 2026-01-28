<?php
ini_set("error_reporting", 1);
session_start();
include("../koneksi.php");
$time = date('Y-m-d H:i:s');
if ($_POST) {
    $Buyer = strtoupper(trim($_POST['Buyer'] ?? ''));

    // cek buyer sudah ada
    $stmt = sqlsrv_query($con, "SELECT COUNT(*) AS cnt FROM db_laborat.vpot_lampbuy WHERE buyer = ?", [$Buyer]);
    $row = ($stmt && ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) ? (int)$r['cnt'] : 0;
    sqlsrv_free_stmt($stmt);

    if ($row > 0) {
        echo " <script>
                alert('Buyer telah memiliki list lampu, harap pergi menuju edit !');
                window.location='?p=Lampu-Buyer';</script>";
    } else {
        if (!empty($_POST['lampu1'])) {
            sqlsrv_query($con,"INSERT INTO db_laborat.vpot_lampbuy (flag,buyer,lampu,created_at,create_by) VALUES (1,?,?,?,?)",
                [$Buyer, $_POST['lampu1'], $time, $_SESSION['userLAB']]);
        }
        if (!empty($_POST['lampu2'])) {
            sqlsrv_query($con,"INSERT INTO db_laborat.vpot_lampbuy (flag,buyer,lampu,created_at,create_by) VALUES (2,?,?,?,?)",
                [$Buyer, $_POST['lampu2'], $time, $_SESSION['userLAB']]);
        }
        if (!empty($_POST['lampu3'])) {
            sqlsrv_query($con,"INSERT INTO db_laborat.vpot_lampbuy (flag,buyer,lampu,created_at,create_by) VALUES (3,?,?,?,?)",
                [$Buyer, $_POST['lampu3'], $time, $_SESSION['userLAB']]);
        }

        sqlsrv_query($con,"INSERT into db_laborat.tbl_log (what, what_do, do_by, do_at, ip, os, remark) VALUES (?,?,?,?,?,?,?)",
                        [$Buyer,
                        'INSERT INTO vpot_lampbuy',
                        $_SESSION['userLAB'],
                        $time,
                        $_SESSION['ip'] ?? '',
                        $_SESSION['os'] ?? '',
                        'Renew data lampu']);

        echo " <script>window.location='?p=Lampu-Buyer';</script>";
    }
}
