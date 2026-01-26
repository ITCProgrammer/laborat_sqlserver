<?php
session_start();
include '../../koneksi.php';
include '../../includes/insert_balance_transaction_helper.php';
include '../../includes/check_stock_balance_before_dispensing.php';
header('Content-Type: application/json');

$userScheduled = $_SESSION['userLAB'] ?? '';
if (!$userScheduled) {
    echo json_encode([
        'success' => false,
        'message' => 'Session telah habis, silahkan login ulang terlebih dahulu!'
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$assignments = (isset($data['assignments']) && is_array($data['assignments'])) ? $data['assignments'] : [];
$all_ids_raw = (isset($data['all_ids']) && is_array($data['all_ids'])) ? $data['all_ids'] : [];

$all_ids = array_values(array_unique(array_filter(array_map('intval', $all_ids_raw))));
$submitted_ids = [];

if (empty($all_ids) && empty($assignments)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid.']);
    exit;
}

sqlsrv_query($con, "UPDATE db_laborat.tbl_is_scheduling SET is_scheduling = 0");

try {
    // ===== 1) Ambil mesin sibuk SEBELUM update apa pun =====
    $busyStatuses = ['scheduled', 'in_progress_dispensing', 'in_progress_dyeing', 'stop_dyeing'];
    $ph = "'" . implode("','", $busyStatuses) . "'";
    $sqlBusy = "
        SELECT DISTINCT no_machine
        FROM db_laborat.tbl_preliminary_schedule
        WHERE status IN ($ph)
          AND no_machine IS NOT NULL
          AND no_machine <> ''
    ";
    $stmtBusy = sqlsrv_query($con, $sqlBusy);
    if (!$stmtBusy) throw new Exception("Query busy failed: " . print_r(sqlsrv_errors(), true));

    $mesin_sibuk_sebelumnya = [];
    while ($row = sqlsrv_fetch_array($stmtBusy, SQLSRV_FETCH_ASSOC)) {
        $mesin_sibuk_sebelumnya[] = $row['no_machine'];
    }

    // ===== 2) Stock check (hanya yang memang punya mesin valid) =====
    $assignmentsForStock = array_values(array_filter($assignments, function($it){
        $m = strtoupper(trim($it['machine'] ?? ''));
        return !empty($it['id_schedule']) && $m !== '' && $m !== 'BONRESEP';
    }));

    $check = checkStockAvailability($con, $assignmentsForStock);
    if (!$check['ok']) {
        echo json_encode([
            'success' => false,
            'message' => $check['message'],
            'detail'  => $check['failed']
        ]);
        exit;
    }

    // Kumpulkan ID yang benar-benar diproses (yang dipilih)
    $idsSelected = array_values(array_unique(array_map('intval', array_column($assignments, 'id_schedule'))));

    // ===== 3) Base update: HANYA id terpilih yang dipasang status scheduled =====
    if (!empty($idsSelected)) {
        $phIds = implode(',', array_fill(0, count($idsSelected), '?'));
        $sqlBase = "
            UPDATE db_laborat.tbl_preliminary_schedule
            SET status = 'scheduled',
                user_scheduled = ?,
                pass_dispensing = 0
            WHERE id IN ($phIds) AND pass_dispensing = 0
        ";
        $paramsBase = array_merge([$userScheduled], $idsSelected);
        $stmtBase = sqlsrv_query($con, $sqlBase, $paramsBase);
        if (!$stmtBase) throw new Exception("Prepare/exec base update failed: " . print_r(sqlsrv_errors(), true));
    }

    // ===== 4) Update assignment mesin (non-BON) + insertBalance + is_old_data =====
    foreach ($assignments as $item) {
        $id = intval($item['id_schedule'] ?? 0);
        $machine = strtoupper(trim($item['machine'] ?? ''));
        $group = trim($item['group'] ?? '');

        if (!$id) continue;

        // Skip BONRESEP / kosong => biarkan scheduled tapi mesin NULL
        if ($machine === '' || $machine === 'BONRESEP') {
            continue;
        }

        $stmt = sqlsrv_query($con, "
            UPDATE db_laborat.tbl_preliminary_schedule
            SET no_machine = ?, id_group = ?, status = 'scheduled', user_scheduled = ?
            WHERE id = ? AND pass_dispensing = 0
        ", [$machine, $group, $userScheduled, $id]);
        if (!$stmt) throw new Exception("Update assignment failed: " . print_r(sqlsrv_errors(), true));

        $submitted_ids[] = $id;

        insertBalanceTransaction($con, $id);

        // tandai is_old_data kalau mesin sudah sibuk sebelumnya (exclude bon)
        if (in_array($machine, $mesin_sibuk_sebelumnya, true)) {
        sqlsrv_query($con, "UPDATE db_laborat.tbl_preliminary_schedule SET is_old_data = 1 WHERE id = ? AND is_bonresep = 0", [$id]);
        }
    }

    // ===== 5) Tandai data yg tidak dipilih (non-bon) sebagai old =====
    if (!empty($idsSelected) || !empty($all_ids)) {
        $submitted_ids = array_values(array_unique($submitted_ids));
        // Gunakan all_ids jika masih dikirim; kalau tidak, pakai idsSelected sebagai basis
        $basisIds = !empty($all_ids) ? $all_ids : $idsSelected;
        $not_selected_ids = array_values(array_diff($basisIds, $submitted_ids));

        if (!empty($not_selected_ids)) {
            $phNot = implode(',', array_fill(0, count($not_selected_ids), '?'));
            $sqlNot = "UPDATE db_laborat.tbl_preliminary_schedule SET is_old_data = 1 WHERE id IN ($phNot) AND is_bonresep = 0 AND pass_dispensing = 0";
            $stmtNot = sqlsrv_query($con, $sqlNot, $not_selected_ids);
            if (!$stmtNot) throw new Exception("Update not-selected failed: " . print_r(sqlsrv_errors(), true));
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
