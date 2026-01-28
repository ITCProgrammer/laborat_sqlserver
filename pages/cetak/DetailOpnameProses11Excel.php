<?php
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=detailtutupproses11_" . date("Ymd", strtotime($_GET['tgl'])) . "_" . $_GET['tipe'] . ".xls");
header("Pragma: no-cache");
header("Expires: 0");
//disini script laporan anda
ob_start();
?>
<?php
$tgl_tutup = isset($_GET['tgl']) ? $_GET['tgl'] : '';
$warehouse = isset($_GET['tipe']) ? $_GET['tipe'] : '';

// echo "<pre>";
// print_r($_GET); // Debug POST value
// echo "</pre>";
?>
<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";
$tgl = date("Y-m-d");
// Konversi ke format seperti pada gambar
$bulanIndo = [
    'January' => 'JANUARI',
    'February' => 'FEBRUARI',
    'March' => 'MARET',
    'April' => 'APRIL',
    'May' => 'MEI',
    'June' => 'JUNI',
    'July' => 'JULI',
    'August' => 'AGUSTUS',
    'September' => 'SEPTEMBER',
    'October' => 'OKTOBER',
    'November' => 'NOVEMBER',
    'December' => 'DESEMBER'
];

$date = new DateTime($tgl_tutup);
$hari = $date->format('d');
$bulan = $bulanIndo[$date->format('F')];
$tahun = $date->format('Y');
?>
<style type="text/css">
.no-border th {
    border: none !important;
  }	
</style>


<p><br>
<table border="1" style="border-collapse: collapse;">
                  <thead>
                  <tr class="no-border">
                    <th colspan="7" align="center"><strong>LAPORAN STOCK GD. KIMIA</strong></th>
                  </tr>
                  <tr class="no-border">
                    <th colspan="7" align="center"><strong>PT. INDO TAICHEN TEXTILE INDUSTRY</strong></th>
                  </tr>
                  <tr>
                    <td colspan="7" align="center" style="border-bottom: 0px solid black;border-top: 0px solid black;border-right: 0px solid black; border-left: 0px solid black;">FW-##-GD#-02/03</td>
                  </tr>
                  <tr>
                    <td colspan="7" align="left" style="border-bottom: 0px solid black;border-top: 0px solid black;border-right: 0px solid black; border-left: 0px solid black;">&nbsp;</td>
                  </tr>
                  <tr>
                    <td colspan="7" align="left" style="border-bottom: 0px solid black;border-top: 0px solid black;border-right: 0px solid black; border-left: 0px solid black;">&nbsp;</td>
                  </tr>
                  <tr>
                    <td colspan="7" align="left" style="border-bottom: 0px solid black;border-top: 0px solid black;border-right: 0px solid black; border-left: 0px solid black;"><strong><?Php echo "TANGGAL : $hari $bulan $tahun"; ?></strong>
                    </td>
                    </tr>
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
   $no=1;   
   $c=0;
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
            $qty_num = (float) ($r['total_qty'] ?? 0);

            $value = (string) $qty_num;
              
              if (strpos($value, '.') !== false) {
                $formatted = rtrim(rtrim($value, '0'), '.');

                if (strpos($formatted, '.') === false) {
                  $formatted .= '.00';
                } else {
                  $decimal_part = explode('.', $formatted)[1];
                  if (strlen($decimal_part) === 1) {
                    $formatted .= '0';
                  }
                }
              } else {
                $formatted = $value . '.00';
              }       
              
              
?>
	  <tr>
	  <td><?php echo $no; ?></td>
      <td><?php echo $r['KODE_OBAT']; ?></td>
      <td ><?php echo $r['LONGDESCRIPTION']; ?></td>
      <td><?php echo $r['LOTCODE']; ?></td>
      <td><?php echo $r['LOGICALWAREHOUSECODE']; ?></td>
      <td align="right"><?= $formatted ?></td>     
      </tr>	  				  
