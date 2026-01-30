<?php
include "../../koneksi.php";

$is_revision = isset($_GET['is_revision']) ? (int)$_GET['is_revision'] : 0;

$sqlApproved = "
  SELECT id, customer, code, tgl_approve_lab, pic_lab, status, approvalrmpdatetime
  FROM db_laborat.approval_bon_order
  WHERE is_revision = ?
  ORDER BY id DESC
";
$resApproved = sqlsrv_query($con, $sqlApproved, [$is_revision]);

function fmt_ymd($value) {
    if ($value instanceof DateTime) {
        return $value->format('Y-m-d');
    }
    if (empty($value)) {
        return '';
    }
    return date('Y-m-d', strtotime($value));
}

function fmt_value($value) {
    if ($value instanceof DateTime) {
        return $value->format('Y-m-d H:i:s');
    }
    return $value;
}

while ($resApproved && ($row = sqlsrv_fetch_array($resApproved, SQLSRV_FETCH_ASSOC))) {
    $code = strtoupper(trim($row['code']));
    $statusClass = ($row['status'] === 'Approved') ? 'text-success' : 'text-danger';
    $approvalRmp = fmt_ymd($row['approvalrmpdatetime']);
    $tglApproveLab = fmt_value($row['tgl_approve_lab']);

    echo "<tr>";
    echo   "<td style='display: none;'>" . htmlspecialchars($row['id']) . "</td>";
    echo   "<td>" . htmlspecialchars($row['customer']) . "</td>";
    echo   "<td>
              <a href=\"#\" class=\"btn btn-primary btn-sm open-detail\"
                 data-code=\"" . htmlspecialchars($code) . "\"
                 data-toggle=\"modal\" data-target=\"#detailModal\">" .
                 htmlspecialchars($code) .
              "</a>
            </td>";
    echo   "<td>" . htmlspecialchars($approvalRmp) . "</td>";
    echo   "<td>" . htmlspecialchars($tglApproveLab) . "</td>";
    echo   "<td>" . htmlspecialchars($row['pic_lab']) . "</td>";
    echo   "<td><strong class=\"" . $statusClass . "\">" . htmlspecialchars($row['status']) . "</strong></td>";
    echo "</tr>";
}
