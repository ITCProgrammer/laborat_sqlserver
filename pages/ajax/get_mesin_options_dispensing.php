<?php
header('Content-Type: application/json');
include "../../koneksi.php";

$groupName = $_GET['group'] ?? '';
$groupName = trim($groupName);

$keterangan = '';
$suhu = null;
$machines = [];

// Ambil dyeing dan suhu dari master_suhu
$stmt = sqlsrv_query($con, "SELECT TOP 1 dyeing, suhu FROM db_laborat.master_suhu WHERE [group] = ?", [$groupName]);
$rowDS = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;
$dyeingValue = $rowDS['dyeing'] ?? null;
$suhu = $rowDS['suhu'] ?? null;

// Konversi dyeing ke keterangan
if ($dyeingValue == "1") {
    $keterangan = 'POLY';
} elseif ($dyeingValue == "2") {
    $keterangan = 'COTTON';
}

// Logika pilihan mesin
if ($keterangan === 'COTTON' && $suhu == 80) {
    $machines = ['A6', 'C1'];
} elseif ($keterangan) {
    $stmtMesin = sqlsrv_query($con, "
        SELECT no_machine 
        FROM db_laborat.master_mesin 
        WHERE keterangan = ? AND no_machine NOT IN ('A6', 'C1')
    ", [$keterangan]);
    
    while ($row = sqlsrv_fetch_array($stmtMesin, SQLSRV_FETCH_ASSOC)) {
        $machines[] = $row['no_machine'];
    }
}

// echo json_encode($machines);

$filteredMachines = [];
foreach ($machines as $machine) {
$stmtCheckOldData = sqlsrv_query($con, "SELECT COUNT(*) AS total FROM db_laborat.tbl_preliminary_schedule WHERE no_machine = ? AND is_old_data = 1 AND id_group <> ? AND status IN  ('scheduled', 'in_progress_dispensing', 'in_progress_dyeing')", [$machine, $groupName]);
    $rowC = $stmtCheckOldData ? sqlsrv_fetch_array($stmtCheckOldData, SQLSRV_FETCH_ASSOC) : null;
    $count = (int)($rowC['total'] ?? 0);

    if ($count == 0) {
        $filteredMachines[] = $machine;
    }
}

echo json_encode($filteredMachines);
