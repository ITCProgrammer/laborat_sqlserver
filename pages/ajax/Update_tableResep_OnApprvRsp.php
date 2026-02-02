<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";
session_start();
$time = date('Y-m-d H:i:s');
if (!empty($_POST['conc'])) {
    $conc = $_POST['conc'];
    $dt = $time;
    $doby = $_SESSION['userLAB'];
} else {
    $conc = 0;
    $dt = "";
    $doby = "";
}
if (!empty($_POST['conc1'])) {
    $conc1 = $_POST['conc1'];
    $dt1 = $time;
    $doby1 = $_SESSION['userLAB'];
} else {
    $conc1 = 0;
    $dt1 = "";
    $doby1 = "";
}
if (!empty($_POST['conc2'])) {
    $conc2 = $_POST['conc2'];
    $dt2 = $time;
    $doby2 = $_SESSION['userLAB'];
} else {
    $conc2 = 0;
    $dt2 = "";
    $doby2 = "";
}
if (!empty($_POST['conc3'])) {
    $conc3 = $_POST['conc3'];
    $dt3 = $time;
    $doby3 = $_SESSION['userLAB'];
} else {
    $conc3 = 0;
    $dt3 = "";
    $doby3 = "";
}
if (!empty($_POST['conc4'])) {
    $conc4 = $_POST['conc4'];
    $dt4 = $time;
    $doby4 = $_SESSION['userLAB'];
} else {
    $conc4 = 0;
    $dt = "";
    $doby = "";
}
if (!empty($_POST['conc5'])) {
    $conc5 = $_POST['conc5'];
    $dt5 = $time;
    $doby5 = $_SESSION['userLAB'];
} else {
    $conc5 = 0;
    $dt5 = "";
    $doby5 = "";
}
if (!empty($_POST['conc6'])) {
    $conc6 = $_POST['conc6'];
    $dt6 = $time;
    $doby6 = $_SESSION['userLAB'];
} else {
    $conc6 = 0;
    $dt6 = "";
    $doby6 = "";
}
if (!empty($_POST['conc7'])) {
    $conc7 = $_POST['conc7'];
    $dt7 = $time;
    $doby7 = $_SESSION['userLAB'];
} else {
    $conc7 = 0;
    $dt7 = "";
    $doby7 = "";
}
if (!empty($_POST['conc8'])) {
    $conc8 = $_POST['conc8'];
    $dt8 = $time;
    $doby8 = $_SESSION['userLAB'];
} else {
    $conc8 = 0;
    $dt8 = "";
    $doby8 = "";
}
if (!empty($_POST['conc9'])) {
    $conc9 = $_POST['conc9'];
    $dt9 = $time;
    $doby9 = $_SESSION['userLAB'];
} else {
    $conc9 = 0;
    $dt9 = "";
    $doby9 = "";
}

