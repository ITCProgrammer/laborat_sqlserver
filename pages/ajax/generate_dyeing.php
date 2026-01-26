<?php
session_start();
include __DIR__ . '/../../koneksi.php';

// Ambil daftar mesin
$allMachines = [];
$sqlMachines = "SELECT no_machine FROM db_laborat.master_mesin";
$resMachines = sqlsrv_query($con, $sqlMachines);
if ($resMachines === false) {
    http_response_code(500);
    echo json_encode(['error' => 'get machines failed', 'detail' => sqlsrv_errors()]);
    exit;
}
while ($row = sqlsrv_fetch_array($resMachines, SQLSRV_FETCH_ASSOC)) {
    $allMachines[] = $row['no_machine'];
}

// Ambil data utama (tidak termasuk old_data)
$statuses = [
    'scheduled',
    'in_progress_dispensing',
    'in_progress_dyeing',
    // 'stop_dyeing'
];

$statusList = "'" . implode("','", $statuses) . "'";

$sql = "SELECT tps.no_resep, tps.no_machine, tps.status, tps.dyeing_start, tps.is_test, ms.[group], ms.product_name, ms.waktu
        FROM db_laborat.tbl_preliminary_schedule tps
        LEFT JOIN db_laborat.master_suhu ms ON LTRIM(RTRIM(tps.code)) = LTRIM(RTRIM(ms.code))
        LEFT JOIN db_laborat.tbl_matching ON 
            CASE WHEN LEFT(tps.no_resep, 2) = 'DR' 
                THEN LEFT(tps.no_resep, LEN(tps.no_resep) - 2)
                ELSE tps.no_resep
            END = db_laborat.tbl_matching.no_resep
        WHERE tps.status IN ($statusList) AND tps.is_old_data = 0 AND tps.is_old_cycle = 0
        ORDER BY
            CASE 
                WHEN db_laborat.tbl_matching.jenis_matching IN ('LD', 'LD NOW') THEN 1
                WHEN db_laborat.tbl_matching.jenis_matching IN ('Matching Ulang', 'Matching Ulang NOW', 'Matching Development', 'Perbaikan' , 'Perbaikan NOW') THEN 2
                ELSE 3
            END,
            CASE 
                WHEN tps.order_index > 0 THEN 0 
                ELSE 1 
            END, 
            tps.order_index ASC,
            ms.suhu DESC, 
            ms.waktu DESC, 
            tps.no_resep ASC";

$result = sqlsrv_query($con, $sql);
if ($result === false) {
    http_response_code(500);
    echo json_encode(['error' => 'main query failed', 'detail' => sqlsrv_errors(), 'sql' => $sql]);
    exit;
}

$data = [];
$maxPerMachine = 0;

while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    $machine = $row['no_machine'] ?: 'UNASSIGNED';
    $group = $row['group'];

    $data[$machine][] = [
        'no_resep'    => $row['no_resep'],
        'status'      => $row['status'],
        'group'       => $group,
        'product_name'=> $row['product_name'],
        'dyeing_start'=> $row['dyeing_start'],
        'waktu'       => $row['waktu'],
        'is_test'     => $row['is_test']
    ];

    if (count($data[$machine]) > $maxPerMachine) {
        $maxPerMachine = count($data[$machine]);
    }
}

// Buat array urut dengan maksimal 24
$maxRow = 24;
foreach ($data as $machine => $entries) {
    // Tambahkan data kosong jika kurang dari 24
    while (count($entries) < $maxRow) {
        $entries[] = null;
    }
    $data[$machine] = $entries;
}

// Ambil old data (is_old_data = 1)
$oldDataList = [];
$oldQuery = "SELECT tps.no_resep, tps.no_machine, tps.status, tps.dyeing_start, tps.is_test, ms.[group], ms.product_name, ms.waktu
             FROM db_laborat.tbl_preliminary_schedule tps
             LEFT JOIN db_laborat.master_suhu ms ON LTRIM(RTRIM(tps.code)) = LTRIM(RTRIM(ms.code))
             WHERE tps.is_old_data = 1 AND tps.status IN ($statusList) AND tps.is_old_cycle = 0
             ORDER BY tps.no_resep";
$oldResult = sqlsrv_query($con, $oldQuery);
if ($oldResult === false) {
    http_response_code(500);
    echo json_encode(['error' => 'old query failed', 'detail' => sqlsrv_errors()]);
    exit;
}

while ($row = sqlsrv_fetch_array($oldResult, SQLSRV_FETCH_ASSOC)) {
    $oldDataList[] = $row;
}

// Mesin yang punya data utama
$machinesWithMainData = array_keys($data);

// Pindahkan semua old data ke mesin yang benar-benar kosong (tidak ada data utama)
$remainingOldData = [];

