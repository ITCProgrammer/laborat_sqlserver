<?php
// pages/ajax/get_notif_tbo.php
require_once '../../koneksi.php';
header('Content-Type: application/json');

if (! $con) {
    echo json_encode(['error' => 'Koneksi SQL Server gagal']);
    exit;
}

// query untk ambil code yang baru
$newCodes = 0;
$countRev = 0;
$newListed = [];
$revisiListed = [];

$q_code_baru = "
    SELECT
        t.CODE AS total_new
    FROM db_laborat.tbl_header_bonorder AS t
    LEFT JOIN db_laborat.approval_bon_order AS a
        ON a.code = t.CODE
    WHERE
        t.BUYER IS NOT NULL
        AND t.APPROVED_RMP_DATETIME IS NOT NULL
        AND a.code IS NULL;
";

$stmt_baru = sqlsrv_query($con, $q_code_baru);
if ($stmt_baru === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Query code baru gagal', 'detail' => sqlsrv_errors()]);
    exit;
}

while ($data_baru = sqlsrv_fetch_array($stmt_baru, SQLSRV_FETCH_ASSOC)) {
    $newListed[] = $data_baru['total_new'];
    $newCodes += 1;
}
sqlsrv_free_stmt($stmt_baru);

// Query untuk revisi.
$query_revisi1 = "
    SELECT DISTINCT
        s.CODE
    FROM (
        SELECT
            t.CODE,
            t.ORDERLINE,

            CASE
                WHEN l.revisic  <> t.REV_DEPT1 THEN 1
                WHEN l.revisic1 <> t.REV_DEPT2 THEN 1
                WHEN l.revisic2 <> t.REV_DEPT3 THEN 1
                WHEN l.revisic3 <> t.REV_DEPT4 THEN 1
                WHEN l.revisic4 <> t.REV_DEPT5 THEN 1
            END AS update_revisi_dept,

            CASE
                WHEN l.revisid <> t.REV_COMN1 THEN 1
                WHEN l.revisi2 <> t.REV_COMN2 THEN 1
                WHEN l.revisi3 <> t.REV_COMN3 THEN 1
                WHEN l.revisi4 <> t.REV_COMN4 THEN 1
                WHEN l.revisi5 <> t.REV_COMN5 THEN 1
            END AS update_revisi_comn,

            CASE
                WHEN l.revisi1date <> t.REV_DATE1 THEN 1
                WHEN l.revisi2date <> t.REV_DATE2 THEN 1
                WHEN l.revisi3date <> t.REV_DATE3 THEN 1
                WHEN l.revisi4date <> t.REV_DATE4 THEN 1
                WHEN l.revisi5date <> t.REV_DATE5 THEN 1
            END AS update_revisi_date,

            CASE
                WHEN l.orderline IS NULL THEN 1
            END AS baru

        FROM db_laborat.tbl_line_bonorder AS t

        LEFT JOIN (
            SELECT DISTINCT code, status, is_revision
            FROM db_laborat.approval_bon_order
            WHERE is_revision = 0
        ) AS a
            ON a.code = t.CODE

        OUTER APPLY (
            SELECT TOP (1) lr.*
            FROM db_laborat.line_revision AS lr
            WHERE lr.code = t.CODE
              AND lr.orderline = t.ORDERLINE
            ORDER BY lr.id DESC
        ) AS l

        WHERE
            (
                t.REV_DEPT1 IS NOT NULL OR t.REV_DEPT2 IS NOT NULL OR t.REV_DEPT3 IS NOT NULL OR t.REV_DEPT4 IS NOT NULL OR t.REV_DEPT5 IS NOT NULL
                OR t.REV_COMN1 IS NOT NULL OR t.REV_COMN2 IS NOT NULL OR t.REV_COMN3 IS NOT NULL OR t.REV_COMN4 IS NOT NULL OR t.REV_COMN5 IS NOT NULL
                OR t.REV_DATE1 IS NOT NULL OR t.REV_DATE2 IS NOT NULL OR t.REV_DATE3 IS NOT NULL OR t.REV_DATE4 IS NOT NULL OR t.REV_DATE5 IS NOT NULL
            )
            AND a.status = 'Approved'
            AND t.IS_ACTIVE = 1
    ) AS s
    WHERE
        s.update_revisi_dept = 1
        OR s.update_revisi_comn = 1
        OR s.update_revisi_date = 1
        OR s.baru = 1;
";

$stmt_revisi1 = sqlsrv_query($con, $query_revisi1);
if ($stmt_revisi1 === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Query revisi gagal', 'detail' => sqlsrv_errors()]);
    exit;
}

$revisiListed = [];
while ($data_revisi1 = sqlsrv_fetch_array($stmt_revisi1, SQLSRV_FETCH_ASSOC)) {
    $revisiListed[] = strtoupper(trim($data_revisi1['CODE']));
    $countRev += 1;
}

sqlsrv_free_stmt($stmt_revisi1);

$response = [
    'new'    => ['count' => $newCodes,    'codes' => $newListed],
    'revisi' => ['count' => $countRev, 'codes' => $revisiListed],
    'total'  => $newCodes + $countRev,
];

echo json_encode($response);
