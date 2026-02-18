<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/koneksi.php';

function clean_date($v)
{
  $v = trim((string)$v);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
}

function clean_time($v)
{
  $v = trim((string)$v);
  return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $v) ? $v : '';
}

function slug_id($s)
{
  $s = strtoupper(trim((string)$s));
  if ($s === '') return 'NA';
  $s = preg_replace('/[^A-Z0-9]+/', '-', $s);
  return trim($s, '-');
}

function normalize_ymd($v)
{
  if ($v instanceof DateTimeInterface) {
    return $v->format('Y-m-d');
  }

  $s = trim((string)$v);
  if ($s === '') return '';
  if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;

  $ts = strtotime($s);
  if ($ts === false) return '';
  return date('Y-m-d', $ts);
}

function display_ymd($ymd)
{
  $ts = strtotime((string)$ymd);
  if ($ts === false) return (string)$ymd;
  return date('d-m-Y', $ts);
}

function compute_date_totals(array $rows)
{
  $totAwarded = 0;
  $totPossible = 0;
  foreach ($rows as $r) {
    $totAwarded += (int)($r['points_awarded'] ?? 0);
    $totPossible += (int)($r['possible_points'] ?? 0);
  }
  $ratio = ($totPossible > 0) ? ($totAwarded / $totPossible) : 0;
  return [$totAwarded, $totPossible, $ratio];
}

function render_date_detail_table(array $rows, string $dateKey)
{
  [$totAwarded, $totPossible] = compute_date_totals($rows);

  echo '<table class="table table-bordered table-condensed points-table" style="margin-bottom:0;">';
  echo '  <thead>';
  echo '    <tr class="active">';
  echo '      <th class="text-center" style="width:50%;">JOB</th>';
  echo '      <th class="text-center" style="width:25%;">POINTS AWARDED</th>';
  echo '      <th class="text-center" style="width:25%;">POSIBLE POINTS</th>';
  echo '    </tr>';
  echo '  </thead>';
  echo '  <tbody>';
  foreach ($rows as $r) {
    echo '    <tr>';
    echo '      <td>' . htmlspecialchars((string)$r['job']) . '</td>';
    echo '      <td class="text-center">' . (int)$r['points_awarded'] . '</td>';
    echo '      <td class="text-center">' . (int)$r['possible_points'] . '</td>';
    echo '    </tr>';
  }
  echo '    <tr class="active">';
  echo '      <td class="text-right"><strong>TOTAL ' . htmlspecialchars(display_ymd($dateKey)) . '</strong></td>';
  echo '      <td class="text-center"><strong>' . $totAwarded . '</strong></td>';
  echo '      <td class="text-center"><strong>' . $totPossible . '</strong></td>';
  echo '    </tr>';
  echo '  </tbody>';
  echo '</table>';
}

