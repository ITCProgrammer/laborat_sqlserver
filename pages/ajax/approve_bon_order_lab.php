<?php
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ERROR | E_PARSE);

require_once '../../koneksi.php';

/** Ubah berbagai format tanggal ke 'YYYY-mm-dd' atau NULL */
function to_date($s) {
    $s = trim((string)$s);
    if ($s === '') return null;
    $s = str_replace(['T', '/'], [' ', '-'], $s);
    $ts = strtotime($s);
    if ($ts === false) return null;
    return date('Y-m-d', $ts);
}

/** Ambil POST wajib, jika kosong -> error 400 */
function req($key) {
    if (!isset($_POST[$key])) {
        http_response_code(400);
        echo "Parameter '$key' wajib diisi.";
        exit;
    }
    return $_POST[$key];
}

// --- Ambil input utama ---
$code            = strtoupper(trim(req('code')));
$customer        = trim(req('customer'));                 // dari DOM
$tgl_approve_rmp = trim(req('tgl_approve_rmp'));          // string tanggal
$pic_lab         = trim(req('pic_lab'));
$status          = trim(req('status'));                   // 'Approved' / 'Rejected'
$is_revision     = intval($_POST['is_revision'] ?? 0);
$approvalrmpdatetime = trim(req('approvalrmpdatetime'));
if ($approvalrmpdatetime === '') {
    $approvalrmpdatetime = null;
}

// kolom-kolom revisi (header)
$revisic     = $_POST['revisic']     ?? '';
$revisi2     = $_POST['revisi2']     ?? '';
$revisi3     = $_POST['revisi3']     ?? '';
$revisi4     = $_POST['revisi4']     ?? '';
$revisi5     = $_POST['revisi5']     ?? '';
$revisin     = $_POST['revisin']     ?? '';
$drevisi2    = $_POST['drevisi2']    ?? '';
$drevisi3    = $_POST['drevisi3']    ?? '';
$drevisi4    = $_POST['drevisi4']    ?? '';
$drevisi5    = $_POST['drevisi5']    ?? '';
$revisi1date = $_POST['revisi1date'] ?? '';
$revisi2date = $_POST['revisi2date'] ?? '';
$revisi3date = $_POST['revisi3date'] ?? '';
$revisi4date = $_POST['revisi4date'] ?? '';
$revisi5date = $_POST['revisi5date'] ?? '';

// snapshot detail line (JSON dari endpoint JSON)
$lines_json_raw = $_POST['lines_json'] ?? '';
$lines = [];
if ($lines_json_raw !== '') {
    $tmp = json_decode($lines_json_raw, true);
    if (is_array($tmp)) $lines = $tmp;
}

// Validasi status
if (!in_array($status, ['Approved', 'Rejected'], true)) {
    http_response_code(400);
    echo "Status tidak valid.";
    exit;
}

// Siapkan nilai tanggal lab
$tgl_approve_lab_val  = ($status === 'Approved') ? date('Y-m-d H:i:s') : null;
$tgl_rejected_lab_val = ($status === 'Rejected') ? date('Y-m-d H:i:s') : null;

// Siapkan nilai tanggal RMP
$tgl_approve_rmp_val = to_date($tgl_approve_rmp);

// Mulai transaksi
sqlsrv_begin_transaction($con);

