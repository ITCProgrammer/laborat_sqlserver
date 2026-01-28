<?php
ini_set("error_reporting", 1);
session_start();
include("../koneksi.php");
$time = date('Y-m-d H:i:s');
if ($_POST) {
    $Buyer = strtoupper(trim($_POST['Buyer'] ?? ''));
    $sql = sqlsrv_query($con,"SELECT COUNT(*) AS cnt FROM db_laborat.vpot_lampbuy where buyer = ?", [$Buyer]);
    $row = ($sql && ($r = sqlsrv_fetch_array($sql, SQLSRV_FETCH_ASSOC))) ? (int)$r['cnt'] : 0;
    // if ($row > 0) {
    //     echo " <script>
    //             alert('Buyer telah memiliki list lampu, harap pergi menuju edit !');
    //             window.location='?p=Lampu-Buyer';</script>";
    // } else {
    sqlsrv_query($con,"DELETE from db_laborat.vpot_lampbuy where buyer = ?", [$Buyer]);
    // var_dump(print_r($delete));
    // die;
    if (!empty($_POST['lampu1'])) {
        sqlsrv_query($con,"INSERT INTO db_laborat.vpot_lampbuy(flag,buyer,lampu,created_at,create_by) VALUES (1, ?, ?, ?, ?)",
            [$Buyer, $_POST['lampu1'], $time, $_SESSION['userLAB']]);
    }
    if (!empty($_POST['lampu2'])) {
        sqlsrv_query($con,"INSERT INTO db_laborat.vpot_lampbuy(flag,buyer,lampu,created_at,create_by) VALUES (2, ?, ?, ?, ?)",
            [$Buyer, $_POST['lampu2'], $time, $_SESSION['userLAB']]);
    }
    if (!empty($_POST['lampu3'])) {
        sqlsrv_query($con,"INSERT INTO db_laborat.vpot_lampbuy(flag,buyer,lampu,created_at,create_by) VALUES (3, ?, ?, ?, ?)",
            [$Buyer, $_POST['lampu3'], $time, $_SESSION['userLAB']]);
    }

    echo " <script>window.location='?p=Lampu-Buyer';</script>";
    // }
}
