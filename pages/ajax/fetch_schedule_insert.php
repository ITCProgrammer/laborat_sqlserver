<?php
session_start();
include '../../koneksi.php';

$statuses = [
    'scheduled',
    'in_progress_dispensing',
    'in_progress_dyeing',
    // 'in_progress_darkroom',
    // 'ok'
];

$statusList = "'" . implode("','", $statuses) . "'";

$sql = "SELECT tps.no_resep, tps.no_machine, tps.status, ms.[group], ms.product_name
        FROM db_laborat.tbl_preliminary_schedule tps
        LEFT JOIN db_laborat.master_suhu ms ON tps.code = ms.code
        WHERE tps.status IN ($statusList)
        ORDER BY tps.no_machine ASC, tps.id ASC";

$result = sqlsrv_query($con, $sql);

$data = [];
$maxPerMachine = 0;

while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    $machine = $row['no_machine'] ?: 'UNASSIGNED';
    $group = $row['group'];

    $data[$machine][] = [
        'no_resep' => $row['no_resep'],
        'status' => $row['status'],
        'group' => $group,
        'product_name' => $row['product_name']
    ];

    if (count($data[$machine]) > $maxPerMachine) {
        $maxPerMachine = count($data[$machine]);
    }
}

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

        // Dapatkan nilai dyeing
        $stmt = sqlsrv_query($con, "SELECT TOP 1 dyeing FROM db_laborat.master_suhu WHERE [group] = ?", [$groupName]);
        $dyeingValue = null;
        if ($stmt && ($rowD = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
            $dyeingValue = $rowD['dyeing'];
        }

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

            $tempListMap[$machine] = [$desc]; // masih array agar JS tetap kompatibel
        }
    }
}

$response = [
    'data' => $data,
    'tempListMap' => $tempListMap,
    'maxPerMachine' => $maxPerMachine
];

header('Content-Type: application/json');
echo json_encode($response);