function render_user_daily_summary_modal(array $rowsByDate, string $user, string $scopeKey = '')
{
  if (empty($rowsByDate)) {
    echo '<div class="alert alert-info" style="margin-bottom:0;">Tidak ada data.</div>';
    return;
  }

  ksort($rowsByDate);
  $uSlug = slug_id(($scopeKey !== '' ? $scopeKey . '-' : '') . $user);
  $tableId = 'dt-summary-' . $uSlug;
  $modalsHtml = '';

  echo '<table id="' . $tableId . '" data-user="' . htmlspecialchars($user, ENT_QUOTES) . '" class="table table-bordered table-condensed dt-summary" style="width:100%;">';
  echo '  <thead>';
  echo '    <tr class="active">';
  echo '      <th class="text-center" style="width:18%;">Tanggal</th>';
  echo '      <th class="text-center" style="width:15%;">Ratio</th>';
  echo '      <th class="text-center">Detail</th>';
  echo '    </tr>';
  echo '  </thead>';
  echo '  <tbody>';

  foreach ($rowsByDate as $dateKey => $rows) {
    [, , $ratio] = compute_date_totals($rows);
    $jobCount = count($rows);
    $ratioTxt = number_format($ratio, 4, '.', '');
    $dSlug = str_replace('-', '', $dateKey);
    $modalId = 'modal-' . $uSlug . '-' . $dSlug;

    echo '<tr>';
    echo '  <td class="text-center"><strong>' . htmlspecialchars($dateKey) . '</strong></td>';
    echo '  <td class="text-center"><span class="ratio-pill">' . $ratioTxt . '</span></td>';
    echo '  <td class="text-center"><button type="button" class="btn btn-xs btn-info" data-toggle="modal" data-target="#' . $modalId . '">Detail</button></td>';
    echo '</tr>';

    ob_start();
    echo '<div class="modal fade" id="' . $modalId . '" tabindex="-1" role="dialog" aria-hidden="true">';
    echo '  <div class="modal-dialog modal-lg" role="document">';
    echo '    <div class="modal-content">';
    echo '      <div class="modal-header">';
    echo '        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
    echo '        <h4 class="modal-title">Detail Points - ' . htmlspecialchars($user) . ' | ' . htmlspecialchars($dateKey) . '</h4>';
    echo '      </div>';
    echo '      <div class="modal-body">';
    echo '        <div style="margin-bottom:10px; display:flex; gap:8px; flex-wrap:wrap;">';
    echo '          <span class="ratio-pill">Ratio: ' . $ratioTxt . '</span>';
    echo '          <span class="job-total-badge">Total Job: ' . $jobCount . '</span>';
    echo '        </div>';
    render_date_detail_table($rows, $dateKey);
    echo '      </div>';
    echo '      <div class="modal-footer">';
    echo '        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>';
    echo '      </div>';
    echo '    </div>';
    echo '  </div>';
    echo '</div>';
    $modalsHtml .= ob_get_clean();
  }

  echo '  </tbody>';
  echo '</table>';
  echo $modalsHtml;
}

/* =========================
   Filter (layout mirip report-matching.php)
========================= */
$today = date('Y-m-d');

$dateStart = isset($_POST['date_start']) ? clean_date($_POST['date_start']) : '';
$timeStart = isset($_POST['time_start']) ? clean_time($_POST['time_start']) : '';
$dateEnd   = isset($_POST['date_end']) ? clean_date($_POST['date_end']) : '';
$timeEnd   = isset($_POST['time_end']) ? clean_time($_POST['time_end']) : '';

$dateStart = ($dateStart !== '') ? $dateStart : $today;
$timeStart = ($timeStart !== '') ? $timeStart : '23:00';
$dateEnd   = ($dateEnd !== '') ? $dateEnd : date('Y-m-d', strtotime($today . ' + 1 day'));
$timeEnd   = ($timeEnd !== '') ? $timeEnd : '23:00';

$dtStart = trim($dateStart . ' ' . $timeStart);
$dtEnd   = trim($dateEnd . ' ' . $timeEnd);

if ($dtStart > $dtEnd) {
  $tmp = $dtStart;
  $dtStart = $dtEnd;
  $dtEnd = $tmp;

  $dateStart = substr($dtStart, 0, 10);
  $timeStart = substr($dtStart, 11, 5);
  $dateEnd   = substr($dtEnd, 0, 10);
  $timeEnd   = substr($dtEnd, 11, 5);
}

$activeUser = isset($_POST['active_user']) ? strtoupper(trim((string)$_POST['active_user'])) : '';

/* =========================
   Query db_laborat
========================= */
$sql = "SELECT
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
          COALESCE(NULLIF(LTRIM(RTRIM(u.jabatan)), ''), '-') AS jabatan
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

$stmt = sqlsrv_query($con, $sql, [$dtStart, $dtEnd]);
if ($stmt === false) {
  die("Query gagal: " . print_r(sqlsrv_errors(), true));
}

