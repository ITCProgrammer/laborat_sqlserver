<?php
include "../../koneksi.php";
header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $query = "SELECT * FROM db_laborat.master_mesin WHERE id = ?";
    $stmt = sqlsrv_prepare($con, $query, [$id]);

    if (!$stmt) {
        $errors = sqlsrv_errors();
        echo json_encode([
            "error" => $errors ? $errors[0]['message'] : "Query gagal diprepare"
        ]);
        exit;
    }

    if (sqlsrv_execute($stmt)) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        echo json_encode($row ? $row : new stdClass());
    } else {
        $errors = sqlsrv_errors();
        echo json_encode([
            "error" => $errors ? $errors[0]['message'] : "Query gagal dieksekusi"
        ]);
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($con);
} else {
    echo json_encode(["error" => "ID tidak ditemukan"]);
}
