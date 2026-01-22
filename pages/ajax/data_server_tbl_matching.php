<?php
ini_set("error_reporting", 1);
include '../../koneksi.php';
if (! $con) {
    http_response_code(500);
    echo json_encode(['error' => 'Koneksi SQL Server gagal']);
    exit;
}
$requestData = $_REQUEST;
$columns = array(
    0 => 'id',
    1 => 'no_resep',
    2 => 'no_order',
    3 => 'warna',
    4 => 'no_warna',
    5 => 'no_item',
    6 => 'langganan',
    7 => 'no_po',
    8 => 'status',
);
// set_order_type("desc");
$searchVal = $requestData['search']['value'] ?? '';

$baseSql = "FROM db_laborat.tbl_matching a
            LEFT JOIN db_laborat.tbl_status_matching b ON a.no_resep = b.idm
            WHERE a.status_bagi = 'siap bagi' AND ISNULL(b.status, 'siap bagi') = 'siap bagi'";
$params = [];
if (!empty($searchVal)) {
    $baseSql .= " AND (a.no_resep LIKE ? OR a.no_order LIKE ? OR a.warna LIKE ? OR a.no_warna LIKE ? OR a.no_item LIKE ? OR a.no_po LIKE ? OR ISNULL(b.status, 'belum bagi') LIKE ?)";
    $like = '%' . $searchVal . '%';
    $params = [$like, $like, $like, $like, $like, $like, $like];
}

$countSql = "SELECT COUNT(*) AS cnt " . $baseSql;
$stmtCount = sqlsrv_query($con, $countSql, $params);
$rowCount = sqlsrv_fetch_array($stmtCount, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmtCount);
$totalData = $totalFiltered = $rowCount ? (int)$rowCount['cnt'] : 0;

$orderColIdx = (int)($requestData['order'][0]['column'] ?? 0);
$orderDir = strtolower($requestData['order'][0]['dir'] ?? 'desc');
$orderDir = $orderDir === 'asc' ? 'ASC' : 'DESC';
$start  = (int)($requestData['start'] ?? 0);
$length = (int)($requestData['length'] ?? 10);

$orderCols = [
    0 => 'a.id',
    1 => 'a.no_resep',
    2 => 'a.no_order',
    3 => 'a.warna',
    4 => 'a.no_warna',
    5 => 'a.no_item',
    6 => 'a.langganan',
    7 => 'a.no_po',
    8 => 'b.status'
];
$orderCol = $orderCols[$orderColIdx] ?? 'a.id';

$sql = "SELECT a.id, a.no_resep, a.no_order, a.warna, a.no_warna, a.no_item, a.langganan, a.no_po, ISNULL(b.status, 'siap bagi') AS status "
      . $baseSql .
      " GROUP BY a.id, a.no_resep, a.no_order, a.warna, a.no_warna, a.no_item, a.langganan, a.no_po, b.status
        ORDER BY $orderCol $orderDir
        OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
$paramsData = array_merge($params, [$start, $length]);
$query = sqlsrv_query($con, $sql, $paramsData);
//----------------------------------------------------------------------------------
$data = array();
$no = 1;
while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
    $status =  '<a href="#" class="btn btn-xs btn-success"><strong>' . $row['status'] . '</strong></a href="#">';
    $nestedData = array();

    $nestedData[] = '<div class="btn-group-vertical" role="group" aria-label="..."><a href="javascript:void(0)" class="_hapus btn btn-xs btn-danger"><i class="fa fa-trash"></i></a><a href="index1.php?p=edit_matching&rcode=' . $row['no_resep'] . '" class="_edit btn btn-xs btn-primary"><i class="fa fa-pencil"></i></a><a style="color: black;" target="_blank" href="pages/cetak/matching.php?idkk=' . $row['no_resep'] . '" class="btn btn-xs btn-warning"><i class="fa fa-print"></i></a></div>';
    $nestedData[] = '<a href="javascript:void(0)" class="pilih">' . $row["no_resep"] . '</a>';
    $nestedData[] = $row["no_order"];
    $nestedData[] = $row["no_po"];
    $nestedData[] = $row["warna"];
    $nestedData[] = $row["no_warna"];
    $nestedData[] = $row["langganan"];
    $nestedData[] = $row["no_item"];
    $nestedData[] = $status;

    $data[] = $nestedData;
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
