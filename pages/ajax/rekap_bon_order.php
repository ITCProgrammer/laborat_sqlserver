<?php
ini_set("error_reporting", 1);
session_start();
include '../../koneksi.php';

$sqlsrvLab = $con;
if (! $sqlsrvLab) {
    die('Koneksi SQL Server db_laborat gagal.');
}

$kemarin = date('Y-m-d', strtotime('-1 day'));
$today = date('Y-m-d');

$todays = date('N'); // 1 = Senin, 7 = Minggu

if ($todays == 1) {
    // Hari ini Senin, jadi kemarin dianggap Hari Sabtu (2 hari sebelumnya)
    $kemarin = date('Y-m-d', strtotime('-2 days'));
} else {
    $kemarin = date('Y-m-d', strtotime('-1 day'));
}

$tanggalAwal = '2025-06-01';

// Ambil semua PIC
$rekap = [];
$resPIC = sqlsrv_query($sqlsrvLab, "SELECT username FROM db_laborat.tbl_user WHERE pic_bonorder = 1 ORDER BY id ASC", [], ["Scrollable" => SQLSRV_CURSOR_KEYSET]);
while ($resPIC && ($row = sqlsrv_fetch_array($resPIC, SQLSRV_FETCH_ASSOC))) {
    $pic = $row['username'];
    $rekap[$pic] = [
        'approved' => 0,
        'reject' => 0,
        'matching_ulang' => 0,
        'ok' => 0
    ];
}
if ($resPIC) {
    sqlsrv_free_stmt($resPIC);
}

$sqlApproved = "SELECT * FROM db_laborat.approval_bon_order WHERE CONVERT(date, tgl_approve_lab) = ? ORDER BY id DESC";
$resultApproved = sqlsrv_query($sqlsrvLab, $sqlApproved, [$kemarin], ["Scrollable" => SQLSRV_CURSOR_KEYSET]);
$approve_today = $resultApproved ? sqlsrv_num_rows($resultApproved) : 0;

// Rekap Approved & Rejected dari approval_bon_order
$resApproval = sqlsrv_query(
    $sqlsrvLab,
    "SELECT pic_lab, status
     FROM db_laborat.approval_bon_order
     WHERE (status = 'Approved' AND CONVERT(date, tgl_approve_lab) = ?)
        OR (status = 'Rejected' AND CONVERT(date, tgl_rejected_lab) = ?)",
    [$kemarin, $kemarin],
    ["Scrollable" => SQLSRV_CURSOR_KEYSET]
);

while ($resApproval && ($row = sqlsrv_fetch_array($resApproval, SQLSRV_FETCH_ASSOC))) {
    $pic = $row['pic_lab'];
    $status = strtolower(trim($row['status']));

    if (!isset($rekap[$pic])) {
        $rekap[$pic] = [
            'approved' => 0,
            'reject' => 0,
            'matching_ulang' => 0,
            'ok' => 0
        ];
    }

    if ($status === 'approved') {
        $rekap[$pic]['approved'] += 1;
    } elseif ($status === 'rejected') {
        $rekap[$pic]['reject'] += 1;
    }
}
if ($resApproval) {
    sqlsrv_free_stmt($resApproval);
}

// Rekap status_matching_bon_order JOIN approval_bon_order (ambil yg code match & sesuai tanggal H-1)
$resStatus = sqlsrv_query(
    $sqlsrvLab,
    "SELECT 
        smb.pic_check, 
        LOWER(LTRIM(RTRIM(smb.status_bonorder))) AS status_bonorder
     FROM db_laborat.status_matching_bon_order smb
     JOIN db_laborat.approval_bon_order ab ON ab.code = smb.salesorder
     WHERE (ab.status = 'Approved' AND CONVERT(date, ab.tgl_approve_lab) = ?)
        OR (ab.status = 'Rejected' AND CONVERT(date, ab.tgl_rejected_lab) = ?)",
    [$kemarin, $kemarin],
    ["Scrollable" => SQLSRV_CURSOR_KEYSET]
);

while ($resStatus && ($row = sqlsrv_fetch_array($resStatus, SQLSRV_FETCH_ASSOC))) {
    $pic = $row['pic_check'];
    $status = $row['status_bonorder'];

    if (!isset($rekap[$pic])) {
        $rekap[$pic] = [
            'approved' => 0,
            'reject' => 0,
            'matching_ulang' => 0,
            'ok' => 0
        ];
    }

    if ($status === 'matching ulang' || $status === 'matching_ulang') {
        $rekap[$pic]['matching_ulang'] += 1;
    } elseif ($status === 'ok') {
        $rekap[$pic]['ok'] += 1;
    }
}
if ($resStatus) {
    sqlsrv_free_stmt($resStatus);
}

