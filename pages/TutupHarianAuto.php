<?php
include "../koneksi.php";
ini_set("error_reporting", 1);
$Awal = date("Y-m-d");
//$Awal = date("Y-m-d", strtotime("-2 day"));

// $cektgl=mysqli_query($con,"SELECT DATE_FORMAT(NOW(),'%Y-%m-%d') as tgl,COUNT(tgl_tutup) as ck ,DATE_FORMAT(NOW(),'%H') as jam,DATE_FORMAT(NOW(),'%H:%i') as jam1 FROM tblopname_11 WHERE tgl_tutup='".$Awal."' LIMIT 1");
// $dcek=mysqli_fetch_array($cektgl);

$sql = " SELECT TOP 1
    CONVERT(varchar(10), GETDATE(), 23) AS tgl,
    COUNT(tgl_tutup) AS ck,
    RIGHT('0' + CAST(DATEPART(HOUR, GETDATE()) AS varchar(2)), 2) AS jam,
    CONVERT(varchar(5), GETDATE(), 108) AS jam1
FROM db_laborat.tblopname_11
WHERE tgl_tutup = ?
";

$cektgl = sqlsrv_query($conn, $sql, [$Awal]);
if ($cektgl === false) {
	die(print_r(sqlsrv_errors(), true));
}

$dcek = sqlsrv_fetch_array($cektgl, SQLSRV_FETCH_ASSOC);

$t1=strtotime($Awal);
$t2=strtotime($dcek['tgl']);
$selh=round(abs($t2-$t1)/(60*60*45));

if($dcek['ck']>0){	
	
		echo "<script>";
		echo "alert('Stok Tgl ".$Awal." Ini Sudah Pernah ditutup')";
		echo "</script>";	
	
	}else if($dcek['jam'] < 6){		
		echo "<script>";
		echo "alert('Tidak Bisa Tutup Sebelum jam 11 Malam Sampai jam 12 Malam, Sekarang Masih Jam ".$dcek['jam1']."')";
		echo "</script>";
	
			}
			else{	
			$sqlDB21 = " SELECT 
		b.ITEMTYPECODE,
		b.LOGICALWAREHOUSECODE,
		b.DECOSUBCODE01,
		b.DECOSUBCODE02,
		b.DECOSUBCODE03,
		b.DECOSUBCODE04,
		b.DECOSUBCODE05,
		b.DECOSUBCODE06,
		b.DECOSUBCODE07,
		b.DECOSUBCODE08,
		b.DECOSUBCODE09,
		b.DECOSUBCODE10, 
		b.WAREHOUSELOCATIONCODE,
		b.WHSLOCATIONWAREHOUSEZONECODE,
		b.LOTCODE,
		TRIM(b.DECOSUBCODE01) || '-' || TRIM(b.DECOSUBCODE02) || '-' || TRIM(b.DECOSUBCODE03) AS KODE_OBAT,
		b.BASEPRIMARYUNITCODE,
		b.BASEPRIMARYQUANTITYUNIT,
		p.LONGDESCRIPTION
		FROM 
		BALANCE b
		LEFT JOIN PRODUCT p ON 
		p.ITEMTYPECODE = b.ITEMTYPECODE 
			AND p.SUBCODE01 = b.DECOSUBCODE01
			AND p.SUBCODE02 = b.DECOSUBCODE02 
			AND p.SUBCODE03 = b.DECOSUBCODE03 
			AND p.SUBCODE04 = b.DECOSUBCODE04 
			AND p.SUBCODE05 = b.DECOSUBCODE05 
			AND p.SUBCODE06 = b.DECOSUBCODE06 
			AND p.SUBCODE07 = b.DECOSUBCODE07 
			AND p.SUBCODE08 = b.DECOSUBCODE08 
			AND p.SUBCODE09 = b.DECOSUBCODE09 
			AND p.SUBCODE10 = b.DECOSUBCODE10 
		WHERE 
		b.ITEMTYPECODE = 'DYC'
		AND b.DETAILTYPE = 1
		AND b.LOGICALWAREHOUSECODE IN ('M510','M101')";
			$stmt1   = db2_exec($conn1,$sqlDB21, array('cursor'=>DB2_SCROLLABLE));
			//}				  
			while($rowdb21 = db2_fetch_assoc($stmt1)){

			$sql = " INSERT INTO db_laborat.tblopname_11a
				(
				ITEMTYPECODE, LOGICALWAREHOUSECODE,
				DECOSUBCODE01, DECOSUBCODE02, DECOSUBCODE03, DECOSUBCODE04, DECOSUBCODE05,
				DECOSUBCODE06, DECOSUBCODE07, DECOSUBCODE08, DECOSUBCODE09, DECOSUBCODE10,
				WAREHOUSELOCATIONCODE, WHSLOCATIONWAREHOUSEZONECODE,
				LOTCODE, KODE_OBAT, LONGDESCRIPTION,
				BASEPRIMARYUNITCODE, BASEPRIMARYQUANTITYUNIT,
				tgl_tutup, tgl_buat
				)
				VALUES
				(
				?, ?,
				?, ?, ?, ?, ?,
				?, ?, ?, ?, ?,
				?, ?,
				?, ?, ?,
				?, ?,
				?, GETDATE()
				)";

				$params = [
					$rowdb21['ITEMTYPECODE'],
					$rowdb21['LOGICALWAREHOUSECODE'],
					$rowdb21['DECOSUBCODE01'],
					$rowdb21['DECOSUBCODE02'],
					$rowdb21['DECOSUBCODE03'],
					$rowdb21['DECOSUBCODE04'],
					$rowdb21['DECOSUBCODE05'],
					$rowdb21['DECOSUBCODE06'],
					$rowdb21['DECOSUBCODE07'],
					$rowdb21['DECOSUBCODE08'],
					$rowdb21['DECOSUBCODE09'],
					$rowdb21['DECOSUBCODE10'],
					$rowdb21['WAREHOUSELOCATIONCODE'],
					$rowdb21['WHSLOCATIONWAREHOUSEZONECODE'],
					$rowdb21['LOTCODE'],
					$rowdb21['KODE_OBAT'],
					$rowdb21['LONGDESCRIPTION'],
					$rowdb21['BASEPRIMARYUNITCODE'],
					$rowdb21['BASEPRIMARYQUANTITYUNIT'],
					$Awal
				];

				$stmt = sqlsrv_query($connSqlServer, $sql, $params);
				if ($stmt === false)
					die(print_r(sqlsrv_errors(), true));
			
			}
			if($stmt){		
				echo "<script>";
				echo "alert('Stok Tgl ".$Awal." Sudah ditutup')";
				echo "window.open('', '_self').close();"; // cara variasi untuk nutup tab
				echo "</script>";
				// echo "<meta http-equiv='refresh' content='0; url=TutupHarianAuto.php?note=Berhasil'>";
				
				}			
		}
?>