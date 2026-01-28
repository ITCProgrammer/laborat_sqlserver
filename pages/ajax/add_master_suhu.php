<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../../koneksi.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = trim($_POST['product_name']);
    $program = $_POST['program'];
    $dyeing = $_POST['dyeing'];
    $dispensing = $_POST['dispensing'];
    $status = isset($_POST['status']) ? intval($_POST['status']) : 1;

    // Ambil suhu & durasi dari product_name
    preg_match("/(\d+)[^\d]+X[^\d]+(\d+)/", $product_name, $matches);
    $suhu = isset($matches[1]) ? $matches[1] : '';
    $durasi = isset($matches[2]) ? $matches[2] : '';

    if ($suhu == '' || $durasi == '') {
        echo "<div style='color:red'>Format Product Name salah. Contoh benar: 60°C X 30 MNT</div>";
    } else {
        if ($program == 'KONSTAN') {
            $prefix = "1";

            // Cari apakah sudah ada suhu dengan awalan ini
            $query = "SELECT TOP 1 [group] FROM db_laborat.master_suhu WHERE program = ? AND suhu LIKE ? AND dyeing = ?";
            $params = ['1', $suhu . '%', $dyeing];
            $result = sqlsrv_query($con, $query, $params);
            if ($result !== false && ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC))) {
                $group = $row['group'];
            } else {
                // Ambil max kode belakang, generate baru
                $last = sqlsrv_query(
                    $con,
                    "SELECT MAX(CAST(SUBSTRING([group], 2, 2) AS int)) as max_suffix FROM db_laborat.master_suhu WHERE program = ?",
                    ['1']
                );
                $max_suffix = 0;
                if ($last !== false && ($row = sqlsrv_fetch_array($last, SQLSRV_FETCH_ASSOC))) {
                    $max_suffix = (int) $row['max_suffix'];
                }
                $next_suffix = str_pad(((int)$max_suffix) + 1, 2, '0', STR_PAD_LEFT);
                $group = "1" . $next_suffix;
            }

            $code = $suhu . str_pad($durasi, 2, '0', STR_PAD_LEFT) . "1" . $dyeing . $dispensing;
        } elseif ($program == 'RAISING') {
            // Raising: group selalu naik satu angka
            $prefix = "2";
            $last = sqlsrv_query(
                $con,
                "SELECT MAX(CAST([group] AS int)) as max_group FROM db_laborat.master_suhu WHERE program = ?",
                ['2']
            );
            $last_group = 0;
            if ($last !== false && ($row = sqlsrv_fetch_array($last, SQLSRV_FETCH_ASSOC))) {
                $last_group = (int) $row['max_group'];
            }
            $group = $last_group ? $last_group + 1 : 201;

            $code = $suhu . str_pad($durasi, 2, '0', STR_PAD_LEFT) . "2" . $dyeing . $dispensing;
        } else {
            echo "<div style='color:red'>Program tidak valid</div>";
            exit;
        }

        // Simpan ke database
        $stmt = sqlsrv_query(
            $con,
            "INSERT INTO db_laborat.master_suhu ([group], product_name, code, program, dyeing, dispensing, suhu, waktu, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$group, $product_name, $code, $prefix, $dyeing, $dispensing, $suhu, $durasi, $status]
        );
        $success = ($stmt !== false);

        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'Data berhasil ditambahkan!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data.']);
        }
    }
}
?>