$rowsByUser = [];
$totalRows = 0;

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
  $user = strtoupper(trim((string)($row['people_involved'] ?? '')));
  $jabatan = strtoupper(trim((string)($row['jabatan'] ?? '-')));
  if ($user === '') continue;
  if ($jabatan === '') $jabatan = '-';
  $approveDate = normalize_ymd($row['approve_at'] ?? null);
  if ($approveDate === '') continue;

  if (!isset($rowsByUser[$jabatan])) {
    $rowsByUser[$jabatan] = [];
  }
  if (!isset($rowsByUser[$jabatan][$user])) {
    $rowsByUser[$jabatan][$user] = [];
  }
  if (!isset($rowsByUser[$jabatan][$user][$approveDate])) {
    $rowsByUser[$jabatan][$user][$approveDate] = [];
  }

  $rowsByUser[$jabatan][$user][$approveDate][] = [
    'job' => (string)($row['idm'] ?? ''),
    'points_awarded' => (int)($row['score'] ?? 0),
    'possible_points' => 10,
  ];
  $totalRows++;
}
sqlsrv_free_stmt($stmt);

$allJabatan = array_keys($rowsByUser);
sort($allJabatan);

$allUsers = [];
foreach ($allJabatan as $j) {
  if (!isset($rowsByUser[$j]) || !is_array($rowsByUser[$j])) continue;
  ksort($rowsByUser[$j], SORT_NATURAL | SORT_FLAG_CASE);
  foreach ($rowsByUser[$j] as $u => $byDate) {
    $allUsers[$u] = true;
    if (is_array($byDate)) {
      ksort($rowsByUser[$j][$u]);
    }
  }
}
$totalUsers = count($allUsers);

$activeJabatan = isset($_POST['active_jabatan']) ? strtoupper(trim((string)$_POST['active_jabatan'])) : '';
if ($activeJabatan === '' || !in_array($activeJabatan, $allJabatan, true)) {
  $activeJabatan = $allJabatan[0] ?? '';
}

$activeUserByJabatan = [];
foreach ($allJabatan as $j) {
  $users = array_keys($rowsByUser[$j]);
  if (empty($users)) continue;

  if ($j === $activeJabatan && $activeUser !== '' && in_array($activeUser, $users, true)) {
    $activeUserByJabatan[$j] = $activeUser;
  } else {
    $activeUserByJabatan[$j] = $users[0];
  }
}

if ($activeUser === '' && $activeJabatan !== '' && isset($activeUserByJabatan[$activeJabatan])) {
  $activeUser = $activeUserByJabatan[$activeJabatan];
}
?>

<style>
  .nav-tabs > li.active > a,
  .nav-tabs > li.active > a:hover,
  .nav-tabs > li.active > a:focus {
    font-weight: 700;
    background: #4caf50;
    border-color: #4caf50;
    color: #fff;
  }

  .nav-tabs > li > a {
    font-weight: 600;
  }

  .ratio-pill {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 14px;
    background: #cfe8ff;
    border: 1px solid #9fd0ff;
    font-size: 12px;
    font-weight: 700;
  }

  .job-total-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 14px;
    background: #f2f4f7;
    border: 1px solid #cfd6df;
    color: #2e3a4a;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.2;
  }

  .points-table {
    margin-bottom: 0 !important;
  }

  .points-table th {
    background: #f2f2f2;
    color: #111;
    text-align: center;
    border: 1px solid #cfcfcf !important;
  }

  .points-table td {
    border: 1px solid #d7d7d7 !important;
  }
</style>

