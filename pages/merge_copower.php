<?php
ini_set("error_reporting", 1);
session_start();
include "koneksi.php";

$time = date('Y-m-d H:i:s');
$id_matching = $_POST['id_matching'];
$id_status   = $_POST['id_status'];
$ip_num      = $_SERVER['REMOTE_ADDR'];

$fail = function($ctx){
    $err = print_r(sqlsrv_errors(), true);
    echo "<pre>[$ctx] gagal.\n$err</pre>";
    exit;
};

// set status hold
if(!sqlsrv_query($con,"UPDATE db_laborat.tbl_status_matching SET status = 'hold' where id = ?", [$id_status])) $fail('update status');

if (isset($_POST['submit'])) {

    // validasi file
    $fileName = $_FILES['file']['name'];
    $fileTmpName = $_FILES['file']['tmp_name'];
    $fileSize = $_FILES['file']['size'];
    $fileError = $_FILES['file']['error'];
    $fileExt = explode('.', $fileName);
    $fileActualExt = strtolower(end($fileExt));
    $allowed = array('txt');

    if (!in_array($fileActualExt, $allowed)) {
        die(" <script>alert('Type File yang anda upload tidak di izinkan !'); location.href='index1.php?p=Status-Handle&idm=" . $id_status . "';</script>");
    }
    if ($fileError !== 0) {
        die(" <script>alert('File Anda Mengandung Konten Berbahaya'); location.href='index1.php?p=Status-Handle&idm=" . $id_status . "';</script>");
    }
    if ($fileSize >= 524288) {
        die(" <script>alert('File Maximal 500kb!'); location.href='index1.php?p=Status-Handle&idm=" . $id_status . "';</script>");
    }

    $fname = $fileName;
    $rawBaseName = pathinfo($fname, PATHINFO_FILENAME);
    $extension = pathinfo($fname, PATHINFO_EXTENSION);
    $counter = 0;
    while (file_exists('uploads/' . $fname)) {
        $fname = $rawBaseName . '_(' . ($counter + 1) . ').' . $extension;
        $counter++;
    };
    move_uploaded_file($_FILES['file']['tmp_name'], 'uploads/' . $fname);

    // baca file
    $file = new SplFileObject('uploads/' . $fname);
    $parts = [];
    while (!$file->eof()) {
        $line = $file->fgets();
        $parts[] = preg_split('/  +/', $line);
    }

    $master = 0;
    $count = intval(count($parts) - 2);
    for ($i = 0; $i <= $count; $i++) {
        if ($i == $master) {
            $rcode = substr($parts[$i][0], 3);
        } else if ($i > $master) {
            $dyess = trim($parts[$i][0]);
            if ($dyess === '') continue;
            $qty = floatval(substr(trim($parts[$i][1]), 0, -1));
            $C_uom = substr(trim($parts[$i][1]), -1);

            $sql = sqlsrv_query($con,"SELECT TOP 1 Product_Name from db_laborat.tbl_dyestuff where code = ?", [$dyess]);
            if(!$sql) $fail("lookup $dyess");
            $data = sqlsrv_fetch_array($sql, SQLSRV_FETCH_ASSOC);
            $uom = ($C_uom == 'F') ? ' (%)' : ' (Gr/L)';

            $sql_max = sqlsrv_query($con,"SELECT max(flag) as max_flag from db_laborat.tbl_matching_detail where id_matching = ? and id_status = ?", [$id_matching, $id_status]);
            if(!$sql_max) $fail('max_flag');
            $max = sqlsrv_fetch_array($sql_max, SQLSRV_FETCH_ASSOC);
            $flag = empty($max['max_flag']) ? 1 : ($max['max_flag'] + 1);

            $ins = sqlsrv_query($con,"INSERT into db_laborat.tbl_matching_detail 
                        (id_matching,id_status,resep,flag,kode,nama,conc1,time_1,doby1,remark,inserted_at,inserted_by)
                        VALUES (?,?,?,?,?,?,?,GETDATE(),?, 'from merge Co-power',GETDATE(),?)",
                        [$id_matching,$id_status,$rcode,$flag,$dyess,$data['Product_Name'].$uom,$qty,$_SESSION['userLAB'],$_SESSION['userLAB']]);
            if(!$ins) $fail("insert detail $dyess");
        }
    }
    $sqlNoResep = sqlsrv_query($con,"SELECT idm from db_laborat.tbl_status_matching where id = ?",[$id_status]);
    if(!$sqlNoResep) $fail('get idm');
    $NoResep = sqlsrv_fetch_array($sqlNoResep, SQLSRV_FETCH_ASSOC);
    sqlsrv_query($con,"INSERT INTO db_laborat.log_status_matching (ids,status,info,do_by,do_at,ip_address)
            VALUES (?,?,?,?,?,?)",
            [$NoResep['idm'], 'hold', "Merge data from $fname", $_SESSION['userLAB'], $time, $ip_num]);

    echo "<script>location.href='index1.php?p=Hold-Handle&idm=" . $id_status . "';</script>";
}
?>