foreach ($oldDataList as $old) {
    $machine = $old['no_machine'] ?: 'UNASSIGNED';

    if (!in_array($machine, $machinesWithMainData) || count($data[$machine]) === 0) {
        if (!isset($data[$machine])) $data[$machine] = [];

        // Tambahkan old data pada mesin yang kosong
        while (count($data[$machine]) < $maxRow) {
            $data[$machine][] = null;
        }

        $data[$machine][] = [
            'no_resep' => $old['no_resep'],
            'status' => $old['status'],
            'group' => $old['group'],
            'product_name' => $old['product_name'],
            'dyeing_start' => $old['dyeing_start'],
            'waktu' => $old['waktu'],
            'justMoved' => true,
            'is_test' => $old['is_test']
        ];
    } else {
        $remainingOldData[] = $old;
    }
}

// Hitung ulang maxPerMachine setelah penambahan old data
foreach ($data as $rows) {
    if (count($rows) > $maxPerMachine) {
        $maxPerMachine = count($rows);
    }
}

// Buat tempListMap dari data utama
$tempListMap = [];

foreach ($data as $machine => $entries) {
    $groupSet = [];

    foreach ($entries as $entry) {
        if (!empty($entry['group'])) {
            $groupSet[$entry['group']] = true;
        }
    }

    $groupNames = array_keys($groupSet);
    $firstGroup = $groupNames[0] ?? null;

    if ($firstGroup) {
        $groupName = $firstGroup;

        // Ambil info dyeing
        $stmt = sqlsrv_query($con, "SELECT TOP 1 dyeing FROM db_laborat.master_suhu WHERE [group] = ?", [$groupName]);
        $rowD = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;
        $dyeingValue = $rowD['dyeing'] ?? null;

        $keterangan = '';
        if ($dyeingValue == "1") {
            $keterangan = 'POLY';
        } elseif ($dyeingValue == "2") {
            $keterangan = 'COTTON';
        }

        // Ambil informasi suhu
        $stmtTemp = sqlsrv_query($con, "SELECT TOP 1 program, suhu, product_name FROM db_laborat.master_suhu WHERE [group] = ?", [$groupName]);
        $row = $stmtTemp ? sqlsrv_fetch_array($stmtTemp, SQLSRV_FETCH_ASSOC) : null;

        if ($row) {
            $desc = '';
            if ($row['program'] == 1) {
                $desc .= 'Constant ' . $row['suhu'];
            } elseif ($row['program'] == 2) {
                $desc .= 'Raising ' . $row['product_name'];
            } else {
                $desc .= 'Unknown';
            }

            $tempListMap[$machine] = [$desc];
        }
    }
}

$oldMachineMap = [];

foreach ($oldDataList as $old) {
    $machine = $old['no_machine'] ?: 'UNASSIGNED';

    if (!isset($oldMachineMap[$machine])) {
        $oldMachineMap[$machine] = [];
    }

    $oldMachineMap[$machine][] = $old;
}

// Buat tempListMapNext untuk old data (Next Cycle)
$tempListMapNext = [];

foreach ($oldMachineMap as $machine => $oldEntries) {
    $groupSet = [];

    foreach ($oldEntries as $entry) {
        if (!empty($entry['group'])) {
            $groupSet[$entry['group']] = true;
        }
    }

    $groupNames = array_keys($groupSet);
    $firstGroup = $groupNames[0] ?? null;

    if ($firstGroup) {
        $groupName = $firstGroup;

        // Ambil info dyeing
        $stmt = sqlsrv_query($con, "SELECT TOP 1 dyeing FROM db_laborat.master_suhu WHERE [group] = ?", [$groupName]);
        $rowD = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;
        $dyeingValue = $rowD['dyeing'] ?? null;

        $keterangan = '';
        if ($dyeingValue == "1") {
            $keterangan = 'POLY';
        } elseif ($dyeingValue == "2") {
            $keterangan = 'COTTON';
        }

        $stmtTemp = sqlsrv_query($con, "SELECT TOP 1 program, suhu, product_name FROM db_laborat.master_suhu WHERE [group] = ?", [$groupName]);
        $row = $stmtTemp ? sqlsrv_fetch_array($stmtTemp, SQLSRV_FETCH_ASSOC) : null;

        if ($row) {
            $desc = '';
            if ($row['program'] == 1) {
                $desc .= 'Constant ' . $row['suhu'];
            } elseif ($row['program'] == 2) {
                $desc .= 'Raising ' . $row['product_name'];
            } else {
                $desc .= 'Unknown';
            }

            if (!isset($tempListMapNext[$machine]) || !in_array($desc, $tempListMapNext[$machine])) {
                $tempListMapNext[$machine][] = $desc;
            }
        }
    }
}

$response = [
    'data' => $data,
    'tempListMap' => $tempListMap,
    'tempListMapNext' => $tempListMapNext,
    'maxPerMachine' => $maxPerMachine,
    'oldDataList' => $remainingOldData,
    'allMachines'   => $allMachines
];

header('Content-Type: application/json');
echo json_encode($response);
