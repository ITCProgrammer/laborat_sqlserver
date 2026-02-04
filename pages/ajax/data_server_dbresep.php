<?php
ini_set("error_reporting", 1);
include '../../koneksi.php';
$requestData = $_REQUEST;
$columns = array(
    0 => 'idm',
    1 => 'flag',
    2 => 'no_order',
    3 => 'grp',
    4 => 'jenis_kain',
    5 => 'order',
    6 => 'lot',
    7 => 'no_item',
    8 => 'no_po',
    9 => 'no_warna',
    10 => 'warna',
    11 => 'langganan',
    12 => 'created_at',
    13 => 'created_by',
    14 => 'action'
);

//----------------------------------------------------------------------------------
// SELECT a.id, a.idm,b.no_order, c.flag, a.grp, a.jenis_kain, c.`order`, c.lot,
// b.no_item, b.no_po, b.no_warna, b.warna, a.approve_at
// FROM tbl_status_matching a
// join tbl_matching b on b.no_resep = a.idm
// join tbl_orderchild c on c.id_status = a.id and c.id_matching = b.id
// group by c.id, c.`order`
// order by a.idm, c.flag;

function fetch_count($sql, array $params = [])
{
    global $con;
    $stmt = sqlsrv_query($con, $sql, $params);
    if (! $stmt) {
        return 0;
    }
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);
    return isset($row['cnt']) ? (int)$row['cnt'] : 0;
}

$baseFrom = " FROM db_laborat.tbl_status_matching a
              JOIN db_laborat.tbl_matching b ON b.no_resep = a.idm
              JOIN db_laborat.tbl_orderchild c ON c.id_status = a.id AND c.id_matching = b.id";

$totalData = fetch_count(
    "SELECT COUNT(DISTINCT c.id) AS cnt" . $baseFrom
);
$totalFiltered = $totalData;

//----------------------------------------------------------------------------------
$sql = "SELECT DISTINCT
            a.id, a.idm, b.no_order, c.flag, a.grp, b.jenis_kain,
            c.[order] AS [order], c.lot, b.no_item, b.no_po, b.no_warna,
            b.warna, c.created_at, c.created_by, b.langganan";
$sql .= $baseFrom;
$sql .= " WHERE a.approve = 'TRUE' AND a.status = 'selesai' ";

$params = [];
if (!empty($requestData['search']['value'])) {
    //----------------------------------------------------------------------------------
    $search = '%' . $requestData['search']['value'] . '%';
    $sql .= " AND (b.no_warna LIKE ? OR b.no_order LIKE ? OR b.no_item LIKE ?
                   OR b.warna LIKE ? OR a.idm LIKE ? OR c.[order] LIKE ?)";
    $params = [$search, $search, $search, $search, $search, $search];
}
//----------------------------------------------------------------------------------
$countFilteredSql = "SELECT COUNT(DISTINCT c.id) AS cnt" . $baseFrom .
    " WHERE a.approve = 'TRUE' AND a.status = 'selesai' ";
if (!empty($requestData['search']['value'])) {
    $countFilteredSql .= " AND (b.no_warna LIKE ? OR b.no_order LIKE ? OR b.no_item LIKE ?
                                 OR b.warna LIKE ? OR a.idm LIKE ? OR c.[order] LIKE ?)";
}
$totalFiltered = fetch_count($countFilteredSql, $params);

$orderColumns = [
    0 => 'a.idm',
    1 => 'c.flag',
    2 => 'b.no_order',
    3 => 'a.grp',
    4 => 'b.jenis_kain',
    5 => 'c.[order]',
    6 => 'c.lot',
    7 => 'b.no_item',
    8 => 'b.no_po',
    9 => 'b.no_warna',
    10 => 'b.warna',
    11 => 'b.langganan',
    12 => 'c.created_at',
    13 => 'c.created_by',
    14 => 'a.id'
];

$orderIndex = (int)($requestData['order'][0]['column'] ?? 0);
$orderDir = strtolower($requestData['order'][0]['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
$orderBy = $orderColumns[$orderIndex] ?? 'a.idm';

$start = (int)($requestData['start'] ?? 0);
$length = (int)($requestData['length'] ?? 10);
$sql .= " ORDER BY $orderBy $orderDir, c.flag ASC
          OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
$params[] = $start;
$params[] = $length;

$query = sqlsrv_query($con, $sql, $params, ['Scrollable' => SQLSRV_CURSOR_KEYSET]);

$data = array();
$no = 1;
while ($row = $query ? sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC) : null) {
    $nestedData = array();
    $nestedData[] =
        '<b>▕ Rcode > ' . $row["idm"] . ' &nbsp;&nbsp;▕&nbsp;&nbsp;J.kain > ' . $row['jenis_kain'] . '
        <br />
        ▕  No.Warna > ' . $row["no_warna"] . ' &nbsp;&nbsp;▕&nbsp;&nbsp; Warna > ' . $row['warna'] . '&nbsp;&nbsp;▕&nbsp;&nbsp;P.Order >' . $row['no_order'] . '&nbsp;&nbsp;▕&nbsp;&nbsp;  No.item > ' . $row['no_item'] . '</b>▕ 
        <li class="btn-group" role="group" aria-label="...">
        <a href="index1.php?p=Detail-status-approved&idm=' . $row['id'] . '" class="btn btn-primary btn-xs"><i class="fa fa-fw fa-search"></i></a>
        <a href="pages/cetak/cetak_resep.php?ids=' . $row['id'] . '&idm=' . $row['idm'] . '" class="btn btn-danger btn-xs" target="_blank"><i class="fa fa-fw fa-print"></i></a>
      </li>';
    $nestedData[] = $row["no_order"];
    $nestedData[] = $row["flag"];
    $nestedData[] = $row["grp"];
    $nestedData[] = $row["jenis_kain"];
    $nestedData[] = $row["order"];
    $nestedData[] = $row["lot"];
    $nestedData[] = $row["no_item"];
    $nestedData[] = $row["no_po"];
    $nestedData[] = $row["no_warna"];
    $nestedData[] = $row["warna"];
    $nestedData[] = $row["langganan"];
    if ($row["created_at"] instanceof DateTime) {
        $nestedData[] = $row["created_at"]->format('Y-m-d');
    } else {
        $nestedData[] = $row["created_at"] ? substr((string)$row["created_at"], 0, 10) : '';
    }
    $nestedData[] = $row["created_by"];
    //     $nestedData[] = '<li class="btn-group" role="group" aria-label="...">
    //     <a href="index1.php?p=Detail-status-approved&idm=' . $row['id'] . '" class="btn btn-primary btn-xs"><i class="fa fa-fw fa-search"></i></a>
    //     <a href="pages/cetak/cetak_resep.php?ids=' . $row['id'] . '&idm=' . $row['idm'] . '" class="btn btn-danger btn-xs" target="_blank"><i class="fa fa-fw fa-print"></i></a>
    //   </li>';
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
