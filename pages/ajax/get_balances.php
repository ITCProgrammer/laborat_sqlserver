<?php
header('Content-Type: application/json');

// koneksi ke DB
include "../../koneksi.php";

$status = $_POST['status'] ?? '';
$qty_filter = $_POST['qty_filter'] ?? 'nonzero';

$sqlBalance = "SELECT DISTINCT
          b.G_B as greige_blc,
          b.DECOSUBCODE01 as decosub01,
          b.DECOSUBCODE02 as decosub02,
          b.DECOSUBCODE03 as decosub03,
          b.DECOSUBCODE04 as decosub04,
          b.LOTCODE as lot_code,
          b.WHSLOCATIONWAREHOUSEZONECODE as warehouse_zone_code,
          b.WAREHOUSELOCATIONCODE as warehouse_location_code,
          b.NUMBERID as element_id,
          b.ELEMENTSCODE as element_code,
          b.LOGICALWAREHOUSECODE as logical_warehouse_code,
          b.BASEPRIMARYQUANTITYUNIT as base_primary_quantity_unit,
          b.BASEPRIMARYUNITCODE as base_primary_unit_code,
          b.ISNOWDATA as is_now_data,
          b.CREATIONDATETIME as creation_datetime,
          bep.month_period as month_period,
          CASE
            WHEN bep.month_period IS NOT NULL THEN CONVERT(varchar(10), DATEADD(month, bep.month_period, b.CREATIONDATETIME), 23)
            ELSE NULL
          END AS expired_date,
          CASE
              WHEN tre.element_id IS NOT NULL THEN 1
              ELSE 0
          END AS on_matching
        FROM db_laborat.balance b
        LEFT JOIN db_laborat.tbl_resep_element tre ON b.NUMBERID = tre.element_id
        LEFT JOIN db_laborat.balance_expired_period bep ON (
            bep.warehouse_zone = b.WHSLOCATIONWAREHOUSEZONECODE
            AND CHARINDEX(',' + b.DECOSUBCODE01 + ',', ',' + ISNULL(bep.subcode01, '') + ',') > 0
            AND bep.greige_blc = b.G_B
        )
        WHERE 1=1";

if ($status == 'matching') {
  $sqlBalance .= " AND tre.element_id IS NOT NULL";
}
if ($status == 'available') {
  $sqlBalance .= " AND tre.element_id IS NULL";
}
if ($status == 'expired') {
  // only include rows where we have a computed expired_date and it's before now
  $sqlBalance .= " AND bep.month_period IS NOT NULL AND DATEADD(month, bep.month_period, b.CREATIONDATETIME) < GETDATE()";
}

// Apply qty filter: 'nonzero' (default) => qty > 0; 'include_zero' => no filter; 'only_zero' => qty = 0
if ($qty_filter === 'nonzero') {
  $sqlBalance .= " AND ISNULL(b.BASEPRIMARYQUANTITYUNIT, 0) > 0";
} elseif ($qty_filter === 'only_zero') {
  $sqlBalance .= " AND ISNULL(b.BASEPRIMARYQUANTITYUNIT, 0) = 0";
}

$sqlBalance .= " ORDER BY b.CREATIONDATETIME DESC";

$qBalance = sqlsrv_query($con, $sqlBalance);

$data = [];

while ($qBalance && ($row = sqlsrv_fetch_array($qBalance, SQLSRV_FETCH_ASSOC))) {
  $item_code = implode('', [
    $row['decosub01'],
    $row['decosub02'],
    $row['decosub03'],
    $row['decosub04']
  ]);
  $row['item_code'] = $item_code;

  // expired_date and greige_blc are computed/joined in the SQL
  $row['greige_blc'] = $row['greige_blc'] ?? null;

  $data[] = $row;
}
if ($qBalance) {
  sqlsrv_free_stmt($qBalance);
}

echo json_encode([
  "data" => $data
]);