// Total Bon Order diterima H-1 (via query dari ITXVIEW)
$approvedCodes = [];
$resCode = sqlsrv_query($sqlsrvLab, "SELECT code FROM db_laborat.approval_bon_order");
while ($resCode && ($r = sqlsrv_fetch_array($resCode, SQLSRV_FETCH_ASSOC))) {
    $approvedCodes[] = "'" . str_replace("'", "''", $r['code']) . "'";
}
if ($resCode) {
    sqlsrv_free_stmt($resCode);
}
$codeList = implode(",", $approvedCodes);

$sqlTBO1 = "SELECT DISTINCT 
                isa.CODE AS CODE,
                COALESCE(ip.LANGGANAN, '') || COALESCE(ip.BUYER, '') AS CUSTOMER,
                isa.TGL_APPROVEDRMP AS TGL_APPROVE_RMP,
                VARCHAR_FORMAT(a.VALUETIMESTAMP, 'YYYY-MM-DD HH24:MI:SS') AS ApprovalRMPDateTime
            FROM ITXVIEW_SALESORDER_APPROVED isa
            LEFT JOIN SALESORDER s
                ON s.CODE = isa.CODE
            LEFT JOIN ITXVIEW_PELANGGAN ip
                ON ip.ORDPRNCUSTOMERSUPPLIERCODE = s.ORDPRNCUSTOMERSUPPLIERCODE
                AND ip.CODE = s.CODE
            LEFT JOIN ADSTORAGE a
                ON a.UNIQUEID = s.ABSUNIQUEID
                AND a.FIELDNAME = 'ApprovalRMPDateTime'
            WHERE a.VALUETIMESTAMP IS NOT NULL
                AND DATE(a.VALUETIMESTAMP) = '$kemarin'
";
if (!empty($codeList)) {
    $sqlTBO1 .= " AND isa.CODE NOT IN ($codeList)";
}

$resultTBO1 = db2_exec($conn1, $sqlTBO1, ['cursor' => DB2_SCROLLABLE]);
$totalH11 = db2_num_rows($resultTBO1);

$totalH1 = $approve_today + $totalH11;
// Hitung total per status
$totalApproved = $totalReject = $totalMatchingUlang = $totalOK = 0;
foreach ($rekap as $data) {
    $totalApproved += $data['approved'];
    $totalReject += $data['reject'];
    $totalMatchingUlang += $data['matching_ulang'];
    $totalOK += $data['ok'];
}

$sisaReview = $totalH1 - ($totalApproved + $totalReject);
?>

<div class="col-md-6">
    <div class="box">
        <h4 class="text-center" style="font-weight: bold;">REKAP STATUS BON ORDER <span class="text-center" style="font-weight: bold;">H-1 (<?=$kemarin; ?>)</span></h4>

        <table class="table table-chart">
            <thead class="table-secondary">
                <tr class="text-center">
                    <th style="text-align: center;">PIC</th>
                    <th style="text-align: center;">Approved</th>
                    <!-- <th style="text-align: center;">Reject</th> -->
                    <th style="text-align: center;">Matching Ulang</th>
                    <th style="text-align: center;">OK</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rekap as $pic => $data): ?>
                    <tr>
                        <td><?= htmlspecialchars($pic) ?></td>
                        <td class="text-center"><?= $data['approved'] ?></td>
                        <!-- <td class="text-center"><?= $data['reject'] ?></td> -->
                        <td class="text-center"><?= $data['matching_ulang'] ?></td>
                        <td class="text-center"><?= $data['ok'] ?></td>
                    </tr>
                <?php endforeach; ?>

                <tr class="fw-bold table-light">
                    <th>Total</th>
                    <th style="text-align: center;"><?= $totalApproved ?></th>
                    <!-- <th style="text-align: center;"><?= $totalReject ?></th> -->
                    <th style="text-align: center;"><?= $totalMatchingUlang ?></th>
                    <th style="text-align: center;"><?= $totalOK ?></th>
                </tr>
                <tr class="table-warning fw-bold">
                    <th>Total Bon Order Diterima H-1</th>
                    <th colspan="4" style="text-align: center;"><?= $totalH1 ?></th>
                    <!-- <th colspan="4" style="text-align: center;"><?= $totalH1 ?></th> -->
                </tr>
                <tr class="table-danger fw-bold">
                    <th>Sisa Bon Order Belum Direview</th>
                    <th colspan="4" style="text-align: center;"><?= $totalH11 ?></th>
                    <!-- <th colspan="4" style="text-align: center;"><?= max(0, $sisaReview) ?></th> -->
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php
/* ===== Rekap Points Awarded (sinkron dengan Points-Awarded-New) ===== */
$pointsEndDate = $kemarin;
$pointsStartDate = date('Y-m-d', strtotime($pointsEndDate . ' -1 day'));
$dtStartPoints = $pointsStartDate . ' 23:00';
$dtEndPoints   = $pointsEndDate . ' 23:00';

