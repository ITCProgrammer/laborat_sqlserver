<?php
include "../../koneksi.php";

$approvedCodes = [];
$res = sqlsrv_query($con, "SELECT code FROM db_laborat.approval_bon_order WHERE is_revision = 0");
while ($res && ($r = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC))) {
    $approvedCodes[] = strtoupper(trim($r['code']));
}
$codeList = $approvedCodes;

$sqlTBO = "SELECT DISTINCT 
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
                AND DATE(s.CREATIONDATETIME) > DATE('2025-06-01')";

if (!empty($codeList)) {
    $inList = implode(",", array_map(function ($c) {
        return "'" . db2_quote($c) . "'";
    }, $codeList));
    $sqlTBO .= " AND isa.CODE NOT IN ($inList)";
}

$resultTBO = db2_exec($conn1, $sqlTBO, ['cursor' => DB2_SCROLLABLE]);

function db2_quote($s) { return str_replace("'", "''", $s); }

$picOptions = [];
$queryPIC = "SELECT username FROM db_laborat.tbl_user WHERE pic_bonorder = 1 ORDER BY id ASC";
$resultPIC = sqlsrv_query($con, $queryPIC);
while ($resultPIC && ($rowPIC = sqlsrv_fetch_array($resultPIC, SQLSRV_FETCH_ASSOC))) {
    $picOptions[] = $rowPIC['username'];
}

$seen = [];
while ($row = db2_fetch_assoc($resultTBO)) {
    $code = strtoupper(trim($row['CODE']));
    if (in_array($code, $seen)) continue;
    $seen[] = $code;
    $customer = trim($row['CUSTOMER']);
    $tgl = trim($row['APPROVALRMPDATETIME']);

    echo "<tr>
        <td>$customer</td>
        <td>
            <a href='#' class='btn btn-primary btn-sm open-detail' data-code='$code' data-toggle='modal' data-target='#detailModal'>
                $code
            </a>
        </td>
        <td>$tgl</td>
        <td>
            <div class='d-flex align-items-center gap-2'>
                <select class='form-control form-control-sm pic-select' data-code='$code'>
                    <option value=''>-- Pilih PIC --</option>";
    foreach ($picOptions as $username) {
        $safe = htmlspecialchars($username);
        echo "<option value='$safe'>$safe</option>";
    }

    echo "      </select>
                <button class='btn btn-success btn-sm approve-btn' data-code='$code'>Approve</button>
            </div>
        </td>
    </tr>";
}
?>
