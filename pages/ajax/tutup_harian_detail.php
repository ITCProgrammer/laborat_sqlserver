<?php
// koneksi ke DB (SQL Server)
include "../../koneksi.php";

$tgl_tutup = $_POST['tgl_tutup'] ?? '';
$warehouse = $_POST['warehouse'] ?? '';

$sql = "SELECT
            ITEMTYPECODE,
            KODE_OBAT,
            LONGDESCRIPTION,
            LOTCODE,
            LOGICALWAREHOUSECODE,
            WAREHOUSELOCATIONCODE,
            WHSLOCATIONWAREHOUSEZONECODE,
            TGL_TUTUP,
            SUM(BASEPRIMARYQUANTITYUNIT) AS total_qty,
            BASEPRIMARYUNITCODE
        FROM
        (
            SELECT DISTINCT
                ITEMTYPECODE,
                KODE_OBAT,
                LONGDESCRIPTION,
                LOTCODE,
                LOGICALWAREHOUSECODE,
                WAREHOUSELOCATIONCODE,
                WHSLOCATIONWAREHOUSEZONECODE,
                TGL_TUTUP,
                BASEPRIMARYQUANTITYUNIT,
                BASEPRIMARYUNITCODE
            FROM db_laborat.tblopname_11
            WHERE
                CAST(tgl_tutup AS date) = ?
                AND LOGICALWAREHOUSECODE = ?
                AND KODE_OBAT <> 'E-1-000'
        ) AS sub
        GROUP BY
            ITEMTYPECODE,
            KODE_OBAT,
            LONGDESCRIPTION,
            LOTCODE,
            LOGICALWAREHOUSECODE,
            WAREHOUSELOCATIONCODE,
            WHSLOCATIONWAREHOUSEZONECODE,
            TGL_TUTUP,
            BASEPRIMARYUNITCODE
        ORDER BY KODE_OBAT ASC";

$params = [$tgl_tutup, $warehouse];

$stmt = sqlsrv_query($con, $sql, $params);
if ($stmt === false) {
    echo "<p class='text-danger'>Query gagal: " . print_r(sqlsrv_errors(), true) . "</p>";
    exit;
}

// cek ada row atau tidak
$firstRow = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if ($firstRow) {
    $no = 1;
    echo "<table class='table table-bordered table-striped' id='detailmasukTable'>";
    echo "<thead>
            <tr>
                <th class='text-center'>No</th>
                <th class='text-center'>Kode Obat</th>
                <th class='text-center'>Nama Obat</th>
                <th class='text-center'>Lot</th>
                <th class='text-center'>Logical Warehouse</th>
                <th class='text-center'>Qty (Ending Balance)</th>
            </tr>
          </thead>";
    echo "<tbody>";

    // function kecil untuk format .00
    $formatQty = function ($val) {
        if ($val === null)
            return "0.00";
        $value = (string) $val;

        if (strpos($value, '.') !== false) {
            $formatted = rtrim(rtrim($value, '0'), '.');
            if (strpos($formatted, '.') === false) {
                $formatted .= '.00';
            } else {
                $decimal_part = explode('.', $formatted)[1];
                if (strlen($decimal_part) === 1) {
                    $formatted .= '0';
                }
            }
        } else {
            $formatted = $value . '.00';
        }
        return $formatted;
    };

    // proses row pertama
    do {
        $value = $firstRow['total_qty'];
        $formatted = $formatQty($value);

        echo "<tr>
                <td class='text-center'>{$no}</td>
                <td>" . htmlspecialchars($firstRow['KODE_OBAT']) . "</td>
                <td>" . htmlspecialchars($firstRow['LONGDESCRIPTION']) . "</td>
                <td>" . htmlspecialchars($firstRow['LOTCODE']) . "</td>
                <td class='text-center'>" . htmlspecialchars($firstRow['LOGICALWAREHOUSECODE']) . "</td>
                <td class='text-right'>{$formatted}</td>
              </tr>";
        $no++;

    } while ($firstRow = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC));

    echo "</tbody></table>";
} else {
    echo "<p class='text-warning'>Tidak ada data untuk ditampilkan.</p>";
}
?>
