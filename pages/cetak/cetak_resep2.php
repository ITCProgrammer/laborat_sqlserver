<?php
    ini_set("error_reporting", 1);
    session_start();
    include "../../koneksi.php";
    $ids = $_GET['ids'];
    $idm = $_GET['idm'];
    $ip_num = $_SERVER['REMOTE_ADDR'];
    $time = date('Y-m-d H:i:s');
    sqlsrv_query(
        $con,
        "INSERT INTO db_laborat.log_status_matching (ids, status, info, do_by, do_at, ip_address)
         VALUES (?, ?, ?, ?, ?, ?)",
        [$idm, 'print', 'cetak resep', $_SESSION['userLAB'] ?? '', $time, $ip_num]
    );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!-- <link href="styles_cetak.css" rel="stylesheet" type="text/css"> -->
    <title>Cetak Form Tempelan Laborat</title>
</head>
<style>
    body,
    td,
    th {
        /*font-family: Courier New, Courier, monospace; */
        font-family: sans-serif, Roman, serif;
        font-size: 8pt;
    }

    pre {
        font-family: sans-serif, Roman, serif;
        clear: both;
        margin: 0px auto 0px;
        padding: 0px;
        white-space: pre-wrap;
        /* Since CSS 2.1 */
        white-space: -moz-pre-wrap;
        /* Mozilla, since 1999 */
        white-space: -pre-wrap;
        /* Opera 4-6 */
        white-space: -o-pre-wrap;
        /* Opera 7 */
        word-wrap: break-word;
    }

    body {
        margin: 0px auto 0px;
        padding: 2px;
        font-size: 8pt;
        color: #000;
        width: 98%;
        background-position: top;
        background-color: #fff;
    }

    .table-list {
        clear: both;
        text-align: left;
        border-collapse: collapse;
        margin: 0px 0px 10px 0px;
        background: #fff;
    }

    .table-list td {
        color: #333;
        font-size: 8pt;
        border-color: #fff;
        border-collapse: collapse;
        vertical-align: center;
        padding: 3px 5px;
        border-bottom: 1px #000000 solid;
        border-left: 1px #000000 solid;
        border-right: 1px #000000 solid;


    }

    .table-list1 {
        clear: both;
        text-align: left;
        border-collapse: collapse;
        margin: 0px 0px 5px 0px;
        background: #fff;
    }

    .table-list1 td {
        color: #333;
        font-size: 8pt;
        border-color: #fff;
        border-collapse: collapse;
        vertical-align: center;
        padding: 1px 3px;
        border-bottom: 1px #000000 solid;
        border-top: 1px #000000 solid;
        border-left: 1px #000000 solid;
        border-right: 1px #000000 solid;


    }

    #nocetak {
        display: none;
    }

    @page {
        size: F4;
        margin: 10px 10px 10px 10px;
        font-size: 8pt !important;
        font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
    }

    @media print {
        @page {
            size: F4;
            margin: 10px 10px 10px 10px;
            font-size: 8pt !important;
        }

        html,
        body {
            height: 330mm;
            width: 210mm;
            background: #FFF;
            overflow: visible;
        }

        /* body {
            padding-top: 15mm;
        } */

        .table-ttd {
            border-collapse: collapse;
            width: 100%;
            font-size: 8pt !important;
        }

        .table-ttd tr,
        .table-ttd tr td {
            border: 0.5px solid black;
            padding: 4px;
            padding: 4px;
            font-size: 8pt !important;
        }
    }

    .table-ttd {
        border-collapse: collapse;
        width: 100%;
        font-size: 8pt !important;
    }

    .table-ttd tr,
    .table-ttd tr td {
        border: 1px solid black;
        padding: 5px;
        padding: 5px;
        font-size: 8pt !important;
    }

    tr {
        /* page-break-before: always; */
        /* page-break-inside: avoid; */
        /* font-size: 8pt !important; */
    }

    .tablee td,
    .tablee th {
        /* border: 1px solid black; */
        padding: 5px;
        font-size: 8pt !important;

    }

    .rotation {
        transform: rotate(-90deg);
        /* Legacy vendor prefixes that you probably don't need... */
        /* Safari */
        -webkit-transform: rotate(-90deg);
        /* Firefox */
        -moz-transform: rotate(-90deg);
        /* IE */
        -ms-transform: rotate(-90deg);
        /* Opera */
        -o-transform: rotate(-90deg);
        /* Internet Explorer */
        filter: progid:DXImageTransform.Microsoft.BasicImage(rotation=3);
    }

    ul,
    li {
        list-style-type: none;
        font-size: 8pt !important;
    }

    .tablee tr:nth-child(even) {
        background-color: #f2f2f2;
        font-size: 8pt !important;
    }

    .table-ttd thead tr td,
    #tr-footer {
        font-weight: bold;
    }

    .tablee th {
        padding-top: 1ptpx;
        padding-bottom: 12px;
        text-align: left;
        background-color: #4CAF50;
        color: white;
        font-size: 8pt !important;
    }
</style>
<style media="print">
    @page {
        size: auto;
        margin: 15px;
    }

    html,
    body {
        height: 100%;
    }
</style>

