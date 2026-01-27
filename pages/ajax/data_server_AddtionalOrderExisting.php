<?php
ini_set("error_reporting", 1);
session_start();
include '../../koneksi.php';
if (! $con) {
    http_response_code(500);
    echo json_encode(['error' => 'Koneksi SQL Server gagal']);
    exit;
}

$requestData = $_REQUEST;
$columns = array(
    0 => 'id',
    1 => 'flag',
    2 => '[order]',
    3 => 'lot',
    4 => 'jenis_benang',
    5 => 'created_at',
);

$idMatching = $_POST['id_matching'] ?? '';
$search = $requestData['search']['value'] ?? '';

$where = "id_matching = ?";
$params = [$idMatching];
if (!empty($search)) {
    $where .= " AND [order] LIKE ?";
    $params[] = $search . '%';
}

// hitung total/filtered
$countSql = "SELECT COUNT(*) AS cnt FROM db_laborat.tbl_orderchild WHERE $where";
$stmtCount = sqlsrv_query($con, $countSql, $params);
$rowCount = sqlsrv_fetch_array($stmtCount, SQLSRV_FETCH_ASSOC);
$totalFiltered = $rowCount ? (int)$rowCount['cnt'] : 0;
$totalData = $totalFiltered;
sqlsrv_free_stmt($stmtCount);

// order dan paging
$orderColIdx = (int)($requestData['order'][0]['column'] ?? 0);
$orderCol = $columns[$orderColIdx] ?? 'id';
$orderDir = strtolower($requestData['order'][0]['dir'] ?? 'asc');
$orderDir = ($orderDir === 'desc') ? 'DESC' : 'ASC';
$start  = (int)($requestData['start'] ?? 0);
$length = (int)($requestData['length'] ?? 10);

$sql = "SELECT id, flag, [order], lot, jenis_benang, created_at 
        FROM db_laborat.tbl_orderchild
        WHERE $where
        ORDER BY $orderCol $orderDir
        OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

$paramsData = array_merge($params, [$start, $length]);
$query = sqlsrv_query($con, $sql, $paramsData);

$data = array();
while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
    $nestedData = array();
    $nestedData[] = $row["id"];
    $nestedData[] = $row["flag"] . '. <a href="javascript:void(0)" class="btn btn-danger btn-xs _hapusOrder" data-pk="' . $row["id"] . '"><i class="fa fa-trash"></i></a>';
    $nestedData[] = $row["order"];
    $nestedData[] = $row["lot"];
    $nestedData[] = $row["jenis_benang"];
    $created = $row["created_at"];
    if ($created instanceof DateTimeInterface) {
        $created = $created->format('Y-m-d');
    } else {
        $created = substr((string)$created, 0, 10);
    }
    $nestedData[] = $created;
    $data[] = $nestedData;
}
sqlsrv_free_stmt($query);

$json_data = array(
    "draw"            => intval($requestData['draw']),
    "recordsTotal"    => intval($totalData),
    "recordsFiltered" => intval($totalFiltered),
    "data"            => $data
);
echo json_encode($json_data);
