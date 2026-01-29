<?php
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=StockOpname Gd Kimia " . date($_GET['tgl_tutup'])." ". $_GET['warehouse'] . ".xls"); //ganti nama sesuai keperluan
header("Pragma: no-cache");
header("Expires: 0");
//disini script laporan anda
ob_start();


// ini_set("error_reporting", 1);
include "../../koneksi.php";
include "../../includes/Penomoran_helper.php";

$tgl_tutup = isset($_GET['tgl_tutup']) ? $_GET['tgl_tutup'] : '';
$warehouse = isset($_GET['warehouse']) ? $_GET['warehouse'] : '';
$tgl = date("Y-m-d");
?>
<html>
    <head>
        <meta charset="UTF-8">
        <style>
            td,
            th {
                mso-number-format: "\@";
                padding: 5px;
                border: 1px solid #000;
            }

            .number {
                mso-number-format: "#,##0";
            }

            .int {
                mso-number-format: "0";
            }

            th {
                background-color: #f0f0f0;
            }
        </style>
    </head>
    <body>
    <?php
    if(trim($warehouse," ")=="M101"){
        $queryOLD = "SELECT *
            FROM db_laborat.tbl_stock_opname_gk 
            WHERE 
                CAST(tgl_tutup AS date) = CONVERT(date, ?, 23)
                AND LOGICALWAREHOUSECODE = ?
            ORDER BY KODE_OBAT ASC";

        $query = "SELECT o.*, d.total_qty as total_o11
            FROM db_laborat.tbl_stock_opname_gk o
            LEFT JOIN 
            (
                SELECT 
                    ITEMTYPECODE,
                    KODE_OBAT,
                    LONGDESCRIPTION,
                    LOTCODE,
                    LOGICALWAREHOUSECODE,
                    tgl_tutup,
                    SUM(BASEPRIMARYQUANTITYUNIT) AS total_qty,
                    BASEPRIMARYUNITCODE,
                    '0'AS dummy
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
                    WHERE CAST(tgl_tutup AS date) = CONVERT(date, ?, 23)
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
            AND o.LOTCODE = d.LOTCODE
            AND o.LOGICALWAREHOUSECODE = d.LOGICALWAREHOUSECODE
            AND CAST(o.tgl_tutup AS date) = CAST(d.tgl_tutup AS date)
            WHERE 
                CAST(o.tgl_tutup AS date) = CONVERT(date, ?, 23)
                AND o.LOGICALWAREHOUSECODE = ?
            ORDER BY o.KODE_OBAT ASC";

        $params = [$tgl_tutup, $warehouse, $tgl_tutup, $warehouse, $tgl_tutup, $warehouse];

        $stmt = sqlsrv_query($con, $query, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        if (sqlsrv_has_rows($stmt)) {
            $no = 1;
            echo "<table class='table table-bordered table-striped' id='detailmasukTable'>";
            echo "<thead>
                    <tr>
                        <th align='center'>No</th>
                        <th align='center'>Kode Obat</th>
                        <th align='center'>Nama Obat</th>
                        <th align='center'>Lot</th>
                        <th align='center'>Logical Warehouse</th>
                        <th align='center'>Qty (Ending Balance) GR</th>
                        <th align='center'>QTY Dus</th>
                        <th align='center'>Standar packaging</th>
                        <th align='center'>Total Stock</th>
                    </tr>
                </thead>";
            echo "<tbody>";

            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                echo "<tr>
                        <td align='center'>{$no}</td>
                        <td>".htmlspecialchars($row['KODE_OBAT'])."</td>
                        <td>".htmlspecialchars($row['LONGDESCRIPTION'])."</td>
                        <td>".htmlspecialchars($row['LOTCODE'])."</td>
                        <td align='center'>".htmlspecialchars($row['LOGICALWAREHOUSECODE'])."</td>
                        <td class='number'>".($row['total_o11']*1000)."</td>
                        <td class='number'>".$row['qty_dus']."</td>
                        <td class='number'>".doubleval($row['pakingan_standar'])."</td>
                        <td class='number'>".$row['total_stock']."</td>
                    </tr>";
                $no++;
            }

            echo "</tbody></table>";
        } else {
            echo "Data Tutup Buku Tidak Tersedia";
        }
    } else if (trim($warehouse, " ") == "M510") {
        $queryOLD = "SELECT *
        FROM db_laborat.tbl_stock_opname_gk 
        WHERE 
            CAST(tgl_tutup AS date) = CONVERT(date, ?, 23)
            AND LOGICALWAREHOUSECODE = ?
        ORDER BY KODE_OBAT ASC";

        $query = "SELECT o.*, d.total_qty as total_o11
        FROM db_laborat.tbl_stock_opname_gk o
        LEFT JOIN 
        (
            SELECT 
                ITEMTYPECODE,
                KODE_OBAT,
                LONGDESCRIPTION,
                LOTCODE,
                LOGICALWAREHOUSECODE,
                tgl_tutup,
                SUM(BASEPRIMARYQUANTITYUNIT) AS total_qty,
                BASEPRIMARYUNITCODE,
                '0' AS dummy
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
                WHERE CAST(tgl_tutup AS date) = CONVERT(date, ?, 23)
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
        AND o.LOTCODE = d.LOTCODE
        AND o.LOGICALWAREHOUSECODE = d.LOGICALWAREHOUSECODE
        AND CAST(o.tgl_tutup AS date) = CAST(d.tgl_tutup AS date)
        WHERE 
            CAST(o.tgl_tutup AS date) = CONVERT(date, ?, 23)
            AND o.LOGICALWAREHOUSECODE = ?
        ORDER BY o.KODE_OBAT ASC";

        $params = [$tgl_tutup, $warehouse, $tgl_tutup, $warehouse, $tgl_tutup, $warehouse];

        $stmt = sqlsrv_query($con, $query, $params);
        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        if (sqlsrv_has_rows($stmt)) {
            $no = 1;
            echo "<table class='table table-bordered table-striped' id='detailmasukTable'>";
            echo "<thead>
                <tr>
                    <th align='center'>No</th>
                    <th align='center'>Kode Obat</th>
                    <th align='center'>Nama Obat</th>
                    <th align='center'>Lot</th>
                    <th align='center'>Logical Warehouse</th>
                    <th align='center'>Qty (Ending Balance) GR</th>
                    <th align='center'>Kategori</th>
                    <th align='center'>QTY Scan</th>
                    <th align='center'>Standar</th>
                    <th align='center'>Total Stock</th>
                </tr>
              </thead>";
            echo "<tbody>";

            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                echo "<tr>
                    <td align='center'>{$no}</td>
                    <td>" . htmlspecialchars($row['KODE_OBAT']) . "</td>
                    <td>" . htmlspecialchars($row['LONGDESCRIPTION']) . "</td>
                    <td>" . htmlspecialchars($row['LOTCODE']) . "</td>
                    <td align='center'>" . htmlspecialchars($row['LOGICALWAREHOUSECODE']) . "</td>
                    <td class='number'>" . ($row['total_o11'] * 1000) . "</td>
                    <td align='center'>" . ucfirst($row['kategori']) . "</td>
                    <td class='number'>" . $row['qty_dus'] . "</td>
                    <td class='number'>" . doubleval($row['pakingan_standar']) . "</td>
                    <td class='number'>" . $row['total_stock'] . "</td>
                  </tr>";
                $no++;
            }

            echo "</tbody></table>";
        } else {
            echo "Data Tutup Buku Tidak Tersedia";
        }
    }
    ?>
    </body>
</html>
<?php
 ob_end_flush(); 
 
 ?>