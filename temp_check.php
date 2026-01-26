<?php
include 'koneksi.php';
$statuses=array('scheduled','in_progress_dispensing','in_progress_dyeing');
$statusList="'".implode("','",$statuses)."'";
$sql="SELECT tps.no_resep FROM db_laborat.tbl_preliminary_schedule tps WHERE tps.status IN ($statusList) AND tps.is_old_data = 0";
$stmt=sqlsrv_query($con,$sql);
if(!$stmt){var_dump(sqlsrv_errors());} else {echo "OK\n";}
?>
