<?php
ini_set("error_reporting", 1);
include '../../koneksi.php';
session_start();
$requestData = $_REQUEST;
$columns = array(
    0 => 'id_status',
    1 => 'grp',
    2 => 'matcher',
    3 => 'idm',
    4 => 'no_order',
    5 => 'langganan',
    6 => 'warna',
    7 => 'no_warna',
    8 => 'no_item',
    9 => 'no_po',
    10 => 'cocok_warna',
    11 => 'approve_at',
    12 => 'tgl_arsip'
);

$baseSql = "FROM db_laborat.tbl_status_matching a
            JOIN db_laborat.tbl_matching b ON a.idm = b.no_resep
            JOIN db_laborat.log_status_matching lsm ON a.idm = lsm.ids AND lsm.info = 'Resep di arsipkan'
            WHERE a.status = 'arsip'"; 

$countSql = "SELECT COUNT(*) AS total FROM (
                SELECT a.id
                $baseSql
            ) x";
$countStmt = sqlsrv_query($con, $countSql);
$countRow = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
$totalData = (int)($countRow['total'] ?? 0);
$totalFiltered = $totalData;

$params = [];
$whereSearch = '';
if (!empty($requestData['search']['value'])) {
    $term = '%' . $requestData['search']['value'] . '%';
    $whereSearch = " AND (a.idm LIKE ? OR b.warna LIKE ? OR b.no_warna LIKE ? OR b.no_order LIKE ? OR CONVERT(varchar(19), lsm.do_at, 120) LIKE ? OR b.no_item LIKE ?)";
    $params = [$term, $term, $term, $term, $term, $term];
}

$countFilteredSql = "SELECT COUNT(*) AS total FROM (
                        SELECT a.id
                        $baseSql $whereSearch
                    ) x";
$countFilteredStmt = sqlsrv_query($con, $countFilteredSql, $params);
$countFilteredRow = sqlsrv_fetch_array($countFilteredStmt, SQLSRV_FETCH_ASSOC);
$totalFiltered = (int)($countFilteredRow['total'] ?? 0);

$orderColIndex = (int)($requestData['order'][0]['column'] ?? 0);
$orderCol = $columns[$orderColIndex] ?? 'id_status';
$orderDir = strtoupper($requestData['order'][0]['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$start = (int)($requestData['start'] ?? 0);
$length = (int)($requestData['length'] ?? 10);

$sql = "SELECT a.id as id_status, a.created_at as tgl_buat_status, a.created_by as status_created_by, b.id as id_matching,
               a.grp, a.matcher, a.idm, b.no_order, b.langganan, b.no_warna, b.warna, b.no_item, b.no_po, b.cocok_warna, a.approve_at, a.status, lsm.do_at as tgl_arsip
        $baseSql $whereSearch
        ORDER BY $orderCol $orderDir
        OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
$paramsPaged = array_merge($params, [$start, $length]);
$query = sqlsrv_query($con, $sql, $paramsPaged);

$data = array();
$no = 1;
while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
    $nestedData = array();
    $nestedData[] = $no . '. <input type="checkbox" id="' . $no . '" id_status="' . ($row['id_status'] ?? '') . '" id_matching="' . ($row['id_matching'] ?? '') . '" idm="' . ($row['idm'] ?? '') . '" no_order="' . ($row['no_order'] ?? '') . '">';
    $nestedData[] = '<li class="btn-group" role="group"><a href="?p=Detail-status-wait-approve&idm=' . ($row['id_status'] ?? '') . '" class="btn btn-xs btn-info"><i class="fa fa-link"></i></a><button type="button" class="btn btn-xs btn-danger delete" id_status="' . ($row['id_status'] ?? '') . '" id_matching="' . ($row['id_matching'] ?? '') . '" idm="' . ($row['idm'] ?? '') . '" no_order="' . ($row['no_order'] ?? '') . '" why_batal="HAPUS_ARSIP"><i class="fa fa-trash"></i></button></li>';
    $nestedData[] = $row["grp"] ?? '';
    $nestedData[] = $row["matcher"] ?? '';
    $nestedData[] = $row["idm"] ?? '';
    $nestedData[] = $row["no_order"] ?? '';
    $nestedData[] = $row["langganan"] ?? '';
    $nestedData[] = $row["warna"] ?? '';
    $nestedData[] = $row["no_warna"] ?? '';
    $nestedData[] = $row["no_item"] ?? '';
    $nestedData[] = $row["no_po"] ?? '';
    $nestedData[] = $row["cocok_warna"] ?? '';
    $approveAt = $row["approve_at"] ?? null;
    $tglArsip = $row["tgl_arsip"] ?? null;
    $nestedData[] = ($approveAt instanceof DateTimeInterface) ? $approveAt->format('Y-m-d H:i:s') : ($approveAt ?? '');
    $nestedData[] = ($tglArsip instanceof DateTimeInterface) ? $tglArsip->format('Y-m-d H:i:s') : ($tglArsip ?? '');

    $data[] = $nestedData;
    $no++;
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
