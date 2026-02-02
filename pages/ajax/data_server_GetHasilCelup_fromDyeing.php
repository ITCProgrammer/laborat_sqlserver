<?php
ini_set("error_reporting", 1);
include __DIR__ . '/../../koneksi.php';
$time = date('Y-m-d H:i:s');
function cekDesimal($angka)
								{
									$bulat = round($angka);
									if ($bulat > $angka) {
										$jam = $bulat - 1;
										$waktu = $jam . ":30";
									} else {
										$jam = $bulat;
										$waktu = $jam . ":00";
									}
									return $waktu;
								}

$requestData = $_REQUEST;
$columns = array(
    0 => 'id',
    1 => 'no_order',
    2 => 'nokk',
    3 => 'lot',
    4 => 'bruto',
    5 => 'loading',
    6 => 'k_resep',
    7 => 'proses',
    8 => 'lama_proses',
    9 => 'status',
    10 => 'benang',
    11 => 'ket',
    12 => 'no_resep',
    13 => 'l_r',
    14 => 'no_mesin',
    15 => 'tgl_update',
    16 => 'analisa',
);

$rcode = $_POST['r_code'] ?? '';

$sql = "SELECT 
            b.id, 
            c.no_order, 
            d.tgl_update, 
            b.nokk, 
            c.lot, 
            b.k_resep, 
            b.proses, 
            b.lama_proses, 
            b.status, 
            b.analisa,  
            c.no_resep, 
            d.l_r, 
            c.no_mesin, 
            d.bruto, 
            (CAST(d.bruto AS DECIMAL(18,4)) / NULLIF(c.kapasitas, 0) * 100) as loading_fix, 
            z.jenis_note, 
            b.analisa_resep,
            z.note,
            b.ket, 
            d.benang,
            d.nodemand,
            c.target,
            b.no_resep as resep1_dye,
            b.no_resep2 as resep2_dye
        FROM db_laborat.db_laborat.tbl_status_matching a
            JOIN db_laborat.db_laborat.tbl_matching x ON a.idm = x.no_resep
            JOIN db_dying.db_dying.tbl_hasilcelup b ON a.idm = b.rcode
            JOIN db_dying.db_dying.tbl_montemp d ON b.id_montemp = d.id
            JOIN db_dying.db_dying.tbl_schedule c ON d.id_schedule = c.id
            LEFT JOIN db_laborat.db_laborat.tbl_note_celup z ON b.nokk = z.kk
        WHERE a.idm = ? AND b.rcode = ?
        ORDER BY b.id DESC";

// db_dye di SQL Server, pakai sqlsrv
$stmt = sqlsrv_query($con_db_dyeing, $sql, [$rcode, $rcode]);
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'error' => sqlsrv_errors(),
        'sql'   => $sql,
        'params'=> [$rcode, $rcode]
    ]);
    exit;
}
$rows = [];
while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $rows[] = $r;
}

$totalData = count($rows);
$totalFiltered = $totalData;

// Pagination ala DataTables
$start  = isset($requestData['start']) ? (int)$requestData['start'] : 0;
$length = isset($requestData['length']) ? (int)$requestData['length'] : -1;
if ($length === -1) {
    $pageRows = $rows;
} else {
    $pageRows = array_slice($rows, $start, $length);
}

$data = array();
$no = $start + 1;
foreach ($pageRows as $row) {
    $resep1 = $row["resep1_dye"];
    $resep2 = $row["resep2_dye"];

    [$prod1, $line1] = array_pad(explode('-', $resep1, 2), 2, '');
    [$prod2, $line2] = array_pad(explode('-', $resep2, 2), 2, '');

    $tglUpdate = $row["tgl_update"];
    if ($tglUpdate instanceof DateTimeInterface) {
        $tglUpdate = $tglUpdate->format('Y-m-d H:i:s');
    }

    if ($_POST['p'] == 'Detail-status-approved') {
        $index = $no++;
        $data_action = '<strong style="border-bottom: solid #808080 1px;">LAB : ' . $row['note'] . ' <br> <br> DYE : ' . $row['analisa_resep'] . '</strong>';
    } else {
        $index = $no++ .  '.&nbsp;&nbsp; <a hreff="javascript:void(0)" data-pk="' . $row["id"] . '" class="btn btn-xs btn-danger delete_celup"><i class="fa fa-trash" aria-hidden="true"></i>
        </a>';
        $data_action = '<strong style="border-bottom: solid #808080 1px;">LAB : ' . $row['note'] . ' <br> <br> DYE : ' . $row['analisa_resep'] . ' </strong> <br /><a href="javascript:void(0)" class="btn btn-xs btn-warning _addnoteclp" data-kk="' . $row["nokk"] . '"><i class="fa fa-edit"></i></a>';
    }

    $linkresep = '<a href="https://online.indotaichen.com/laporan/dye_search_detail_recipe.php?prod_order=' . $prod1 . '&line=' . $line1 . '" class="btn btn-xs btn-info bon_resep" target="_blank" style="display: block; margin-bottom: 5px;">Resep 1 : ' . $resep1 . '</a>';

    if (!empty($prod2) && !empty($line2)) {
        $linkresep .= '<a href="https://online.indotaichen.com/laporan/dye_search_detail_recipe.php?prod_order=' . $prod2 . '&line=' . $line2 . '" class="btn btn-xs btn-info bon_resep" target="_blank" style="display: block; margin-bottom: 5px;">Resep 2 : ' . $resep2 . '</a>';
    }

    $nestedData = array();
    $nestedData[] = $row["id"];
    $nestedData[] = $index;
    $nestedData[] = $row["no_order"];
    $nestedData[] = '<a target="_BLANK" href="http://online.indotaichen.com/laporan/ppc_filter.php?prod_order='.$row["nokk"].'&kkoke=ya">'. $row["nokk"] .'</a>';
    $nestedData[] = $row["nodemand"];
    $nestedData[] = $row["lot"];
    $nestedData[] = $row["bruto"] . ' Kg';
    $nestedData[] = round($row["loading_fix"], 4) . ' %';
    $nestedData[] = $row["l_r"];
    $nestedData[] = $row["no_mesin"];
    $nestedData[] = $row["k_resep"];
    $nestedData[] = $row["proses"];
    $nestedData[] = $row["status"];
    $nestedData[] = '';
    $nestedData[] = $row["ket"];
    $nestedData[] = ($row["target"]);
    $nestedData[] = $row["lama_proses"];
    $nestedData[] = $linkresep;
    $nestedData[] = $data_action;
    $nestedData[] = $tglUpdate;
    $nestedData[] = $row["analisa"];

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
