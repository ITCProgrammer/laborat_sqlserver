<?php
ini_set("error_reporting", 1);
session_start();
include "../../koneksi.php";
//--
$idkk = isset($_REQUEST['idkk']) ? (int) $_REQUEST['idkk'] : 0;
$act = isset($_GET['g']) ? $_GET['g'] : '';
$data = sqlsrv_query($con, "SELECT * FROM db_laborat.tbl_test_qc WHERE id = ? ORDER BY id DESC OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY", [$idkk]);
$r = $data ? sqlsrv_fetch_array($data, SQLSRV_FETCH_ASSOC) : null;
$r = $r ?: [];
$tglUpdate = $r['tgl_update'] ?? null;
if ($tglUpdate instanceof DateTimeInterface) {
  $tglUpdate = $tglUpdate->format('Y-m-d H:i:s');
} elseif ($tglUpdate === null) {
  $tglUpdate = '';
}

$buyer = $r['buyer'] ?? '';
$pelanggan = $r['pelanggan'] ?? '';
$noPo = $r['no_po'] ?? '';
$noOrder = $r['no_order'] ?? '';
$noItem = $r['no_item'] ?? '';
$jenisKain = $r['jenis_kain'] ?? '';
$warna = $r['warna'] ?? '';
$noWarna = $r['no_warna'] ?? '';
$prosesFin = $r['proses_fin'] ?? '';
$treatment = $r['treatment'] ?? '';
$noCounter = $r['no_counter'] ?? '';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="styles_cetak.css" rel="stylesheet" type="text/css">
<title>Cetak Label</title>
<style>
	td{
	border-top:0px #000000 solid; 
	border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; 
	border-right:0px #000000 solid;
	}
	</style>
</head>


<body>
<table width="100%" border="0"style="width: 7in;">
  <tbody>    
    <tr>
      <td align="left" valign="top" style="height: 1.6in;"><table width="100%" border="0" class="table-list1" style="width: 2.3in;">
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size: 8px;"><?php echo $buyer;?></div></td>
        </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:9px;">
            <?php
      $pelanggan = $pelanggan;
      $result = '';
      $result1 = '';
      $pos = strpos($pelanggan, "/");
	  if ($pos !== false && $pos > 0) {
        $pos1 = $pos;
        $result = substr($pelanggan, 0, $pos1);
        $pos2 = $pos + 1;
        $result1 = substr($pelanggan, $pos2);
      }
?>
            <?php echo substr($result,0,13)."/".substr($result1,0,13);?></div></td>
        </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:9px;"><?php echo substr($noPo,0,31);?></div></td>
        </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:9px;"><strong><?php echo $noOrder;?></strong><?php echo " (".$noItem.")";?></div></td>
        </tr>
        <tr>
          <td valign="top" style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:6px;"><?php echo substr($jenisKain,0,100);?></div></td>
        </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:9px;"><span style="font-size:7px;"><?php echo "<strong><span style='font-size:9px;'>".substr($warna,0,60)."</span></strong>/".substr($noWarna,0,15);?></span></div></td>
        </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:9px;"><?php echo $tglUpdate !== '' ? date('d-m-Y H:i', strtotime(substr($tglUpdate,0,18))) : '';?></div></td>
        </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:9px;"><?php echo $treatment;?></div>            <div style="font-size:9px;"></div></td>
          </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><span style="font-size:9px;"><?php echo $noCounter;?></span></td>
          </tr>
      </table></td>
      <td align="left" valign="top" style="height: 1.6in;"><table width="100%" border="0" class="table-list1" style="width: 2.3in;">
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size: 8px;"><?php echo $buyer;?></div></td>
        </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:9px;">
            <?php
      $pelanggan = $pelanggan;
      $result = '';
      $result1 = '';
      $pos = strpos($pelanggan, "/");
	  if ($pos !== false && $pos > 0) {
        $pos1 = $pos;
        $result = substr($pelanggan, 0, $pos1);
        $pos2 = $pos + 1;
        $result1 = substr($pelanggan, $pos2);
      }
?>
            <?php echo substr($result,0,13)."/".substr($result1,0,13);?></div></td>
        </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:9px;"><?php echo substr($noPo,0,31);?></div></td>
        </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:9px;"><strong><?php echo $noOrder;?></strong><?php echo " (".$noItem.")";?></div></td>
        </tr>
        <tr>
          <td valign="top" style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:6px;"><?php echo substr($jenisKain,0,100);?></div></td>
        </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:9px;"><span style="font-size:7px;"><?php echo "<strong><span style='font-size:9px;'>".substr($warna,0,60)."</span></strong>/".substr($noWarna,0,15);?></span></div></td>
        </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:9px;"><?php echo ($tglUpdate !== '' ? date('d-m-Y H:i', strtotime(substr($tglUpdate,0,18))) : '')."/".substr($prosesFin,0,14);?></div></td>
        </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:9px;"><?php echo $treatment;?></div>            <div style="font-size:9px;"></div></td>
          </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><span style="font-size:9px;"><?php echo $noCounter;?></span></td>
          </tr>
      </table></td>
      <td align="left" valign="top" style="height: 1.6in;"><table width="100%" border="0" class="table-list1" style="width: 2.3in;">
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size: 8px;"><?php echo $buyer;?></div></td>
        </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:9px;">
            <?php
      $pelanggan = $pelanggan;
      $result = '';
      $result1 = '';
      $pos = strpos($pelanggan, "/");
	  if ($pos !== false && $pos > 0) {
        $pos1 = $pos;
        $result = substr($pelanggan, 0, $pos1);
        $pos2 = $pos + 1;
        $result1 = substr($pelanggan, $pos2);
      }
?>
            <?php echo substr($result,0,13)."/".substr($result1,0,13);?></div></td>
        </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:9px;"><?php echo substr($noPo,0,31);?></div></td>
        </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:9px;"><strong><?php echo $noOrder;?></strong><?php echo " (".$noItem.")";?></div></td>
        </tr>
        <tr>
          <td valign="top" style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:6px;"><?php echo substr($jenisKain,0,100);?></div></td>
        </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:9px;"><span style="font-size:7px;"><?php echo "<strong><span style='font-size:9px;'>".substr($warna,0,60)."</span></strong>/".substr($noWarna,0,15);?></span></div></td>
        </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:9px;"><?php echo ($tglUpdate !== '' ? date('d-m-Y H:i', strtotime(substr($tglUpdate,0,18))) : '')."/".substr($prosesFin,0,14);?></div></td>
        </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><div style="font-size:9px;"><?php echo $treatment;?></div></td>
          </tr>
        <tr>
          <td style="border-top:0px #000000 solid; border-bottom:0px #000000 solid;
	border-left:0px #000000 solid; border-right:0px #000000 solid;"><span style="font-size:9px;"><?php echo $noCounter;?></span></td>
          </tr>
      </table></td>
    </tr>
  </tbody>
</table>
</body>
</html>