$sqlPoints = "SELECT
                  sm.idm,
                  sm.approve_at,
                  sm.timer,
                  (t.hari * 24 + t.jam) AS total_jam,
                  CASE
                      WHEN (t.hari * 24 + t.jam) < 24 THEN 10
                      WHEN (t.hari * 24 + t.jam) BETWEEN 24 AND 48 THEN 9
                      WHEN (t.hari * 24 + t.jam) BETWEEN 49 AND 72 THEN 8
                      WHEN (t.hari * 24 + t.jam) BETWEEN 73 AND 96 THEN 7
                      WHEN (t.hari * 24 + t.jam) BETWEEN 97 AND 120 THEN 6
                      WHEN (t.hari * 24 + t.jam) BETWEEN 121 AND 144 THEN 5
                      WHEN (t.hari * 24 + t.jam) BETWEEN 145 AND 168 THEN 4
                      WHEN (t.hari * 24 + t.jam) BETWEEN 169 AND 192 THEN 3
                      WHEN (t.hari * 24 + t.jam) BETWEEN 193 AND 216 THEN 2
                      WHEN (t.hari * 24 + t.jam) BETWEEN 217 AND 240 THEN 1
                      ELSE 0
                  END AS score,
                  LOWER(LTRIM(RTRIM(psu.username))) AS people_involved,
                  COALESCE(NULLIF(LTRIM(RTRIM(u.level_jabatan)), ''), '-') AS jabatan
              FROM db_laborat.tbl_status_matching sm
              CROSS APPLY (
                  SELECT
                      TRY_CONVERT(int, LEFT(sm.timer, CHARINDEX(' Hari', sm.timer) - 1)) AS hari,
                      TRY_CONVERT(int, LTRIM(RTRIM(SUBSTRING(
                          sm.timer,
                          CHARINDEX('Hari,', sm.timer) + LEN('Hari,'),
                          CHARINDEX(' Jam', sm.timer) - (CHARINDEX('Hari,', sm.timer) + LEN('Hari,'))
                      )))) AS jam
              ) t
              LEFT JOIN (
                  SELECT DISTINCT
                      x.idm_key,
                      x.username
                  FROM (
                      SELECT
                          CASE
                              WHEN LEFT(ps.no_resep, 2) = 'DR' AND CHARINDEX('-', ps.no_resep) > 0
                                  THEN LEFT(ps.no_resep, CHARINDEX('-', ps.no_resep) - 1)
                              ELSE ps.no_resep
                          END AS idm_key,
                          v.username
                      FROM db_laborat.tbl_preliminary_schedule ps
                      CROSS APPLY (VALUES
                          (NULLIF(LTRIM(RTRIM(ps.user_scheduled)), '')),
                          (NULLIF(LTRIM(RTRIM(ps.user_dispensing)), '')),
                          (NULLIF(LTRIM(RTRIM(ps.user_dyeing)), '')),
                          (NULLIF(LTRIM(RTRIM(ps.user_darkroom_start)), '')),
                          (NULLIF(LTRIM(RTRIM(ps.user_darkroom_end)), ''))
                      ) v(username)
                      WHERE v.username IS NOT NULL
                  ) x
              ) psu
                ON psu.idm_key = sm.idm
              LEFT JOIN db_laborat.tbl_user u ON u.username = LOWER(LTRIM(RTRIM(psu.username)))
              WHERE
                sm.approve_at BETWEEN ? AND ?
                AND psu.username IS NOT NULL
              ORDER BY
                u.jabatan ASC,
		            psu.username ASC";

$stmtPoints = sqlsrv_query($sqlsrvLab, $sqlPoints, [$dtStartPoints, $dtEndPoints]);
if (! $stmtPoints) {
    die("Query points gagal: " . print_r(sqlsrv_errors(), true));
}

