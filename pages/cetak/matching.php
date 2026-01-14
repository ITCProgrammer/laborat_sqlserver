<?php
session_start();
include "../../koneksi.php";
// Tampilkan error kecuali notice deprecation dari lib QR (override setting dari koneksi.php)
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('error_reporting', E_ALL & ~E_DEPRECATED);
if (! $con) {
  die('Koneksi SQL Server db_laborat gagal.');
}

function normalizeDateVal($val) {
  return $val instanceof DateTime ? $val->format('Y-m-d H:i:s') : $val;
}
//--
$time = date('Y-m-d H:i:s');
//-- 
$idkk = $_REQUEST['idkk'];
$act = $_GET['g'] ?? '';
//-
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link href="styles_cetak.css" rel="stylesheet" type="text/css">
  <title>Cetak Kartu Matching</title>
  <style>
    .hurufvertical {
      writing-mode: tb-rl;
      -webkit-transform: rotate(-90deg);
      -moz-transform: rotate(-90deg);
      -o-transform: rotate(-90deg);
      -ms-transform: rotate(-90deg);
      transform: rotate(180deg);
      white-space: nowrap;
      float: left;
    }
  </style>
</head>

<body>
  <?php
    $stmtHeader = sqlsrv_query($con, "SELECT * FROM db_laborat.tbl_matching WHERE no_resep = ?", [$idkk]);
    if (! $stmtHeader) {
        die('Load data matching gagal: ' . print_r(sqlsrv_errors(), true));
    }
    $data = sqlsrv_fetch_array($stmtHeader, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmtHeader);
    if (! $data) {
        die('Data matching tidak ditemukan.');
    }
    $data['tgl']          = date('d M Y');
    $data['tgl_in']       = normalizeDateVal($data['tgl_in'] ?? null);
    $data['tgl_delivery'] = normalizeDateVal($data['tgl_delivery'] ?? null);
    $tglInDisplay         = !empty($data['tgl_in']) ? date("d-m-Y", strtotime($data['tgl_in'])) : '';
    $tglDeliveryDisplay   = !empty($data['tgl_delivery']) ? date("d-m-Y", strtotime($data['tgl_delivery'])) : '';

    $ip_num = $_SERVER['REMOTE_ADDR'];
    sqlsrv_query(
        $con,
        "INSERT INTO db_laborat.log_status_matching (ids, status, info, do_by, do_at, ip_address) VALUES (?, ?, ?, ?, ?, ?)",
        [$idkk, 'print', 'cetak kartu matching', $_SESSION['userLAB'] ?? '', $time, $ip_num]
    );
  ?>
  <?php
    function qr_data_uri(string $text, int $ecc = QR_ECLEVEL_L, int $size = 3, int $margin = 0): string {
        ob_start();
        QRcode::png($text, null, $ecc, $size, $margin); // output biner PNG ke buffer
        $png = ob_get_clean();
        return 'data:image/png;base64,' . base64_encode($png);
    }

    function qr_data_uri_with_label(
        string $text,
        string $label = '',
        int $ecc = QR_ECLEVEL_H,
        int $size = 4,
        int $margin = 1
    ): string {
        if ($label === '') {
            $label = $text;
        }

        // 1. Generate QR ke buffer (PNG biner)
        ob_start();
        QRcode::png($text, null, $ecc, $size, $margin);
        $png = ob_get_clean();

        // 2. Bikin image GD dari PNG
        $im = imagecreatefromstring($png);
        if (!$im) {
            // fallback: kalau gagal, balikin QR biasa saja
            return 'data:image/png;base64,' . base64_encode($png);
        }

        $w = imagesx($im);
        $h = imagesy($im);

        // 3. Siapkan area kotak putih di tengah
        //    (ukuran 50% lebar, 18% tinggi QR – bisa diubah-ubah)
        $boxWidth  = (int)($w * 0.5);
        $boxHeight = (int)($h * 0.18);

        $boxX1 = (int)(($w - $boxWidth) / 2);
        $boxY1 = (int)(($h - $boxHeight) / 2);
        $boxX2 = $boxX1 + $boxWidth;
        $boxY2 = $boxY1 + $boxHeight;

        $white = imagecolorallocate($im, 255, 255, 255);
        imagefilledrectangle($im, $boxX1, $boxY1, $boxX2, $boxY2, $white);

        // 4. Tulis teks di tengah kotak
        //    (pakai font built-in GD, biar nggak ribet TTF)
        //    Batasi panjang teks biar nggak kepanjangan
        $label = substr($label, -14); // ambil 14 karakter terakhir (opsional)

        $black = imagecolorallocate($im, 0, 0, 0);
        $font  = 3; // built-in font size 3

        $textWidth  = imagefontwidth($font) * strlen($label);
        $textHeight = imagefontheight($font);

        $textX = (int)(($w - $textWidth) / 2);
        $textY = (int)(($h - $textHeight) / 2);

        imagestring($im, $font, $textX, $textY, $label, $black);

        // 5. Kembalikan ke PNG biner
        ob_start();
        imagepng($im);
        imagedestroy($im);
        $outPng = ob_get_clean();

        // 6. Jadikan data URI seperti sebelumnya
        return 'data:image/png;base64,' . base64_encode($outPng);
    }
  ?>
  <?php
    include('../../phpqrcode/qrlib.php');

    // Data untuk QR Code
    $qrcode = $data['no_resep'];

    // Membuat QR Code dalam file PNG

    // if (strtoupper(substr($qrcode, 0, 2)) === 'DR') {
    //     $qrcodeA = $qrcode . '-A';
    //     $fileqrA = 'qrcode_A.png';
    //     QRcode::png($qrcodeA, $fileqrA, QR_ECLEVEL_L, 3, 0);

    //     $qrcodeB = $qrcode . '-B';
    //     $fileqrB = 'qrcode_B.png';
    //     QRcode::png($qrcodeB, $fileqrB, QR_ECLEVEL_L, 3, 0);
    // } else {
    //     $fileqr = 'qrcode.png';
    //     QRcode::png($qrcode, $fileqr, QR_ECLEVEL_L, 3, 0);
    // }

    $isDR = (strtoupper(substr($qrcode, 0, 2)) === 'DR');

    if ($isDR) {
        $qrA_uri = qr_data_uri($qrcode . '-A');
        $qrB_uri = qr_data_uri($qrcode . '-B');
    } else {
        $qr_uri  = qr_data_uri($qrcode);
    }
  ?>
  <table width="100%" border="0">
    <tr style="font-size: 10px;">
      <td width="13%">GRAMASI PERMINTAAN:</td>
      <td width="10%"><strong><?Php echo $data['lebar'] . " x " . $data['gramasi'] . " gr/m2"; ?></strong></td>
      <td width="11%">GRAMASI AKTUAL:</td>
      <td width="11%">&nbsp;</td>
      <td width="16%">BERAT:</td>
      <td width="15%">&nbsp;</td>
      <td width="9%">&nbsp;</td>
      <td width="15%" style="text-align: right;">No. Form : <?= (strtoupper(substr($qrcode, 0, 2)) === 'DR' ? 'FW-12-LAB-05(A)/01' : 'FW-12-LAB-05/07') ?></td>
    </tr>
  </table>
  <table width="100%" border="0" class="table-list1">
    <tbody>
      <tr>
        <td width="35" style="border-right:0px #000000 solid;"><strong style="font-size: 14px;">R</strong>SUFFIX</td>
        <td width="5" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
        <td width="53" style="border-left:0px #000000 solid;"><strong>
            <?php
            // if (substr($data['no_resep'], 0, 2) == 'D2' OR substr($data['no_resep'], 0, 1) == 'C' OR substr($data['no_resep'], 0, 2) == 'DR' OR substr($data['no_resep'], 0, 2) == 'OB') {  
            //   echo substr($data['no_resep'], 2).'L';
            // }elseif (substr($data['no_resep'], 0, 1) == 'R' or substr($data['no_resep'], 0, 1) == 'A'){
            //   echo substr($data['no_resep'], 1).'L';
            // }
            echo $data['no_resep'];
            ?>
          </strong>
        </td>
        <td width="55" style="border-right:0px #000000 solid;"><strong style="font-size: 14px;">I</strong>TEM</td>
        <td width="5" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
        <td colspan="5" style="border-left:0px #000000 solid;"><strong><?Php echo $data['no_item']; ?></strong></td>
        <td colspan="6" align="center" style="border-bottom:0px #000000 solid;">
          <?php if ($data['jenis_matching'] == "L/D" or $data['jenis_matching'] == "LD NOW") : ?>
            <strong style="font-size: 16px;">KARTU MATCHING L/D</strong>
          <?php else : ?>
            <strong style="font-size: 16px;">KARTU MATCHING</strong>
          <?php endif; ?>
        </td>
        <td width="40" style="border-right:0px #000000 solid;"><strong style="font-size: 14px;">L</strong>ANGGANAN</td>
        <td width="2" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
        <td width="200" style="border-left:0px #000000 solid;"><strong style="font-size: 8px;"><?Php echo $data['langganan']; ?></strong></td>
        <td width="400" rowspan="8" style="background-color: #f0f0f0; font-size: 20px; text-align:center; vertical-align:middle;"><strong style="opacity:0.4;">FOR RFID LABEL</strong></td>
      </tr>
      <tr>
        <td style="border-right:0px #000000 solid;"><strong style="font-size: 14px;">M</strong>ATCHER</td>
        <td style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
        <td style="border-left:0px #000000 solid;">&nbsp;</td>
        <td style="border-right:0px #000000 solid;"><strong style="font-size: 14px;">P</strong>O <strong style="font-size: 14px;">G</strong>REIGE</td>
        <td style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
        <td colspan="5" style="border-left:0px #000000 solid;"><strong><?Php if ($data['no_po'] == "NULL") {
                                                                          echo " ";
                                                                        } else {
                                                                          echo $data['no_po'];
                                                                        }   ?></strong></td>
        <td colspan="6" align="left" valign="top" style="border-top:0px #000000 solid;">CATATAN:</td>
        <td style="border-right:0px #000000 solid;"><strong style="font-size: 14px;">P</strong>ROSES
        </td>
        <td style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
        <td style="border-left:0px #000000 solid;"><strong><?Php echo $data['proses']; ?></strong></td>
      </tr>
      <tr>
        <td rowspan="2" style="border-right:0px #000000 solid;"><strong style="font-size: 14px;">T</strong>IME <strong style="font-size: 14px;">I</strong>N</td>
        <td rowspan="2" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
        <td rowspan="2" style="border-left:0px #000000 solid;"><strong><?Php echo $tglInDisplay; ?></strong></td>
        <td rowspan="2" style="border-right:0px #000000 solid;"><strong style="font-size: 14px;">K</strong>AIN</td>
        <td rowspan="2" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
        <td colspan="5" rowspan="2" style="border-left:0px #000000 solid;"><strong style="font-size: 8px;"><?Php if ($data['jenis_kain'] == "NULL") {
                                                                                                              echo "";
                                                                                                            } else {
                                                                                                              echo $data['jenis_kain'];
                                                                                                            } ?></strong></td>
        <td width="40" rowspan="2">Recipe Code</td>
        <td colspan="2" rowspan="2" style="border-right:0px #000000 solid;"><strong><?Php echo $data['recipe_code']; ?></strong></td>
        <td width="40" rowspan="2">Color Code</td>
        <td colspan="2" rowspan="2" style="border-right:0px #000000 solid;"><?Php echo $data['color_code']; ?></strong></td>
        <td rowspan="2" style="border-right:0px #000000 solid;">STD COCOK WARNA </td>
        <td rowspan="2" style="border-right:0px #000000 solid; border-left:0px #000000 solid;"></td>
        <td style="border-left:0px #000000 solid;">1. <strong><?Php echo $data['cocok_warna']; ?></strong></td>
      </tr>
      <tr>
        <td style="border-left:0px #000000 solid;">2.</td>
      </tr>
      <tr>
        <td rowspan="2" style="border-right:0px #000000 solid;"><strong style="font-size: 14px;">D</strong>ELIVERY</td>
        <td rowspan="2" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
        <td rowspan="2" style="border-left:0px #000000 solid;"><strong><?Php echo $tglDeliveryDisplay; ?></strong></td>
        <td rowspan="2" style="border-right:0px #000000 solid;"><strong style="font-size: 14px;">B</strong>ENANG</td>
        <td rowspan="2" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
        <td colspan="5" rowspan="2" style="border-left:0px #000000 solid;"><strong style="font-size: 8px;"><?Php if ($data['benang'] == "NULL") {
                                                                                                              echo "";
                                                                                                            } else {
                                                                                                              echo $data['benang'];
                                                                                                            } ?></strong></td>
        <td rowspan="4">T-Side</td>
        <td width="20" style="border-right:0px #000000 solid;">L : R </td>
        <td width="60" style="border-left:0px #000000 solid;">:</td>
        <td rowspan="4">C-Side</td>
        <td width="20" style="border-right:0px #000000 solid;">L : R </td>
        <td width="60" style="border-left:0px #000000 solid;">:</td>
        <td rowspan="2" style="border-right:0px #000000 solid;"><strong style="font-size: 14px;">W</strong>ARNA</td>
        <td rowspan="2" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
        <td rowspan="2" style="border-left:0px #000000 solid;"><strong style="font-size: 9px;"><?Php echo $data['warna']; ?></strong></td>
      </tr>
      <?php
        // Ambil data suhu pertama
        $tempCode1 = $data['temp_code'];
        $product_name1 = '...°C X ...MNT';
        if ($tempCode1) {
            $stmt1 = sqlsrv_query($con, "SELECT product_name FROM db_laborat.master_suhu WHERE code = ? AND status = 1", [$tempCode1]);
            if ($stmt1) {
                $row1 = sqlsrv_fetch_array($stmt1, SQLSRV_FETCH_ASSOC);
                if (! empty($row1['product_name'])) {
                    $product_name1 = $row1['product_name'];
                }
                sqlsrv_free_stmt($stmt1);
            }
        }

        // Ambil data suhu kedua
        $tempCode2 = $data['temp_code2'];
        $product_name2 = '...°C X ...MNT';
        if ($tempCode2) {
            $stmt2 = sqlsrv_query($con, "SELECT product_name FROM db_laborat.master_suhu WHERE code = ? AND status = 1", [$tempCode2]);
            if ($stmt2) {
                $row2 = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);
                if (! empty($row2['product_name'])) {
                    $product_name2 = $row2['product_name'];
                }
                sqlsrv_free_stmt($stmt2);
            }
        }
      ?>

    
      <?php
        $idkk_raw = strtoupper($_GET['idkk'] ?? '');

        $prefix1 = substr($idkk_raw, 0, 1);
        $prefix2 = substr($idkk_raw, 0, 2);

        if ($prefix2 === 'DR') {
            ?>
            <tr>
                <td colspan="2" align="center"><?= $product_name1; ?></td>
                <td colspan="2" align="center"><?= $product_name2; ?></td>
      
            </tr>
            <?php
        } elseif ($prefix1 === 'R' || $prefix2 === 'OB') {
            ?>
            <tr>
                <td colspan="2" align="center"></td>
                <td colspan="2" align="center"><?= $product_name1; ?></td>
      
            </tr>
            <?php
        } elseif ($prefix1 === 'A' || $prefix1 === 'D' || $prefix2 === 'CD') {
            ?>
            <tr>
                <td colspan="2" align="center"><?= $product_name1; ?></td>
                <td colspan="2" align="center"></td>
      
            </tr>
            <?php
        }
      ?>

      <tr><?php $i = 1;
          $sqlLamp = sqlsrv_query($con, "SELECT lampu FROM db_laborat.vpot_lampbuy WHERE buyer = ? ORDER BY flag", [$data['buyer']]); ?>
        <td rowspan="2" style="border-right:0px #000000 solid;" colspan="3"><strong>LAMPU</strong> : <?php if ($sqlLamp) { while ($lamp = sqlsrv_fetch_array($sqlLamp, SQLSRV_FETCH_ASSOC)) {
                                                                                                        echo $i++ . '.(' . $lamp['lampu'] . '), ';
                                                                                                      }
                                                                                                      sqlsrv_free_stmt($sqlLamp);
                                                                                                    } ?>
        </td>
        <td rowspan="2" style="border-right:0px #000000 solid;">
          <strong style="font-size: 14px;">T</strong>IME <strong style="font-size: 14px;">O</strong>UT
        </td>
        <?php
        $sql_ci_y = sqlsrv_query($con, "SELECT * FROM db_laborat.tbl_status_matching WHERE idm = ?", [$data['no_resep']]);
        $row_ci_y = $sql_ci_y ? sqlsrv_fetch_array($sql_ci_y, SQLSRV_FETCH_ASSOC) : [];
        if ($sql_ci_y) {
          sqlsrv_free_stmt($sql_ci_y);
        }
        ?>
        <td rowspan="2" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
        <td width="33" rowspan="3" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">&nbsp;</td>
        <td width="70" rowspan="2" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">
          <span style="border-left:0px #000000 solid;">
            <span style="border-right:0px #000000 solid;">
              <strong style="font-size: 11px;">C</strong>IE
              <strong style="font-size: 11px;">W</strong>I
            </span>
          </span> : <?= $row_ci_y['cie_wi'] ?? ''; ?>
        </td>
        <td width="1" rowspan="2" style="border-right:0px #000000 solid; border-left:0px #000000 solid;"></td>
        <td width="85" rowspan="2" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">
          <span style="border-left:0px #000000 solid;">
            <strong style="font-size: 8px;">
              <strong style="font-size: 11px;">C</strong>IE
              <strong style="font-size: 11px;">T</strong>INT
            </strong>
          </span> : <?= $row_ci_y['cie_tint'] ?? ''; ?>
        </td>
        <td width="35" rowspan="2" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">
          <span style="border-left:0px #000000 solid;">
            <strong style="font-size: 8px;">
              <strong style="font-size: 11px;">Y</strong>Ness
            </strong>
          </span> : <?= $row_ci_y['yellowness'] ?? ''; ?>
        </td>
        <!-- <td width="33" rowspan="3" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">&nbsp;</td> -->
        <td style="border-right:0px #000000 solid;">PH </td>
        <td style="border-left:0px #000000 solid;">:</td>
        <td colspan="2">&nbsp;</td>
        <td rowspan="2" style="border-right:0px #000000 solid;">
          <strong style="font-size: 14px;">L</strong>AB
          <strong style="font-size: 14px;">D</strong>IP
          <strong style="font-size: 14px;">N</strong>O
        </td>
        <td rowspan="2" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
        <td rowspan="2" style="border-left:0px #000000 solid;">
          <strong><?Php echo $data['no_warna']; ?></strong>
        </td>
      </tr>
      <tr>
        <td style="border-right:0px #000000 solid;">RC/Blc</td>
        <td style="border-left:0px #000000 solid;">:</td>
        <td style="border-right:0px #000000 solid;">Soaping</td>
        <td style="border-left:0px #000000 solid;"> :</td>
      </tr>
    </tbody>
  </table>
  <table width="100%" border="0" class="table-list1">
    <tbody>
      <tr align="center">
        <td width="1%">&nbsp;</td>
        <td width="5%"><strong>
            <font size="-1">D/A CODE</font>
          </strong></td>
        <td colspan="4" width="15%"><strong>
            <font size="+2">D/A NAME</font>
          </strong></td>
        <td width="4%"><strong>
            <font size="+2">1</font>
          </strong></td>
        <td width="4%"><strong>
            <font size="+2">2</font>
          </strong></td>
        <td width="4%"><strong>
            <font size="+2">3</font> 
          </strong></td>
        <td width="4%"><strong>
            <font size="+2">4</font>
          </strong></td>
        <td width="4%"><strong>
            <font size="+2">5</font>
          </strong></td>
        <td width="4%"><strong>
            <font size="+2">6</font>
          </strong></td>
        <td width="4%"><strong>
            <font size="+2">7</font>
          </strong></td>
        <td width="4%"><strong>
            <font size="+2">8</font>
          </strong></td>
        <td width="4%"><strong>
            <font size="+2">9</font>
          </strong></td>
        <td width="4%"><strong>
            <font size="+2">10</font>
          </strong></td>
        <td width="4%"><strong>
            <font size="+2">11</font>
          </strong></td>
        <td width="4%"><strong>
            <font size="+2">12</font>
          </strong></td>
        <td width="4%"><strong>
            <font size="+2">13</font>
          </strong></td>
        <td width="4%"><strong>
            <font size="+2">14</font>
          </strong></td>
        <td width="4%"><strong>
            <font size="+2">15</font>
          </strong></td>
      </tr>
      <?php
        $no = 1;
        $qry1 = sqlsrv_query($con, "SELECT * FROM db_laborat.tbl_matching_detail WHERE id_matching = ? AND jenis = 'cotton' ORDER BY id ASC", [$data['id']]);
        while ($qry1 && ($r = sqlsrv_fetch_array($qry1, SQLSRV_FETCH_ASSOC))) { ?>
          <tr>
            <?php if ($no < 2) { ?><td rowspan="<?php $sp = 12;
                                                echo $sp - $no; ?>"><a class="hurufvertical"><strong>SIDE A</strong></a></td> <?php } ?>
            <td align="center" style="height: 15px;"><?php echo strtoupper($r['kode']); ?></td>
            <td colspan="4"><?php echo $r['nama']; ?></td>
            <td align="center"><?php echo $r['lab']; ?></td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>
      <?php $no++; } ?>
      <?php if ($qry1) { sqlsrv_free_stmt($qry1); } ?>
      <?php for ($i = $no; $i <= 7; $i++) { ?>
        <tr>
          <?php if ($i < 2) { ?><td rowspan="11" style="border-bottom: double;"><a class="hurufvertical"><strong>SIDE A</strong></a></td> <?php } ?>
          <td style="height: 15px;">&nbsp;</td>
          <td colspan="4">&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
      <?php } ?>
      <tr>
        <td style="height: 15px;">&nbsp;</td>
        <td colspan="4">&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td style="height: 15px;">&nbsp;</td>
        <td colspan="4">&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td style="height: 15px;">&nbsp;</td>
        <td colspan="4">&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>

      <tr>
        <?php if ($isDR): ?>
          <td align="center" colspan="2" style="border-bottom:5px solid black !important;">
            <img src="<?= $qrA_uri ?>" alt="QR Code A" class="qrcode" width="80%" height="80%">
          </td>
          <td align="left" colspan="3" style="border-bottom:5px solid black !important; height: 100px; border-bottom: double;">Comment Colorist<br><br><br><br><br><br></td>
          <td align="center" style="border-bottom:5px solid black !important; height: 50px; border-bottom: double;">&nbsp;</td>
        <?php else: ?>
          <td colspan="5" align="left" style="border-bottom:5px solid black !important; height: 100px; border-bottom: double;">Comment Colorist<br><br><br><br><br></td>
          <td align="center" style="border-bottom:5px solid black !important; height: 50px; border-bottom: double;">&nbsp;</td>
        <?php endif; ?>

        <td align="center" style="border-bottom:5px solid black !important; height: 50px; border-bottom: double;">&nbsp;</td>
        <td align="center" style="border-bottom:5px solid black !important; height: 50px; border-bottom: double;">&nbsp;</td>
        <td align="center" style="border-bottom:5px solid black !important; height: 50px; border-bottom: double;">&nbsp;</td>
        <td align="center" style="border-bottom:5px solid black !important; height: 50px; border-bottom: double;">&nbsp;</td>
        <td align="center" style="border-bottom:5px solid black !important; height: 50px; border-bottom: double;">&nbsp;</td>
        <td align="center" style="border-bottom:5px solid black !important; height: 50px; border-bottom: double;">&nbsp;</td>
        <td align="center" style="border-bottom:5px solid black !important; height: 50px; border-bottom: double;">&nbsp;</td>
        <td align="center" style="border-bottom:5px solid black !important; height: 50px; border-bottom: double;">&nbsp;</td>
        <td align="center" style="border-bottom:5px solid black !important; height: 50px; border-bottom: double;">&nbsp;</td>
        <td align="center" style="border-bottom:5px solid black !important; height: 50px; border-bottom: double;">&nbsp;</td>
        <td align="center" style="border-bottom:5px solid black !important; height: 50px; border-bottom: double;">&nbsp;</td>
        <td align="center" style="border-bottom:5px solid black !important; height: 50px; border-bottom: double;">&nbsp;</td>
        <td align="center" style="border-bottom:5px solid black !important; height: 50px; border-bottom: double;">&nbsp;</td>
        <td align="center" style="border-bottom:5px solid black !important; height: 50px; border-bottom: double;">&nbsp;</td>
      </tr>
      <?php
        $no1 = 1;
        $qry2 = sqlsrv_query($con, "SELECT * FROM db_laborat.tbl_matching_detail WHERE id_matching = ? AND jenis = 'polyester' ORDER BY id ASC", [$data['id']]);
        while ($qry2 && ($r1 = sqlsrv_fetch_array($qry2, SQLSRV_FETCH_ASSOC))) { ?>
        <tr>
          <?php if ($no1 < 2) { ?><td rowspan="<?php $sp1 = 15;
                                                echo $sp1 - $no1; ?>"><a class="hurufvertical"><strong>SIDE B</strong></a></td> <?php } ?>
          <td align="center" style="height: 15px;"><?php echo strtoupper($r1['kode']); ?></td>
          <td colspan="4"><?php echo $r1['nama']; ?></td>
          <td align="center"><?php echo $r1['lab']; ?></td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
      <?php $no1++; } ?>
      <?php if ($qry2) { sqlsrv_free_stmt($qry2); } ?>
      <?php for ($i1 = $no1; $i1 <= 7; $i1++) { ?>
        <tr>
          <?php if ($i1 < 2) { ?><td rowspan="11"><a class="hurufvertical"><strong>SIDE B</strong></a></td> <?php } ?>
          <td style="height: 15px;">&nbsp;</td>
          <td colspan="4">&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
      <?php } ?>
      <tr>
        <td style="height: 15px;">&nbsp;</td>
        <td colspan="4">&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td style="height: 15px;">&nbsp;</td>
        <td colspan="4">&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td style="height: 15px;">&nbsp;</td>
        <td colspan="4">&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td align="center" colspan="2" rowspan="2">
          <img src="<?= $isDR ? $qrB_uri : $qr_uri ?>" alt="QR Code" class="qrcode" width="80%" height="80%">
        </td>
        <td align="left" colspan="3" style="height: 60px;">Comment Colorist<br><br><br><br><br></td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
        <td align="center">&nbsp;</td>
      </tr>
      <tr> 
        <?php $sqlOrder = sqlsrv_query($con, "SELECT [order] FROM db_laborat.tbl_orderchild WHERE id_matching = ? AND [order] <> ?", [$data['id'], $data['no_order']]); ?>
        <td rowspan="2" style="height: 98%;"><a class="hurufvertical"><strong>SAMPLE</strong></a></td>
        <td rowspan="5" colspan="3" valign="top"> 
          <?php if ($data['jenis_matching'] == "L/D") : ?>
            <strong style="font-size: 21px;">R</strong>EQUEST NO :
          <?php elseif ($data['jenis_matching'] == "LD NOW") : ?>
            <strong style="font-size: 21px;">R</strong>EQUEST NO :
          <?php else : ?>
            <strong style="font-size: 21px;">NO.</strong>ORDER :
          <?php endif; ?>
            <?php echo $data['no_order'] ?>,
            <?php if ($sqlOrder) { while ($order = sqlsrv_fetch_array($sqlOrder, SQLSRV_FETCH_ASSOC)) {
              echo $order['order'] . ', ';
            }
            sqlsrv_free_stmt($sqlOrder);
            } ?>
            <div align="right"><strong style="font-size: 21px;">
                <?php if ($data['salesman_sample'] == "1") {
                  echo "S/S";
                } ?></strong>
            </div>
            QTY ORDER : <strong><?Php echo $data['qty_order']; ?></strong>
        </td>
        <td width="4%" align="center">&nbsp;</td>
        <td width="4%" align="center">&nbsp;</td>
        <td width="4%" rowspan="2" align="left">&nbsp;</td>
        <td width="5%" rowspan="2" align="center">&nbsp;</td>
        <td rowspan="5" align="center">&nbsp;</td>
        <td rowspan="5" align="center">&nbsp;</td>
        <td rowspan="5" align="center">&nbsp;</td>
        <td rowspan="5" align="center">&nbsp;</td>
        <td rowspan="5" align="center">&nbsp;</td>
        <td rowspan="5" align="center">&nbsp;</td>
        <td rowspan="5" align="center">&nbsp;</td>
        <td rowspan="5" align="center">&nbsp;</td>
        <td rowspan="5" align="center">&nbsp;</td>
        <td rowspan="5" align="center">&nbsp;</td>
        <td rowspan="5" align="center">&nbsp;</td>
      </tr>
      <!-- 
        <tr>
          <td style="border-bottom: 0px; border-top:0px;" align="center"><a style="font-size: 8px;">&nbsp;</a></td>
          <td style="border-bottom: 0px; border-top:0px;" align="center"><a style="font-size: 8px;">&nbsp;</td>
        </tr>
        <tr>
          <td style="border-bottom: 0px; border-top:0px;" align="center"><a style="font-size: 8px;">&nbsp;</a></td>
          <td style="border-bottom: 0px; border-top:0px;" width="2%" align="center">&nbsp;</td>
          <td style="border-bottom: 0px; border-top:0px;" width="4%" align="left">&nbsp;</td>
          <td style="border-bottom: 0px; border-top:0px;" width="5%" align="center">&nbsp;</td>
        </tr>
        <tr>
          <td style="border-bottom: 0px; border-top:0px;" align="center">&nbsp;</td>
          <td style="border-bottom: 0px; border-top:0px;" align="center">&nbsp;</td>
          <td style="border-bottom: 0px; border-top:0px;" rowspan="2" align="left">&nbsp;</td>
          <td style="border-bottom: 0px; border-top:0px;" rowspan="2" align="center">&nbsp;</td>
        </tr>
        <tr>
          <td style="border-bottom: 0px; border-top:0px;" align="center">&nbsp;</td>
          <td style="border-bottom: 0px; border-top:0px;" align="center">&nbsp;</td>
        </tr>
        <tr>
          <td style="border-bottom: 0px; border-top:0px;" align="center">&nbsp;</td>
          <td style="border-bottom: 0px; border-top:0px;" align="center">&nbsp;</td>
          <td style="border-bottom: 0px; border-top:0px;" rowspan="2" align="left">&nbsp;</td>
          <td style="border-bottom: 0px; border-top:0px;" rowspan="2" align="center">&nbsp;</td>
        </tr>
        <tr>
          <td style="border-bottom: 0px; border-top:0px;" align="center">&nbsp;asdsadsad</td>
          <td style="border-bottom: 0px; border-top:0px;" align="center">&nbsp;asdsad</td>
        </tr>
        <tr>
          <td colspan="5" rowspan="3" valign="top" style="height: 0.65in;"></td>
          <td rowspan="3" align="center">&nbsp;</td>
          <td rowspan="3" align="center">&nbsp;</td>
          <td style="border-bottom: 0px;" align="left"><a style="font-size: 9px;">&nbsp;</a></td>
          <td style="border-bottom: 0px;" align="center">&nbsp;</td>
          <td rowspan="3" align="center">&nbsp;</td>
          <td rowspan="3" align="center">&nbsp;</td>
          <td rowspan="3" align="center">&nbsp;</td>
          <td rowspan="3" align="center">&nbsp;</td>
          <td rowspan="3" align="center">&nbsp;</td>
          <td rowspan="3" align="center">&nbsp;</td>
          <td rowspan="3" align="center">&nbsp;</td>
          <td rowspan="3" align="center">&nbsp;</td>
          <td rowspan="3" align="center">&nbsp;</td>
          <td rowspan="3" align="center">&nbsp;</td>
          <td rowspan="3" align="center">&nbsp;</td>
        </tr>
        <tr>
          <td style="border-bottom: 0px; border-top:0px;" align="left"><a style="font-size: 9px;">&nbsp;</a></td>
          <td style="border-bottom: 0px; border-top:0px;" align="center">&nbsp;</td>
        </tr>
        <tr>
          <td style=" border-top:0px;" align="left">&nbsp;</td>
          <td style="border-top:0px;" align="center">&nbsp;</td>
        </tr> 
      -->
    </tbody>
    <hr>
  </table>
  <!-- <br>
  <?php if ($data['jenis_matching'] == "LD NOW" OR $data['jenis_matching'] == "L/D") : ?>
    <table width="100%" border="0" class="table-list1">
      <tbody>
        <tr>
          <td rowspan="12" style="width: 15px;"><a class="hurufvertical"><strong>SAMPLE</strong></a></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
        </tr>
        <tr>
          <td style="height: 15px;"></td>
          <td></td>
          <td></td>
          <td></td>
          
          <td style="height: 15px;"></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td style="height: 140px;"></td>
          <td></td>
          <td></td>
          <td></td>
          
          <td style="height: 140px;"></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
        
        <tr>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
        </tr>
        <tr>
          <td style="height: 15px;"></td>
          <td></td>
          <td></td>
          <td></td>
          
          <td style="height: 15px;"></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td style="height: 140px;"></td>
          <td></td>
          <td></td>
          <td></td>
          
          <td style="height: 140px;"></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
        
        <tr>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
        </tr>
        <tr>
          <td style="height: 15px;"></td>
          <td></td>
          <td></td>
          <td></td>
          
          <td style="height: 15px;"></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td style="height: 160px;"></td>
          <td></td>
          <td></td>
          <td></td>
          
          <td style="height: 160px;"></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
        
        <tr>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 15px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
        </tr>
        <tr>
          <td style="height: 15px;"></td>
          <td></td>
          <td></td>
          <td></td>
          
          <td style="height: 15px;"></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td style="height: 160px;"></td>
          <td></td>
          <td></td>
          <td></td>
          
          <td style="height: 160px;"></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
      </tbody>
    </table>
  <?php else : ?>
    <table width="100%" border="0" class="table-list1">
      <tbody>
        <tr>
          <td rowspan="6" style="width: 15px;"><a class="hurufvertical"><strong>SAMPLE</strong></a></td>
          <td style="height: 30px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 30px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 30px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 30px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
        </tr>
        <tr>
          <td style="height: 30px;"><strong></strong></td>
          <td><strong></strong></td>
          <td><strong></strong></td>
          <td><strong></strong></td>
        </tr>
        <tr>
          <td style="height: 320px;"><strong></strong></td>
          <td><strong></strong></td>
          <td><strong></strong></td>
          <td><strong></strong></td>
        </tr>
        
        <tr>
          <td style="height: 30px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 30px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 30px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
          <td style="height: 30px;" align="center"><span style="font-size:20px;">&nbsp;</span></td>
        </tr>
        <tr>
          <td style="height: 30px;"><strong></strong></td>
          <td><strong></strong></td>
          <td><strong></strong></td>
          <td><strong></strong></td>
        </tr>
        <tr>
          <td style="height: 320px;"><strong></strong></td>
          <td><strong></strong></td>
          <td><strong></strong></td>
          <td><strong></strong></td>
        </tr>
      </tbody>
    </table>
  <?php endif; ?> -->
</body>

</html>
<script>
  // setTimeout(function() {
  //   window.print()
  // }, 1500);
</script>
