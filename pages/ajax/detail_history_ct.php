<?php
include __DIR__ . '/../../koneksi.php';

$no_resep = $_POST['no_resep'] ?? '';

function fmtDt($v) {
    if ($v instanceof DateTimeInterface) return $v->format('Y-m-d H:i:s');
    return $v;
}

function dateOnly($v) {
    if ($v instanceof DateTimeInterface) return $v->format('Y-m-d');
    $s = trim((string)$v);
    if ($s === '') return '';
    $ts = strtotime($s);
    if ($ts === false) return '';
    return date('Y-m-d', $ts);
}

function isAfterApproveDate($value, $approveAt) {
    $valueDate = dateOnly($value);
    $approveDate = dateOnly($approveAt);
    if ($valueDate === '' || $approveDate === '') return false;
    return $valueDate > $approveDate;
}

function idmFromNoResep($noResep) {
    $noResep = trim((string)$noResep);
    if ($noResep === '') return '';

    if (strtoupper(substr($noResep, 0, 2)) === 'DR') {
        $dashPos = strpos($noResep, '-');
        if ($dashPos !== false) {
            return substr($noResep, 0, $dashPos);
        }
    }

    return $noResep;
}

$idm = idmFromNoResep($no_resep);
$approveAt = null;

if ($idm !== '') {
    $stmtApprove = sqlsrv_query(
        $con,
        "SELECT TOP 1 approve_at
         FROM db_laborat.tbl_status_matching
         WHERE idm = ? AND approve_at IS NOT NULL
         ORDER BY approve_at DESC, id DESC",
        [$idm]
    );

    if ($stmtApprove && ($rowApprove = sqlsrv_fetch_array($stmtApprove, SQLSRV_FETCH_ASSOC))) {
        $approveAt = $rowApprove['approve_at'] ?? null;
    }
}

$sql = "SELECT 
            t.no_resep,
            dt.creation_sec AS creationdatetime,
            SUM(CASE WHEN t.is_test = 0 THEN 1 ELSE 0 END) AS qty_normal,
            SUM(CASE WHEN t.is_test = 1 THEN 1 ELSE 0 END) AS qty_test,
            MAX(t.status) AS status,

            -- Ambil dispensing_start terbaru (berdasarkan creation per detik)
            (
                SELECT TOP 1 x.dispensing_start
                FROM db_laborat.tbl_preliminary_schedule x
                CROSS APPLY (SELECT CAST(x.creationdatetime AS datetime2(0)) AS creation_sec) dx
                WHERE x.no_resep = t.no_resep
                AND dx.creation_sec = dt.creation_sec
                AND x.dispensing_start IS NOT NULL
                ORDER BY x.dispensing_start DESC
            ) AS dispensing_start,

            (
                SELECT TOP 1 x.user_dispensing
                FROM db_laborat.tbl_preliminary_schedule x
                CROSS APPLY (SELECT CAST(x.creationdatetime AS datetime2(0)) AS creation_sec) dx
                WHERE x.no_resep = t.no_resep
                AND dx.creation_sec = dt.creation_sec
                AND x.dispensing_start IS NOT NULL
                ORDER BY x.dispensing_start DESC
            ) AS user_dispensing,

            MAX(t.dyeing_start) AS dyeing_start,
            MAX(t.user_dyeing) AS user_dyeing,
            MAX(t.darkroom_start) AS darkroom_start,
            MAX(t.user_darkroom_start) AS user_darkroom_start,
            MAX(t.darkroom_end) AS darkroom_end,
            MAX(t.user_darkroom_end) AS user_darkroom_end,
            MAX(t.sekali_celup) AS sekali_celup,
            MAX(t.username) AS username,
            MAX(t.user_scheduled) AS user_scheduled,
            MAX(t.end_to_repeat) AS end_to_repeat,
            MAX(t.time_end_to_repeat) AS time_end_to_repeat,
            MAX(t.hold_to_repeat) AS hold_to_repeat,
            MAX(t.time_hold_to_repeat) AS time_hold_to_repeat,
            MAX(t.hold_to_end) AS hold_to_end,
            MAX(t.time_hold_to_end) AS time_hold_to_end
        FROM db_laborat.tbl_preliminary_schedule t
        CROSS APPLY (SELECT CAST(t.creationdatetime AS datetime2(0)) AS creation_sec) dt
        WHERE t.no_resep = ?
        GROUP BY t.no_resep, dt.creation_sec
        ORDER BY dt.creation_sec ASC;";

$stmt = sqlsrv_query($con, $sql, [$no_resep]);
?>

<style>
    #detailTable tbody td.award-excluded-cell {
        background-color: #ffe8a1 !important;
    }
</style>

<?php if (!empty($idm)): ?>
<div class="alert alert-info" style="margin-bottom:8px;">
    <strong>No Resep:</strong> <?= htmlspecialchars($idm) ?>
    <span style="margin:0 8px;">|</span>
    <strong>Approved At:</strong> <?= !empty($approveAt) ? fmtDt($approveAt) : '-' ?>
    <br><small class="">Kolom berwarna = tanggal proses melewati approve at, tidak masuk point awarded.</small>
</div>
<?php endif; ?>

