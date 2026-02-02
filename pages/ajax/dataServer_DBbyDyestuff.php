<?php
ini_set("error_reporting", 1);
include '../../koneksi.php';
$requestData = $_REQUEST;
$columns = array(
    0 => 'id',
    1 => 'id_status',
    2 => 'kode',
    3 => 'nama',
    4 => 'no_resep'
);

$baseSql = "FROM db_laborat.tbl_matching_detail a
            JOIN db_laborat.tbl_matching b on a.id_matching = b.id
            JOIN db_laborat.tbl_status_matching c on a.id_status = c.id
            WHERE c.status = 'selesai' and c.approve = 'TRUE' and a.kode <> '---'";

$countSql = "SELECT COUNT(*) AS total FROM (
                SELECT DISTINCT a.id, a.id_status, a.kode, a.nama, b.no_resep
                $baseSql
            ) x";
$countStmt = sqlsrv_query($con, $countSql);
$countRow = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
$totalData = (int)($countRow['total'] ?? 0);
$totalFiltered = $totalData;

//----------------------------------------------------------------------------------
$params = [];
$whereSearch = '';

if (!empty($requestData['search']['value'])) {
    //----------------------------------------------------------------------------------
    $term = $requestData['search']['value'] . '%';
    $whereSearch = " AND (a.kode LIKE ? OR a.nama LIKE ? OR b.no_resep LIKE ?)";
    $params = [$term, $term, $term];
}
//----------------------------------------------------------------------------------
$countFilteredSql = "SELECT COUNT(*) AS total FROM (
                        SELECT DISTINCT a.id, a.id_status, a.kode, a.nama, b.no_resep
                        $baseSql $whereSearch
                    ) x";
$countFilteredStmt = sqlsrv_query($con, $countFilteredSql, $params);
$countFilteredRow = sqlsrv_fetch_array($countFilteredStmt, SQLSRV_FETCH_ASSOC);
$totalFiltered = (int)($countFilteredRow['total'] ?? 0);

$orderColIndex = (int)($requestData['order'][0]['column'] ?? 0);
$orderCol = $columns[$orderColIndex] ?? 'id';
$orderDir = strtoupper($requestData['order'][0]['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$start = (int)($requestData['start'] ?? 0);
$length = (int)($requestData['length'] ?? 10);

$sql = "SELECT DISTINCT a.id, a.id_status, a.kode, a.nama, b.no_resep
        $baseSql $whereSearch
        ORDER BY a.$orderCol $orderDir, a.id $orderDir
        OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
$paramsPaged = array_merge($params, [$start, $length]);
$query = sqlsrv_query($con, $sql, $paramsPaged);

$data = array();
$no = 1;
while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
    $nestedData = array();
    $nestedData[] = $no++;
    $nestedData[] = $row["id"] ?? '';
    $nestedData[] = $row["id_status"] ?? '';
    $nestedData[] = $row["kode"] ?? '';
    $nestedData[] = $row["nama"] ?? '';
    $nestedData[] = $row["no_resep"] ?? '';
    $nestedData[] = '<a class="btn btn-xs btn-info" href="?p=Detail-status-approved&idm=' . $row['id_status'] . '"><i class="fa fa-link"></i></a>';
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