try {
    // Insert ke approval_bon_order (HEADER tetap sama)
    $sql = "
        INSERT INTO db_laborat.approval_bon_order
            (code, customer, tgl_approve_rmp, tgl_approve_lab, tgl_rejected_lab, pic_lab, status, is_revision,
             revisic, revisi2, revisi3, revisi4, revisi5,
             revisin, drevisi2, drevisi3, drevisi4, drevisi5,
             revisi1date, revisi2date, revisi3date, revisi4date, revisi5date, approvalrmpdatetime)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?,
             ?, ?, ?, ?, ?,
             ?, ?, ?, ?, ?,
             ?, ?, ?, ?, ?, ?)
    ";

    $params = [
        $code,
        $customer,
        $tgl_approve_rmp_val,
        $tgl_approve_lab_val,
        $tgl_rejected_lab_val,
        $pic_lab,
        $status,
        $is_revision,

        $revisic,
        $revisi2,
        $revisi3,
        $revisi4,
        $revisi5,

        $revisin,
        $drevisi2,
        $drevisi3,
        $drevisi4,
        $drevisi5,

        to_date($revisi1date),
        to_date($revisi2date),
        to_date($revisi3date),
        to_date($revisi4date),
        to_date($revisi5date),
        $approvalrmpdatetime
    ];

    $stmt = sqlsrv_query($con, $sql, $params);
    if (!$stmt) {
        $errors = sqlsrv_errors();
        throw new Exception("Gagal simpan header: " . ($errors ? $errors[0]['message'] : 'unknown error'));
    }

    $idRes = sqlsrv_query($con, "SELECT SCOPE_IDENTITY() AS id");
    $idRow = $idRes ? sqlsrv_fetch_array($idRes, SQLSRV_FETCH_ASSOC) : null;
    $approval_id = $idRow ? (int) $idRow['id'] : 0;

    // Jika ada data line -> insert batch ke line_revision
    if (!empty($lines)) {
        $values = [];
        foreach ($lines as $ln) {
            // ====== AMBIL NILAI DENGAN NAMA BARU (sesuai DB2) + fallback ke nama lama ======
            $orderline  = trim((string)($ln['orderline'] ?? ''));

            // C-group: revisic (utama) + revisic1..revisic4
            $lv_revisic  = trim((string)($ln['revisic']  ?? ''));                            // C (utama)
            $lv_revc1    = trim((string)($ln['revisic1'] ?? ($ln['revisi2'] ?? '')));        // fallback lama
            $lv_revc2    = trim((string)($ln['revisic2'] ?? ($ln['revisi3'] ?? '')));
            $lv_revc3    = trim((string)($ln['revisic3'] ?? ($ln['revisi4'] ?? '')));
            $lv_revc4    = trim((string)($ln['revisic4'] ?? ($ln['revisi5'] ?? '')));

            // D-group: revisid (utama) + revisi2..revisi5
            $lv_revid    = trim((string)($ln['revisid']  ?? ($ln['revisin']  ?? '')));       // fallback lama: revisin
            $lv_revid1   = trim((string)($ln['revisi2'] ?? ($ln['drevisi2'] ?? '')));
            $lv_revid2   = trim((string)($ln['revisi3'] ?? ($ln['drevisi3'] ?? '')));
            $lv_revid3   = trim((string)($ln['revisi4'] ?? ($ln['drevisi4'] ?? '')));
            $lv_revid4   = trim((string)($ln['revisi5'] ?? ($ln['drevisi5'] ?? '')));

            // Dates (tetap sama namanya)
            $d1 = to_date($ln['revisi1date'] ?? '');
            $d2 = to_date($ln['revisi2date'] ?? '');
            $d3 = to_date($ln['revisi3date'] ?? '');
            $d4 = to_date($ln['revisi4date'] ?? '');
            $d5 = to_date($ln['revisi5date'] ?? '');

            $values[] = [
                $approval_id,
                $code,
                $orderline,
                $lv_revisic,
                $lv_revc1,
                $lv_revc2,
                $lv_revc3,
                $lv_revc4,
                $lv_revid,
                $lv_revid1,
                $lv_revid2,
                $lv_revid3,
                $lv_revid4,
                $d1, $d2, $d3, $d4, $d5
            ];
        }

        if (!empty($values)) {
            $sqlLines = "
                INSERT INTO db_laborat.line_revision
                    (approval_id, code, orderline,
                     revisic, revisic1, revisic2, revisic3, revisic4,
                     revisid, revisi2, revisi3, revisi4, revisi5,
                     revisi1date, revisi2date, revisi3date, revisi4date, revisi5date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            foreach ($values as $v) {
                $stmtLine = sqlsrv_query($con, $sqlLines, $v);
                if (!$stmtLine) {
                    $errors = sqlsrv_errors();
                    throw new Exception("Gagal simpan detail line: " . ($errors ? $errors[0]['message'] : 'unknown error'));
                }
            }
        }
    }

    sqlsrv_commit($con);

    // Respon sukses
    // echo "Data approval berhasil disimpan" . (!empty($lines) ? " (termasuk " . count($lines) . " baris line)." : ".");
    echo "Data approval berhasil disimpan";

} catch (Exception $e) {
    sqlsrv_rollback($con);
    http_response_code(500);
    echo $e->getMessage();
}
