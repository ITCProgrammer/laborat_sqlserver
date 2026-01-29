<?php
include "../../koneksi.php";
include "../../includes/Penomoran_helper.php";

$tgl_tutup = $_POST['tgl_tutup'];
$warehouse = $_POST['warehouse'];

$sudahKonfirm = "<button class='btn btn-warning btn-sm detail' title='Detail' data-toggle='tooltip' ><i class='fa fa-info'></i></button> <span style='margin-left:5px;min-width:35px'><i class='fa fa-check' aria-hidden='true'></i> OK</span>";
$belumKonfirm = "<button class='btn btn-warning btn-sm detail' title='Detail' data-toggle='tooltip' ><i class='fa fa-info'></i></button> <button class='btn btn-primary btn-sm confirm' title='Confirm' data-toggle='tooltip' ><i class='fa fa-check-square-o' aria-hidden='true'></i></button>";

$checkSql = "SELECT TOP 1 id
             FROM db_laborat.tbl_stock_opname_gk
             WHERE CAST(tgl_tutup AS date) = ?
               AND KODE_OBAT <> 'E-1-000'";

$checkStmt = sqlsrv_query($con, $checkSql, [$tgl_tutup]);
if ($checkStmt === false) {
    die("<p class='text-danger'>Query cek gagal: " . print_r(sqlsrv_errors(), true) . "</p>");
}
$cekRow = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($checkStmt);

$row_count = $cekRow ? 1 : 0;

if ($row_count == 0) {

    $insertSql = "INSERT INTO db_laborat.tbl_stock_opname_gk
        (ITEMTYPECODE,KODE_OBAT,LONGDESCRIPTION,LOTCODE,LOGICALWAREHOUSECODE,tgl_tutup,total_qty,BASEPRIMARYUNITCODE,pakingan_standar)
        SELECT
            ITEMTYPECODE,
            KODE_OBAT,
            LONGDESCRIPTION,
            LOTCODE,
            LOGICALWAREHOUSECODE,
            tgl_tutup,
            SUM(BASEPRIMARYQUANTITYUNIT) AS total_qty,
            BASEPRIMARYUNITCODE,
            0
        FROM db_laborat.tblopname_11
        WHERE CAST(tgl_tutup AS date) = ?
          AND KODE_OBAT <> 'E-1-000'
        GROUP BY
            ITEMTYPECODE,
            KODE_OBAT,
            LONGDESCRIPTION,
            LOTCODE,
            LOGICALWAREHOUSECODE,
            tgl_tutup,
            BASEPRIMARYUNITCODE";

    $insertStmt = sqlsrv_query($con, $insertSql, [$tgl_tutup]);
    if ($insertStmt === false) {
        die("<p class='text-danger'>Insert migrasi gagal: " . print_r(sqlsrv_errors(), true) . "</p>");
    }
    sqlsrv_free_stmt($insertStmt);
}


