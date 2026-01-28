<?php
ini_set("error_reporting", 1);
include '../../koneksi.php';
$requestData = $_REQUEST;
$columns = array(
    0 => 'id',
    1 => 'code',
	2 => 'code_new',
    3 => 'Product_Name',
    4 => 'liquid_powder',
    5 => 'Product_Unit',
    6 => 'is_active'
);
// set_order_type("desc");
$search = isset($requestData['search']['value']) ? trim($requestData['search']['value']) : '';
$whereSql = '';
$params = [];

if ($search !== '') {
    $whereSql = " WHERE code LIKE ? OR Product_Name LIKE ? ";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}

$totalData = 0;
$countAll = sqlsrv_query($con, "SELECT COUNT(*) AS cnt FROM db_laborat.tbl_dyestuff");
if ($countAll && ($row = sqlsrv_fetch_array($countAll, SQLSRV_FETCH_ASSOC))) {
    $totalData = (int) $row['cnt'];
}
if ($countAll) {
    sqlsrv_free_stmt($countAll);
}

$totalFiltered = $totalData;
if ($whereSql !== '') {
    $countFiltered = sqlsrv_query($con, "SELECT COUNT(*) AS cnt FROM db_laborat.tbl_dyestuff $whereSql", $params);
    if ($countFiltered && ($row = sqlsrv_fetch_array($countFiltered, SQLSRV_FETCH_ASSOC))) {
        $totalFiltered = (int) $row['cnt'];
    }
    if ($countFiltered) {
        sqlsrv_free_stmt($countFiltered);
    }
}

$orderColIndex = isset($requestData['order'][0]['column']) ? (int) $requestData['order'][0]['column'] : 0;
$orderCol = isset($columns[$orderColIndex]) ? $columns[$orderColIndex] : 'id';
$orderDir = (isset($requestData['order'][0]['dir']) && strtolower($requestData['order'][0]['dir']) === 'desc') ? 'DESC' : 'ASC';
$start = isset($requestData['start']) ? (int) $requestData['start'] : 0;
$length = isset($requestData['length']) ? (int) $requestData['length'] : 10;
if ($length < 0) {
    $length = 10;
}

$sql = "SELECT id, ket, code, code_new, Product_Name, liquid_powder, Product_Unit, is_active
        FROM db_laborat.tbl_dyestuff $whereSql
        ORDER BY $orderCol $orderDir
        OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
$dataParams = $params;
$dataParams[] = $start;
$dataParams[] = $length;

$query = sqlsrv_query($con, $sql, $dataParams);
//----------------------------------------------------------------------------------
$data = array();
$no = 1;
while ($query && ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC))) {
    if ($row["Product_Unit"] == 1) {
        $uom = "%";
    } elseif ($row["Product_Unit"] == 0) {
        $uom = "Gr/L";
    }else{
        $uom = "-";
    }
    if ($row["is_active"] == 'FALSE') {
        $status = '<button type="button" class="btn btn-xs btn-danger">' . $row['is_active'] . '</button>';
    } else {
        $status = '<button type="button" class="btn btn-xs btn-success">' . $row['is_active'] . '</button>';
    }
    $nestedData = array();
    $nestedData[] = $no++;
    $nestedData[] = $row["code"];
	$nestedData[] = $row["code_new"];
    $nestedData[] = $row["Product_Name"];
    $nestedData[] = $row["liquid_powder"];
    $nestedData[] = $uom;
    $nestedData[] = $row["ket"];
    $nestedData[] = $status;
    $nestedData[] = '<button class="btn btn-sm btn-warning dyess_edit" id="' . $row["id"] . '"><i class="fa fa-edit"></i></button>';

    $data[] = $nestedData;
}
if ($query) {
    sqlsrv_free_stmt($query);
}
//----------------------------------------------------------------------------------
$json_data = array(
    "draw"            => intval($requestData['draw']),
    "recordsTotal"    => intval($totalData),
    "recordsFiltered" => intval($totalFiltered),
    "data"            => $data
);
//----------------------------------------------------------------------------------
echo json_encode($json_data);
