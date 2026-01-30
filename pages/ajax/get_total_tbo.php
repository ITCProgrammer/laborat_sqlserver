<?php
include "../../koneksi.php";

function sqlsrv_value_to_string($value) {
    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m-d H:i:s');
    }
    if (is_resource($value)) {
        $v = stream_get_contents($value);
        return $v === false ? '' : $v;
    }
    if ($value === null) return '';
    return (string)$value;
}

$approvedCodes = [];
$res = sqlsrv_query($con, "SELECT code FROM db_laborat.approval_bon_order WHERE is_revision = 0");
if ($res) {
    while ($r = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
        $code = strtoupper(trim(sqlsrv_value_to_string($r['code'] ?? '')));
        if ($code !== '') {
            $approvedCodes[] = str_replace("'", "''", $code);
        }
    }
}
$codeList = '';
if (!empty($approvedCodes)) {
    $codeList = implode(",", array_map(function ($c) { return "'" . $c . "'"; }, $approvedCodes));
}

$sqlTBO = "SELECT DISTINCT isa.CODE AS CODE
           FROM ITXVIEW_SALESORDER_APPROVED isa
           LEFT JOIN SALESORDER s ON s.CODE = isa.CODE
           LEFT JOIN ITXVIEW_PELANGGAN ip ON ip.ORDPRNCUSTOMERSUPPLIERCODE = s.ORDPRNCUSTOMERSUPPLIERCODE
                AND ip.CODE = s.CODE
           LEFT JOIN ADSTORAGE a ON a.UNIQUEID = s.ABSUNIQUEID
                AND a.FIELDNAME = 'ApprovalRMPDateTime'
           WHERE isa.APPROVEDRMP IS NOT NULL AND isa.TGL_APPROVEDRMP IS NOT NULL
             AND CAST(s.CREATIONDATETIME AS DATE) > '2025-06-01'
             AND a.VALUETIMESTAMP IS NOT NULL
             AND ip.LANGGANAN IS NOT NULL";

if ($codeList !== '') {
    $sqlTBO .= " AND isa.CODE NOT IN ($codeList)";
}

$resultTBO = db2_exec($conn1, $sqlTBO, ['cursor' => DB2_SCROLLABLE]);

$codes = [];
while ($row = db2_fetch_assoc($resultTBO)) {
    $codes[] = $row['CODE'];
}

echo json_encode([
    'count' => count($codes),
    'codes' => $codes
]);