<body>
    <?php
        $qry = sqlsrv_query($con,"SELECT TOP (1) * , a.id as id_status, b.id as id_matching
                                    from db_laborat.tbl_status_matching a 
                                    join db_laborat.tbl_matching b on a.idm = b.no_resep 
                                    where a.id = '$ids'
                                    ORDER BY a.id desc");
        $data = sqlsrv_fetch_array($qry, SQLSRV_FETCH_ASSOC);
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if ($value instanceof DateTimeInterface) {
                    $data[$key] = $value->format('Y-m-d H:i:s');
                }
            }
        }
        // Default guards to avoid undefined variables in later sections
        for ($i = 1; $i <= 27; $i++) {
            if (!isset(${"kode_lama".$i})) {
                ${"kode_lama".$i} = '';
            }
            if (!isset(${"kdbr".$i}) || !is_array(${"kdbr".$i})) {
                ${"kdbr".$i} = ['Product_Name' => ''];
            } else {
                ${"kdbr".$i} += ['Product_Name' => ''];
            }
        }
    ?>
    <br>
    <!--<div align="right" style="font-size: 12px;">FW-12-LAB-04</div>-->
    <table width="100%" border="0" class="table-list1">
        <tr>
          <td style="border-right:0px #000000 solid;">Recipe Code</td>
          <td style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
          <td style="border-left:0px #000000 solid;"><strong><?Php echo $data['recipe_code']; ?></strong></td>
          <td style="border-right:0px #000000 solid;">Color Code</td>
          <td style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
          <td style="border-left:0px #000000 solid;"><strong><?Php echo $data['color_code']; ?></strong></td>
          <td colspan="3" style="text-align: right;" ><span style="font-size: 9px;">FW-12-LAB-04</span></td>
        </tr>
        <tr>
            <td width="9%" style="border-right:0px #000000 solid;">Suffix</td>
            <td width="1%" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
            <td width="20%" style="border-left:0px #000000 solid;"><strong><?Php echo $data['no_resep']; ?></strong></td>
            <td width="10%" style="border-right:0px #000000 solid;">LAB DIP No</td>
            <td width="1%" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
            <td width="31%" style="border-left:0px #000000 solid;"><strong><?Php echo $data['no_warna']; ?></strong></td>
            <td width="15%" style="border-right:0px #000000 solid;">Gramasi Aktual</td>
            <td width="1%" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
            <td width="12%" style="border-left:0px #000000 solid;"><strong><?Php if ($data['lebar_aktual'] != "") {
                                                                                echo floatval($data['lebar_aktual']) . " x " . floatval($data['gramasi_aktual']) . " gr/m2";
                                                                            } else {
                                                                                echo "<font color=white>" . floatval($data['lebar_aktual']) . "</font>&nbsp;&nbsp;&nbsp;&nbsp; x <font color=white> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . floatval($data['gramasi_aktual']) . "</font> gr/m2";
                                                                            } ?></strong></td>
        </tr>
        <tr>
            <td style="border-right:0px #000000 solid;">Tanggal</td>
            <td style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
            <td style="border-left:0px #000000 solid;"><strong><?php echo date("Y-m-d"); ?></strong></td>
            <td style="border-right:0px #000000 solid;">Warna</td>
            <td style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
            <td style="border-left:0px #000000 solid;"><strong><?Php echo $data['warna']; ?></strong></td>
            <td style="border-right:0px #000000 solid;">Gramasi Permintaan</td>
            <td style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
            <td style="border-left:0px #000000 solid;"><strong><?Php echo floatval($data['lebar']) . " x " . floatval($data['gramasi']) . " gr/m2"; ?></strong></td>
        </tr>
        <tr>
            <td style="border-right:0px #000000 solid;">Item</td>
            <td style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
            <td style="border-left:0px #000000 solid;"><strong><?Php echo $data['no_item']; ?></strong></td>
            <td style="border-right:0px #000000 solid;">Langganan</td>
            <td style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
            <td style="border-left:0px #000000 solid;"><?Php echo $data['langganan']; ?></td>
            <td style="border-right:0px #000000 solid;">% Kadar Air</td>
            <td style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
            <td style="border-left:0px #000000 solid;"><strong><?Php echo floatval($data['kadar_air']); ?> %</strong></td>
        </tr>
        <tr>
            <td style="border-right:0px #000000 solid;">PO Greige</td>
            <td style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
            <td style="border-left:0px #000000 solid;"><strong><?Php echo $data['no_po']; ?></strong></td>
            <td style="border-right:0px #000000 solid;"><?php if ($data['jenis_matching'] != 'L/D') {
                                                            echo 'No. Order';
                                                        } else {
                                                            echo 'Request No';
                                                        } ?></td>
            <td style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
            <td style="border-left:0px #000000 solid;"><?Php echo $data['no_order']; ?></td>
            <td style="border-right:0px #000000 solid;">Jml Percobaan</td>
            <td style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
            <td style="border-left:0px #000000 solid;"><strong><?Php echo $data['percobaan_ke']; ?></strong></td>
        </tr>
        <tr style="height: 0.4in">
            <td valign="top" style="border-right:0px #000000 solid;">Jenis Kain</td>
            <td valign="top" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
            <td valign="top" style="border-left:0px #000000 solid;"><?Php echo $data['jenis_kain']; ?></td>
            <td valign="top" style="border-right:0px #000000 solid;">Benang</td>
            <td valign="top" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
            <td valign="top" style="border-left:0px #000000 solid;"><?Php echo $data['benang']; ?></td>
            <td valign="top" style="border-right:0px #000000 solid;">Cocok Warna</td>
            <td valign="top" style="border-right:0px #000000 solid; border-left:0px #000000 solid;">:</td>
            <td valign="top" style="border-left:0px #000000 solid;"><?Php echo $data['cocok_warna']; ?></td>
        </tr>
    </table>
    <table width="100%" border="1" class="table-list1">
        <tr style="height: 0.3in">
            <td width="8%" align="center"><strong>KODE</strong></td>
            <td width="10%" align="center"><strong>ERP KODE</strong></td>
            <td width="5%" align="center"><strong>LAB</td>
            <td width="5%" align="center"><strong>Adj-1</td>
            <td width="5%" align="center"><strong>Adj-2</strong></td>
            <td width="5%" align="center"><strong>Adj-3</strong></td>
            <td width="5%" align="center"><strong>Adj-4</strong></td>
            <td width="5%" align="center"><strong>Adj-5</strong></td>
            <td width="5%" align="center"><strong>Adj-6</strong></td>
            <td width="5%" align="center"><strong>Adj-7</strong></td>
            <td colspan="2" align="center"><strong>BODY</strong></td>
        </tr>
        <!-- BARIS 1 -->
        <?php
            $resep1 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 1");
            $rsp1 = sqlsrv_fetch_array($resep1, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp1)) {
                $rsp1 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
            
            $KodeBaru = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp1[kode]'");
            $kdbr = sqlsrv_fetch_array($KodeBaru, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr)) {
                $kdbr = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }
            
            if($kdbr['code_new']){ 
                $kode_lama = $rsp1['kode'];
                $kode_baru = $kdbr['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp1[kode]'");
                $kdbr_now = sqlsrv_fetch_array($KodeBaru_now, SQLSRV_FETCH_ASSOC);
                if($kdbr_now['code'] && $kdbr_now['code_new']){
                    $kode_lama = $kdbr_now['code'];
                    $kode_baru = $kdbr_now['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama = $rsp1['kode'];
                    $kode_baru = $kdbr['code_new'];
                }
            }
            $kode_lama1 = isset($kode_lama) ? $kode_lama : ($rsp1['kode'] ?? '');
            if ($kode_lama1 === null) {
                $kode_lama1 = '';
            }
            $kode_lama1 = (string)$kode_lama1;
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr['ket'] == 'Suhu'){
                        echo $kdbr['Product_Name'];
                    }else{
                        echo $kode_baru;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp1['conc1']) != 0) echo floatval($rsp1['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp1['conc2']) != 0) echo floatval($rsp1['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp1['conc3']) != 0) echo floatval($rsp1['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp1['conc4']) != 0) echo floatval($rsp1['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp1['conc5']) != 0) echo floatval($rsp1['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp1['conc6']) != 0) echo floatval($rsp1['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp1['conc7']) != 0) echo floatval($rsp1['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp1['conc8']) != 0) echo floatval($rsp1['conc8']) ?></td>
            <?php
            $sql_Norder1 = sqlsrv_query($con,"SELECT [order] FROM db_laborat.tbl_orderchild 
            where id_matching = '$data[id_matching]' and id_status = '$data[id_status]' order by flag OFFSET 0 ROWS FETCH NEXT 50 ROWS ONLY");
            $iteration = 1;
            ?>
            <td colspan="2" rowspan="5" valign="top">
                <?php while ($no = sqlsrv_fetch_array($sql_Norder1, SQLSRV_FETCH_ASSOC)) { ?>
                    <?php echo $iteration++ . '.(' . $no['order'] ?>)&nbsp;&nbsp;&nbsp;
                <?php } ?>
				<div align="right"><strong style="font-size: 21px;"><?php if($data['salesman_sample']=="1"){ echo "S/S"; } ?></strong></div>
            </td>
        </tr>
        <!-- BARIS 2 -->
        <?php
            $resep2 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 2 
                                                                order by flag asc");
            $rsp2 = sqlsrv_fetch_array($resep2, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp2)) {
                $rsp2 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
            $KodeBaru2 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp2[kode]'");
            $kdbr2 = sqlsrv_fetch_array($KodeBaru2, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr2)) {
                $kdbr2 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr2 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }

            if($kdbr2['code_new']){ 
                $kode_lama2 = $rsp2['kode'];
                $kode_baru2 = $kdbr2['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now2 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp2[kode]'");
                $kdbr_now2 = sqlsrv_fetch_array($KodeBaru_now2, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now2)) {
                    $kdbr_now2 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now2 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now2['code'] && $kdbr_now2['code_new']){
                    $kode_lama2 = $kdbr_now2['code'];
                    $kode_baru2 = $kdbr_now2['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama2 = $rsp2['kode'];
                    $kode_baru2 = $kdbr2['code_new'];
                }
            }
            // JIKA KODE DYESTUFF SUHU MAKA YG DITAMPILKAN ADALAH PRODUCT_NAME
            
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama2; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr2['ket'] == 'Suhu'){
                        echo $kdbr2['Product_Name'];
                    }else{
                        echo $kode_baru2;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp2['conc1']) != 0) echo floatval($rsp2['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp2['conc2']) != 0) echo floatval($rsp2['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp2['conc3']) != 0) echo floatval($rsp2['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp2['conc4']) != 0) echo floatval($rsp2['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp2['conc5']) != 0) echo floatval($rsp2['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp2['conc6']) != 0) echo floatval($rsp2['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp2['conc7']) != 0) echo floatval($rsp2['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp2['conc8']) != 0) echo floatval($rsp2['conc8']) ?></td>
        </tr>
        <!-- BARIS 3 -->
        <?php
            $resep3 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 3 
                                                                order by flag asc");
            $rsp3 = sqlsrv_fetch_array($resep3, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp3)) {
                $rsp3 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
            
            $KodeBaru3 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp3[kode]'");
            $kdbr3 = sqlsrv_fetch_array($KodeBaru3, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr3)) {
                $kdbr3 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr3 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }

            if($kdbr3['code_new']){ 
                $kode_lama3 = $rsp3['kode'];
                $kode_baru3 = $kdbr3['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now3 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp3[kode]'");
                $kdbr_now3 = sqlsrv_fetch_array($KodeBaru_now3, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now3)) {
                    $kdbr_now3 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now3 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now3['code'] && $kdbr_now3['code_new']){
                    $kode_lama3 = $kdbr_now3['code'];
                    $kode_baru3 = $kdbr_now3['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama3 = $rsp3['kode'];
                    $kode_baru3 = $kdbr3['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama3; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr3['ket'] == 'Suhu'){
                        echo $kdbr3['Product_Name'];
                    }else{
                        echo $kode_baru3;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp3['conc1']) != 0) echo floatval($rsp3['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp3['conc2']) != 0) echo floatval($rsp3['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp3['conc3']) != 0) echo floatval($rsp3['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp3['conc4']) != 0) echo floatval($rsp3['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp3['conc5']) != 0) echo floatval($rsp3['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp3['conc6']) != 0) echo floatval($rsp3['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp3['conc7']) != 0) echo floatval($rsp3['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp3['conc8']) != 0) echo floatval($rsp3['conc8']) ?></td>
        </tr>
        <!-- BARIS 4 -->
        <?php
            $resep4 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 4 
                                                                order by flag asc");
            $rsp4 = sqlsrv_fetch_array($resep4, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp4)) {
                $rsp4 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
                    
            $KodeBaru4 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp4[kode]'");
            $kdbr4 = sqlsrv_fetch_array($KodeBaru4, SQLSRV_FETCH_ASSOC);	
            if (!is_array($kdbr4)) {
                $kdbr4 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr4 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }
            
            if($kdbr4['code_new']){ 
                $kode_lama4 = $rsp4['kode'];
                $kode_baru4 = $kdbr4['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now4 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp4[kode]'");
                $kdbr_now4 = sqlsrv_fetch_array($KodeBaru_now4, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now4)) {
                    $kdbr_now4 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now4 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now4['code'] && $kdbr_now4['code_new']){
                    $kode_lama4 = $kdbr_now4['code'];
                    $kode_baru4 = $kdbr_now4['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama4 = $rsp4['kode'];
                    $kode_baru4 = $kdbr4['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama4; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr4['ket'] == 'Suhu'){
                        echo $kdbr4['Product_Name'];
                    }else{
                        echo $kode_baru4;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp4['conc1']) != 0) echo floatval($rsp4['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp4['conc2']) != 0) echo floatval($rsp4['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp4['conc3']) != 0) echo floatval($rsp4['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp4['conc4']) != 0) echo floatval($rsp4['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp4['conc5']) != 0) echo floatval($rsp4['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp4['conc6']) != 0) echo floatval($rsp4['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp4['conc7']) != 0) echo floatval($rsp4['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp4['conc8']) != 0) echo floatval($rsp4['conc8']) ?></td>
        </tr>
        <!-- BARIS 5 -->
        <?php
            $resep5 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 5 
                                                                order by flag asc");
            $rsp5 = sqlsrv_fetch_array($resep5, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp5)) {
                $rsp5 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
                    
            $KodeBaru5 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp5[kode]'");
            $kdbr5 = sqlsrv_fetch_array($KodeBaru5, SQLSRV_FETCH_ASSOC);	
            if (!is_array($kdbr5)) {
                $kdbr5 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr5 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }
            
            if($kdbr5['code_new']){ 
                $kode_lama5 = $rsp5['kode'];
                $kode_baru5 = $kdbr5['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now5 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp5[kode]'");
                $kdbr_now5 = sqlsrv_fetch_array($KodeBaru_now5, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now5)) {
                    $kdbr_now5 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now5 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now5['code'] && $kdbr_now5['code_new']){
                    $kode_lama5 = $kdbr_now5['code'];
                    $kode_baru5 = $kdbr_now5['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama5 = $rsp5['kode'];
                    $kode_baru5 = $kdbr5['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama5; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr5['ket'] == 'Suhu'){
                        echo $kdbr5['Product_Name'];
                    }else{
                        echo $kode_baru5;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp5['conc1']) != 0) echo floatval($rsp5['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp5['conc2']) != 0) echo floatval($rsp5['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp5['conc3']) != 0) echo floatval($rsp5['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp5['conc4']) != 0) echo floatval($rsp5['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp5['conc5']) != 0) echo floatval($rsp5['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp5['conc6']) != 0) echo floatval($rsp5['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp5['conc7']) != 0) echo floatval($rsp5['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp5['conc8']) != 0) echo floatval($rsp5['conc8']) ?></td>
        </tr>
        <!-- BARIS 6 -->
        <?php
            $resep6 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 6 
                                                                order by flag asc");
            $rsp6 = sqlsrv_fetch_array($resep6, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp6)) {
                $rsp6 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
                    
            $KodeBaru6 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp6[kode]'");
            $kdbr6 = sqlsrv_fetch_array($KodeBaru6, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr6)) {
                $kdbr6 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr6 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }
            
            if($kdbr6['code_new']){ 
                $kode_lama6 = $rsp6['kode'];
                $kode_baru6 = $kdbr6['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now6 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp6[kode]'");
                $kdbr_now6 = sqlsrv_fetch_array($KodeBaru_now6, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now6)) {
                    $kdbr_now6 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now6 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now6['code'] && $kdbr_now6['code_new']){
                    $kode_lama6 = $kdbr_now6['code'];
                    $kode_baru6 = $kdbr_now6['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama6 = $rsp6['kode'];
                    $kode_baru6 = $kdbr6['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama6; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr6['ket'] == 'Suhu'){
                        echo $kdbr6['Product_Name'];
                    }else{
                        echo $kode_baru6;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp6['conc1']) != 0) echo floatval($rsp6['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp6['conc2']) != 0) echo floatval($rsp6['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp6['conc3']) != 0) echo floatval($rsp6['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp6['conc4']) != 0) echo floatval($rsp6['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp6['conc5']) != 0) echo floatval($rsp6['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp6['conc6']) != 0) echo floatval($rsp6['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp6['conc7']) != 0) echo floatval($rsp6['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp6['conc8']) != 0) echo floatval($rsp6['conc8']) ?></td>
            <td style="font-weight: bold;">Create Resep : <?php echo $data['create_resep'] ?></td>
            <td style="font-weight: bold;">Acc Tes Ulang OK : <?php echo $data['acc_ulang_ok'] ?></td>
        </tr>
        <!-- BARIS 7 -->
        <?php
            $resep7 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 7 
                                                                order by flag asc");
            $rsp7 = sqlsrv_fetch_array($resep7, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp7)) {
                $rsp7 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
                    
            $KodeBaru7 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp7[kode]'");
            $kdbr7 = sqlsrv_fetch_array($KodeBaru7, SQLSRV_FETCH_ASSOC);		
            if (!is_array($kdbr7)) {
                $kdbr7 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr7 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }

            if($kdbr7['code_new']){ 
                $kode_lama7 = $rsp7['kode'];
                $kode_baru7 = $kdbr7['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now7 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp7[kode]'");
                $kdbr_now7 = sqlsrv_fetch_array($KodeBaru_now7, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now7)) {
                    $kdbr_now7 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now7 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now7['code'] && $kdbr_now7['code_new']){
                    $kode_lama7 = $kdbr_now7['code'];
                    $kode_baru7 = $kdbr_now7['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama7 = $rsp7['kode'];
                    $kode_baru7 = $kdbr7['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama7; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr7['ket'] == 'Suhu'){
                        echo $kdbr7['Product_Name'];
                    }else{
                        echo $kode_baru7;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp7['conc1']) != 0) echo floatval($rsp7['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp7['conc2']) != 0) echo floatval($rsp7['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp7['conc3']) != 0) echo floatval($rsp7['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp7['conc4']) != 0) echo floatval($rsp7['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp7['conc5']) != 0) echo floatval($rsp7['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp7['conc6']) != 0) echo floatval($rsp7['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp7['conc7']) != 0) echo floatval($rsp7['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp7['conc8']) != 0) echo floatval($rsp7['conc8']) ?></td>
          <td style="font-weight: bold;">Acc Resep Pertama 1 : <?php echo $data['acc_resep1'] ?></td>
            <td valign="top"><span style="font-weight: bold;">Acc Resep Pertama 2 : <?php echo $data['acc_resep1'] ?></span></td>
        </tr>
        <!-- BARIS 8 -->
        <?php
            $resep8 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 8 
                                                                order by flag asc");
            $rsp8 = sqlsrv_fetch_array($resep8, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp8)) {
                $rsp8 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
            if (!is_array($rsp8)) {
                $rsp8 = [
                    'kode' => '',
                    'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0,
                    'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0
                ];
            }
            $kode8 = $rsp8['kode'];
            $kdbr8 = [];
            if ($kode8 !== '') {
                $KodeBaru8 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$kode8'");
                $kdbr8 = sqlsrv_fetch_array($KodeBaru8, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr8)) {
                    $kdbr8 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
                } else {
                    $kdbr8 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
                }
            }
            if (!is_array($kdbr8)) {
                $kdbr8 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr8 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }
            
            if($kdbr8['code_new']){ 
                $kode_lama8 = $kode8;
                $kode_baru8 = $kdbr8['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                if ($kode8 !== '') {
                    $KodeBaru_now8 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$kode8'");
                    $kdbr_now8 = sqlsrv_fetch_array($KodeBaru_now8, SQLSRV_FETCH_ASSOC);
                    if (!is_array($kdbr_now8)) {
                        $kdbr_now8 = ['code' => '', 'code_new' => ''];
                    } else {
                        $kdbr_now8 += ['code' => '', 'code_new' => ''];
                    }
                    if($kdbr_now8['code'] && $kdbr_now8['code_new']){
                        $kode_lama8 = $kdbr_now8['code'];
                        $kode_baru8 = $kdbr_now8['code_new'];
                    // JIKA KODE BARU KOSONG
                    }else{
                        $kode_lama8 = $kode8;
                        $kode_baru8 = $kdbr8['code_new'];
                    }
                } else {
                    $kode_lama8 = '';
                    $kode_baru8 = '';
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama8; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr8['ket'] == 'Suhu'){
                        echo $kdbr8['Product_Name'];
                    }else{
                        echo $kode_baru8;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp8['conc1']) != 0) echo floatval($rsp8['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp8['conc2']) != 0) echo floatval($rsp8['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp8['conc3']) != 0) echo floatval($rsp8['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp8['conc4']) != 0) echo floatval($rsp8['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp8['conc5']) != 0) echo floatval($rsp8['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp8['conc6']) != 0) echo floatval($rsp8['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp8['conc7']) != 0) echo floatval($rsp8['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp8['conc8']) != 0) echo floatval($rsp8['conc8']) ?></td>
            <td style="font-weight: bold;">Colorist 1 : <?php echo $data['colorist1'] ?></td>
            <td style="font-weight: bold;">Colorist 2 : <?php echo $data['colorist2'] ?></td>
        </tr>
        <!-- BARIS 9 -->
        <?php
            $resep9 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 9 
                                                                order by flag asc");
            $rsp9 = sqlsrv_fetch_array($resep9, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp9)) {
                $rsp9 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
            if (!is_array($rsp9)) {
                $rsp9 = [
                    'kode' => '',
                    'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0,
                    'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0
                ];
            }
            $kode9 = $rsp9['kode'];
            $kdbr9 = [];
            if ($kode9 !== '') {
                $KodeBaru9 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$kode9'");
                $kdbr9 = sqlsrv_fetch_array($KodeBaru9, SQLSRV_FETCH_ASSOC);	
                if (!is_array($kdbr9)) {
                    $kdbr9 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
                } else {
                    $kdbr9 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
                }
            }
            if (!is_array($kdbr9)) {
                $kdbr9 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr9 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }
            
            if($kdbr9['code_new']){ 
                $kode_lama9 = $kode9;
                $kode_baru9 = $kdbr9['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                if ($kode9 !== '') {
                    $KodeBaru_now9 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$kode9'");
                    $kdbr_now9 = sqlsrv_fetch_array($KodeBaru_now9, SQLSRV_FETCH_ASSOC);
                    if (!is_array($kdbr_now9)) {
                        $kdbr_now9 = ['code' => '', 'code_new' => ''];
                    } else {
                        $kdbr_now9 += ['code' => '', 'code_new' => ''];
                    }
                    if($kdbr_now9['code'] && $kdbr_now9['code_new']){
                        $kode_lama9 = $kdbr_now9['code'];
                        $kode_baru9 = $kdbr_now9['code_new'];
                    // JIKA KODE BARU KOSONG
                    }else{
                        $kode_lama9 = $kode9;
                        $kode_baru9 = $kdbr9['code_new'];
                    }
                } else {
                    $kode_lama9 = '';
                    $kode_baru9 = '';
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama9; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr9['ket'] == 'Suhu'){
                        echo $kdbr9['Product_Name'];
                    }else{
                        echo $kode_baru9;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp9['conc1']) != 0) echo floatval($rsp9['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp9['conc2']) != 0) echo floatval($rsp9['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp9['conc3']) != 0) echo floatval($rsp9['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp9['conc4']) != 0) echo floatval($rsp9['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp9['conc5']) != 0) echo floatval($rsp9['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp9['conc6']) != 0) echo floatval($rsp9['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp9['conc7']) != 0) echo floatval($rsp9['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp9['conc8']) != 0) echo floatval($rsp9['conc8']) ?></td>
            <td colspan="2" rowspan="2" align="center"><strong>LAB. SAMPLE</strong></td>
        </tr>
        <!-- BARIS 10 -->
        <?php
            $resep10 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 10 
                                                                order by flag asc");
            $rsp10 = sqlsrv_fetch_array($resep10, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp10)) {
                $rsp10 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
                    
            $KodeBaru10 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp10[kode]'");
            $kdbr10 = sqlsrv_fetch_array($KodeBaru10, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr10)) {
                $kdbr10 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr10 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }
            
            if($kdbr10['code_new']){ 
                $kode_lama10 = $rsp10['kode'];
                $kode_baru10 = $kdbr10['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now10 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp10[kode]'");
                $kdbr_now10 = sqlsrv_fetch_array($KodeBaru_now10, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now10)) {
                    $kdbr_now10 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now10 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now10['code'] && $kdbr_now10['code_new']){
                    $kode_lama10 = $kdbr_now10['code'];
                    $kode_baru10 = $kdbr_now10['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama10 = $rsp10['kode'];
                    $kode_baru10 = $kdbr10['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama10; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr10['ket'] == 'Suhu'){
                        echo $kdbr10['Product_Name'];
                    }else{
                        echo $kode_baru10;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp10['conc1']) != 0) echo floatval($rsp10['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp10['conc2']) != 0) echo floatval($rsp10['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp10['conc3']) != 0) echo floatval($rsp10['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp10['conc4']) != 0) echo floatval($rsp10['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp10['conc5']) != 0) echo floatval($rsp10['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp10['conc6']) != 0) echo floatval($rsp10['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp10['conc7']) != 0) echo floatval($rsp10['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp10['conc8']) != 0) echo floatval($rsp10['conc8']) ?></td>
        </tr>
        <!-- BARIS 11 -->
        <?php
            $resep11 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 11 
                                                                order by flag asc");
            $rsp11 = sqlsrv_fetch_array($resep11, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp11)) {
                $rsp11 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
                    
            $KodeBaru11 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp11[kode]'");
            $kdbr11 = sqlsrv_fetch_array($KodeBaru11, SQLSRV_FETCH_ASSOC);		
            if (!is_array($kdbr11)) {
                $kdbr11 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr11 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }

            if($kdbr11['code_new']){ 
                $kode_lama11 = $rsp11['kode'];
                $kode_baru11 = $kdbr11['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now11 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp11[kode]'");
                $kdbr_now11 = sqlsrv_fetch_array($KodeBaru_now11, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now11)) {
                    $kdbr_now11 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now11 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now11['code'] && $kdbr_now11['code_new']){
                    $kode_lama11 = $kdbr_now11['code'];
                    $kode_baru11 = $kdbr_now11['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama11 = $rsp11['kode'];
                    $kode_baru11 = $kdbr11['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama11; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr11['ket'] == 'Suhu'){
                        echo $kdbr11['Product_Name'];
                    }else{
                        echo $kode_baru11;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp11['conc1']) != 0) echo floatval($rsp11['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp11['conc2']) != 0) echo floatval($rsp11['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp11['conc3']) != 0) echo floatval($rsp11['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp11['conc4']) != 0) echo floatval($rsp11['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp11['conc5']) != 0) echo floatval($rsp11['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp11['conc6']) != 0) echo floatval($rsp11['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp11['conc7']) != 0) echo floatval($rsp11['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp11['conc8']) != 0) echo floatval($rsp11['conc8']) ?></td>
            <?php
            $sql_Norder1 = sqlsrv_query($con,"SELECT [order] FROM db_laborat.tbl_orderchild 
            where id_matching = '$data[id_matching]' and id_status = '$data[id_status]' order by flag OFFSET 51 ROWS FETCH NEXT 100 ROWS ONLY");
            $iteration = 1;
            ?>
            <td colspan="2" rowspan="10" valign="top">
                <?php while ($no = sqlsrv_fetch_array($sql_Norder1, SQLSRV_FETCH_ASSOC)) { ?>
                    <?php echo $iteration++ . '.(' . $no['order']; ?>)&nbsp;&nbsp;&nbsp;
                <?php } ?>
            </td>
        </tr>
        <!-- BARIS 12 -->
        <?php
            $resep12 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 12
                                                                order by flag asc");
            $rsp12 = sqlsrv_fetch_array($resep12, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp12)) {
                $rsp12 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
            
            $KodeBaru12 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp12[kode]'");
            $kdbr12 = sqlsrv_fetch_array($KodeBaru12, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr12)) {
                $kdbr12 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr12 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }

            if($kdbr12['code_new']){ 
                $kode_lama12 = $rsp12['kode'];
                $kode_baru12 = $kdbr12['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now12 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp12[kode]'");
                $kdbr_now12 = sqlsrv_fetch_array($KodeBaru_now12, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now12)) {
                    $kdbr_now12 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now12 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now12['code'] && $kdbr_now12['code_new']){
                    $kode_lama12 = $kdbr_now12['code'];
                    $kode_baru12 = $kdbr_now12['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama12 = $rsp12['kode'];
                    $kode_baru12 = $kdbr12['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama12; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr12['ket'] == 'Suhu'){
                        echo $kdbr12['Product_Name'];
                    }else{
                        echo $kode_baru12;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp12['conc1']) != 0) echo floatval($rsp12['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp12['conc2']) != 0) echo floatval($rsp12['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp12['conc3']) != 0) echo floatval($rsp12['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp12['conc4']) != 0) echo floatval($rsp12['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp12['conc5']) != 0) echo floatval($rsp12['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp12['conc6']) != 0) echo floatval($rsp12['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp12['conc7']) != 0) echo floatval($rsp12['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp12['conc8']) != 0) echo floatval($rsp12['conc8']) ?></td>
        </tr>
        <!-- BARIS 13 -->
        <?php
            $resep13 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 13 
                                                                order by flag asc");
            $rsp13 = sqlsrv_fetch_array($resep13, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp13)) {
                $rsp13 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
                    
            $KodeBaru13 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp13[kode]'");
            $kdbr13 = sqlsrv_fetch_array($KodeBaru13, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr13)) {
                $kdbr13 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr13 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }

            if($kdbr13['code_new']){ 
                $kode_lama13 = $rsp13['kode'];
                $kode_baru13 = $kdbr13['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now13 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp13[kode]'");
                $kdbr_now13 = sqlsrv_fetch_array($KodeBaru_now13, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now13)) {
                    $kdbr_now13 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now13 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now13['code'] && $kdbr_now13['code_new']){
                    $kode_lama13 = $kdbr_now13['code'];
                    $kode_baru13 = $kdbr_now13['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama13 = $rsp13['kode'];
                    $kode_baru13 = $kdbr13['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama13; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr13['ket'] == 'Suhu'){
                        echo $kdbr13['Product_Name'];
                    }else{
                        echo $kode_baru13;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp13['conc1']) != 0) echo floatval($rsp13['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp13['conc2']) != 0) echo floatval($rsp13['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp13['conc3']) != 0) echo floatval($rsp13['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp13['conc4']) != 0) echo floatval($rsp13['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp13['conc5']) != 0) echo floatval($rsp13['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp13['conc6']) != 0) echo floatval($rsp13['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp13['conc7']) != 0) echo floatval($rsp13['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp13['conc8']) != 0) echo floatval($rsp13['conc8']) ?></td>
        </tr>
        <!-- BARIS 14 -->
        <?php
            $resep14 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 14 
                                                                order by flag asc");
            $rsp14 = sqlsrv_fetch_array($resep14, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp14)) {
                $rsp14 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
                    
            $KodeBaru14 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp14[kode]'");
            $kdbr14 = sqlsrv_fetch_array($KodeBaru14, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr14)) {
                $kdbr14 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr14 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }

            if($kdbr14['code_new']){ 
                $kode_lama14 = $rsp14['kode'];
                $kode_baru14 = $kdbr14['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now14 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp14[kode]'");
                $kdbr_now14 = sqlsrv_fetch_array($KodeBaru_now14, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now14)) {
                    $kdbr_now14 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now14 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now14['code'] && $kdbr_now14['code_new']){
                    $kode_lama14 = $kdbr_now14['code'];
                    $kode_baru14 = $kdbr_now14['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama14 = $rsp14['kode'];
                    $kode_baru14 = $kdbr14['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama14; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr14['ket'] == 'Suhu'){
                        echo $kdbr14['Product_Name'];
                    }else{
                        echo $kode_baru14;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp14['conc1']) != 0) echo floatval($rsp14['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp14['conc2']) != 0) echo floatval($rsp14['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp14['conc3']) != 0) echo floatval($rsp14['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp14['conc4']) != 0) echo floatval($rsp14['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp14['conc5']) != 0) echo floatval($rsp14['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp14['conc6']) != 0) echo floatval($rsp14['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp14['conc7']) != 0) echo floatval($rsp14['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp14['conc8']) != 0) echo floatval($rsp14['conc8']) ?></td>
        </tr>
        <!-- BARIS 15 -->
        <?php
            $resep15 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 15 
                                                                order by flag asc");
            $rsp15 = sqlsrv_fetch_array($resep15, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp15)) {
                $rsp15 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
                    
            $KodeBaru15 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp15[kode]'");
            $kdbr15 = sqlsrv_fetch_array($KodeBaru15, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr15)) {
                $kdbr15 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr15 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }

            if($kdbr15['code_new']){ 
                $kode_lama15 = $rsp15['kode'];
                $kode_baru15 = $kdbr15['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now15 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp15[kode]'");
                $kdbr_now15 = sqlsrv_fetch_array($KodeBaru_now15, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now15)) {
                    $kdbr_now15 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now15 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now15['code'] && $kdbr_now15['code_new']){
                    $kode_lama15 = $kdbr_now15['code'];
                    $kode_baru15 = $kdbr_now15['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama15 = $rsp15['kode'];
                    $kode_baru15 = $kdbr15['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama15; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr15['ket'] == 'Suhu'){
                        echo $kdbr15['Product_Name'];
                    }else{
                        echo $kode_baru15;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp15['conc1']) != 0) echo floatval($rsp15['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp15['conc2']) != 0) echo floatval($rsp15['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp15['conc3']) != 0) echo floatval($rsp15['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp15['conc4']) != 0) echo floatval($rsp15['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp15['conc5']) != 0) echo floatval($rsp15['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp15['conc6']) != 0) echo floatval($rsp15['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp15['conc7']) != 0) echo floatval($rsp15['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp15['conc8']) != 0) echo floatval($rsp15['conc8']) ?></td>
        </tr>
        <!-- BARIS 16 -->
        <?php
            $resep16 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 16 
                                                                order by flag asc");
            $rsp16 = sqlsrv_fetch_array($resep16, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp16)) {
                $rsp16 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
                    
            $KodeBaru16 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp16[kode]'");
            $kdbr16 = sqlsrv_fetch_array($KodeBaru16, SQLSRV_FETCH_ASSOC);	
            if (!is_array($kdbr16)) {
                $kdbr16 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr16 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }
            
            if($kdbr16['code_new']){ 
                $kode_lama16 = $rsp16['kode'];
                $kode_baru16 = $kdbr16['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now16 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp16[kode]'");
                $kdbr_now16 = sqlsrv_fetch_array($KodeBaru_now16, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now16)) {
                    $kdbr_now16 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now16 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now16['code'] && $kdbr_now16['code_new']){
                    $kode_lama16 = $kdbr_now16['code'];
                    $kode_baru16 = $kdbr_now16['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama16 = $rsp16['kode'];
                    $kode_baru16 = $kdbr16['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama16; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr16['ket'] == 'Suhu'){
                        echo $kdbr16['Product_Name'];
                    }else{
                        echo $kode_baru16;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp16['conc1']) != 0) echo floatval($rsp16['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp16['conc2']) != 0) echo floatval($rsp16['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp16['conc3']) != 0) echo floatval($rsp16['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp16['conc4']) != 0) echo floatval($rsp16['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp16['conc5']) != 0) echo floatval($rsp16['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp16['conc6']) != 0) echo floatval($rsp16['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp16['conc7']) != 0) echo floatval($rsp16['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp16['conc8']) != 0) echo floatval($rsp16['conc8']) ?></td>
        </tr>
        <!-- BARIS 17 -->
        <?php
            $resep17 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 17 
                                                                order by flag asc");
            $rsp17 = sqlsrv_fetch_array($resep17, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp17)) {
                $rsp17 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
                    
            $KodeBaru17 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp17[kode]'");
            $kdbr17 = sqlsrv_fetch_array($KodeBaru17, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr17)) {
                $kdbr17 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr17 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }
            
            if($kdbr17['code_new']){ 
                $kode_lama17 = $rsp17['kode'];
                $kode_baru17 = $kdbr17['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now17 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp17[kode]'");
                $kdbr_now17 = sqlsrv_fetch_array($KodeBaru_now17, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now17)) {
                    $kdbr_now17 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now17 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now17['code'] && $kdbr_now17['code_new']){
                    $kode_lama17 = $kdbr_now17['code'];
                    $kode_baru17 = $kdbr_now17['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama17 = $rsp17['kode'];
                    $kode_baru17 = $kdbr17['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama17; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr17['ket'] == 'Suhu'){
                        echo $kdbr17['Product_Name'];
                    }else{
                        echo $kode_baru17;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp17['conc1']) != 0) echo floatval($rsp17['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp17['conc2']) != 0) echo floatval($rsp17['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp17['conc3']) != 0) echo floatval($rsp17['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp17['conc4']) != 0) echo floatval($rsp17['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp17['conc5']) != 0) echo floatval($rsp17['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp17['conc6']) != 0) echo floatval($rsp17['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp17['conc7']) != 0) echo floatval($rsp17['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp17['conc8']) != 0) echo floatval($rsp17['conc8']) ?></td>
        </tr>
        <!-- BARIS 18 -->
        <?php
            $resep18 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 18 
                                                                order by flag asc");
            $rsp18 = sqlsrv_fetch_array($resep18, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp18)) {
                $rsp18 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
                    
            $KodeBaru18 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp18[kode]'");
            $kdbr18 = sqlsrv_fetch_array($KodeBaru18, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr18)) {
                $kdbr18 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr18 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }
            
            if($kdbr18['code_new']){ 
                $kode_lama18 = $rsp18['kode'];
                $kode_baru18 = $kdbr18['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now18 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp18[kode]'");
                $kdbr_now18 = sqlsrv_fetch_array($KodeBaru_now18, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now18)) {
                    $kdbr_now18 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now18 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now18['code'] && $kdbr_now18['code_new']){
                    $kode_lama18 = $kdbr_now18['code'];
                    $kode_baru18 = $kdbr_now18['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama18 = $rsp18['kode'];
                    $kode_baru18 = $kdbr18['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama18; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr18['ket'] == 'Suhu'){
                        echo $kdbr18['Product_Name'];
                    }else{
                        echo $kode_baru18;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp18['conc1']) != 0) echo floatval($rsp18['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp18['conc2']) != 0) echo floatval($rsp18['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp18['conc3']) != 0) echo floatval($rsp18['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp18['conc4']) != 0) echo floatval($rsp18['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp18['conc5']) != 0) echo floatval($rsp18['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp18['conc6']) != 0) echo floatval($rsp18['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp18['conc7']) != 0) echo floatval($rsp18['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp18['conc8']) != 0) echo floatval($rsp18['conc8']) ?></td>
        </tr>
        <!-- BARIS 19 -->
        <?php
            $resep19 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 19 
                                                                order by flag asc");
            $rsp19 = sqlsrv_fetch_array($resep19, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp19)) {
                $rsp19 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
                    
            $KodeBaru19 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp19[kode]'");
            $kdbr19 = sqlsrv_fetch_array($KodeBaru19, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr19)) {
                $kdbr19 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr19 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }

            if($kdbr19['code_new']){ 
                $kode_lama19 = $rsp19['kode'];
                $kode_baru19 = $kdbr19['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now19 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp19[kode]'");
                $kdbr_now19 = sqlsrv_fetch_array($KodeBaru_now19, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now19)) {
                    $kdbr_now19 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now19 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now19['code'] && $kdbr_now19['code_new']){
                    $kode_lama19 = $kdbr_now19['code'];
                    $kode_baru19 = $kdbr_now19['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama19 = $rsp19['kode'];
                    $kode_baru19 = $kdbr19['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama19; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr19['ket'] == 'Suhu'){
                        echo $kdbr19['Product_Name'];
                    }else{
                        echo $kode_baru19;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp19['conc1']) != 0) echo floatval($rsp19['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp19['conc2']) != 0) echo floatval($rsp19['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp19['conc3']) != 0) echo floatval($rsp19['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp19['conc4']) != 0) echo floatval($rsp19['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp19['conc5']) != 0) echo floatval($rsp19['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp19['conc6']) != 0) echo floatval($rsp19['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp19['conc7']) != 0) echo floatval($rsp19['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp19['conc8']) != 0) echo floatval($rsp19['conc8']) ?></td>
        </tr>
        <!-- BARIS 20 -->
        <?php
            $resep20 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 20 
                                                                order by flag asc");
            $rsp20 = sqlsrv_fetch_array($resep20, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp20)) {
                $rsp20 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
                    
            $KodeBaru20 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp20[kode]'");
            $kdbr20 = sqlsrv_fetch_array($KodeBaru20, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr20)) {
                $kdbr20 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr20 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }
            
            if($kdbr20['code_new']){ 
                $kode_lama20 = $rsp20['kode'];
                $kode_baru20 = $kdbr20['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now20 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp20[kode]'");
                $kdbr_now20 = sqlsrv_fetch_array($KodeBaru_now20, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now20)) {
                    $kdbr_now20 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now20 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now20['code'] && $kdbr_now20['code_new']){
                    $kode_lama20 = $kdbr_now20['code'];
                    $kode_baru20 = $kdbr_now20['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama20 = $rsp20['kode'];
                    $kode_baru20 = $kdbr20['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama20; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr20['ket'] == 'Suhu'){
                        echo $kdbr20['Product_Name'];
                    }else{
                        echo $kode_baru20;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp20['conc1']) != 0) echo floatval($rsp20['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp20['conc2']) != 0) echo floatval($rsp20['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp20['conc3']) != 0) echo floatval($rsp20['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp20['conc4']) != 0) echo floatval($rsp20['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp20['conc5']) != 0) echo floatval($rsp20['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp20['conc6']) != 0) echo floatval($rsp20['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp20['conc7']) != 0) echo floatval($rsp20['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp20['conc8']) != 0) echo floatval($rsp20['conc8']) ?></td>
        </tr>
        <!-- BARIS 21 -->
        <?php
            $resep21 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 21 
                                                                order by flag asc");
            $rsp21 = sqlsrv_fetch_array($resep21, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp21)) {
                $rsp21 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
            
            $KodeBaru21 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp21[kode]'");
            $kdbr21 = sqlsrv_fetch_array($KodeBaru21, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr21)) {
                $kdbr21 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr21 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }

            if($kdbr21['code_new']){ 
                $kode_lama21 = $rsp21['kode'];
                $kode_baru21 = $kdbr21['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now21 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp21[kode]'");
                $kdbr_now21 = sqlsrv_fetch_array($KodeBaru_now21, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now21)) {
                    $kdbr_now21 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now21 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now21['code'] && $kdbr_now21['code_new']){
                    $kode_lama21 = $kdbr_now21['code'];
                    $kode_baru21 = $kdbr_now21['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama21 = $rsp21['kode'];
                    $kode_baru21 = $kdbr21['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama21; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr21['ket'] == 'Suhu'){
                        echo $kdbr21['Product_Name'];
                    }else{
                        echo $kode_baru21;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp21['conc1']) != 0) echo floatval($rsp21['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp21['conc2']) != 0) echo floatval($rsp21['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp21['conc3']) != 0) echo floatval($rsp21['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp21['conc4']) != 0) echo floatval($rsp21['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp21['conc5']) != 0) echo floatval($rsp21['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp21['conc6']) != 0) echo floatval($rsp21['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp21['conc7']) != 0) echo floatval($rsp21['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp21['conc8']) != 0) echo floatval($rsp21['conc8']) ?></td>
            <td width="20%" rowspan="2" align="center"><strong>BEFORE SOAPING</strong></td>
            <td width="21%" rowspan="2" align="center"><strong>T-SIDE</strong></td>
        </tr>
        <!-- BARIS 22 -->
        <?php
            $resep22 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 22 
                                                                order by flag asc");
            $rsp22 = sqlsrv_fetch_array($resep22, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp22)) {
                $rsp22 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
                    
            $KodeBaru22 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp22[kode]'");
            $kdbr22 = sqlsrv_fetch_array($KodeBaru22, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr22)) {
                $kdbr22 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr22 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }
            
            if($kdbr22['code_new']){ 
                $kode_lama22 = $rsp22['kode'];
                $kode_baru22 = $kdbr22['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now22 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp22[kode]'");
                $kdbr_now22 = sqlsrv_fetch_array($KodeBaru_now22, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now22)) {
                    $kdbr_now22 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now22 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now22['code'] && $kdbr_now22['code_new']){
                    $kode_lama22 = $kdbr_now22['code'];
                    $kode_baru22 = $kdbr_now22['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama22 = $rsp22['kode'];
                    $kode_baru22 = $kdbr22['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama22; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr22['ket'] == 'Suhu'){
                        echo $kdbr22['Product_Name'];
                    }else{
                        echo $kode_baru22;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp22['conc1']) != 0) echo floatval($rsp22['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp22['conc2']) != 0) echo floatval($rsp22['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp22['conc3']) != 0) echo floatval($rsp22['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp22['conc4']) != 0) echo floatval($rsp22['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp22['conc5']) != 0) echo floatval($rsp22['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp22['conc6']) != 0) echo floatval($rsp22['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp22['conc7']) != 0) echo floatval($rsp22['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp22['conc8']) != 0) echo floatval($rsp22['conc8']) ?></td>
        </tr>
        <!-- BARIS 23 -->
        <?php
            $resep23 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 23 
                                                                order by flag asc");
            $rsp23 = sqlsrv_fetch_array($resep23, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp23)) {
                $rsp23 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
            
            $KodeBaru23 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp23[kode]'");
            $kdbr23 = sqlsrv_fetch_array($KodeBaru23, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr23)) {
                $kdbr23 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr23 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }
            
            if($kdbr23['code_new']){ 
                $kode_lama23 = $rsp23['kode'];
                $kode_baru23 = $kdbr23['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now23 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp23[kode]'");
                $kdbr_now23 = sqlsrv_fetch_array($KodeBaru_now23, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now23)) {
                    $kdbr_now23 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now23 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now23['code'] && $kdbr_now23['code_new']){
                    $kode_lama23 = $kdbr_now23['code'];
                    $kode_baru23 = $kdbr_now23['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama23 = $rsp23['kode'];
                    $kode_baru23 = $kdbr23['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama23; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr23['ket'] == 'Suhu'){
                        echo $kdbr23['Product_Name'];
                    }else{
                        echo $kode_baru23;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp23['conc1']) != 0) echo floatval($rsp23['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp23['conc2']) != 0) echo floatval($rsp23['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp23['conc3']) != 0) echo floatval($rsp23['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp23['conc4']) != 0) echo floatval($rsp23['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp23['conc5']) != 0) echo floatval($rsp23['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp23['conc6']) != 0) echo floatval($rsp23['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp23['conc7']) != 0) echo floatval($rsp23['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp23['conc8']) != 0) echo floatval($rsp23['conc8']) ?></td>
            <td rowspan="7">&nbsp;</td>
            <td rowspan="7">&nbsp;</td>
        </tr>
        <!-- BARIS 24 -->
        <?php
            $resep24 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 24 
                                                                order by flag asc");
            $rsp24 = sqlsrv_fetch_array($resep24, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp24)) {
                $rsp24 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
            
            $KodeBaru24 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp24[kode]'");
            $kdbr24 = sqlsrv_fetch_array($KodeBaru24, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr24)) {
                $kdbr24 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr24 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }
            
            if($kdbr24['code_new']){ 
                $kode_lama24 = $rsp24['kode'];
                $kode_baru24 = $kdbr24['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now24 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp24[kode]'");
                $kdbr_now24 = sqlsrv_fetch_array($KodeBaru_now24, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now24)) {
                    $kdbr_now24 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now24 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now24['code'] && $kdbr_now24['code_new']){
                    $kode_lama24 = $kdbr_now24['code'];
                    $kode_baru24 = $kdbr_now24['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama24 = $rsp24['kode'];
                    $kode_baru24 = $kdbr24['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama24; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr24['ket'] == 'Suhu'){
                        echo $kdbr24['Product_Name'];
                    }else{
                        echo $kode_baru24;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp24['conc1']) != 0) echo floatval($rsp24['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp24['conc2']) != 0) echo floatval($rsp24['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp24['conc3']) != 0) echo floatval($rsp24['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp24['conc4']) != 0) echo floatval($rsp24['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp24['conc5']) != 0) echo floatval($rsp24['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp24['conc6']) != 0) echo floatval($rsp24['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp24['conc7']) != 0) echo floatval($rsp24['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp24['conc8']) != 0) echo floatval($rsp24['conc8']) ?></td>
        </tr>
        <!-- BARIS 25 -->
        <?php
            $resep25 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 25 
                                                                order by flag asc");
            $rsp25 = sqlsrv_fetch_array($resep25, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp25)) {
                $rsp25 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
            
            $KodeBaru25 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp25[kode]'");
            $kdbr25 = sqlsrv_fetch_array($KodeBaru25, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr25)) {
                $kdbr25 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr25 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }
            
            if($kdbr25['code_new']){ 
                $kode_lama25 = $rsp25['kode'];
                $kode_baru25 = $kdbr25['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now25 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp25[kode]'");
                $kdbr_now25 = sqlsrv_fetch_array($KodeBaru_now25, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now25)) {
                    $kdbr_now25 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now25 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now25['code'] && $kdbr_now25['code_new']){
                    $kode_lama25 = $kdbr_now25['code'];
                    $kode_baru25 = $kdbr_now25['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama25 = $rsp25['kode'];
                    $kode_baru25 = $kdbr25['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama25; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr25['ket'] == 'Suhu'){
                        echo $kdbr25['Product_Name'];
                    }else{
                        echo $kode_baru25;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp25['conc1']) != 0) echo floatval($rsp25['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp25['conc2']) != 0) echo floatval($rsp25['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp25['conc3']) != 0) echo floatval($rsp25['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp25['conc4']) != 0) echo floatval($rsp25['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp25['conc5']) != 0) echo floatval($rsp25['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp25['conc6']) != 0) echo floatval($rsp25['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp25['conc7']) != 0) echo floatval($rsp25['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp25['conc8']) != 0) echo floatval($rsp25['conc8']) ?></td>
        </tr>
        <!-- BARIS 26 -->
        <?php
            $resep26 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 26 
                                                                order by flag asc");
            $rsp26 = sqlsrv_fetch_array($resep26, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp26)) {
                $rsp26 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
            
            $KodeBaru26 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp26[kode]'");
            $kdbr26 = sqlsrv_fetch_array($KodeBaru26, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr26)) {
                $kdbr26 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr26 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }

            if($kdbr26['code_new']){ 
                $kode_lama26 = $rsp26['kode'];
                $kode_baru26 = $kdbr26['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now26 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp26[kode]'");
                $kdbr_now26 = sqlsrv_fetch_array($KodeBaru_now26, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now26)) {
                    $kdbr_now26 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now26 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now26['code'] && $kdbr_now26['code_new']){
                    $kode_lama26 = $kdbr_now26['code'];
                    $kode_baru26 = $kdbr_now26['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama26 = $rsp26['kode'];
                    $kode_baru26 = $kdbr26['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama26; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr26['ket'] == 'Suhu'){
                        echo $kdbr26['Product_Name'];
                    }else{
                        echo $kode_baru26;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp26['conc1']) != 0) echo floatval($rsp26['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp26['conc2']) != 0) echo floatval($rsp26['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp26['conc3']) != 0) echo floatval($rsp26['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp26['conc4']) != 0) echo floatval($rsp26['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp26['conc5']) != 0) echo floatval($rsp26['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp26['conc6']) != 0) echo floatval($rsp26['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp26['conc7']) != 0) echo floatval($rsp26['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp26['conc8']) != 0) echo floatval($rsp26['conc8']) ?></td>
        </tr>
        <!-- BARIS 27 -->
        <?php
            $resep27 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_matching_detail where id_matching = '$data[id_matching]'
                                                                and id_status = '$data[id_status]' 
                                                                and flag = 27 
                                                                order by flag asc");
            $rsp27 = sqlsrv_fetch_array($resep27, SQLSRV_FETCH_ASSOC);
            if (!is_array($rsp27)) {
                $rsp27 = ['kode' => '', 'conc1' => 0, 'conc2' => 0, 'conc3' => 0, 'conc4' => 0, 'conc5' => 0, 'conc6' => 0, 'conc7' => 0, 'conc8' => 0];
            }
                    
            $KodeBaru27 = sqlsrv_query($con,"SELECT TOP (1) * FROM db_laborat.tbl_dyestuff where code = '$rsp27[kode]'");
            $kdbr27 = sqlsrv_fetch_array($KodeBaru27, SQLSRV_FETCH_ASSOC);
            if (!is_array($kdbr27)) {
                $kdbr27 = ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            } else {
                $kdbr27 += ['code_new' => '', 'ket' => '', 'Product_Name' => ''];
            }

            if($kdbr27['code_new']){ 
                $kode_lama27 = $rsp27['kode'];
                $kode_baru27 = $kdbr27['code_new'];
            // JIKA KODE BARU MASUK KE KODE LAMA, PENCARIAN BERDASARKAN KODE LAMA
            }else{
                $KodeBaru_now27 = sqlsrv_query($con,"SELECT TOP (1) [code], code_new FROM db_laborat.tbl_dyestuff where code_new = '$rsp27[kode]'");
                $kdbr_now27 = sqlsrv_fetch_array($KodeBaru_now27, SQLSRV_FETCH_ASSOC);
                if (!is_array($kdbr_now27)) {
                    $kdbr_now27 = ['code' => '', 'code_new' => ''];
                } else {
                    $kdbr_now27 += ['code' => '', 'code_new' => ''];
                }
                if($kdbr_now27['code'] && $kdbr_now27['code_new']){
                    $kode_lama27 = $kdbr_now27['code'];
                    $kode_baru27 = $kdbr_now27['code_new'];
                // JIKA KODE BARU KOSONG
                }else{
                    $kode_lama27 = $rsp27['kode'];
                    $kode_baru27 = $kdbr27['code_new'];
                }
            }
        ?>
        <tr style="height: 0.2in" class="flag">
            <td style="font-weight: bold;"><?php echo $kode_lama27; ?></td>
            <td style="font-weight: bold;">
                <?php 
                    if($kdbr27['ket'] == 'Suhu'){
                        echo $kdbr27['Product_Name'];
                    }else{
                        echo $kode_baru27;
                    }
                ?>
            </td>
            <td style="font-weight: bold;"><?php if (floatval($rsp27['conc1']) != 0) echo floatval($rsp27['conc1']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp27['conc2']) != 0) echo floatval($rsp27['conc2']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp27['conc3']) != 0) echo floatval($rsp27['conc3']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp27['conc4']) != 0) echo floatval($rsp27['conc4']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp27['conc5']) != 0) echo floatval($rsp27['conc5']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp27['conc6']) != 0) echo floatval($rsp27['conc6']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp27['conc7']) != 0) echo floatval($rsp27['conc7']) ?></td>
            <td style="font-weight: bold;"><?php if (floatval($rsp27['conc8']) != 0) echo floatval($rsp27['conc8']) ?></td>
        </tr>

        <tr style="height: 0.4in">
            <td colspan="4">&nbsp;</td>
            <?php if(substr($data['no_resep'], 0, 2) == 'OB') : ?>
                <td colspan="6" align="center">T/C-SIDE</td>
            <?php else : ?>
                <td colspan="3" align="center">T-SIDE</td>
                <td colspan="3" align="center">C.SIDE</td>
            <?php endif; ?>
        </tr>
        <tr style="height: 0.4in">
            <td colspan="4" align="center">Temp x Time</td>
            <?php if(substr($data['no_resep'], 0, 2) == 'OB') : ?>
                <td colspan="6" align="center">
                    <!-- KHUSUS OB -->
                    <?php
                        if(substr($kode_lama1, 0, 3) == 'W-H'){
                            echo $kdbr1['Product_Name'];
                        }elseif(substr($kode_lama2, 0, 3) == 'W-H'){
                            echo $kdbr2['Product_Name'];
                        }elseif(substr($kode_lama3, 0, 3) == 'W-H'){
                            echo $kdbr3['Product_Name'];
                        }elseif(substr($kode_lama4, 0, 3) == 'W-H'){
                            echo $kdbr4['Product_Name'];
                        }elseif(substr($kode_lama5, 0, 3) == 'W-H'){
                            echo $kdbr5['Product_Name'];
                        }elseif(substr($kode_lama6, 0, 3) == 'W-H'){
                            echo $kdbr6['Product_Name'];
                        }elseif(substr($kode_lama7, 0, 3) == 'W-H'){
                            echo $kdbr7['Product_Name'];
                        }elseif(substr($kode_lama8, 0, 3) == 'W-H'){
                            echo $kdbr8['Product_Name'];
                        }elseif(substr($kode_lama9, 0, 3) == 'W-H'){
                            echo $kdbr9['Product_Name'];
                        }elseif(substr($kode_lama10, 0, 3) == 'W-H'){
                            echo $kdbr10['Product_Name'];
                        }elseif(substr($kode_lama11, 0, 3) == 'W-H'){
                            echo $kdbr11['Product_Name'];
                        }elseif(substr($kode_lama12, 0, 3) == 'W-H'){
                            echo $kdbr12['Product_Name'];
                        }elseif(substr($kode_lama13, 0, 3) == 'W-H'){
                            echo $kdbr13['Product_Name'];
                        }elseif(substr($kode_lama14, 0, 3) == 'W-H'){
                            echo $kdbr14['Product_Name'];
                        }elseif(substr($kode_lama15, 0, 3) == 'W-H'){
                            echo $kdbr15['Product_Name'];
                        }elseif(substr($kode_lama16, 0, 3) == 'W-H'){
                            echo $kdbr16['Product_Name'];
                        }elseif(substr($kode_lama17, 0, 3) == 'W-H'){
                            echo $kdbr17['Product_Name'];
                        }elseif(substr($kode_lama18, 0, 3) == 'W-H'){
                            echo $kdbr18['Product_Name'];
                        }elseif(substr($kode_lama19, 0, 3) == 'W-H'){
                            echo $kdbr19['Product_Name'];
                        }elseif(substr($kode_lama20, 0, 3) == 'W-H'){
                            echo $kdbr20['Product_Name'];
                        }elseif(substr($kode_lama21, 0, 3) == 'W-H'){
                            echo $kdbr21['Product_Name'];
                        }elseif(substr($kode_lama22, 0, 3) == 'W-H'){
                            echo $kdbr22['Product_Name'];
                        }elseif(substr($kode_lama23, 0, 3) == 'W-H'){
                            echo $kdbr23['Product_Name'];
                        }elseif(substr($kode_lama24, 0, 3) == 'W-H'){
                            echo $kdbr24['Product_Name'];
                        }elseif(substr($kode_lama25, 0, 3) == 'W-H'){
                            echo $kdbr25['Product_Name'];
                        }elseif(substr($kode_lama26, 0, 3) == 'W-H'){
                            echo $kdbr26['Product_Name'];
                        }elseif(substr($kode_lama27, 0, 3) == 'P-L'){
                            echo $kdbr27['Product_Name'];
                        }else{
                            echo "0";
                        }
                    ?>
                </td>
            <?php else : ?>
                <td colspan="3" align="center">
                    <!-- POLY -->
                    <?php
                        // Pastikan variabel kode_lama/kdbr terdefinisi agar substr tidak error
                        for ($i = 1; $i <= 27; $i++) {
                            if (!isset(${"kode_lama".$i})) {
                                ${"kode_lama".$i} = '';
                            }
                            if (!isset(${"kdbr".$i}) || !is_array(${"kdbr".$i})) {
                                ${"kdbr".$i} = ['Product_Name' => ''];
                            } else {
                                ${"kdbr".$i} += ['Product_Name' => ''];
                            }
                        }
                        $kode_temp_suhu = '';
                        if(substr($kode_lama1, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr1['Product_Name'];
                        }
                        if(substr($kode_lama2, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr2['Product_Name'];
                        }
                        if(substr($kode_lama3, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr3['Product_Name'];
                        }
                        if(substr($kode_lama4, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr4['Product_Name'];
                        }
                        if(substr($kode_lama5, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr5['Product_Name'];
                        }
                        if(substr($kode_lama6, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr6['Product_Name'];
                        }
                        if(substr($kode_lama7, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr7['Product_Name'];
                        }
                        if(substr($kode_lama8, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr8['Product_Name'];
                        }
                        if(substr($kode_lama9, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr9['Product_Name'];
                        }
                        if(substr($kode_lama10, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr10['Product_Name'];
                        }
                        if(substr($kode_lama11, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr11['Product_Name'];
                        }
                        if(substr($kode_lama12, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr12['Product_Name'];
                        }
                        if(substr($kode_lama13, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr13['Product_Name'];
                        }
                        if(substr($kode_lama14, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr14['Product_Name'];
                        }
                        if(substr($kode_lama15, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr15['Product_Name'];
                        }
                        if(substr($kode_lama16, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr16['Product_Name'];
                        }
                        if(substr($kode_lama17, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr17['Product_Name'];
                        }
                        if(substr($kode_lama18, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr18['Product_Name'];
                        }
                        if(substr($kode_lama19, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr19['Product_Name'];
                        }
                        if(substr($kode_lama20, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr20['Product_Name'];
                        }
                        if(substr($kode_lama21, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr21['Product_Name'];
                        }
                        if(substr($kode_lama22, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr22['Product_Name'];
                        }
                        if(substr($kode_lama23, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr23['Product_Name'];
                        }
                        if(substr($kode_lama24, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr24['Product_Name'];
                        }
                        if(substr($kode_lama25, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr25['Product_Name'];
                        }
                        if(substr($kode_lama26, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr26['Product_Name'];
                        }
                        if(substr($kode_lama27, 0, 3) == 'P-L'){
                            $kode_temp_suhu = $kdbr27['Product_Name'];
                        }

                        if(!empty($kode_temp_suhu)){
                            echo $kode_temp_suhu;
                        }else{
                            echo '0';
                        }
                    ?>
                </td>
                <td colspan="3" align="center">
                    <!-- COTTON -->
                    <?php
                        $kode_temp_suhu_cotton = '';
                        if(substr($kode_lama1, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr1['Product_Name'];
                        }
                        if(substr($kode_lama2, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr2['Product_Name'];
                        }
                        if(substr($kode_lama3, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr3['Product_Name'];
                        }
                        if(substr($kode_lama4, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr4['Product_Name'];
                        }
                        if(substr($kode_lama5, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr5['Product_Name'];
                        }
                        if(substr($kode_lama6, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr6['Product_Name'];
                        }
                        if(substr($kode_lama7, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr7['Product_Name'];
                        }
                        if(substr($kode_lama8, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr8['Product_Name'];
                        }
                        if(substr($kode_lama9, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr9['Product_Name'];
                        }
                        if(substr($kode_lama10, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr10['Product_Name'];
                        }
                        if(substr($kode_lama11, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr11['Product_Name'];
                        }
                        if(substr($kode_lama12, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr12['Product_Name'];
                        }
                        if(substr($kode_lama13, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr13['Product_Name'];
                        }
                        if(substr($kode_lama14, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr14['Product_Name'];
                        }
                        if(substr($kode_lama15, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr15['Product_Name'];
                        }
                        if(substr($kode_lama16, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr16['Product_Name'];
                        }
                        if(substr($kode_lama17, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr17['Product_Name'];
                        }
                        if(substr($kode_lama18, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr18['Product_Name'];
                        }
                        if(substr($kode_lama19, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr19['Product_Name'];
                        }
                        if(substr($kode_lama20, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr20['Product_Name'];
                        }
                        if(substr($kode_lama21, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr21['Product_Name'];
                        }
                        if(substr($kode_lama22, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr22['Product_Name'];
                        }
                        if(substr($kode_lama23, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr23['Product_Name'];
                        }
                        if(substr($kode_lama24, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr24['Product_Name'];
                        }
                        if(substr($kode_lama25, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr25['Product_Name'];
                        }
                        if(substr($kode_lama26, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr26['Product_Name'];
                        }
                        if(substr($kode_lama27, 0, 3) == 'C-T'){
                            $kode_temp_suhu_cotton = $kdbr27['Product_Name'];
                        }

                        if(!empty($kode_temp_suhu_cotton)){
                            echo $kode_temp_suhu_cotton;
                        }else{
                            echo '0';
                        }
                    ?>
                </td>
            <?php endif; ?>
        </tr>
        <tr style="height: 0.4in">
            <td colspan="4" align="center">L:R</td>
            <td colspan="3" align="center"><?php echo $data['lr'] ?></td>
            <td colspan="3" align="center"><?php echo $data['second_lr'] ?></td>
            <td valign="top"><strong>PROSES</strong> : <?php echo $data['proses'] ?></td>
            <?php $sqlLampu =  sqlsrv_query($con,"SELECT lampu FROM db_laborat.vpot_lampbuy where buyer = '$data[buyer]' order by flag"); ?>
            <td valign="top">
                <strong>LAMPU : </strong>
                <?php $ii = 1;
                while ($lampu = sqlsrv_fetch_array($sqlLampu, SQLSRV_FETCH_ASSOC)) {
                    echo $ii++ . '(' . $lampu['lampu'] . '), ';
                } ?>
            </td>
        </tr>
        <tr style="height: 0.4in">
            <td colspan="4" align="center">pH</td>
            <td colspan="3" align="center">&nbsp; <?php echo floatval($data['ph']); ?></td>
            <td colspan="3" align="center">&nbsp; 0</td>
            <td style="font-weight: bold;" colspan="2">
                CIE WI &nbsp;&nbsp;: <?php echo number_format($data['cie_wi'], 2); ?>
                <pre style="display: inline-block; margin-left: 200px;">CIE TINT : <?php echo number_format($data['cie_tint'], 2); ?></pre>
                <pre style="display: inline-block; margin-left: 200px;">YELLOWNESS : <?php echo number_format($data['yellowness'], 2); ?></pre>
            </td>
        </tr>
        <tr style="height: 0.4in">
            <td colspan="4" align="center">RC</td>
            <td colspan="3" align="center">
                <!-- RC -->
                <?php
                    if(substr($kode_lama1, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr1['Product_Name'];
                    }
                    if(substr($kode_lama2, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr2['Product_Name'];
                    }
                    if(substr($kode_lama3, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr3['Product_Name'];
                    }
                    if(substr($kode_lama4, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr4['Product_Name'];
                    }
                    if(substr($kode_lama5, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr5['Product_Name'];
                    }
                    if(substr($kode_lama6, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr6['Product_Name'];
                    }
                    if(substr($kode_lama7, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr7['Product_Name'];
                    }
                    if(substr($kode_lama8, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr8['Product_Name'];
                    }
                    if(substr($kode_lama9, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr9['Product_Name'];
                    }
                    if(substr($kode_lama10, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr10['Product_Name'];
                    }
                    if(substr($kode_lama11, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr11['Product_Name'];
                    }
                    if(substr($kode_lama12, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr12['Product_Name'];
                    }
                    if(substr($kode_lama13, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr13['Product_Name'];
                    }
                    if(substr($kode_lama14, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr14['Product_Name'];
                    }
                    if(substr($kode_lama15, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr15['Product_Name'];
                    }
                    if(substr($kode_lama16, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr16['Product_Name'];
                    }
                    if(substr($kode_lama17, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr17['Product_Name'];
                    }
                    if(substr($kode_lama18, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr18['Product_Name'];
                    }
                    if(substr($kode_lama19, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr19['Product_Name'];
                    }
                    if(substr($kode_lama20, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr20['Product_Name'];
                    }
                    if(substr($kode_lama21, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr21['Product_Name'];
                    }
                    if(substr($kode_lama22, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr22['Product_Name'];
                    }
                    if(substr($kode_lama23, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr23['Product_Name'];
                    }
                    if(substr($kode_lama24, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr24['Product_Name'];
                    }
                    if(substr($kode_lama25, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr25['Product_Name'];
                    }
                    if(substr($kode_lama26, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr26['Product_Name'];
                    }
                    if(substr($kode_lama27, 0, 3) == 'R-C'){
                        $ket_rc = $kdbr27['Product_Name'];
                    }

                    if(!empty($ket_rc)){
                        echo $ket_rc;
                    }else{
                        echo '0';
                    }
                ?>
            </td>
            <td colspan="3"  align="center">&nbsp; 0</td>
            <td colspan="2" align="center"><strong>GREIGE</strong></td>
        </tr>
        <tr style="height: 0.4in">
            <td colspan="4" align="center">Bleaching</td>
            <td colspan="3" align="center">
                <!-- BLEACHING -->
                <?php
                    if(substr($kode_lama1, 0, 3) == 'B-L' AND $kdbr1['ket'] == 'Suhu'){
                        $ket_bl = $kdbr1['Product_Name'];
                    }
                    if(substr($kode_lama2, 0, 3) == 'B-L' AND $kdbr2['ket'] == 'Suhu'){
                        $ket_bl = $kdbr2['Product_Name'];
                    }
                    if(substr($kode_lama3, 0, 3) == 'B-L' AND $kdbr3['ket'] == 'Suhu'){
                        $ket_bl = $kdbr3['Product_Name'];
                    }
                    if(substr($kode_lama4, 0, 3) == 'B-L' AND $kdbr4['ket'] == 'Suhu'){
                        $ket_bl = $kdbr4['Product_Name'];
                    }
                    if(substr($kode_lama5, 0, 3) == 'B-L' AND $kdbr5['ket'] == 'Suhu'){
                        $ket_bl = $kdbr5['Product_Name'];
                    }
                    if(substr($kode_lama6, 0, 3) == 'B-L' AND $kdbr6['ket'] == 'Suhu'){
                        $ket_bl = $kdbr6['Product_Name'];
                    }
                    if(substr($kode_lama7, 0, 3) == 'B-L' AND $kdbr7['ket'] == 'Suhu'){
                        $ket_bl = $kdbr7['Product_Name'];
                    }
                    if(substr($kode_lama8, 0, 3) == 'B-L' AND $kdbr8['ket'] == 'Suhu'){
                        $ket_bl = $kdbr8['Product_Name'];
                    }
                    if(substr($kode_lama9, 0, 3) == 'B-L' AND $kdbr9['ket'] == 'Suhu'){
                        $ket_bl = $kdbr9['Product_Name'];
                    }
                    if(substr($kode_lama10, 0, 3) == 'B-L' AND $kdbr10['ket'] == 'Suhu'){
                        $ket_bl = $kdbr10['Product_Name'];
                    }
                    if(substr($kode_lama11, 0, 3) == 'B-L' AND $kdbr11['ket'] == 'Suhu'){
                        $ket_bl = $kdbr11['Product_Name'];
                    }
                    if(substr($kode_lama12, 0, 3) == 'B-L' AND $kdbr12['ket'] == 'Suhu'){
                        $ket_bl = $kdbr12['Product_Name'];
                    }
                    if(substr($kode_lama13, 0, 3) == 'B-L' AND $kdbr13['ket'] == 'Suhu'){
                        $ket_bl = $kdbr13['Product_Name'];
                    }
                    if(substr($kode_lama14, 0, 3) == 'B-L' AND $kdbr14['ket'] == 'Suhu'){
                        $ket_bl = $kdbr14['Product_Name'];
                    }
                    if(substr($kode_lama15, 0, 3) == 'B-L' AND $kdbr15['ket'] == 'Suhu'){
                        $ket_bl = $kdbr15['Product_Name'];
                    }
                    if(substr($kode_lama16, 0, 3) == 'B-L' AND $kdbr16['ket'] == 'Suhu'){
                        $ket_bl = $kdbr16['Product_Name'];
                    }
                    if(substr($kode_lama17, 0, 3) == 'B-L' AND $kdbr17['ket'] == 'Suhu'){
                        $ket_bl = $kdbr17['Product_Name'];
                    }
                    if(substr($kode_lama18, 0, 3) == 'B-L' AND $kdbr18['ket'] == 'Suhu'){
                        $ket_bl = $kdbr18['Product_Name'];
                    }
                    if(substr($kode_lama19, 0, 3) == 'B-L' AND $kdbr19['ket'] == 'Suhu'){
                        $ket_bl = $kdbr19['Product_Name'];
                    }
                    if(substr($kode_lama20, 0, 3) == 'B-L' AND $kdbr20['ket'] == 'Suhu'){
                        $ket_bl = $kdbr20['Product_Name'];
                    }
                    if(substr($kode_lama21, 0, 3) == 'B-L' AND $kdbr21['ket'] == 'Suhu'){
                        $ket_bl = $kdbr21['Product_Name'];
                    }
                    if(substr($kode_lama22, 0, 3) == 'B-L' AND $kdbr22['ket'] == 'Suhu'){
                        $ket_bl = $kdbr22['Product_Name'];
                    }
                    if(substr($kode_lama23, 0, 3) == 'B-L' AND $kdbr23['ket'] == 'Suhu'){
                        $ket_bl = $kdbr23['Product_Name'];
                    }
                    if(substr($kode_lama24, 0, 3) == 'B-L' AND $kdbr24['ket'] == 'Suhu'){
                        $ket_bl = $kdbr24['Product_Name'];
                    }
                    if(substr($kode_lama25, 0, 3) == 'B-L' AND $kdbr25['ket'] == 'Suhu'){
                        $ket_bl = $kdbr25['Product_Name'];
                    }
                    if(substr($kode_lama26, 0, 3) == 'B-L' AND $kdbr26['ket'] == 'Suhu'){
                        $ket_bl = $kdbr26['Product_Name'];
                    }
                    if(substr($kode_lama27, 0, 3) == 'B-L' AND $kdbr27['ket'] == 'Suhu'){
                        $ket_bl = $kdbr27['Product_Name'];
                    }
                    if(!empty($ket_bl)){
                        echo $ket_bl;
                    }else{
                        echo "0";
                    }
                ?>
            </td>
            <td colspan="3"  align="center">&nbsp; 0</td>
            <td colspan="2" rowspan="4" valign="top">Info Dyeing : <?php echo $data['remark_dye'] ?></td>
        </tr>
        <tr style="height: 0.4in">
            <td colspan="4" align="center">Soaping</td>
            <td colspan="3" align="center">&nbsp; 0</td>
            <td colspan="3" align="center">
                <!-- SOAPING -->
                <?php
                    if(substr($kode_lama1, 0, 1) == 'S'){
                        $ket_soaping = $kdbr1['Product_Name'];
                    }
                    if(substr($kode_lama2, 0, 1) == 'S'){
                        $ket_soaping = $kdbr2['Product_Name'];
                    }
                    if(substr($kode_lama3, 0, 1) == 'S'){
                        $ket_soaping = $kdbr3['Product_Name'];
                    }
                    if(substr($kode_lama4, 0, 1) == 'S'){
                        $ket_soaping = $kdbr4['Product_Name'];
                    }
                    if(substr($kode_lama5, 0, 1) == 'S'){
                        $ket_soaping = $kdbr5['Product_Name'];
                    }
                    if(substr($kode_lama6, 0, 1) == 'S'){
                        $ket_soaping = $kdbr6['Product_Name'];
                    }
                    if(substr($kode_lama7, 0, 1) == 'S'){
                        $ket_soaping = $kdbr7['Product_Name'];
                    }
                    if(substr($kode_lama8, 0, 1) == 'S'){
                        $ket_soaping = $kdbr8['Product_Name'];
                    }
                    if(substr($kode_lama9, 0, 1) == 'S'){
                        $ket_soaping = $kdbr9['Product_Name'];
                    }
                    if(substr($kode_lama10, 0, 1) == 'S'){
                        $ket_soaping = $kdbr10['Product_Name'];
                    }
                    if(substr($kode_lama11, 0, 1) == 'S'){
                        $ket_soaping = $kdbr11['Product_Name'];
                    }
                    if(substr($kode_lama12, 0, 1) == 'S'){
                        $ket_soaping = $kdbr12['Product_Name'];
                    }
                    if(substr($kode_lama13, 0, 1) == 'S'){
                        $ket_soaping = $kdbr13['Product_Name'];
                    }
                    if(substr($kode_lama14, 0, 1) == 'S'){
                        $ket_soaping = $kdbr14['Product_Name'];
                    }
                    if(substr($kode_lama15, 0, 1) == 'S'){
                        $ket_soaping = $kdbr15['Product_Name'];
                    }
                    if(substr($kode_lama16, 0, 1) == 'S'){
                        $ket_soaping = $kdbr16['Product_Name'];
                    }
                    if(substr($kode_lama17, 0, 1) == 'S'){
                        $ket_soaping = $kdbr17['Product_Name'];
                    }
                    if(substr($kode_lama18, 0, 1) == 'S'){
                        $ket_soaping = $kdbr18['Product_Name'];
                    }
                    if(substr($kode_lama19, 0, 1) == 'S'){
                        $ket_soaping = $kdbr19['Product_Name'];
                    }
                    if(substr($kode_lama20, 0, 1) == 'S'){
                        $ket_soaping = $kdbr20['Product_Name'];
                    }
                    if(substr($kode_lama21, 0, 1) == 'S'){
                        $ket_soaping = $kdbr21['Product_Name'];
                    }
                    if(substr($kode_lama22, 0, 1) == 'S'){
                        $ket_soaping = $kdbr22['Product_Name'];
                    }
                    if(substr($kode_lama23, 0, 1) == 'S'){
                        $ket_soaping = $kdbr23['Product_Name'];
                    }
                    if(substr($kode_lama24, 0, 1) == 'S'){
                        $ket_soaping = $kdbr24['Product_Name'];
                    }
                    if(substr($kode_lama25, 0, 1) == 'S'){
                        $ket_soaping = $kdbr25['Product_Name'];
                    }
                    if(substr($kode_lama26, 0, 1) == 'S'){
                        $ket_soaping = $kdbr26['Product_Name'];
                    }
                    if(substr($kode_lama27, 0, 1) == 'S'){
                        $ket_soaping = $kdbr27['Product_Name'];
                    }

                    if(!empty($ket_soaping)){
                        echo $ket_soaping;
                    }else{
                        echo "0";
                    }
                ?>
            </td>
        </tr>
        <tr style="height: 0.4in">
            <td colspan="4" align="center">BEFORE RC / Bleaching</td>
            <td colspan="6" align="center">AFTER RC / Bleaching</td>
        </tr>
        <tr style="height: 1.5in">
            <td colspan="4">&nbsp;</td>
            <td colspan="6">&nbsp;</td>
        </tr>
        <tr>
            <td style="height: 0.2in" colspan="4">&nbsp;</td>
            <td colspan="3" align="center" style="height: 0.2in"><strong>Matcher</strong></td>
            <td style="height: 0.2in" colspan="3" align="center"> <strong>Buka Resep</strong></td>
            <td style="height: 0.2in" colspan="2" align="center"> <strong>Approved</strong></td>
        </tr>
        <tr>
            <td style="height: 0.2in" colspan="4">Nama</td>
            <td colspan="3" align="center" style="height: 0.2in"><?php echo $data['final_matcher'] ?></td>
            <td style="height: 0.2in" colspan="3" align="center"><?php echo $data['selesai_by'] ?></td>
            <td style="height: 0.2in" colspan="2" align="center"><?php echo $data['approve_by'] ?></td>
        </tr>
        <tr>
            <td style="height: 0.2in" colspan="4">Tanggal</td>
            <td colspan="3" align="center" style="height: 0.2in"><?php echo $data['selesai_at'] ?></td>
            <td style="height: 0.2in" colspan="3" align="center"><?php echo $data['selesai_at'] ?></td>
            <td style="height: 0.2in" colspan="2" align="center"><?php echo $data['approve_at'] ?></td>
        </tr>
        <tr>
            <td style="height: 0.4in" colspan="4">TTD</td>
            <td colspan="3" align="center" style="height: 0.4in">&nbsp;</td>
            <td style="height: 0.4in" colspan="3" align="center"><?php echo $data['selesai_by'] ?></td>
            <td style="height: 0.4in" colspan="2" align="center"><?php echo $data['approve_by'] ?></td>
        </tr>
    </table>
    <!-- <div align="left" style="font-size: 12px;">TTD :</div> -->
</body>

</html>
<script>
    setTimeout(function() {
        window.print()
    }, 1500);
</script>
