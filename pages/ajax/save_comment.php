<?php
    ini_set("error_reporting", 1);
    include "../../koneksi.php";
    session_start();

    $ids      = $_POST['ids'] ?? '';
    $idm      = $_POST['idm'] ?? '';
    $adj_no   = $_POST['adj_no'] ?? '';
    $comment  = $_POST['comment'] ?? '';
    $idUser   = $_POST['idUser'] ?? '';

    $sql = "INSERT INTO db_laborat.tbl_comment (ids, idm, adj, comment, created_by)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = sqlsrv_query($con, $sql, [$ids, $idm, $adj_no, $comment, $idUser]);

    if ($stmt) {
        echo 'SAVED';
    } else {
        $err = sqlsrv_errors();
        echo 'ERROR: ' . ($err[0]['message'] ?? 'unknown');
    }
?>
