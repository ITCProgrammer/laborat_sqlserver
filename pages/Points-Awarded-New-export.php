<?php
// Export mentah hasil query Points-Awarded sesuai filter tanggal/jam.
ini_set('display_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/koneksi.php';

function clean_date($v)
{
    $v = trim((string)$v);
    return preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $v) ? $v : '';
}

function clean_time($v)
{
    $v = trim((string)$v);
    return preg_match('/^(?:[01]\\d|2[0-3]):[0-5]\\d$/', $v) ? $v : '';
}

function normalize_dt_out($v)
{
    if ($v instanceof DateTimeInterface) {
        return $v->format('Y-m-d H:i:s');
    }
    return trim((string)$v);
}

// Ambil filter
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

// Pastikan urutan benar
if ($dtStart > $dtEnd) {
    $tmp = $dtStart;
    $dtStart = $dtEnd;
    $dtEnd = $tmp;
}

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
OUTER APPLY (
    SELECT DISTINCT
        LOWER(LTRIM(RTRIM(v.username))) AS username
    FROM db_laborat.tbl_preliminary_schedule ps
    CROSS APPLY (VALUES
        (NULLIF(LTRIM(RTRIM(ps.user_scheduled)), ''), CAST(ps.creationdatetime AS datetime2)),
        (NULLIF(LTRIM(RTRIM(ps.user_dispensing)), ''), COALESCE(CAST(ps.dispensing_start AS datetime2), CAST(ps.creationdatetime AS datetime2))),
        (NULLIF(LTRIM(RTRIM(ps.user_dyeing)), ''), COALESCE(CAST(ps.dyeing_start AS datetime2), CAST(ps.creationdatetime AS datetime2))),
        (NULLIF(LTRIM(RTRIM(ps.user_darkroom_start)), ''), COALESCE(CAST(ps.darkroom_start AS datetime2), CAST(ps.creationdatetime AS datetime2))),
        (NULLIF(LTRIM(RTRIM(ps.user_darkroom_end)), ''), COALESCE(CAST(ps.darkroom_end AS datetime2), CAST(ps.creationdatetime AS datetime2))),
        (NULLIF(LTRIM(RTRIM(ps.hold_to_repeat)), ''), COALESCE(CAST(ps.time_hold_to_repeat AS datetime2), CAST(ps.creationdatetime AS datetime2))),
        (NULLIF(LTRIM(RTRIM(ps.hold_to_end)), ''), COALESCE(CAST(ps.time_hold_to_end AS datetime2), CAST(ps.creationdatetime AS datetime2)))
    ) v(username, event_time)
    WHERE
        (
            CASE
                WHEN LEFT(ps.no_resep, 2) = 'DR' AND CHARINDEX('-', ps.no_resep) > 0
                    THEN LEFT(ps.no_resep, CHARINDEX('-', ps.no_resep) - 1)
                ELSE ps.no_resep
            END
        ) = sm.idm
        AND v.username IS NOT NULL
        AND v.event_time IS NOT NULL
        AND CONVERT(date, v.event_time) <= CONVERT(date, sm.approve_at)
) psu
LEFT JOIN db_laborat.tbl_user u ON u.username = psu.username
WHERE
    sm.approve_at BETWEEN ? AND ?
    AND psu.username IS NOT NULL
ORDER BY
    u.jabatan ASC,
    psu.username ASC";

$stmt = sqlsrv_query($con, $sql, [$dtStart, $dtEnd]);
if ($stmt === false) {
    http_response_code(500);
    die('Query gagal: ' . print_r(sqlsrv_errors(), true));
}

// Pastikan tidak ada output tertinggal sebelum header.
while (ob_get_level() > 0) {
    ob_end_clean();
}
if (function_exists('header_remove')) {
    header_remove();
}

$filename = 'points-awarded-raw-' . date('Ymd-His') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: public');
header('Expires: 0');

echo "\xEF\xBB\xBF"; // BOM UTF-8
echo "<html><head><meta charset=\"UTF-8\"></head><body>";
echo "<table border=\"1\">";
echo "<tr><th colspan=\"7\">Points Awarded - Raw</th></tr>";
echo "<tr><td colspan=\"7\">Range: " . htmlspecialchars($dtStart) . " s/d " . htmlspecialchars($dtEnd) . "</td></tr>";
echo "<tr>";
echo "<th>IDM</th><th>Approve At</th><th>Timer</th><th>Total Jam</th><th>Score</th><th>People Involved</th><th>Jabatan</th>";
echo "</tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $approveAt = normalize_dt_out($row['approve_at'] ?? '');
    echo "<tr>";
    echo "<td>" . htmlspecialchars((string)($row['idm'] ?? '')) . "</td>";
    echo "<td>" . htmlspecialchars($approveAt) . "</td>";
    echo "<td>" . htmlspecialchars((string)($row['timer'] ?? '')) . "</td>";
    echo "<td>" . (int)($row['total_jam'] ?? 0) . "</td>";
    echo "<td>" . (int)($row['score'] ?? 0) . "</td>";
    echo "<td>" . htmlspecialchars((string)($row['people_involved'] ?? '')) . "</td>";
    echo "<td>" . htmlspecialchars((string)($row['jabatan'] ?? '-')) . "</td>";
    echo "</tr>";
}
sqlsrv_free_stmt($stmt);
echo "</table>";
echo "</body></html>";
exit;
