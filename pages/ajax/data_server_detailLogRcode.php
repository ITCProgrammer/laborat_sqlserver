<?php
ini_set("error_reporting", 1);
include '../../koneksi.php';
$requestData = $_REQUEST;
$columns = array(
    0 => 'ids',
    1 => 'status',
    2 => 'info',
    3 => 'do_by',
    4 => 'do_at',
    5 => 'ip_address'
);
$rcode = isset($requestData['Rcode']) ? $requestData['Rcode'] : '';
$search = isset($requestData['search']['value']) ? trim($requestData['search']['value']) : '';
$whereSql = " WHERE ids = ? ";
$params = [$rcode];

if ($search !== '') {
    $whereSql .= " AND ids LIKE ? ";
    $params[] = '%' . $search . '%';
}

$totalData = 0;
$countAll = sqlsrv_query(
    $con,
    "SELECT COUNT(*) AS cnt FROM db_laborat.log_status_matching WHERE ids = ?",
    [$rcode]
);
if ($countAll && ($row = sqlsrv_fetch_array($countAll, SQLSRV_FETCH_ASSOC))) {
    $totalData = (int) $row['cnt'];
}
if ($countAll) {
    sqlsrv_free_stmt($countAll);
}

$totalFiltered = $totalData;
if ($search !== '') {
    $countFiltered = sqlsrv_query(
        $con,
        "SELECT COUNT(*) AS cnt FROM db_laborat.log_status_matching $whereSql",
        $params
    );
    if ($countFiltered && ($row = sqlsrv_fetch_array($countFiltered, SQLSRV_FETCH_ASSOC))) {
        $totalFiltered = (int) $row['cnt'];
    }
    if ($countFiltered) {
        sqlsrv_free_stmt($countFiltered);
    }
}

$orderColIndex = isset($requestData['order'][0]['column']) ? (int) $requestData['order'][0]['column'] : 0;
$orderCol = isset($columns[$orderColIndex]) ? $columns[$orderColIndex] : 'do_at';
$orderDir = (isset($requestData['order'][0]['dir']) && strtolower($requestData['order'][0]['dir']) === 'desc') ? 'DESC' : 'ASC';
$start = isset($requestData['start']) ? (int) $requestData['start'] : 0;
$length = isset($requestData['length']) ? (int) $requestData['length'] : 10;
if ($length < 0) {
    $length = 10;
}

$sql = "SELECT ids, status, info, do_by, do_at, ip_address
        FROM db_laborat.log_status_matching
        $whereSql
        ORDER BY $orderCol $orderDir
        OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
$dataParams = $params;
$dataParams[] = $start;
$dataParams[] = $length;
// var_dump(print_r($sql));
// die;
$query = sqlsrv_query($con, $sql, $dataParams);
//----------------------------------------------------------------------------------
$data = array();
$no = 1;
while ($query && ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC))) {
    $nestedData = array();
    $nestedData[] = $no++;
    $nestedData[] = $row["status"];
    $nestedData[] = $row["info"];
    $nestedData[] = $row["do_by"];
    $doAt = $row["do_at"];
    if ($doAt instanceof DateTimeInterface) {
        $doAt = $doAt->format('Y-m-d H:i:s');
    } elseif ($doAt === null) {
        $doAt = '0000-00-00 00:00';
    }
    $nestedData[] = substr($doAt, 0, 16);
    $nestedData[] = $row["ip_address"];

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
