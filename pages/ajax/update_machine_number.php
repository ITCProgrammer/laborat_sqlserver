<?php
header('Content-Type: application/json');
include "../../koneksi.php";

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['id'] ?? 0);
$no_machine = trim($data['no_machine'] ?? '');

if ($id && $no_machine !== '') {
    $res = sqlsrv_query($con, "SELECT TOP 1 is_old_data, id_group FROM db_laborat.tbl_preliminary_schedule WHERE id = ?", [$id]);
    $row = $res ? sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC) : null;
    $isOldData = intval($row['is_old_data'] ?? 0);
    $existingGroupId = $row['id_group'] ?? '';

    if ($isOldData === 1) {
        // Cek apakah di mesin target ada data old_data
        $checkOld = sqlsrv_query($con, "
            SELECT COUNT(*) AS total 
            FROM db_laborat.tbl_preliminary_schedule 
            WHERE no_machine = ? AND is_old_data = 1 AND is_old_cycle = 0
        ", [$no_machine]);
        $rowOld = $checkOld ? sqlsrv_fetch_array($checkOld, SQLSRV_FETCH_ASSOC) : null;
        $countOld = intval($rowOld['total'] ?? 0);

        $checkGroup = sqlsrv_query($con, "
            SELECT COUNT(*) AS total 
            FROM db_laborat.tbl_preliminary_schedule 
            WHERE no_machine = ? AND id_group = ? AND is_old_cycle = 0
        ", [$no_machine, $existingGroupId]);
        $rowGroup = $checkGroup ? sqlsrv_fetch_array($checkGroup, SQLSRV_FETCH_ASSOC) : null;
        $countGroup = intval($rowGroup['total'] ?? 0);

        if ($countOld === 0 && $countGroup > 0) {
            sqlsrv_query($con, "UPDATE db_laborat.tbl_preliminary_schedule SET is_old_data = 0 WHERE id = ?", [$id]);
        }

        if ($countOld > 0 && $countGroup > 0) {
            sqlsrv_query($con, "UPDATE db_laborat.tbl_preliminary_schedule SET is_old_data = 0 WHERE id = ?", [$id]);
        }
    }

    $update = sqlsrv_query($con, "UPDATE db_laborat.tbl_preliminary_schedule SET no_machine = ? WHERE id = ?", [$no_machine, $id]);

    if ($update) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => print_r(sqlsrv_errors(), true)]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
}