$jabatanPointAgg = [];
while ($row = sqlsrv_fetch_array($stmtPoints, SQLSRV_FETCH_ASSOC)) {
    $user = strtoupper(trim((string)($row['people_involved'] ?? '')));
    $jabatan = strtoupper(trim((string)($row['jabatan'] ?? '-')));
    if ($user === '') continue;
    if ($jabatan === '') $jabatan = '-';

    if (! isset($jabatanPointAgg[$jabatan])) {
        $jabatanPointAgg[$jabatan] = [];
    }
    if (! isset($jabatanPointAgg[$jabatan][$user])) {
        $jabatanPointAgg[$jabatan][$user] = ['jobs' => 0, 'sumA' => 0, 'sumP' => 0];
    }

    $jabatanPointAgg[$jabatan][$user]['jobs'] += 1;
    $jabatanPointAgg[$jabatan][$user]['sumA'] += (int)($row['score'] ?? 0);
    $jabatanPointAgg[$jabatan][$user]['sumP'] += 10;
}
sqlsrv_free_stmt($stmtPoints);

$pointGroups = [];
ksort($jabatanPointAgg, SORT_NATURAL | SORT_FLAG_CASE);
foreach ($jabatanPointAgg as $jabatan => $usersAgg) {
    $rows = [];
    foreach ($usersAgg as $user => $tot) {
        $ratio = ($tot['sumP'] > 0) ? ($tot['sumA'] / $tot['sumP']) : 0;
        $rows[] = [
            'user' => $user,
            'jobs' => (int)$tot['jobs'],
            'ratio' => (float)$ratio
        ];
    }

    usort($rows, function ($a, $b) {
        if ($a['ratio'] === $b['ratio']) {
            return strnatcasecmp($a['user'], $b['user']);
        }
        return ($a['ratio'] < $b['ratio']) ? 1 : -1;
    });

    $pointGroups[$jabatan] = $rows;
}
?>

<div class="col-md-6">
  <div class="box">
    <h4 class="text-center" style="font-weight:bold;">
      REKAP POINTS AWARDED H-1
      <span style="font-size:13px;">
        (<?= htmlspecialchars($dtStartPoints); ?> s/d <?= htmlspecialchars($dtEndPoints); ?>)
      </span>
    </h4>

	    <table class="table table-chart" style="width:100%;">
	      <thead class="table-secondary">
	        <tr class="text-center" style="background:#eee;">
	          <th style="text-align:center; width:24%;">JABATAN</th>
	          <th style="text-align:center; width:10%;">RANK</th>
	          <th style="text-align:center; width:30%;">NAMA</th>
	          <th style="text-align:center; width:15%;">TOTAL JOB</th>
	          <th style="text-align:center; width:21%;">POINT</th>
	        </tr>
	      </thead>
	      <tbody>
	        <?php if (! empty($pointGroups)): ?>
	          <?php foreach ($pointGroups as $jabatan => $rows): ?>
	            <?php $rowspan = count($rows); ?>
	            <?php foreach ($rows as $idx => $r): ?>
	              <?php
	                $rank = $idx + 1;
	                $rankBg = '#bdbdbd';
	                $rankColor = '#1f1f1f';
	                if ($rank === 1) {
	                  $rankBg = '#34a853';
	                  $rankColor = '#ffffff';
	                } elseif ($rank === 2) {
	                  $rankBg = '#fbbc04';
	                  $rankColor = '#1f1f1f';
	                } elseif ($rank === 3) {
	                  $rankBg = '#1a73e8';
	                  $rankColor = '#ffffff';
	                }
	              ?>
	              <tr>
	                <?php if ($idx === 0): ?>
	                  <td rowspan="<?= (int)$rowspan; ?>" style="vertical-align: middle; font-weight: 700;">
	                    <?= htmlspecialchars($jabatan); ?>
	                  </td>
	                <?php endif; ?>
	                <td class="text-center" style="background:<?= $rankBg; ?>; color:<?= $rankColor; ?>; font-weight:700;">
	                  <?= $rank; ?>
	                </td>
	                <td><?= htmlspecialchars($r['user']); ?></td>
	                <td class="text-center"><?= (int)$r['jobs']; ?></td>
	                <td class="text-center" style="background:#cfe8ff;font-weight:bold;">
	                  <?= number_format((float)$r['ratio'], 4, '.', ''); ?>
	                </td>
	              </tr>
	            <?php endforeach; ?>
	          <?php endforeach; ?>
	        <?php else: ?>
	          <tr><td colspan="5" class="text-center text-muted">Tidak ada data.</td></tr>
	        <?php endif; ?>
	      </tbody>
	    </table>
  </div>
</div>
