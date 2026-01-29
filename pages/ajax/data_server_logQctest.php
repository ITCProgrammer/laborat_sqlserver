<?php
ini_set("error_reporting", 1);
include '../../koneksi.php';

$requestData = $_REQUEST;

$columns = array(
    0 => 'maxid',
    1 => 'no_counter'
);

$search = isset($requestData['search']['value']) ? trim($requestData['search']['value']) : '';
$whereSql = '';
$params = [];

if ($search !== '') {
    $whereSql = " WHERE no_counter LIKE ? ";
    $params[] = '%' . $search . '%';
}

$totalData = 0;
$countAll = sqlsrv_query($con, "SELECT COUNT(DISTINCT no_counter) AS cnt FROM db_laborat.log_qc_test");
if ($countAll && ($row = sqlsrv_fetch_array($countAll, SQLSRV_FETCH_ASSOC))) {
    $totalData = (int) $row['cnt'];
}
if ($countAll) {
    sqlsrv_free_stmt($countAll);
}

$totalFiltered = $totalData;
if ($whereSql !== '') {
    $countFiltered = sqlsrv_query($con, "SELECT COUNT(DISTINCT no_counter) AS cnt FROM db_laborat.log_qc_test $whereSql", $params);
    if ($countFiltered && ($row = sqlsrv_fetch_array($countFiltered, SQLSRV_FETCH_ASSOC))) {
        $totalFiltered = (int) $row['cnt'];
    }
    if ($countFiltered) {
        sqlsrv_free_stmt($countFiltered);
    }
}

$orderColIndex = isset($requestData['order'][0]['column']) ? (int) $requestData['order'][0]['column'] : 0;
$orderCol = isset($columns[$orderColIndex]) ? $columns[$orderColIndex] : 'maxid';
$orderDir = (isset($requestData['order'][0]['dir']) && strtolower($requestData['order'][0]['dir']) === 'desc') ? 'DESC' : 'ASC';
$start = isset($requestData['start']) ? (int) $requestData['start'] : 0;
$length = isset($requestData['length']) ? (int) $requestData['length'] : 10;
if ($length < 0) {
    $length = 10;
}

$sql = "SELECT MAX(id) as maxid, no_counter
        FROM db_laborat.log_qc_test
        $whereSql
        GROUP BY no_counter
        ORDER BY $orderCol $orderDir
        OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
$dataParams = $params;
$dataParams[] = $start;
$dataParams[] = $length;

$query = sqlsrv_query($con, $sql, $dataParams);

$data = array();
$no = 1;

while ($query && ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC))) {
    $nestedData = array();
    $nestedData[] = $no++;
    $nestedData[] = $row["no_counter"];

    $data[] = $nestedData;
}
if ($query) {
    sqlsrv_free_stmt($query);
}

$json_data = array(
    "draw"            => intval($requestData['draw']),
    "recordsTotal"    => intval($totalData),
    "recordsFiltered" => intval($totalFiltered),
    "data"            => $data
);

echo json_encode($json_data);
