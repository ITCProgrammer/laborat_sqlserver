<?php
ini_set("error_reporting", 1);
include '../../koneksi.php';
$requestData = $_REQUEST;
$columns = array(
    0 => 'id',
    1 => 'nama_proses',
    2 => 'is_active',
    3 => 'created_at',
    4 => 'created_by'
);
// set_order_type("desc");
$search = isset($requestData['search']['value']) ? trim($requestData['search']['value']) : '';
$whereSql = '';
$params = [];

if ($search !== '') {
    $whereSql = " WHERE nama_proses LIKE ? OR created_at LIKE ? OR created_by LIKE ? ";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$totalData = 0;
$countAll = sqlsrv_query($con, "SELECT COUNT(*) AS cnt FROM db_laborat.master_proses");
if ($countAll && ($row = sqlsrv_fetch_array($countAll, SQLSRV_FETCH_ASSOC))) {
    $totalData = (int) $row['cnt'];
}
if ($countAll) {
    sqlsrv_free_stmt($countAll);
}

$totalFiltered = $totalData;
if ($whereSql !== '') {
    $countFiltered = sqlsrv_query($con, "SELECT COUNT(*) AS cnt FROM db_laborat.master_proses $whereSql", $params);
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

$sql = "SELECT id, nama_proses, is_active, created_at, created_by
        FROM db_laborat.master_proses $whereSql
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
    if ($row['is_active'] == "TRUE") {
        $btn = '<button class="btn btn-success btn-xs btn-rounded _action" attr-data=' . $row["id"] . '>' . $row["is_active"] . '</button>';
    } else {
        $btn = '<button class="btn btn-danger btn-xs btn-rounded _action" attr-data=' . $row["id"] . '>' . $row["is_active"] . '</button>';
    }

    $createdAt = $row["created_at"];
    if ($createdAt instanceof DateTimeInterface) {
        $createdAt = $createdAt->format('Y-m-d H:i:s');
    }

    $nestedData = array();
    $nestedData[] = $no++;
    $nestedData[] = $row["nama_proses"];
    $nestedData[] = $btn;
    $nestedData[] = $createdAt;
    $nestedData[] = $row["created_by"];

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