$sql = sqlsrv_query(
    $con,
    "SELECT TOP (1) * FROM db_laborat.tbl_matching_detail WHERE id_matching = ? AND id_status = ? AND flag = ?",
    [$_POST['id_matching'], $_POST['id_status'], $_POST['flag']]
);
$data = sqlsrv_fetch_array($sql, SQLSRV_FETCH_ASSOC);
if ($data) {
    if ($data['kode'] != $_POST['code']) {
        sqlsrv_query($con, "UPDATE db_laborat.tbl_matching_detail SET kode = ?, last_edit_at = ?, last_edit_by = ? WHERE id_matching = ? AND id_status = ? AND flag = ?", [$_POST['code'], $time, $_SESSION['userLAB'], $_POST['id_matching'], $_POST['id_status'], $_POST['flag']]);
    }
    if ($data['nama'] != $_POST['desc_code']) {
        sqlsrv_query($con, "UPDATE db_laborat.tbl_matching_detail SET nama = ?, last_edit_at = ?, last_edit_by = ? WHERE id_matching = ? AND id_status = ? AND flag = ?", [$_POST['desc_code'], $time, $_SESSION['userLAB'], $_POST['id_matching'], $_POST['id_status'], $_POST['flag']]);
    }
    if ($data['conc1'] != $conc) {
        sqlsrv_query($con, "UPDATE db_laborat.tbl_matching_detail SET conc1 = ?, time_1 = GETDATE(), doby1 = ?, last_edit_at = ?, last_edit_by = ? WHERE id_matching = ? AND id_status = ? AND flag = ?", [$conc, $_SESSION['userLAB'], $time, $_SESSION['userLAB'], $_POST['id_matching'], $_POST['id_status'], $_POST['flag']]);
    }
    if ($data['conc2'] != $conc1) {
        sqlsrv_query($con, "UPDATE db_laborat.tbl_matching_detail SET conc2 = ?, time_2 = ?, doby2 = ?, last_edit_at = ?, last_edit_by = ? WHERE id_matching = ? AND id_status = ? AND flag = ?", [$conc1, $dt1, $doby1, $time, $_SESSION['userLAB'], $_POST['id_matching'], $_POST['id_status'], $_POST['flag']]);
    }
    if ($data['conc3'] != $conc2) {
        sqlsrv_query($con, "UPDATE db_laborat.tbl_matching_detail SET conc3 = ?, time_3 = ?, doby3 = ?, last_edit_at = ?, last_edit_by = ? WHERE id_matching = ? AND id_status = ? AND flag = ?", [$conc2, $dt2, $doby2, $time, $_SESSION['userLAB'], $_POST['id_matching'], $_POST['id_status'], $_POST['flag']]);
    }
    if ($data['conc4'] != $conc3) {
        sqlsrv_query($con, "UPDATE db_laborat.tbl_matching_detail SET conc4 = ?, time_4 = ?, doby4 = ?, last_edit_at = ?, last_edit_by = ? WHERE id_matching = ? AND id_status = ? AND flag = ?", [$conc3, $dt3, $doby3, $time, $_SESSION['userLAB'], $_POST['id_matching'], $_POST['id_status'], $_POST['flag']]);
    }
    if ($data['conc5'] != $conc4) {
        sqlsrv_query($con, "UPDATE db_laborat.tbl_matching_detail SET conc5 = ?, time_5 = ?, doby5 = ?, last_edit_at = ?, last_edit_by = ? WHERE id_matching = ? AND id_status = ? AND flag = ?", [$conc4, $dt4, $doby4, $time, $_SESSION['userLAB'], $_POST['id_matching'], $_POST['id_status'], $_POST['flag']]);
    }
    if ($data['conc6'] != $conc5) {
        sqlsrv_query($con, "UPDATE db_laborat.tbl_matching_detail SET conc6 = ?, time_6 = ?, doby6 = ?, last_edit_at = ?, last_edit_by = ? WHERE id_matching = ? AND id_status = ? AND flag = ?", [$conc5, $dt5, $doby5, $time, $_SESSION['userLAB'], $_POST['id_matching'], $_POST['id_status'], $_POST['flag']]);
    }
    if ($data['conc7'] != $conc6) {
        sqlsrv_query($con, "UPDATE db_laborat.tbl_matching_detail SET conc7 = ?, time_7 = ?, doby7 = ?, last_edit_at = ?, last_edit_by = ? WHERE id_matching = ? AND id_status = ? AND flag = ?", [$conc6, $dt6, $doby6, $time, $_SESSION['userLAB'], $_POST['id_matching'], $_POST['id_status'], $_POST['flag']]);
    }
    if ($data['conc8'] != $conc7) {
        sqlsrv_query($con, "UPDATE db_laborat.tbl_matching_detail SET conc8 = ?, time_8 = ?, doby8 = ?, last_edit_at = ?, last_edit_by = ? WHERE id_matching = ? AND id_status = ? AND flag = ?", [$conc7, $dt7, $doby7, $time, $_SESSION['userLAB'], $_POST['id_matching'], $_POST['id_status'], $_POST['flag']]);
    }
    if ($data['conc9'] != $conc8) {
        sqlsrv_query($con, "UPDATE db_laborat.tbl_matching_detail SET conc9 = ?, time_9 = ?, doby9 = ?, last_edit_at = ?, last_edit_by = ? WHERE id_matching = ? AND id_status = ? AND flag = ?", [$conc8, $dt8, $doby8, $time, $_SESSION['userLAB'], $_POST['id_matching'], $_POST['id_status'], $_POST['flag']]);
    }
    if ($data['conc10'] != $conc9) {
        sqlsrv_query($con, "UPDATE db_laborat.tbl_matching_detail SET conc10 = ?, time_10 = ?, doby10 = ?, last_edit_at = ?, last_edit_by = ? WHERE id_matching = ? AND id_status = ? AND flag = ?", [$conc9, $dt9, $doby9, $time, $_SESSION['userLAB'], $_POST['id_matching'], $_POST['id_status'], $_POST['flag']]);
    }
    if ($data['remark'] != $_POST['keterangan']) {
        sqlsrv_query($con, "UPDATE db_laborat.tbl_matching_detail SET remark = ? WHERE id_matching = ? AND id_status = ? AND flag = ?", [$_POST['keterangan'], $_POST['id_matching'], $_POST['id_status'], $_POST['flag']]);
    }
    $LIB_SUCCSS = "LIB_SUCCSS";
} else {
    sqlsrv_query(
        $con,
        "INSERT INTO db_laborat.tbl_matching_detail
            (flag, id_matching, id_status, kode, nama, conc1, conc2, conc3, conc4, conc5, conc6, conc7, conc8, conc9, conc10,
             time_1, time_2, time_3, time_4, time_5, time_6, time_7, time_8, time_9, time_10,
             doby1, doby2, doby3, doby4, doby5, doby6, doby7, doby8, doby9, doby10,
             remark, inserted_at, inserted_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [$_POST['flag'], $_POST['id_matching'], $_POST['id_status'], $_POST['code'], $_POST['desc_code'],
            $conc, $conc1, $conc2, $conc3, $conc4, $conc5, $conc6, $conc7, $conc8, $conc9,
            $dt, $dt1, $dt2, $dt3, $dt4, $dt5, $dt6, $dt7, $dt8, $dt9,
            $doby, $doby1, $doby2, $doby3, $doby4, $doby5, $doby6, $doby7, $doby8, $doby9,
            $_POST['keterangan'], $time, $_SESSION['userLAB']
        ]
    );
    $LIB_SUCCSS = "LIB_SUCCSS";
}

$response = array(
    'session' => $LIB_SUCCSS,
    'exp' => 'inserted',
);
echo json_encode($response);