<div class="row">
  <div class="box">
    <div class="box-header with-border">
      <div class="container-fluid">
        <form class="form-inline" method="POST" action="">
          <input type="hidden" name="p" value="Points-Awarded-New">
          <input type="hidden" name="active_jabatan" id="active_jabatan" value="<?php echo htmlspecialchars($activeJabatan); ?>">
          <input type="hidden" name="active_user" id="active_user" value="<?php echo htmlspecialchars($activeUser); ?>">

          <div class="form-group mb-2">
            <input type="text" class="form-control input-sm date-picker" name="date_start" id="date_start" value="<?php echo htmlspecialchars($dateStart); ?>">
          </div>
          <div class="form-group mb-2">
            <input type="text" class="form-control input-sm time-picker" name="time_start" id="time_start" value="<?php echo htmlspecialchars($timeStart); ?>" placeholder="00:00" maxlength="5">
          </div>
          <div class="form-group mb-2">
            <i class="fa fa-share" aria-hidden="true"></i>
          </div>
          <div class="form-group mx-sm-3 mb-2">
            <input type="text" class="form-control input-sm date-picker" name="date_end" id="date_end" value="<?php echo htmlspecialchars($dateEnd); ?>">
          </div>
          <div class="form-group mb-2">
            <input type="text" class="form-control input-sm time-picker" name="time_end" id="time_end" value="<?php echo htmlspecialchars($timeEnd); ?>" placeholder="00:00" maxlength="5">
          </div>
          <button type="submit" name="submit" value="search" class="btn btn-primary btn-sm mb-2">
            <i class="fa fa-search" aria-hidden="true"></i>
          </button>
        </form>
        <hr />
      </div>
    </div>

    <div class="box-body">
      <?php if (empty($allUsers)): ?>
        <div class="alert alert-info" style="margin-bottom: 0;">
          Tidak ada data untuk range <strong><?php echo htmlspecialchars($dtStart); ?></strong> s/d
          <strong><?php echo htmlspecialchars($dtEnd); ?></strong>.
        </div>
      <?php else: ?>
        <div class="alert alert-info">
          Menampilkan <strong><?php echo (int)$totalRows; ?></strong> job dari
          <strong><?php echo (int)$totalUsers; ?></strong> people involved
          dalam <strong><?php echo count($allJabatan); ?></strong> kategori jabatan.
          Range: <strong><?php echo htmlspecialchars($dtStart); ?></strong> s/d
          <strong><?php echo htmlspecialchars($dtEnd); ?></strong>.
        </div>

        <ul class="nav nav-tabs" role="tablist" id="jabatanTabs">
          <?php foreach ($allJabatan as $j):
            $jid = slug_id($j);
            $act = ($j === $activeJabatan) ? 'active' : '';
          ?>
            <li role="presentation" class="<?php echo $act; ?>">
              <a href="#tab-jabatan-<?php echo $jid; ?>"
                 aria-controls="tab-jabatan-<?php echo $jid; ?>"
                 role="tab"
                 data-toggle="tab"
                 data-jabatankey="<?php echo htmlspecialchars($j); ?>">
                <?php echo htmlspecialchars($j); ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>

        <div class="tab-content" style="margin-top: 14px;">
          <?php foreach ($allJabatan as $j):
            $jid = slug_id($j);
            $act = ($j === $activeJabatan) ? 'active' : '';
            $usersInJabatan = $rowsByUser[$j];
            $activeUserThisJabatan = $activeUserByJabatan[$j] ?? '';
          ?>
            <div role="tabpanel" class="tab-pane <?php echo $act; ?>" id="tab-jabatan-<?php echo $jid; ?>">
              <ul class="nav nav-pills people-tabs" role="tablist" style="margin-bottom:10px;">
                <?php foreach ($usersInJabatan as $u => $rowsByDateUser):
                  $uid = slug_id($jid . '-' . $u);
                  $uAct = ($u === $activeUserThisJabatan) ? 'active' : '';
                ?>
                  <li role="presentation" class="<?php echo $uAct; ?>">
                    <a href="#tab-user-<?php echo $uid; ?>"
                       aria-controls="tab-user-<?php echo $uid; ?>"
                       role="tab"
                       data-toggle="tab"
                       data-jabatankey="<?php echo htmlspecialchars($j); ?>"
                       data-userkey="<?php echo htmlspecialchars($u); ?>">
                      <?php echo htmlspecialchars($u); ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>

              <div class="tab-content">
                <?php foreach ($usersInJabatan as $u => $rowsByDate):
                  $uid = slug_id($jid . '-' . $u);
                  $uAct = ($u === $activeUserThisJabatan) ? 'active' : '';
                ?>
                  <div role="tabpanel" class="tab-pane <?php echo $uAct; ?>" id="tab-user-<?php echo $uid; ?>">
                    <div class="alert alert-info" style="margin-bottom:10px;">
                      Menampilkan <strong>ratio per hari</strong> untuk user <strong><?php echo htmlspecialchars($u); ?></strong>
                      pada range <strong><?php echo htmlspecialchars($dtStart); ?></strong> s/d <strong><?php echo htmlspecialchars($dtEnd); ?></strong>.
                    </div>
                    <?php render_user_daily_summary_modal($rowsByDate, $u, $j); ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
