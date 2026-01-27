<?php
ini_set("error_reporting", 1);
session_start();
include "koneksi.php";
$time = date('Y-m-d H:i:s');

// helper untuk menampilkan pesan error SQLSRV lalu stop
function stopOnFail($stmt, $context)
{
    if ($stmt === false) {
        $err = print_r(sqlsrv_errors(), true);
        echo "<pre>[$context] gagal.\n$err</pre>";
        exit;
    }
}

$id_matching = $_POST['id_matching'];
$id_status = $_POST['id_status'];
stopOnFail(sqlsrv_query($con,"DELETE FROM db_laborat.tbl_matching_detail WHERE id_matching = ? AND id_status = ?", [$id_matching, $id_status]), 'hapus detail lama');
stopOnFail(sqlsrv_query($con,"UPDATE db_laborat.tbl_status_matching SET status = 'hold' WHERE id = ?", [$id_status]), 'update status hold');
if (isset($_POST['submit'])) {

    // define attribute from multipart form appart
    $fileName = $_FILES['file']['name'];
    $fileTmpName = $_FILES['file']['tmp_name'];
    $fileSize = $_FILES['file']['size'];
    $master = 0;
    $fileError = $_FILES['file']['error'];
    $fileType = $_FILES['file']['type'];
    $fileExt = explode('.', $fileName);
    $fileActualExt = strtolower(end($fileExt));
    $allowed = array('txt');

    // validation file size format and erroring inside file. => upload into /uploads.
    if (in_array($fileActualExt, $allowed)) {
        if ($fileError === 0) {
            if ($fileSize < 524288) {
                $fname = $fileName;
                $rawBaseName = pathinfo($fname, PATHINFO_FILENAME);
                $extension = pathinfo($fname, PATHINFO_EXTENSION);
                $counter = 0;
                while (file_exists('uploads/' . $fname)) {
                    // rename apabila terdapat file yang sama !
                    $fname = $rawBaseName . '_(' . ($counter + 1) . ').' . $extension;
                    $counter++;
                    // echo " <script>alert('File dengan nama tersebut sudah ada !!!'); location.href='index1.php?p=Export_coPower';</script>";
                    // die;
                };
                move_uploaded_file($_FILES['file']['tmp_name'], 'uploads/' . $fname);
            } else {
                echo " <script>alert('File Maximal 500kb!'); location.href='index1.php?p=Status-Handle&idm=" . $id_status . "';</script>";
                die;
            }
        } else {
            echo " <script>alert('File Anda Mengandung Konten Berbahaya'); location.href='index1.php?p=Status-Handle&idm=" . $id_status . "';</script>";
            die;
        }
    } else {
        echo " <script>alert('Type File yang anda upload tidak di izinkan !'); location.href='index1.php?p=Status-Handle&idm=" . $id_status . "';</script>";
        die;
    }

    // define txt to object array/json.
    $file = new SplFileObject('uploads/' . $fname);
    // wrapping array per line.
    $parts = [];
    while (!$file->eof()) {
        $line = $file->fgets();
        $parts[] = preg_split('/  +/', $line);
    }

    // fetch array using index , first - 1 & last -1 , -1 + -1 = 2;
    $count = intval(count($parts) - 2);
    for ($i = 0; $i <= $count; $i++) {
        if ($i == $master) {
            $rcode = substr($parts[$i][0], 3);
            $color = $parts[$i][1];
        } else if ($i > $master) {
            for ($O = 0; $O <= 0; $O++) {
                $dyess = trim($parts[$i][0]);
                $qty = floatval(substr(trim($parts[$i][1]), 0, -1));
                $C_uom = substr(trim($parts[$i][1]), -1);
                $sql = sqlsrv_query($con,"SELECT TOP 1 Product_Name from db_laborat.tbl_dyestuff where code = ?", [$dyess]);
                stopOnFail($sql, "lookup code $dyess");
                $data = sqlsrv_fetch_array($sql, SQLSRV_FETCH_ASSOC);
                if ($C_uom == 'F') {
                    $uom = '(%)';
                } else if ($C_uom == 'G') {
                    $uom = '(Gr/L)';
                }
                $ins = sqlsrv_query($con,"INSERT INTO db_laborat.tbl_matching_detail 
                        (id_matching,id_status,resep,flag,kode,nama,conc1,time_1,doby1,remark,inserted_at,inserted_by)
                        VALUES (?,?,?,?,?,?,?,GETDATE(),?, 'from Co-power',GETDATE(),?)",
                        [$id_matching,$id_status,$rcode,$i,$dyess,$data['Product_Name']." ".$uom,$qty,$_SESSION['userLAB'],$_SESSION['userLAB']]);
                stopOnFail($ins, "insert detail $dyess");
            }
        }
    }
    $sqlNoResep = sqlsrv_query($con,"SELECT idm from db_laborat.tbl_status_matching where id = ?",[$id_status]);
    stopOnFail($sqlNoResep, 'ambil no resep');
    $NoResep = sqlsrv_fetch_array($sqlNoResep, SQLSRV_FETCH_ASSOC);
    $ip_num = $_SERVER['REMOTE_ADDR'];
    stopOnFail(sqlsrv_query($con,"INSERT INTO db_laborat.log_status_matching (ids,status,info,do_by,do_at,ip_address)
            VALUES (?, 'hold', ?, ?, ?, ?)",
            [$NoResep['idm'], "Import data from $fname", $_SESSION['userLAB'], $time, $ip_num]), 'log import');

    echo "<script>location.href='index1.php?p=Hold-Handle&idm=" . $id_status . "';</script>";
}
