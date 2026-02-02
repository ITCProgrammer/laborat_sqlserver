<?php
ini_set("error_reporting", 1);
include "../../../koneksi.php";

$code = $_POST['code'] ?? '';
$sql = sqlsrv_query(
    $con,
    "SELECT Product_Name, Product_Unit, ket FROM db_laborat.tbl_dyestuff WHERE code = ? AND is_active = 'TRUE'",
    [$code]
);
$result = sqlsrv_fetch_array($sql, SQLSRV_FETCH_ASSOC);
if (!is_array($result)) {
    echo json_encode(['Product_Name' => '']);
    exit;
}
if ($result["Product_Unit"] == 0) {
    $uom = "Gr/L";
} else {
    $uom = "%";
}

if ($result["Product_Name"] == "-----------------------") {
    $resultn = array(
        'Product_Name'  => $result["Product_Name"]
    );
} else {
    if($result["ket"] == "Suhu"){
        $resultn = array(
            'ket'            => $result['ket'],
            'Product_Name'   => $result["Product_Name"]
        );
    }else{
        $resultn = array(
            'Product_Name'  => $result["Product_Name"] . ' (' . $uom . ')'
        );
    }
}

$response = json_encode($resultn);
echo $response;