(function () {
  $(document).on('shown.bs.tab', '#jabatanTabs a[data-toggle="tab"]', function (e) {
    var jabatan = $(e.target).data('jabatankey');
    if (jabatan) $('#active_jabatan').val(jabatan);

    var paneSelector = $(e.target).attr('href');
    var $activeUserTab = $(paneSelector).find('.people-tabs li.active a[data-userkey]').first();
    if ($activeUserTab.length) {
      $('#active_user').val($activeUserTab.data('userkey') || '');
    } else {
      var $firstUserTab = $(paneSelector).find('.people-tabs a[data-userkey]').first();
      if ($firstUserTab.length) {
        $firstUserTab.tab('show');
      } else {
        $('#active_user').val('');
      }
    }
  });

  $(document).on('shown.bs.tab', '.people-tabs a[data-toggle="tab"]', function (e) {
    var user = $(e.target).data('userkey');
    var jabatan = $(e.target).data('jabatankey');
    if (user) $('#active_user').val(user);
    if (jabatan) $('#active_jabatan').val(jabatan);
  });

  $(function () {
    var $activeUserTab = $('.people-tabs li.active a[data-userkey]').first();
    if ($activeUserTab.length) {
      $('#active_user').val($activeUserTab.data('userkey') || '');
      $('#active_jabatan').val($activeUserTab.data('jabatankey') || '');
      return;
    }
    var $activeJabatanTab = $('#jabatanTabs li.active a[data-jabatankey]').first();
    if ($activeJabatanTab.length) {
      $('#active_jabatan').val($activeJabatanTab.data('jabatankey') || '');
    }
  });

  $(document).on('shown.bs.modal', '.modal', function () {
    $(this).find('.modal-body').scrollTop(0);
  });
})();
</script>

<?php if (!empty($allUsers)): ?>
<script>
(function () {
  function initSummaryTables() {
    $('.dt-summary').each(function () {
      if ($.fn.DataTable.isDataTable(this)) return;

      var $t = $(this);
      $t.DataTable({
        dom: 'Bfrtip',
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[0, 'asc']],
        autoWidth: false,
        responsive: false,
        columnDefs: [
          { targets: 2, orderable: false, searchable: false, className: 'text-center' },
          { targets: [0, 1], className: 'text-center' }
        ],
        buttons: [
          { extend: 'copy', text: 'Copy', title: 'Point Awarded - ' + ($t.data('user') || ''), exportOptions: { columns: [0, 1] } },
          { extend: 'excel', text: 'Excel', title: 'Point Awarded - ' + ($t.data('user') || ''), exportOptions: { columns: [0, 1] } },
          { extend: 'csv', text: 'CSV', title: 'Point Awarded - ' + ($t.data('user') || ''), exportOptions: { columns: [0, 1] } },
          { extend: 'pdf', text: 'PDF', title: 'Point Awarded - ' + ($t.data('user') || ''), exportOptions: { columns: [0, 1] } }
        ]
      });
    });
  }

  $(function () {
    initSummaryTables();
  });

  $(document).on('shown.bs.tab', '#jabatanTabs a[data-toggle="tab"], .people-tabs a[data-toggle="tab"]', function () {
    initSummaryTables();
    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
  });
})();
</script>
<?php endif; ?>

<script>
$(function () {
  if (!$.fn.datepicker) return;

  try {
    $('.date-picker').datepicker('destroy');
  } catch (e) {}

  $('.date-picker').datepicker({
    dateFormat: 'yy-mm-dd'
  });
});
</script>