<?php 
    $totqty += $qty_num;
    $no++;
	}
          $totqty_format = (substr(number_format($totqty, 2), -3) == '.00')
            ? number_format($totqty, 0) . '.00'
            : number_format($totqty, 2);?>
				  </tbody>
				<tfoot>
				  <tr>
	    <td>&nbsp;</td>
	    <td>&nbsp;</td>
	    <td>&nbsp;</td>
	    <td>&nbsp;</td>
	    <td>&nbsp;</td>
	    <td align="right">&nbsp;</td>
	    </tr>	
                  
                  <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td><strong>Grand Total</strong></td>
                    <td align="right"><strong><?php echo $totqty_format; ?></strong></td>                
                  <tr>
                    <td style="border-bottom: 0px solid black;border-top: 0px solid black;border-right: 0px solid black; border-left: 0px solid black;">&nbsp;</td>
                    <td style="border-bottom: 0px solid black;border-top: 0px solid black;border-right: 0px solid black; border-left: 0px solid black;">&nbsp;</td>
                    <td style="border-bottom: 0px solid black;border-top: 0px solid black;border-right: 0px solid black; border-left: 0px solid black;">&nbsp;</td>
                    <td style="border-bottom: 0px solid black;border-top: 0px solid black;border-right: 0px solid black; border-left: 0px solid black;">&nbsp;</td>
                    <td align="right" style="border-bottom: 0px solid black;border-top: 0px solid black;border-right: 0px solid black; border-left: 0px solid black;">&nbsp;</td>
                    <td style="border-bottom: 0px solid black;border-top: 0px solid black;border-right: 0px solid black; border-left: 0px solid black;">&nbsp;</td>
                    <td style="border-bottom: 0px solid black;border-top: 0px solid black;border-right: 0px solid black; border-left: 0px solid black;">&nbsp;</td>
                  </tr>
                  <tr>
                    <td colspan="2">&nbsp;</td>
                    <td colspan="1" align="center">Dibuat Oleh :</td>
                    <td colspan="2" align="center">Diperiksa Oleh :</td>
                    <td colspan="1" align="center">Mengetahui:</td>
                    <td align="center" style="border-bottom: 0px solid black;border-top: 0px solid black;border-right: 0px solid black; border-left: 0px solid black;">&nbsp;</td>
                  </tr>
                  <tr>
                    <td colspan="2">NAMA</td>
                    <td colspan="1" align="center">.........</td>
                    <td colspan="2" align="center">.........</td>
                    <td colspan="1" align="center">.........</td>
                    <td align="center" style="border-bottom: 0px solid black;border-top: 0px solid black;border-right: 0px solid black; border-left: 0px solid black;">&nbsp;</td>
                  </tr>
                  <tr>
                    <td colspan="2">JABATAN</td>
                    <td colspan="1" align="center">Staff</td>
                    <td colspan="2" align="center">N/A</td>
                    <td colspan="1" align="center">Leader</td>
                    <td align="center" style="border-bottom: 0px solid black;border-top: 0px solid black;border-right: 0px solid black; border-left: 0px solid black;">&nbsp;</td>
                  </tr>
                  <tr>
                    <td colspan="2">TANGGAL</td>
                    <td colspan="1" align="center">&nbsp;</td>
                    <td colspan="2" align="center">&nbsp;</td>
                    <td colspan="1" align="center">&nbsp;</td>
                    <td align="center" style="border-bottom: 0px solid black;border-top: 0px solid black;border-right: 0px solid black; border-left: 0px solid black;">&nbsp;</td>
                  </tr>
                  <tr>
                    <td colspan="2">TANDA TANGAN
					  <p>&nbsp;</p>
   					<p>&nbsp;</p></td>
                    <td colspan="1">&nbsp;</td>
                    <td colspan="2" align="center">N/A</td>
                    <td colspan="1" align="center">&nbsp;</td>
                    <td align="center" style="border-bottom: 0px solid black;border-top: 0px solid black;border-right: 0px solid black; border-left: 0px solid black;">&nbsp;</td>
                  </tr>
                  </tfoot>                  
                </table>
<br>
<?php ob_end_flush(); ?>