<table id="detailTable" class="table table-sm table-bordered table-sm display compact">
    <thead>
        <tr class='bg-danger'>
        <th>#</th>
        <th>Creation Time</th>
        <th>Qty</th>
        <th>Dispensing Start</th>
        <th>Dyeing Start</th>
        <th>Darkroom Start</th>
        <th>Darkroom End</th>
        <th title="Tidak mendapatkan point awarded">End to Repeat</th>
        <th>Hold to Repeat</th>
        <th>Hold to End</th>
        <th>Status Terakhir</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $no = 1;
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) :
        $colCreation    = isAfterApproveDate($row['creationdatetime'] ?? null, $approveAt);
        $colDispensing  = isAfterApproveDate($row['dispensing_start'] ?? null, $approveAt);
        $colDyeing      = isAfterApproveDate($row['dyeing_start'] ?? null, $approveAt);
        $colDrStart     = isAfterApproveDate($row['darkroom_start'] ?? null, $approveAt);
        $colDrEnd       = isAfterApproveDate($row['darkroom_end'] ?? null, $approveAt);
        $colEndRepeat   = isAfterApproveDate($row['time_end_to_repeat'] ?? null, $approveAt);
        $colHoldRepeat  = isAfterApproveDate($row['time_hold_to_repeat'] ?? null, $approveAt);
        $colHoldEnd     = isAfterApproveDate($row['time_hold_to_end'] ?? null, $approveAt);
    ?>
        <tr>
        <td><?= $no ?></td>

        <td class="text-nowrap <?= $colCreation ? 'award-excluded-cell' : '' ?>">
            <?= fmtDt($row['creationdatetime']) ?>
            <?php if (!empty($row['username'])): ?>
            <br><small class="text-muted">User: <?= htmlspecialchars($row['username']) ?></small>
            <?php endif; ?>
        </td>

        <td>
            <?php
                $q0 = (int)($row['qty_normal'] ?? 0);
                $q1 = (int)($row['qty_test'] ?? 0);

                if ($q0 > 0) {
                    echo $q0;
                }
                if ($q1 > 0) {
                    if ($q0 > 0) echo "<br>";
                    echo $q1 . ' <span class="label label-warning label-small">TEST REPORT</span>';
                }
            ?>
        </td>

        <td class="<?= $colDispensing ? 'award-excluded-cell' : '' ?>">
            <?= fmtDt($row['dispensing_start']) ?>
            <?php if (!empty($row['dispensing_start'])): ?>
            <br><small class="text-muted">User: <?= htmlspecialchars($row['user_dispensing']) ?></small>
            <?php endif; ?>
        </td>

        <td class="<?= $colDyeing ? 'award-excluded-cell' : '' ?>">
            <?= fmtDt($row['dyeing_start']) ?>
            <?php if (!empty($row['dyeing_start'])): ?>
            <br><small class="text-muted">User: <?= htmlspecialchars($row['user_dyeing']) ?></small>
            <?php endif; ?>
        </td>

        <td class="<?= $colDrStart ? 'award-excluded-cell' : '' ?>">
            <?= fmtDt($row['darkroom_start']) ?>
            <?php if (!empty($row['darkroom_start'])): ?>
            <br><small class="text-muted">User: <?= htmlspecialchars($row['user_darkroom_start']) ?></small>
            <?php endif; ?>
        </td>

        <td class="<?= $colDrEnd ? 'award-excluded-cell' : '' ?>">
            <?= fmtDt($row['darkroom_end']) ?>
            <?php if (!empty($row['darkroom_end'])): ?>
            <br><small class="text-muted">User: <?= htmlspecialchars($row['user_darkroom_end']) ?></small>
            <?php endif; ?>
        </td>
        
        <td class="<?= $colEndRepeat ? 'award-excluded-cell' : '' ?>">
            <?= fmtDt($row['time_end_to_repeat']) ?>
            <?php if (!empty($row['end_to_repeat'])): ?>
            <br><small class="text-muted">User: <?= htmlspecialchars($row['end_to_repeat']) ?></small>
            <?php endif; ?>
        </td>
        
        <td class="<?= $colHoldRepeat ? 'award-excluded-cell' : '' ?>">
            <?= fmtDt($row['time_hold_to_repeat']) ?>
            <?php if (!empty($row['hold_to_repeat'])): ?>
            <br><small class="text-muted">User: <?= htmlspecialchars($row['hold_to_repeat']) ?></small>
            <?php endif; ?>
        </td>
        
        <td class="<?= $colHoldEnd ? 'award-excluded-cell' : '' ?>">
            <?= fmtDt($row['time_hold_to_end']) ?>
            <?php if (!empty($row['hold_to_end'])): ?>
            <br><small class="text-muted">User: <?= htmlspecialchars($row['hold_to_end']) ?></small>
            <?php endif; ?>
        </td>

        <td>
        <?= htmlspecialchars($row['status']) ?>
        <?php
            $byUser = '';

            switch ($row['status']) {
                    case 'ready':
                        $byUser = $row['username'] ?? '';
                        break;
                    case 'scheduled':
                        $byUser = $row['user_scheduled'] ?? '';
                        break;
                    case 'in_progress_dispensing':
                        $byUser = $row['user_dispensing'] ?? '';
                        break;
                    case 'in_progress_dyeing':
                        $byUser = $row['user_dyeing'] ?? '';
                        break;
                    case 'in_progress_darkroom':
                        $byUser = $row['user_darkroom_start'] ?? '';
                        break;
                    default:
                        if (!empty($row['user_darkroom_end'])) {
                            $byUser = $row['user_darkroom_end'];
                        } elseif (!empty($row['user_darkroom_start'])) {
                            $byUser = $row['user_darkroom_start'];
                        }
                break;
            }

            if (!empty($byUser)) {
                echo "<br><small class='text-muted'>By: {$byUser}</small>";
            }
        ?>
        </td>
        </tr>
    <?php
        $no++;
    endwhile;
    ?>
    </tbody>
</table>

<script>
$(document).ready(function () {
  $('#detailTable').DataTable({
    pageLength: 25,
    pagingType: "simple_numbers",
    language: {
      paginate: {
        previous: '<i class="fa fa-angle-left"></i>',
        next: '<i class="fa fa-angle-right"></i>'
      }
    }
  });
});
</script>
