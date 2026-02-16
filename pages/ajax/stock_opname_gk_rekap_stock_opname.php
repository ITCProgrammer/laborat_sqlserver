<?php
// di include juga di pages/cetak/cetak_stock_opname_gk_rekap.php
// koneksi ke DB
include "../../koneksi.php";
include "../../includes/Penomoran_helper.php";

$tgl_tutup = $_POST['tgl_tutup'] ?? '';
$tgl_stk_op = $_POST['tgl_stk_op'] ?? '';
$jam_stk_op = $_POST['jam_stk_op'] ?? '';
$kategori = $_POST['kategori'] ?? '';
$jenis_data = $_POST['jenis_data'] ?? "web";

$kemarin = date('Y-m-d',strtotime($tgl_stk_op  . "-1 days"));
$kemarin_stk_transaksi = date('Y-m-d', strtotime($tgl_tutup . "-1 days"));
$tanggal1= date('Y-m-01',strtotime($tgl_stk_op));
$tanggal1_tutup= date('Y-m-01',strtotime($tgl_tutup));
$akhir= date('Y-m-t',strtotime($tgl_stk_op));

$data_now=array();
$data_transaksi=array();
$data_total_pemakaian=array();
$data_blc=array();
$data_opn=array();
$data_saldo=array();

$TOTAL_BLC=0;
$TOTAL_PAKAI=0;
$TOTAL_ENDING_BLC=0;
$TOTAL_STC_OPN=0;
$TOTAL_SD=0;
$TOTAL_SALDO=0;
$TOTAL_PEMASUKAN = 0;

