<?php
    ini_set("error_reporting", 1);
    include "../../koneksi.php";
    session_start();
    if (!$con_nowprd) {
        die(print_r(sqlsrv_errors(), true));
    }

    $ids = $_GET['ids'] ?? '';
    $idm = $_GET['idm'] ?? '';
    $adj = $_GET['adj'] ?? '';

    $sql = "SELECT comment, created_at, created_by FROM db_laborat.tbl_comment 
            WHERE ids = ? AND idm = ? AND adj = ?
            ORDER BY id DESC";
    $res = sqlsrv_query($con, $sql, [$ids, $idm, $adj]);

    $data = [];

    while ($res && ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC))) {
        $created_by = (int)($row['created_by'] ?? 0); // cast to int for safety

        $getUserName = "SELECT username FROM [nowprd].[users]
                        WHERE id = $created_by AND menu = 'prd_bukuresep.php'";
        $res_user = sqlsrv_query($con_nowprd, $getUserName);
        $userName = sqlsrv_fetch_array($res_user, SQLSRV_FETCH_ASSOC);

        $data[] = [
            'comment'       => $row['comment'],
            'created_at'    => ($row['created_at'] instanceof DateTime) ? $row['created_at']->format('Y-m-d H:i:s') : $row['created_at'],
            'username'      => $userName['username'] ?? 'tidak diketahui'
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($data);
?>
