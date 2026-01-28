<?php
ini_set("error_reporting", 1);
session_start();
include '../../koneksi.php';

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$data = null;

$sql = "SELECT id, ket, code, code_new, Product_Name, Product_Unit, is_active
        FROM db_laborat.tbl_dyestuff
        WHERE id = ?";
$stmt = sqlsrv_query($con, $sql, [$id]);
if ($stmt) {
    $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);
}

//----------------------------------------------------------------------------------
//----------------------------------------------------------------------------------
$json_data = array(
    "id" => $data ? $data['id'] : null,
    "ket" => $data ? $data['ket'] : null,
    "code" => $data ? $data['code'] : null,
	"code_new" => $data ? $data['code_new'] : null,
    "Product_Name" => $data ? $data['Product_Name'] : null,
    "Product_Unit" => $data ? $data['Product_Unit'] : null,
    "is_active" => $data ? $data['is_active'] : null
);
//----------------------------------------------------------------------------------
echo json_encode($json_data);