if($kategori=="DYESTUFF"){
    $query_get_data_now = "SELECT p.ITEMTYPECODE ,p.SUBCODE01 ,p.SUBCODE02 ,p.SUBCODE03 ,p.SUBCODE04 , p.LONGDESCRIPTION,
     CASE 
        WHEN b.QTY_MASUK IS NULL THEN 0 ELSE 
        b.QTY_MASUK
    END AS QTY_MASUK
FROM 
    PRODUCT p
LEFT JOIN 
    adstorage c on p.ABSUNIQUEID = c.UNIQUEID and c.FIELDNAME='ShowChemical' AND c.NAMEENTITYNAME ='Product'
LEFT JOIN (SELECT 
            ITEMTYPECODE, 
            DECOSUBCODE01, 
            DECOSUBCODE02, 
            DECOSUBCODE03, 
            SUM(QTY_MASUK) AS QTY_MASUK, 
            SATUAN_MASUK 
        FROM 
        (
            SELECT 
                ITEMTYPECODE, 
                TEMPLATE, 
                DECOSUBCODE01, 
                DECOSUBCODE02, 
                DECOSUBCODE03, 
                SUM(QTY_MASUK) AS QTY_MASUK, 
                SATUAN_MASUK 
            FROM 
            (
                SELECT 
                    s.TRANSACTIONDATE, 
                    s.TRANSACTIONNUMBER, 
                    CASE 
                        WHEN s3.TEMPLATECODE IS NOT NULL THEN s3.TEMPLATECODE 
                        ELSE s.TEMPLATECODE 
                    END AS TEMPLATE, 
                    s3.LOGICALWAREHOUSECODE AS terimadarigd, 
                    s.TEMPLATECODE AS TEMPLATE_S, 
                    s.ITEMTYPECODE, 
                    s.DECOSUBCODE01, 
                    s.DECOSUBCODE02, 
                    s.DECOSUBCODE03, 
                    s2.LONGDESCRIPTION, 
                    TRIM(s.DECOSUBCODE01) || '-' || TRIM(s.DECOSUBCODE02) || '-' || TRIM(s.DECOSUBCODE03) AS KODE_OBAT, 
                    CASE 
                        WHEN s.CREATIONUSER = 'MT_STI' AND s.TEMPLATECODE = 'OPN' AND (s.TRANSACTIONDATE ='2025-07-13' OR s.TRANSACTIONDATE ='2025-10-05') THEN 0 
                        WHEN s.USERPRIMARYUOMCODE = 't' THEN s.USERPRIMARYQUANTITY * 1000000 
                        WHEN s.USERPRIMARYUOMCODE = 'kg' THEN s.USERPRIMARYQUANTITY * 1000 
                        ELSE s.USERPRIMARYQUANTITY 
                    END AS QTY_MASUK, 
                    CASE 
                        WHEN s.USERPRIMARYUOMCODE = 't' THEN 'g' 
                        WHEN s.USERPRIMARYUOMCODE = 'kg' THEN 'g' 
                        ELSE s.USERPRIMARYUOMCODE 
                    END AS SATUAN_MASUK, 
                    CASE 
                        WHEN s.TEMPLATECODE = 'OPN' THEN s2.LONGDESCRIPTION 
                        WHEN s.TEMPLATECODE = 'QCT' THEN s.ORDERCODE 
                        WHEN s.TEMPLATECODE IN ('304','303','203','204') THEN 'Terima dari ' || TRIM(s3.LOGICALWAREHOUSECODE) 
                        WHEN s.TEMPLATECODE = '125' THEN 'Retur dari ' || TRIM(s.ORDERCODE ) 
                    END AS KETERANGAN 
                FROM STOCKTRANSACTION s            
                LEFT JOIN STOCKTRANSACTIONTEMPLATE s2 ON s2.CODE = s.TEMPLATECODE 
                LEFT JOIN INTERNALDOCUMENT i ON i.PROVISIONALCODE = s.ORDERCODE 
                LEFT JOIN ORDERPARTNER o ON o.CUSTOMERSUPPLIERCODE = i.ORDPRNCUSTOMERSUPPLIERCODE 
                LEFT JOIN LOGICALWAREHOUSE l ON l.CODE = o.CUSTOMERSUPPLIERCODE 
                LEFT JOIN STOCKTRANSACTION s3 
                    ON s3.TRANSACTIONNUMBER = s.TRANSACTIONNUMBER 
                    AND NOT s3.LOGICALWAREHOUSECODE = 'M101' 
                    AND s3.DETAILTYPE = 1 
                LEFT JOIN LOGICALWAREHOUSE l2 ON l2.CODE = s3.LOGICALWAREHOUSECODE 
                WHERE 
                    s.ITEMTYPECODE = 'DYC' 
                    AND s.TRANSACTIONDATE BETWEEN '$kemarin_stk_transaksi ' AND '$tgl_stk_op' 
                    AND (
                        (s.TRANSACTIONDATE > '$kemarin_stk_transaksi ' OR (s.TRANSACTIONDATE = '$kemarin_stk_transaksi ' AND s.TRANSACTIONTIME >= '23:01:00'))
                        AND (s.TRANSACTIONDATE < '$tgl_stk_op' OR (s.TRANSACTIONDATE = '$tgl_stk_op' AND s.TRANSACTIONTIME <= '$jam_stk_op:00'))
                    )
                    AND s.TEMPLATECODE IN ('QCT','304','OPN','204','125') 
                    AND NOT COALESCE(TRIM(
                        CASE WHEN s3.TEMPLATECODE IS NOT NULL THEN s3.TEMPLATECODE ELSE s.TEMPLATECODE END
                    ), '') || COALESCE(TRIM(
                        CASE WHEN s3.LOGICALWAREHOUSECODE IS NOT NULL THEN s3.LOGICALWAREHOUSECODE ELSE s.LOGICALWAREHOUSECODE END
                    ), '') IN ('OPNM101','303M101','304M510')
                    AND s.LOGICALWAREHOUSECODE IN ('M510','M101')
            ) AS sub 
            WHERE TEMPLATE <> '304' 
            GROUP BY 
                ITEMTYPECODE, 
                TEMPLATE, 
                DECOSUBCODE01, 
                DECOSUBCODE02, 
                DECOSUBCODE03, 
                SATUAN_MASUK
        ) x 
        GROUP BY 
            ITEMTYPECODE, 
            DECOSUBCODE01, 
            DECOSUBCODE02, 
            DECOSUBCODE03, 
            SATUAN_MASUK )b ON  b.DECOSUBCODE01 = p.SUBCODE01
                            AND b.DECOSUBCODE02 = p.SUBCODE02
                            AND b.DECOSUBCODE03 = p.SUBCODE03
    WHERE 
        c.VALUEBOOLEAN = 1
        AND P.ITEMTYPECODE ='DYC'
        AND (
            p.SUBCODE01 = 'C'
            OR p.SUBCODE01 = 'D'
            OR p.SUBCODE01 = 'R'
            OR p.SUBCODE01 = 'E'
            OR p.SUBCODE01 = 'P'
            OR p.SUBCODE01 = 'N'
        ) 
    ORDER BY p.SUBCODE01 ";

    $result_now = db2_exec($conn1, $query_get_data_now, ['cursor' => DB2_SCROLLABLE]);
    while ($rowdb = db2_fetch_assoc($result_now)) {
        $kode_obat = trim($rowdb["SUBCODE01"]) . "-" . trim($rowdb["SUBCODE02"]) . "-" . trim($rowdb["SUBCODE03"]);
        $data_now[$kode_obat]['kode_obat'] = $kode_obat;
        $data_now[$kode_obat]['LONGDESCRIPTION'] = $rowdb["LONGDESCRIPTION"];
        $data_now[$kode_obat]['QTY_MASUK'] = $rowdb["QTY_MASUK"];
    }


    $query_get_balance="SELECT KODE_OBAT, tgl_tutup, SUM(BASEPRIMARYQUANTITYUNIT) as total_balance 
    FROM    (
		        SELECT DISTINCT
		            ITEMTYPECODE,
		            KODE_OBAT,
		            LONGDESCRIPTION,
		            LOTCODE,
		            LOGICALWAREHOUSECODE,
		            tgl_tutup,
                    WHSLOCATIONWAREHOUSEZONECODE,
		            BASEPRIMARYQUANTITYUNIT,
		            BASEPRIMARYUNITCODE,
                    WAREHOUSELOCATIONCODE
		        FROM db_laborat.tblopname_11
		            WHERE  tgl_tutup = ? 
                    AND NOT kode_obat = 'E-1-000'
                    AND (
                        LEFT(KODE_OBAT, CHARINDEX('-', KODE_OBAT + '-') - 1)  = 'C'
                        OR LEFT(KODE_OBAT, CHARINDEX('-', KODE_OBAT + '-') - 1)  = 'D'
                        OR LEFT(KODE_OBAT, CHARINDEX('-', KODE_OBAT + '-') - 1)  = 'R'
                    )
    	    ) d
    GROUP BY tgl_tutup, KODE_OBAT ";
    $stmt_blc = sqlsrv_query($con, $query_get_balance, [$tgl_tutup]);
    while ($row = sqlsrv_fetch_array($stmt_blc, SQLSRV_FETCH_ASSOC)) {
        $kode_obat=trim($row["KODE_OBAT"]," ");
        $data_blc[$kode_obat]['kode_obat']=$kode_obat;
        $data_blc[$kode_obat]['balance']=$row["total_balance"];
    }

    $query_get_total_stk_opn = "SELECT  o.KODE_OBAT, tgl_tutup, SUM(o.total_stock) as total_stock
    FROM 
        db_laborat.tbl_stock_opname_gk o
    WHERE 
        o.tgl_tutup = ?
        AND (
            LEFT(o.KODE_OBAT, CHARINDEX('-', o.KODE_OBAT + '-') - 1)  = 'C'
            OR LEFT(o.KODE_OBAT, CHARINDEX('-', o.KODE_OBAT + '-') - 1)  = 'D'
            OR LEFT(o.KODE_OBAT, CHARINDEX('-', o.KODE_OBAT + '-') - 1)  = 'R'
        )
    GROUP BY tgl_tutup, KODE_OBAT ";
    $stmt_opn = sqlsrv_query($con, $query_get_total_stk_opn, [$tgl_tutup]);
    while ($row = sqlsrv_fetch_array($stmt_opn, SQLSRV_FETCH_ASSOC)) {
        $kode_obat=trim($row["KODE_OBAT"]," ");
        $data_opn[$kode_obat]['kode_obat']=$kode_obat;
        $data_opn[$kode_obat]['total_stock']=$row["total_stock"];
    }

    $query_saldo_awal="SELECT KODE_OBAT, tgl_tutup, SUM(BASEPRIMARYQUANTITYUNIT) as total_balance 
    FROM    (
		        SELECT DISTINCT
		            ITEMTYPECODE,
		            KODE_OBAT,
		            LONGDESCRIPTION,
		            LOTCODE,
		            LOGICALWAREHOUSECODE,
		            tgl_tutup,
                    WHSLOCATIONWAREHOUSEZONECODE,
		            BASEPRIMARYQUANTITYUNIT,
		            BASEPRIMARYUNITCODE,
                    WAREHOUSELOCATIONCODE
		        FROM db_laborat.tblopname_11
		            WHERE  tgl_tutup = ? 
                    AND NOT kode_obat = 'E-1-000'
                    AND (
                        LEFT(KODE_OBAT, CHARINDEX('-', KODE_OBAT + '-') - 1)  = 'C'
                        OR LEFT(KODE_OBAT, CHARINDEX('-', KODE_OBAT + '-') - 1)  = 'D'
                        OR LEFT(KODE_OBAT, CHARINDEX('-', KODE_OBAT + '-') - 1)  = 'R'
                    )
    	    ) d
    GROUP BY tgl_tutup, KODE_OBAT ";
    $stmt_saldo = sqlsrv_query($con, $query_saldo_awal, [$akhir]);
    while ($row = sqlsrv_fetch_array($stmt_saldo, SQLSRV_FETCH_ASSOC)) {
        $kode_obat=trim($row["KODE_OBAT"]," ");
        $data_saldo[$kode_obat]['kode_obat']=$kode_obat;
        $data_saldo[$kode_obat]['saldo_awal']=$row["total_balance"];
    }
}
else if($kategori=="CHEMICAL"){
    // $query_get_data_now="SELECT p.ITEMTYPECODE ,p.SUBCODE01 ,p.SUBCODE02 ,p.SUBCODE03 ,p.SUBCODE04 , p.LONGDESCRIPTION
    // FROM 
    //     PRODUCT p
    // LEFT JOIN 
    //     adstorage c on p.ABSUNIQUEID = c.UNIQUEID and c.FIELDNAME='ShowChemical' AND c.NAMEENTITYNAME ='Product'
    // WHERE 
    //     c.VALUEBOOLEAN = 1
    //     AND P.ITEMTYPECODE ='DYC'
    //     AND p.SUBCODE01 = 'E'
    // ORDER BY p.SUBCODE01 ";
    // $result_now = db2_exec($conn1, $query_get_data_now, ['cursor' => DB2_SCROLLABLE]);
    // while($rowdb = db2_fetch_assoc($result_now)){
    //     $kode_obat=trim($rowdb["SUBCODE01"]," ")."-".trim($rowdb["SUBCODE02"]," ")."-".trim($rowdb["SUBCODE03"]," ");
    //     $data_now[$kode_obat]['kode_obat']=$kode_obat;
    //     $data_now[$kode_obat]['LONGDESCRIPTION']=$rowdb["LONGDESCRIPTION"];
    // }
    $query_get_data_now = "SELECT p.ITEMTYPECODE ,p.SUBCODE01 ,p.SUBCODE02 ,p.SUBCODE03 ,p.SUBCODE04 , p.LONGDESCRIPTION,
     CASE 
        WHEN b.QTY_MASUK IS NULL THEN 0 ELSE 
        b.QTY_MASUK
    END AS QTY_MASUK
FROM 
    PRODUCT p
LEFT JOIN 
    adstorage c on p.ABSUNIQUEID = c.UNIQUEID and c.FIELDNAME='ShowChemical' AND c.NAMEENTITYNAME ='Product'
LEFT JOIN (SELECT 
            ITEMTYPECODE, 
            DECOSUBCODE01, 
            DECOSUBCODE02, 
            DECOSUBCODE03, 
            SUM(QTY_MASUK) AS QTY_MASUK, 
            SATUAN_MASUK 
        FROM 
        (
            SELECT 
                ITEMTYPECODE, 
                TEMPLATE, 
                DECOSUBCODE01, 
                DECOSUBCODE02, 
                DECOSUBCODE03, 
                SUM(QTY_MASUK) AS QTY_MASUK, 
                SATUAN_MASUK 
            FROM 
            (
                SELECT 
                    s.TRANSACTIONDATE, 
                    s.TRANSACTIONNUMBER, 
                    CASE 
                        WHEN s3.TEMPLATECODE IS NOT NULL THEN s3.TEMPLATECODE 
                        ELSE s.TEMPLATECODE 
                    END AS TEMPLATE, 
                    s3.LOGICALWAREHOUSECODE AS terimadarigd, 
                    s.TEMPLATECODE AS TEMPLATE_S, 
                    s.ITEMTYPECODE, 
                    s.DECOSUBCODE01, 
                    s.DECOSUBCODE02, 
                    s.DECOSUBCODE03, 
                    s2.LONGDESCRIPTION, 
                    TRIM(s.DECOSUBCODE01) || '-' || TRIM(s.DECOSUBCODE02) || '-' || TRIM(s.DECOSUBCODE03) AS KODE_OBAT, 
                    CASE 
                        WHEN s.CREATIONUSER = 'MT_STI' AND s.TEMPLATECODE = 'OPN' AND (s.TRANSACTIONDATE ='2025-07-13' OR s.TRANSACTIONDATE ='2025-10-05') THEN 0 
                        WHEN s.USERPRIMARYUOMCODE = 't' THEN s.USERPRIMARYQUANTITY * 1000000 
                        WHEN s.USERPRIMARYUOMCODE = 'kg' THEN s.USERPRIMARYQUANTITY * 1000 
                        ELSE s.USERPRIMARYQUANTITY 
                    END AS QTY_MASUK, 
                    CASE 
                        WHEN s.USERPRIMARYUOMCODE = 't' THEN 'g' 
                        WHEN s.USERPRIMARYUOMCODE = 'kg' THEN 'g' 
                        ELSE s.USERPRIMARYUOMCODE 
                    END AS SATUAN_MASUK, 
                    CASE 
                        WHEN s.TEMPLATECODE = 'OPN' THEN s2.LONGDESCRIPTION 
                        WHEN s.TEMPLATECODE = 'QCT' THEN s.ORDERCODE 
                        WHEN s.TEMPLATECODE IN ('304','303','203','204') THEN 'Terima dari ' || TRIM(s3.LOGICALWAREHOUSECODE) 
                        WHEN s.TEMPLATECODE = '125' THEN 'Retur dari ' || TRIM(s.ORDERCODE ) 
                    END AS KETERANGAN 
                FROM STOCKTRANSACTION s            
                LEFT JOIN STOCKTRANSACTIONTEMPLATE s2 ON s2.CODE = s.TEMPLATECODE 
                LEFT JOIN INTERNALDOCUMENT i ON i.PROVISIONALCODE = s.ORDERCODE 
                LEFT JOIN ORDERPARTNER o ON o.CUSTOMERSUPPLIERCODE = i.ORDPRNCUSTOMERSUPPLIERCODE 
                LEFT JOIN LOGICALWAREHOUSE l ON l.CODE = o.CUSTOMERSUPPLIERCODE 
                LEFT JOIN STOCKTRANSACTION s3 
                    ON s3.TRANSACTIONNUMBER = s.TRANSACTIONNUMBER 
                    AND NOT s3.LOGICALWAREHOUSECODE = 'M101' 
                    AND s3.DETAILTYPE = 1 
                LEFT JOIN LOGICALWAREHOUSE l2 ON l2.CODE = s3.LOGICALWAREHOUSECODE 
                WHERE 
                    s.ITEMTYPECODE = 'DYC' 
                    AND s.TRANSACTIONDATE BETWEEN '$kemarin_stk_transaksi ' AND '$tgl_stk_op' 
                    AND (
                        (s.TRANSACTIONDATE > '$kemarin_stk_transaksi ' OR (s.TRANSACTIONDATE = '$kemarin_stk_transaksi ' AND s.TRANSACTIONTIME >= '23:01:00'))
                        AND (s.TRANSACTIONDATE < '$tgl_stk_op' OR (s.TRANSACTIONDATE = '$tgl_stk_op' AND s.TRANSACTIONTIME <= '$jam_stk_op:00'))
                    )
                    AND s.TEMPLATECODE IN ('QCT','304','OPN','204','125') 
                    AND NOT COALESCE(TRIM(
                        CASE WHEN s3.TEMPLATECODE IS NOT NULL THEN s3.TEMPLATECODE ELSE s.TEMPLATECODE END
                    ), '') || COALESCE(TRIM(
                        CASE WHEN s3.LOGICALWAREHOUSECODE IS NOT NULL THEN s3.LOGICALWAREHOUSECODE ELSE s.LOGICALWAREHOUSECODE END
                    ), '') IN ('OPNM101','303M101','304M510')
                    AND s.LOGICALWAREHOUSECODE IN ('M510','M101')
            ) AS sub 
            WHERE TEMPLATE <> '304' 
            GROUP BY 
                ITEMTYPECODE, 
                TEMPLATE, 
                DECOSUBCODE01, 
                DECOSUBCODE02, 
                DECOSUBCODE03, 
                SATUAN_MASUK
        ) x 
        GROUP BY 
            ITEMTYPECODE, 
            DECOSUBCODE01, 
            DECOSUBCODE02, 
            DECOSUBCODE03, 
            SATUAN_MASUK )b ON  b.DECOSUBCODE01 = p.SUBCODE01
                            AND b.DECOSUBCODE02 = p.SUBCODE02
                            AND b.DECOSUBCODE03 = p.SUBCODE03
    WHERE 
        c.VALUEBOOLEAN = 1
        AND P.ITEMTYPECODE ='DYC'
        AND (
            p.SUBCODE01 = 'C'
            OR p.SUBCODE01 = 'D'
            OR p.SUBCODE01 = 'R'
            OR p.SUBCODE01 = 'E'
            OR p.SUBCODE01 = 'P'
            OR p.SUBCODE01 = 'N'
        ) 
    ORDER BY p.SUBCODE01 ";

    $result_now = db2_exec($conn1, $query_get_data_now, ['cursor' => DB2_SCROLLABLE]);
    while ($rowdb = db2_fetch_assoc($result_now)) {
        $kode_obat = trim($rowdb["SUBCODE01"]) . "-" . trim($rowdb["SUBCODE02"]) . "-" . trim($rowdb["SUBCODE03"]);
        $data_now[$kode_obat]['kode_obat'] = $kode_obat;
        $data_now[$kode_obat]['LONGDESCRIPTION'] = $rowdb["LONGDESCRIPTION"];
        $data_now[$kode_obat]['QTY_MASUK'] = $rowdb["QTY_MASUK"];
    }

    $query_get_balance="SELECT KODE_OBAT, tgl_tutup, SUM(BASEPRIMARYQUANTITYUNIT) as total_balance 
    FROM (
		        SELECT DISTINCT
		            ITEMTYPECODE,
		            KODE_OBAT,
		            LONGDESCRIPTION,
		            LOTCODE,
		            LOGICALWAREHOUSECODE,
		            tgl_tutup,
                    WHSLOCATIONWAREHOUSEZONECODE,
		            BASEPRIMARYQUANTITYUNIT,
		            BASEPRIMARYUNITCODE,
                    WAREHOUSELOCATIONCODE
		        FROM db_laborat.tblopname_11
		            WHERE  tgl_tutup = ? 
		            AND NOT kode_obat = 'E-1-000'
    			    AND LEFT(KODE_OBAT, CHARINDEX('-', KODE_OBAT + '-') - 1)  = 'E'
    	) d
    GROUP BY tgl_tutup, KODE_OBAT ";
    $stmt_blc = sqlsrv_query($con, $query_get_balance, [$tgl_tutup]);
    while ($row = sqlsrv_fetch_array($stmt_blc, SQLSRV_FETCH_ASSOC)) {
        $kode_obat=trim($row["KODE_OBAT"]," ");
        $data_blc[$kode_obat]['kode_obat']=$kode_obat;
        $data_blc[$kode_obat]['balance']=$row["total_balance"];
    }

    $query_get_total_stk_opn = "SELECT  o.KODE_OBAT, tgl_tutup, SUM(o.total_stock) as total_stock
    FROM 
        db_laborat.tbl_stock_opname_gk o
    WHERE 
        o.tgl_tutup = ?
        AND LEFT(o.KODE_OBAT, CHARINDEX('-', o.KODE_OBAT + '-') - 1)  = 'E'
    GROUP BY tgl_tutup, KODE_OBAT ";
    $stmt_opn = sqlsrv_query($con, $query_get_total_stk_opn, [$tgl_tutup]);
    while ($row = sqlsrv_fetch_array($stmt_opn, SQLSRV_FETCH_ASSOC)) {
        $kode_obat=trim($row["KODE_OBAT"]," ");
        $data_opn[$kode_obat]['kode_obat']=$kode_obat;
        $data_opn[$kode_obat]['total_stock']=$row["total_stock"];
    }

    $query_saldo_awal="SELECT KODE_OBAT, tgl_tutup, SUM(BASEPRIMARYQUANTITYUNIT) as total_balance 
    FROM (
		        SELECT DISTINCT
		            ITEMTYPECODE,
		            KODE_OBAT,
		            LONGDESCRIPTION,
		            LOTCODE,
		            LOGICALWAREHOUSECODE,
		            tgl_tutup,
                    WHSLOCATIONWAREHOUSEZONECODE,
		            BASEPRIMARYQUANTITYUNIT,
		            BASEPRIMARYUNITCODE,
                    WAREHOUSELOCATIONCODE
		        FROM db_laborat.tblopname_11
		            WHERE  tgl_tutup = ? 
                    AND NOT kode_obat = 'E-1-000'
                    AND LEFT(KODE_OBAT, CHARINDEX('-', KODE_OBAT + '-') - 1)  = 'E'
    	) d
    GROUP BY tgl_tutup, KODE_OBAT ";
    $stmt_saldo = sqlsrv_query($con, $query_saldo_awal, [$akhir]);
    while ($row = sqlsrv_fetch_array($stmt_saldo, SQLSRV_FETCH_ASSOC)) {
        $kode_obat=trim($row["KODE_OBAT"]," ");
        $data_saldo[$kode_obat]['kode_obat']=$kode_obat;
        $data_saldo[$kode_obat]['saldo_awal']=$row["total_balance"];
    } 
}

