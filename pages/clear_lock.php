<?php
// Hapus lock log_preliminary (SQL Server)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include dirname(__FILE__) . "/../koneksi.php";

// --- cek konfirmasi
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    $sql = "TRUNCATE TABLE db_laborat.log_preliminary";
    $stmt = sqlsrv_query($con, $sql);
    if ($stmt) {
        echo "<center><h3>✔ Active Lock sudah dihapus (tabel log_preliminary kosong kembali).</h3>
              <p><a href='index1.php?p=Preliminary-Schedule'>Kembali ke halaman Preliminary-Schedule</a></p></center>";
    } else {
        echo "Error: " . print_r(sqlsrv_errors(), true);
    }
} else {
    echo "<center>
            <h3>⚠️ Apakah Anda yakin ingin menghapus semua data lock?</h3>
            <p><a href='clear_lock.php?confirm=yes' style='color:red;'>Ya, hapus semua (TRUNCATE)</a></p>
            <p><a href='index1.php?p=Preliminary-Schedule'>Tidak, kembali ke halaman</a></p>
          </center>";
}
?>
