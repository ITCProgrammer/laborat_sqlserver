<?php
header("Content-type: application/octet-stream");

// filename yang benar (tgl jadi Ymd)
$tglFile = isset($_GET['tgl']) ? date("Ymd", strtotime($_GET['tgl'])) : date("Ymd");
$tipeFile = isset($_GET['tipe']) ? $_GET['tipe'] : '';
header("Content-Disposition: attachment; filename=detailtutup11_" . $tglFile . "_" . $tipeFile . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

ob_start();

$tgl_tutup = isset($_GET['tgl']) ? $_GET['tgl'] : '';
$warehouse = isset($_GET['tipe']) ? $_GET['tipe'] : '';

ini_set("error_reporting", 1);
include "../../koneksi.php"; // 
?>

<table border="1">
  <thead>
    <tr>
      <th><strong>No</strong></th>
      <th><strong>KODE OBAT</strong></th>
      <th><strong>NAMA OBAT</strong></th>
      <th><strong>LOTCODE</strong></th>
      <th><strong>LOGICALWAREHOUSE</strong></th>
      <th><strong>QTY (ENDING BALANCE)</strong></th>
    </tr>
  </thead>
  <tbody>
<?php
$no = 1;
$totqty = 0; 

$sql = "SELECT ITEMTYPECODE,
            KODE_OBAT,
            LONGDESCRIPTION,
            LOTCODE,
            LOGICALWAREHOUSECODE,
            tgl_tutup,
            SUM(BASEPRIMARYQUANTITYUNIT) AS total_qty,
            BASEPRIMARYUNITCODE
        FROM
          (SELECT DISTINCT
            ITEMTYPECODE,
            KODE_OBAT,
            LONGDESCRIPTION,
            LOTCODE,
            LOGICALWAREHOUSECODE,
            WAREHOUSELOCATIONCODE,
            tgl_tutup,
            BASEPRIMARYQUANTITYUNIT,
            BASEPRIMARYUNITCODE
        FROM db_laborat.tblopname_11
        WHERE
            CAST(tgl_tutup AS date) = ?
            AND LOGICALWAREHOUSECODE = ?
            AND KODE_OBAT <> 'E-1-000') AS sub
        GROUP BY
            ITEMTYPECODE,
            KODE_OBAT,
            LONGDESCRIPTION,
            LOTCODE,
            LOGICALWAREHOUSECODE,
            tgl_tutup,
            BASEPRIMARYUNITCODE
        ORDER BY KODE_OBAT ASC";

$params = [$tgl_tutup, $warehouse];

$stmt = sqlsrv_query($con, $sql, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {

    $qty_num = (float)($r['total_qty'] ?? 0);
    

    $formatted = number_format($qty_num, 2, '.', '');

    $value = (string)$qty_num;
    if (strpos($value, '.') !== false) {
        $formatted = rtrim(rtrim($value, '0'), '.');
        if (strpos($formatted, '.') === false) $formatted .= '.00';
        else {
            $decimal_part = explode('.', $formatted)[1];
            if (strlen($decimal_part) === 1) $formatted .= '0';
        }
    } else $formatted = $value . '.00';
?>
    <tr>
      <td><?php echo $no; ?></td>
      <td><?php echo htmlspecialchars($r['KODE_OBAT']); ?></td>
      <td><?php echo htmlspecialchars($r['LONGDESCRIPTION']); ?></td>
      <td><?php echo htmlspecialchars($r['LOTCODE']); ?></td>
      <td><?php echo htmlspecialchars($r['LOGICALWAREHOUSECODE']); ?></td>
      <td align="right"><?php echo $formatted; ?></td>
    </tr>
<?php
    $totqty += $qty_num;
    $no++;
}

// format total jadi 2 desimal
$totqty_format = number_format($totqty, 2, '.', '');
?>
  </tbody>

  <tfoot>
    <tr>
      <td colspan="5"><center><strong>TOTAL</strong></center></td>
      <td><strong><?php echo $totqty_format; ?></strong></td>
    </tr>
  </tfoot>
</table>

<?php ob_end_flush(); ?>