if (count($data_now) > 0) {
    $query_get_data_transaksi="SELECT DECOSUBCODE01, DECOSUBCODE02, DECOSUBCODE03, SUM(BASEPRIMARYQUANTITY) TOTAL 
    FROM(
        SELECT DECOSUBCODE01, DECOSUBCODE02, DECOSUBCODE03, s.BASEPRIMARYQUANTITY ,timestamp(TRANSACTIONDATE,TRANSACTIONTIME) TRANSACTION_TIME 
        FROM DB2ADMIN.STOCKTRANSACTION AS s
        WHERE TRANSACTIONDATE BETWEEN '$kemarin' AND '$tgl_stk_op' 
        AND ITEMTYPECODE ='DYC' AND TEMPLATECODE ='120' 
        AND (
            s.LOGICALWAREHOUSECODE ='M101' OR s.LOGICALWAREHOUSECODE ='M510'
        )
        UNION ALL
        SELECT DECOSUBCODE01, DECOSUBCODE02, DECOSUBCODE03, s.BASEPRIMARYQUANTITY ,timestamp(TRANSACTIONDATE,TRANSACTIONTIME) TRANSACTION_TIME 
        FROM DB2ADMIN.STOCKTRANSACTION AS s
        WHERE TRANSACTIONDATE BETWEEN '$kemarin' AND '$tgl_stk_op' 
        AND ITEMTYPECODE ='DYC' AND TEMPLATECODE ='201' 
        AND (
            s.LOGICALWAREHOUSECODE ='M101' OR s.LOGICALWAREHOUSECODE ='M510'
        )
        UNION ALL
        SELECT DECOSUBCODE01, DECOSUBCODE02, DECOSUBCODE03, s.BASEPRIMARYQUANTITY ,timestamp(TRANSACTIONDATE,TRANSACTIONTIME) TRANSACTION_TIME 
        FROM DB2ADMIN.STOCKTRANSACTION AS s
        WHERE TRANSACTIONDATE BETWEEN '$kemarin' AND '$tgl_stk_op' 
        AND ITEMTYPECODE ='DYC' AND TEMPLATECODE ='098' 
        AND s.LOGICALWAREHOUSECODE ='M510' AND TRANSACTIONDATE <> '2025-10-05'
        UNION ALL
        SELECT s.DECOSUBCODE01, s.DECOSUBCODE02, s.DECOSUBCODE03, s.BASEPRIMARYQUANTITY ,timestamp(s.TRANSACTIONDATE, s.TRANSACTIONTIME) TRANSACTION_TIME
        FROM DB2ADMIN.STOCKTRANSACTION AS s 
        JOIN DB2ADMIN.STOCKTRANSACTION AS s2 ON s.TRANSACTIONNUMBER=s2.TRANSACTIONNUMBER AND s2.TEMPLATECODE = '204'
        WHERE s.TRANSACTIONDATE BETWEEN '$kemarin' AND '$tgl_stk_op' 
		AND s.ITEMTYPECODE = 'DYC'
		AND s.TEMPLATECODE = '203'
		AND s2.LOGICALWAREHOUSECODE <> 'M101'
		AND s2.LOGICALWAREHOUSECODE <> 'M510'
        UNION ALL
        SELECT s.DECOSUBCODE01, s.DECOSUBCODE02, s.DECOSUBCODE03, s.BASEPRIMARYQUANTITY, timestamp(s.TRANSACTIONDATE, s.TRANSACTIONTIME) TRANSACTION_TIME
	    FROM DB2ADMIN.STOCKTRANSACTION AS s
	    JOIN DB2ADMIN.STOCKTRANSACTION AS s2 ON s.TRANSACTIONNUMBER=s2.TRANSACTIONNUMBER AND s2.TEMPLATECODE = '304'
	    WHERE s.TRANSACTIONDATE BETWEEN '$kemarin' AND '$tgl_stk_op' 
		AND s.ITEMTYPECODE = 'DYC'
		AND s.TEMPLATECODE = '303'
		AND s2.LOGICALWAREHOUSECODE <> 'M101'
		AND s2.LOGICALWAREHOUSECODE <> 'M510'
    ) 
    WHERE TRANSACTION_TIME BETWEEN '$kemarin 23:01:00' AND '$tgl_stk_op $jam_stk_op:00'
    GROUP BY DECOSUBCODE01, DECOSUBCODE02, DECOSUBCODE03 ";
    $result_transaksi = db2_exec($conn1, $query_get_data_transaksi, ['cursor' => DB2_SCROLLABLE]);
    while($rowdb = db2_fetch_assoc($result_transaksi)){
        $kode_obat=trim($rowdb["DECOSUBCODE01"]," ")."-".trim($rowdb["DECOSUBCODE02"]," ")."-".trim($rowdb["DECOSUBCODE03"]," ");
        $data_transaksi[$kode_obat]['kode_obat']=$kode_obat;
        $data_transaksi[$kode_obat]['total_tansaksi']=$rowdb["TOTAL"];
    }

    $query_get_total_pemakaian="SELECT DECOSUBCODE01, DECOSUBCODE02, DECOSUBCODE03, SUM(BASEPRIMARYQUANTITY) TOTAL 
    FROM(
        SELECT DECOSUBCODE01, DECOSUBCODE02, DECOSUBCODE03, s.BASEPRIMARYQUANTITY , timestamp(TRANSACTIONDATE,TRANSACTIONTIME) TRANSACTION_TIME 
	    FROM DB2ADMIN.STOCKTRANSACTION AS s
        WHERE TRANSACTIONDATE BETWEEN '$tanggal1_tutup' AND '$tgl_tutup' 
        AND ITEMTYPECODE ='DYC' 
        AND TEMPLATECODE ='120' 
        AND  (s.LOGICALWAREHOUSECODE ='M101' OR s.LOGICALWAREHOUSECODE ='M510' )
        UNION ALL
        SELECT DECOSUBCODE01, DECOSUBCODE02, DECOSUBCODE03, s.BASEPRIMARYQUANTITY , timestamp(TRANSACTIONDATE,TRANSACTIONTIME) TRANSACTION_TIME 
	    FROM DB2ADMIN.STOCKTRANSACTION AS s
        WHERE TRANSACTIONDATE BETWEEN '$tanggal1_tutup' AND '$tgl_tutup' 
        AND ITEMTYPECODE ='DYC' 
        AND TEMPLATECODE ='201' 
        AND  (s.LOGICALWAREHOUSECODE ='M101' OR s.LOGICALWAREHOUSECODE ='M510' )
        UNION ALL
        SELECT DECOSUBCODE01, DECOSUBCODE02, DECOSUBCODE03, s.BASEPRIMARYQUANTITY , timestamp(TRANSACTIONDATE,TRANSACTIONTIME) TRANSACTION_TIME 
        FROM DB2ADMIN.STOCKTRANSACTION AS s
        WHERE TRANSACTIONDATE BETWEEN '$tanggal1_tutup' AND '$tgl_tutup' 
        AND ITEMTYPECODE ='DYC' 
        AND TEMPLATECODE ='098' 
        AND s.LOGICALWAREHOUSECODE ='M510' AND TRANSACTIONDATE <> '2025-10-05'
        UNION ALL
        SELECT s.DECOSUBCODE01, s.DECOSUBCODE02, s.DECOSUBCODE03, s.BASEPRIMARYQUANTITY ,timestamp(s.TRANSACTIONDATE, s.TRANSACTIONTIME) TRANSACTION_TIME
        FROM DB2ADMIN.STOCKTRANSACTION AS s 
        JOIN DB2ADMIN.STOCKTRANSACTION AS s2 ON s.TRANSACTIONNUMBER=s2.TRANSACTIONNUMBER AND s2.TEMPLATECODE = '204'
        WHERE s.TRANSACTIONDATE BETWEEN '$tanggal1_tutup' AND '$tgl_tutup' 
		AND s.ITEMTYPECODE = 'DYC'
		AND s.TEMPLATECODE = '203'
		AND s2.LOGICALWAREHOUSECODE <> 'M101'
		AND s2.LOGICALWAREHOUSECODE <> 'M510'
        UNION ALL
        SELECT s.DECOSUBCODE01, s.DECOSUBCODE02, s.DECOSUBCODE03, s.BASEPRIMARYQUANTITY, timestamp(s.TRANSACTIONDATE, s.TRANSACTIONTIME) TRANSACTION_TIME
	    FROM DB2ADMIN.STOCKTRANSACTION AS s
	    JOIN DB2ADMIN.STOCKTRANSACTION AS s2 ON s.TRANSACTIONNUMBER=s2.TRANSACTIONNUMBER AND s2.TEMPLATECODE = '304'
	    WHERE s.TRANSACTIONDATE BETWEEN '$tanggal1_tutup' AND '$tgl_tutup' 
		AND s.ITEMTYPECODE = 'DYC'
		AND s.TEMPLATECODE = '303'
		AND s2.LOGICALWAREHOUSECODE <> 'M101'
		AND s2.LOGICALWAREHOUSECODE <> 'M510'
    )
    WHERE TRANSACTION_TIME BETWEEN '$tanggal1_tutup 00:00:00' AND '$tgl_tutup 23:00:00'
    GROUP BY DECOSUBCODE01, DECOSUBCODE02, DECOSUBCODE03";
    $result_total_pemakaian = db2_exec($conn1, $query_get_total_pemakaian, ['cursor' => DB2_SCROLLABLE]);
    while($rowdb = db2_fetch_assoc($result_total_pemakaian)){
        $kode_obat=trim($rowdb["DECOSUBCODE01"]," ")."-".trim($rowdb["DECOSUBCODE02"]," ")."-".trim($rowdb["DECOSUBCODE03"]," ");
        $data_total_pemakaian[$kode_obat]['kode_obat']=$kode_obat;
        $data_total_pemakaian[$kode_obat]['total_pemakaian']=$rowdb["TOTAL"];
    }

        $no = 1;
        echo "<table class='table table-bordered table-striped' id='detailmasukTable' border=1>";
        echo "<thead>
                <tr>
                    <th class='text-center'>No</th>
                    <th class='text-center'>Kode Obat</th>
                    <th class='text-center'>Nama Dyestuff/ Kimia</th>
                    <th class='text-center'>Stock Balance</th>
                    <th class='text-center'>Pemasukan</th>
                    <th class='text-center'>Pemakaian</th>
                    <th class='text-center'>Ending Balance</th>
                    <th class='text-center'>Stock Opname</th>
                    <th class='text-center'>Selisih Absolute</th>
                    <th class='text-center'>Selisih + / -</th>
                    <th class='text-center'>TOTAL <br> Pemakaian s/d</th>
                    <th class='text-center'>% Selisih</th>
                    <th class='text-center'>SALDO AWAL</th>
                </tr>
            </thead>";
        echo "<tbody>";

        foreach($data_now as $index => $row){
            //baru
            $pemasukan = floatval($row['QTY_MASUK'] ?? 0);
            //
            $balance=floatval($data_blc[$index]['balance']??0);
            $transaksi=floatval($data_transaksi[$index]['total_tansaksi']??0)*1000;
            $total_stock=floatval($data_opn[$index]['total_stock']??0);
            $total_pemakaian=floatval($data_total_pemakaian[$index]['total_pemakaian']??0)*1000;
            $saldo_awal=floatval($data_saldo[$index]['saldo_awal']??0);
            $total_balance_gram=$balance*1000;
            $saldo_awal_gram=round(($saldo_awal*1000),2);
            $ending_balance=($total_balance_gram+ $pemasukan)-$transaksi;
            $selisih_balance=$ending_balance-$total_stock;
            $selisih_plusminus=$total_stock-$ending_balance;
            if($selisih_balance>=0){
            }else{
                $selisih_balance*= -1;
            }
            // if($selisih_plusminus>=0){
            //     $status="+";
            // }else{
            //     $status="-";
            //     $selisih_plusminus*= -1;
            // }
            if($total_pemakaian==0){
                $selisih_persen=0;
            }else{
                $selisih_persen=$selisih_plusminus/$total_pemakaian;
            }
            $persen_selisih=round(($selisih_persen*100),2);

            //HITUNG TOTAL        
            $TOTAL_BLC+=$total_balance_gram;
            $TOTAL_PAKAI+=$transaksi;
            $TOTAL_ENDING_BLC+=$ending_balance;
            $TOTAL_STC_OPN+=$total_stock;
            $TOTAL_SD+=$total_pemakaian;
            $TOTAL_SALDO+=$saldo_awal_gram;
            //baru
            $TOTAL_PEMASUKAN += $pemasukan;
            //


            if($jenis_data=="excel"){
                echo "<tr>
                    <td class='text-center'>{$no}</td>
                    <td>" . htmlspecialchars($index) . "</td>
                    <td>" . htmlspecialchars($row['LONGDESCRIPTION']) . "</td>
                    <td class='number'>" . round($total_balance_gram,0). "</td>
                    <td class='number'>" . round($pemasukan, 0) . "</td>
                    <td class='number'>" . round($transaksi,0). "</td>
                    <td class='number'>" . round($ending_balance,0). "</td>
                    <td class='number'>" . round($total_stock,0). "</td>
                    <td class='number'>" . round($selisih_balance,0). "</td>
                    <td class='number'>" . round($selisih_plusminus,0). "</td>
                    <td class='number'>" . round($total_pemakaian,0). "</td>
                    <td class='duadigit'>" . round($persen_selisih,2). "</td>
                    <td class='number'>" . round($saldo_awal_gram,0). "</td>
                </tr>";
            }else{
                echo "<tr>
                    <td class='text-center'>{$no}</td>
                    <td>" . htmlspecialchars($index) . "</td>
                    <td>" . htmlspecialchars($row['LONGDESCRIPTION']) . "</td>
                    <td>" . Penomoran_helper::nilaiKeRibuan(round($total_balance_gram,0),',','.') . "</td>
                    <td>" . Penomoran_helper::nilaiKeRibuan(round($pemasukan, 0), ',', '.') . "</td>
                    <td>" . Penomoran_helper::nilaiKeRibuan(round($transaksi,0),',','.') . "</td>
                    <td>" . Penomoran_helper::nilaiKeRibuan(round($ending_balance,0),',','.')  . "</td>
                    <td>" . Penomoran_helper::nilaiKeRibuan(round($total_stock,0),',','.') . "</td>
                    <td>" . Penomoran_helper::nilaiKeRibuan(round($selisih_balance,0),',','.')  . "</td>
                    <td>" . Penomoran_helper::nilaiKeRibuan(round($selisih_plusminus,0),',','.') . "</td>
                    <td>" . Penomoran_helper::nilaiKeRibuan(round($total_pemakaian,0),',','.') . "</td>
                    <td>" . Penomoran_helper::nilaiKeRibuan(round($persen_selisih,2),',','.') . "% </td>
                    <td>" . Penomoran_helper::nilaiKeRibuan(round($saldo_awal_gram,0),',','.'). "</td>
                </tr>";
            }
            $no++;
        }
        $TOTAL_SLS_ABS=$TOTAL_ENDING_BLC-$TOTAL_STC_OPN;
        $TOTAL_SLS_PLUSMIN=$TOTAL_STC_OPN-$TOTAL_ENDING_BLC;
        $TOTAL_PERSEN_SLS='~';
        if($TOTAL_SD!=0){
            $TOTAL_PERSEN_SLS=$TOTAL_SLS_PLUSMIN/$TOTAL_SD;
        }
        if($jenis_data=="excel"){
        echo "</tbody>
        <tfoot>
            <tr>
                <th class='text-center'></th>
                <th class='text-center'></th>
                <th class='text-center'>GRAND TOTAL</th>
                <td class='number'>" . round($TOTAL_BLC,0). "</td>
                <td class='number'>" . round($TOTAL_PEMASUKAN, 0) . "</td>
                <td class='number'>" . round($TOTAL_PAKAI,0). "</td>
                <td class='number'>" . round($TOTAL_ENDING_BLC,0). "</td>
                <td class='number'>" . round($TOTAL_STC_OPN,0). "</td>
                <td class='number'>" . round($TOTAL_SLS_ABS,0). "</td>
                <td class='number'>" . round($TOTAL_SLS_PLUSMIN,0). "</td>
                <td class='number'>" . round($TOTAL_SD,0). "</td>
                <td class='duadigit'>" . round(((is_numeric($TOTAL_PERSEN_SLS) ? $TOTAL_PERSEN_SLS : 0) * 100),2). "</td>
                <td class='number'>" . round($TOTAL_SALDO,0). "</td>
            </tr>
        </tfoot>
        </table>";
        }else{
        echo "</tbody>
        <tfoot>
            <tr>
                <th class='text-center'></th>
                <th class='text-center'></th>
                <th class='text-center'>GRAND TOTAL</th>
                <td>" . Penomoran_helper::nilaiKeRibuan(round($TOTAL_BLC,0),',','.') . "</td>
                <td>" . Penomoran_helper::nilaiKeRibuan(round($TOTAL_PEMASUKAN, 0), ',', '.') . "</td>
                <td>" . Penomoran_helper::nilaiKeRibuan(round($TOTAL_PAKAI,0),',','.') . "</td>
                <td>" . Penomoran_helper::nilaiKeRibuan(round($TOTAL_ENDING_BLC,0),',','.')  . "</td>
                <td>" . Penomoran_helper::nilaiKeRibuan(round($TOTAL_STC_OPN,0),',','.') . "</td>
                <td>" . Penomoran_helper::nilaiKeRibuan(round($TOTAL_SLS_ABS,0),',','.')  . "</td>
                <td>" . Penomoran_helper::nilaiKeRibuan(round($TOTAL_SLS_PLUSMIN,0),',','.')  . "</td>
                <td>" . Penomoran_helper::nilaiKeRibuan(round($TOTAL_SD,0),',','.') . "</td>
                <td>" . Penomoran_helper::nilaiKeRibuan(round(((is_numeric($TOTAL_PERSEN_SLS) ? $TOTAL_PERSEN_SLS : 0) * 100),2),',','.') . "%</td>
                <td>" . Penomoran_helper::nilaiKeRibuan(round($TOTAL_SALDO,0),',','.'). "</td>
            </tr>
        </tfoot>
        </table>";
        }
    }else{
        echo "Data Rekap Stock Opname GK Tidak Tersedia";
    }
?>