if (trim($warehouse) == "M101" || trim($warehouse) == "M510") {

    $query = "SELECT o.*, d.total_qty as total_o11
        FROM db_laborat.tbl_stock_opname_gk o
        LEFT JOIN (
            SELECT
                ITEMTYPECODE,
                KODE_OBAT,
                LONGDESCRIPTION,
                LOTCODE,
                LOGICALWAREHOUSECODE,
                tgl_tutup,
                SUM(BASEPRIMARYQUANTITYUNIT) AS total_qty,
                BASEPRIMARYUNITCODE
            FROM (
                SELECT DISTINCT
                    ITEMTYPECODE,
                    KODE_OBAT,
                    LONGDESCRIPTION,
                    LOTCODE,
                    LOGICALWAREHOUSECODE,
                    tgl_tutup,
                    WHSLOCATIONWAREHOUSEZONECODE,
                    BASEPRIMARYQUANTITYUNIT,
                    BASEPRIMARYUNITCODE
                FROM db_laborat.tblopname_11
                WHERE CAST(tgl_tutup AS date) = ?
                  AND LOGICALWAREHOUSECODE = ?
            ) DST
            GROUP BY
                ITEMTYPECODE,
                KODE_OBAT,
                LONGDESCRIPTION,
                LOTCODE,
                LOGICALWAREHOUSECODE,
                tgl_tutup,
                BASEPRIMARYUNITCODE
        ) d
        ON  o.KODE_OBAT = d.KODE_OBAT
        AND d.LOTCODE = o.LOTCODE
        AND d.tgl_tutup = o.tgl_tutup
        AND d.LOGICALWAREHOUSECODE = o.LOGICALWAREHOUSECODE
        WHERE CAST(o.tgl_tutup AS date) = ?
          AND o.LOGICALWAREHOUSECODE = ?
        ORDER BY o.KODE_OBAT ASC";

    $params = [$tgl_tutup, $warehouse, $tgl_tutup, $warehouse];

    $stmt = sqlsrv_query($con, $query, $params);
    if ($stmt === false) {
        die("<p class='text-danger'>Query gagal: " . print_r(sqlsrv_errors(), true) . "</p>");
    }

    $hasRow = false;
    $no = 1;

    if (trim($warehouse) == "M101") {
        echo "<table class='table table-bordered table-striped' id='detailmasukTable'>";
        echo "<thead>
                <tr>
                    <th class='text-center'>No</th>
                    <th class='text-center'>Kode Obat</th>
                    <th class='text-center'>Nama Obat</th>
                    <th class='text-center'>Lot</th>
                    <th class='text-center'>Logical Warehouse</th>
                    <th class='text-center'>Qty (Ending Balance)</th>
                    <th class='text-center'>QTY Dus</th>
                    <th class='text-center'>Standar packaging</th>
                    <th class='text-center'>Total Stock</th>
                    <th class='text-center'>Konfirmasi</th>
                </tr>
              </thead><tbody>";
    } else { 
        echo "<table class='table table-bordered table-striped' id='detailmasukTable'>";
        echo "<thead>
                <tr>
                    <th class='text-center'>No</th>
                    <th class='text-center'>Kode Obat</th>
                    <th class='text-center'>Nama Obat</th>
                    <th class='text-center'>Lot</th>
                    <th class='text-center'>Logical Warehouse</th>
                    <th class='text-center'>Qty (Ending Balance)</th>
                    <th class='text-center'>Kategori</th>
                    <th class='text-center'>Standar</th>
                    <th class='text-center'>Total Stock</th>
                    <th class='text-center'>Konfirmasi</th>
                </tr>
              </thead><tbody>";
    }

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $hasRow = true;

        $btn = (!empty($row['konfirmasi'])) ? $sudahKonfirm : $belumKonfirm;

        if (trim($warehouse) == "M101") {
            $dus = Penomoran_helper::nilaiKeRibuan($row['qty_dus']);
            echo "<tr data-id='" . $row['id'] . "' data-ps='" . doubleval($row['pakingan_standar']) . "' data-ts='" . $row['total_stock'] . "' data-ko='" . htmlspecialchars($row['KODE_OBAT']) . "'>
                    <td class='text-center'>{$no}</td>
                    <td>" . htmlspecialchars($row['KODE_OBAT']) . "</td>
                    <td>" . htmlspecialchars($row['LONGDESCRIPTION']) . "</td>
                    <td>" . htmlspecialchars($row['LOTCODE']) . "</td>
                    <td class='text-center'>" . htmlspecialchars($row['LOGICALWAREHOUSECODE']) . "</td>
                    <td class='text-right'>" . Penomoran_helper::nilaiKeRibuan(($row['total_o11'] ?? 0) * 1000) . " GR</td>
                    <td class='text-right' id='td_dus_" . $row['id'] . "'>{$dus}</td>
                    <td class='text-right' id='ps_" . $row['id'] . "'>" . Penomoran_helper::nilaiKeRibuan(doubleval($row['pakingan_standar'])) . "</td>
                    <td class='text-right' id='ts_" . $row['id'] . "'>" . Penomoran_helper::nilaiKeRibuan($row['total_stock']) . "</td>
                    <td class='text-center' id='confirm_" . $row['id'] . "'>{$btn}</td>
                  </tr>";
        } else { // M510
            echo "<tr data-id='" . $row['id'] . "' data-ps='" . doubleval($row['pakingan_standar']) . "' data-ts='" . $row['total_stock'] . "' data-ko='" . htmlspecialchars($row['KODE_OBAT']) . "'>
                    <td class='text-center'>{$no}</td>
                    <td>" . htmlspecialchars($row['KODE_OBAT']) . "</td>
                    <td>" . htmlspecialchars($row['LONGDESCRIPTION']) . "</td>
                    <td>" . htmlspecialchars($row['LOTCODE']) . "</td>
                    <td class='text-center'>" . htmlspecialchars($row['LOGICALWAREHOUSECODE']) . "</td>
                    <td class='text-right'>" . Penomoran_helper::nilaiKeRibuan(($row['total_o11'] ?? 0) * 1000) . " GR</td>
                    <td class='text-right' id='td_dus_" . $row['id'] . "'>" . ucfirst($row['kategori']) . "<br/>Qty : " . Penomoran_helper::nilaiKeRibuan($row['qty_dus']) . "</td>
                    <td class='text-right' id='ps_" . $row['id'] . "'>" . Penomoran_helper::nilaiKeRibuan(doubleval($row['pakingan_standar'])) . "</td>
                    <td class='text-right' id='ts_" . $row['id'] . "'>" . Penomoran_helper::nilaiKeRibuan($row['total_stock']) . "</td>
                    <td class='text-center' id='confirm_" . $row['id'] . "'>{$btn}</td>
                  </tr>";
        }

        $no++;
    }

    echo "</tbody></table>";
    sqlsrv_free_stmt($stmt);

    if (!$hasRow) {
        echo "Data Tutup Buku Tidak Tersedia";
    }

} else {
    echo "<p class='text-warning'>Tidak ada data untuk ditampilkan.</p>";
}
?>