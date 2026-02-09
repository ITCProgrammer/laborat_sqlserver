<?php
ini_set("error_reporting", 1);
include "../koneksi.php";
$Awal = date('Y-m-d');
$cektgl = sqlsrv_query(
    $con,
    "SELECT TOP 1
        CONVERT(date, GETDATE()) as tgl,
        COUNT(*) OVER() as ck,
        DATEPART(HOUR, GETDATE()) as jam,
        CONVERT(varchar(5), GETDATE(), 108) as jam1,
        tgl_tutup
     FROM db_laborat.tbl_listsch_11
     WHERE tgl_tutup = ?",
    [$Awal]
);
$dcek = $cektgl ? sqlsrv_fetch_array($cektgl, SQLSRV_FETCH_ASSOC) : ['ck' => 0];
if($dcek['ck']>0){
	echo "<script>";
	echo "alert('Stok Tgl ".$dcek['tgl_tutup']." Ini Sudah Pernah ditutup')";
	echo "</script>";
}else if($_GET['note']!="" or $_GET['note']=="Berhasil"){
	echo "Tutup Transaksi Berhasil";
}else{
?>
<?php
                        
                        $sql = sqlsrv_query(
                            $con,
                            "SELECT a.id, a.no_resep, a.no_order, a.warna, a.no_warna, a.no_item, a.langganan, a.no_po, b.approve, a.jenis_matching, a.benang,
                                    b.id as id_status, b.status, a.status_bagi, ISNULL(b.ket, a.note) as ket, a.tgl_update
                             FROM db_laborat.tbl_matching a
                             LEFT JOIN db_laborat.tbl_status_matching b on a.no_resep = b.idm
                             WHERE b.approve_at IS NULL
                             ORDER BY a.id DESC"
                        );
                                                ?>
<?php 
$sqlupdate = false;
while ($sql && ($li = sqlsrv_fetch_array($sql, SQLSRV_FETCH_ASSOC))) { 
	$sqlupdate = sqlsrv_query(
        $con,
        "INSERT INTO db_laborat.tbl_listsch_11
            (no_resep, no_order, warna, no_warna, no_item, langganan, no_po, approve, jenis_matching, benang, id_status, status, status_bagi, ket, tgl_update, tgl_tutup, tgl_buat)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE())",
        [
            $li['no_resep'],
            $li['no_order'],
            $li['warna'],
            $li['no_warna'],
            $li['no_item'],
            $li['langganan'],
            $li['no_po'],
            $li['approve'],
            $li['jenis_matching'],
            $li['benang'],
            $li['id_status'],
            $li['status'],
            $li['status_bagi'],
            $li['ket'],
            $li['tgl_update'],
            $Awal
        ]
    );
}
if($sqlupdate){
		echo "<meta http-equiv='refresh' content='0; url=cetak/ListScheduleRekapExcel11.php?tgl=$Awal'>";
		//echo "<meta http-equiv='refresh' content='0; url=List-Schedule-Auto.php?note=Berhasil'>";
	}
}
?>

                        
