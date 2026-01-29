<?PHP
  ini_set("error_reporting", 1);
  session_start();
  include "../../koneksi.php";
  include "../../includes/Penomoran_helper.php";
  include('Response.php');

  $username = $_SESSION['userLAB'];
  $tanggal=date('Y-m-d H:i:s');

  $sudahKonfirm="<button class='btn btn-warning btn-sm detail' title='Detail' data-toggle='tooltip' ><i class='fa fa-info'></i></button> <span style='margin-left:5px;min-width:35px'><i class='fa fa-check' aria-hidden='true'></i> OK</span>";
  $belumKonfirm="<button class='btn btn-warning btn-sm detail' title='Detail' data-toggle='tooltip' ><i class='fa fa-info'></i></button> <button class='btn btn-primary btn-sm confirm' title='Confirm' data-toggle='tooltip' ><i class='fa fa-check-square-o' aria-hidden='true'></i></button>";

  $response = new Response();
  $response->setHTTPStatusCode(201);
  if (isset($_SESSION['userLAB'])) {
    if (isset($_POST['status'])) {
        $id = intval($_POST['id_dt']);
        if($_POST['status']=="konfirmasi" && $id != 0){
            $konfirm="1";
            $update = "UPDATE TOP (1) db_laborat.tbl_stock_opname_gk
                SET konfirmasi = ?,
                    konfirmasi_by = ?,
                    konfirmasi_date = ?
                WHERE id = ?";

            $params = [$konfirm, $username, $tanggal, $id];
            $confirm = sqlsrv_query($con, $update, $params);

            if ($confirm !== false) {
                $response->setSuccess(true);
                $response->addMessage("Berhasil Konfirmasi Stock Opname");
                $response->addMessage($id);
                $response->addMessage($sudahKonfirm);
                $response->send();
            }
            else {
                $response->setSuccess(false);
                $response->addMessage("Gagal Konfirmasi Stock Opname : " . print_r(sqlsrv_errors(), true));
                $response->send();
            }
        }
        else if($_POST['status']=="cek_data"){
            $tgl_tutup = $_POST['tgl_tutup'];
            $warehouse = $_POST['warehouse'];
            $query = "SELECT id,qty_dus,total_stock,kategori,pakingan_standar,konfirmasi
                        FROM db_laborat.tbl_stock_opname_gk
                        WHERE tgl_tutup = ?
                        AND LOGICALWAREHOUSECODE = ?
                        ORDER BY KODE_OBAT ASC";

            $params = [$tgl_tutup, $warehouse];
            $stmt = sqlsrv_query($con, $query, $params);

            if ($stmt === false) {
                $err = print_r(sqlsrv_errors(), true);
                echo "<p class='text-danger'>Query gagal: " . $err . "</p>";
                $response->setSuccess(false);
                $response->addMessage("Query gagal: " . $query . " \nERROR : " . $err);
            }

            $num_rows_data = 0;
            $dataOpname = array();

            while ($rowOpname = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $num_rows_data++;

                $tmp_data = array();
                $tmp_data['id'] = $rowOpname['id'];
                $tmp_data['qty_dus'] = Penomoran_helper::nilaiKeRibuan($rowOpname['qty_dus']);
                $tmp_data['total_stock'] = Penomoran_helper::nilaiKeRibuan($rowOpname['total_stock']);
                $tmp_data['pakingan_standar'] = Penomoran_helper::nilaiKeRibuan($rowOpname['pakingan_standar']);

                if ($rowOpname['konfirmasi']) {
                    $tmp_data['konfirm'] = $sudahKonfirm;
                } else {
                    $tmp_data['konfirm'] = $belumKonfirm;
                }
                $dataOpname[] = $tmp_data;
            }

            if ($num_rows_data > 0) {
                    $response->setSuccess(true);
                    $response->addMessage("Berhasil Check Data");
                    $response->addMessage($num_rows_data);
                    $response->setData($dataOpname);
                }else{
                    $response->setSuccess(false);
                    $response->addMessage("Gagal Check Data");
                }
                $response->send();
        }
        else if($_POST['status']=="cek_data_m510"){
            $tgl_tutup = $_POST['tgl_tutup'];
            $warehouse = $_POST['warehouse'];
            $query = "SELECT id,qty_dus,total_stock,kategori,pakingan_standar,konfirmasi
                        FROM db_laborat.tbl_stock_opname_gk
                        WHERE tgl_tutup = ?
                        AND LOGICALWAREHOUSECODE = ?
                        ORDER BY KODE_OBAT ASC";

            $params = [$tgl_tutup, $warehouse];
            $stmt = sqlsrv_query($con, $query, $params);

            if ($stmt === false) {
                $err = print_r(sqlsrv_errors(), true);
                echo "<p class='text-danger'>Query gagal: " . $err . "</p>";
                $response->setSuccess(false);
                $response->addMessage("Query gagal: " . $query . " \nERROR : " . $err);
            }

            $num_rows_data = 0;
            $dataOpname = array();

            while ($rowOpname = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $num_rows_data++;

                $tmp_data = array();
                $tmp_data['id'] = $rowOpname['id'];
                $tmp_data['qty_dus'] = Penomoran_helper::nilaiKeRibuan($rowOpname['qty_dus']);
                $tmp_data['total_stock'] = Penomoran_helper::nilaiKeRibuan($rowOpname['total_stock']);
                $tmp_data['pakingan_standar'] = Penomoran_helper::nilaiKeRibuan($rowOpname['pakingan_standar']);

                if ($rowOpname['konfirmasi']) {
                    $tmp_data['konfirm'] = $sudahKonfirm;
                } else {
                    $tmp_data['konfirm'] = $belumKonfirm;
                }
                $dataOpname[] = $tmp_data;
            }

            if ($num_rows_data > 0) {
                    $response->setSuccess(true);
                    $response->addMessage("Berhasil Check Data");
                    $response->addMessage($num_rows_data);
                    $response->setData($dataOpname);
                }else{
                    $response->setSuccess(false);
                    $response->addMessage("Gagal Check Data");
                }
                $response->send();
        }
        else if($_POST['status']=="get_scan_opname" && $id != 0){
            $scan=array();
            $sqlScan = "SELECT s.*, o.konfirmasi
                        FROM db_laborat.tbl_scan_stock_opname_gk s
                        LEFT JOIN db_laborat.tbl_stock_opname_gk o ON s.id_dt = o.id
                        WHERE s.id_dt = ?";

            $scanData = sqlsrv_query($con, $sqlScan, [$id]);
            while ($rowScan = sqlsrv_fetch_array($scanData, SQLSRV_FETCH_ASSOC)) {
                $tmp_data['qty_dus']=Penomoran_helper::nilaiKeRibuan($rowScan['qty_dus']);
                $tmp_data['kategori']=ucfirst($rowScan['kategori']);
                $tmp_data['kategoriText']=ucfirst($rowScan['kategori']). ($rowScan['kategori']=="utuhan"?"":" (Bukaan)");
                $tmp_data['pakingan_standar']=Penomoran_helper::nilaiKeRibuan(doubleval($rowScan['pakingan_standar']));
                $tmp_data['konfirmasi']=$rowScan['konfirmasi'];
                $tmp_data['username']=strtoupper($username);
                $tmp_data['total_stock']=Penomoran_helper::nilaiKeRibuan($rowScan['total_stock']);
                $tmp_data['id']=$rowScan['id'];
                $db = $rowScan['time'];

                if ($db instanceof DateTime) {
                    $timestamp = $db->getTimestamp();
                } else {
                    $timestamp = strtotime((string) $db);
                }
                $tmp_data['time']=date("d/m/Y H:i:s", $timestamp);
                $scan[]=$tmp_data;
            }
            $response->setSuccess(true);
            $response->addMessage("Berhasil get scan");
            $response->setData($scan);
            $response->send();
        }
        else if ($_POST['status'] == "edit_scan_opname" && $id != 0) {

            $sqlEdit = "SELECT s.*, o.konfirmasi
                FROM db_laborat.tbl_scan_stock_opname_gk s
                LEFT JOIN db_laborat.tbl_stock_opname_gk o ON s.id_dt = o.id
                WHERE s.id = ?";

            $editData = sqlsrv_query($con, $sqlEdit, [$id]);
            if ($editData === false) {
                $response->setSuccess(false);
                $response->addMessage("Query gagal: " . $sqlEdit . " \nERROR : " . print_r(sqlsrv_errors(), true));
                $response->send();
                exit;
            }

            $num_rows_data = 0;
            $dataEdit = array();

            while ($row = sqlsrv_fetch_array($editData, SQLSRV_FETCH_ASSOC)) {
                $num_rows_data++;

                $dataEdit['id'] = $row['id'];
                $dataEdit['kode_obat'] = $_POST['kode_obat'];
                $dataEdit['qty_dus'] = $row['qty_dus'];
                $dataEdit['pakingan_standar'] = $row['pakingan_standar'];
                $dataEdit['total_stock'] = $row['total_stock'];
                $dataEdit['kategori'] = $row['kategori'];
                $dataEdit['konfirmasi'] = $row['konfirmasi'];
                $dataEdit['username'] = strtoupper($username);

                $dataEdit['qty_dus_text'] = Penomoran_helper::nilaiKeRibuan($row['qty_dus']);
                $dataEdit['pakingan_standar_text'] = Penomoran_helper::nilaiKeRibuan($row['pakingan_standar']);
                $dataEdit['total_stock_text'] = Penomoran_helper::nilaiKeRibuan($row['total_stock']);

                // inisiasi data awal standar packaging
                $dataEdit['ut'] = 0;
                $dataEdit['tg'] = 0;
                $dataEdit['bj'] = 0;
                $dataEdit['bp'] = 0;
                $dataEdit['bk'] = 0;
            }

            if ($num_rows_data > 0) {

                $sp = "SELECT TOP (1) *
               FROM db_laborat.tbl_standar_packaging s
               WHERE s.kode_erp = ?";

                $spResult = sqlsrv_query($con, $sp, [$_POST['kode_obat']]);
                if ($spResult === false) {
                    $response->setSuccess(false);
                    $response->addMessage("Query gagal: " . $sp . " \nERROR : " . print_r(sqlsrv_errors(), true));
                    $response->send();
                    exit;
                }

                while ($rowSP = sqlsrv_fetch_array($spResult, SQLSRV_FETCH_ASSOC)) {
                    $dataEdit['ut'] = $rowSP['pakingan_utuh'];
                    $dataEdit['tg'] = $rowSP['tinggi_pakingan'];
                    $dataEdit['bj'] = $rowSP['bj_pakingan'];
                    $dataEdit['bp'] = $rowSP['berat_pakingan'];
                    $dataEdit['bk'] = $rowSP['berat_pakingan_botol_kecil'];
                }

                $response->setSuccess(true);
                $response->addMessage("Berhasil Menampilkan Tutup Buku");
                $response->addMessage($num_rows_data);
                $response->setData($dataEdit);

            } else {
                $response->setSuccess(false);
                $response->addMessage("Data Tutup Buku Untuk " . $_POST['kode_obat'] . "  Tidak Tersedia");
            }

            $response->send();
        } else if ($_POST['status'] == "simpan_scan" && $id != 0) {

            // update scan (SQL Server)
            $update = "UPDATE db_laborat.tbl_scan_stock_opname_gk
               SET qty_dus = ?,
                   pakingan_standar = ?,
                   kategori = ?,
                   total_stock = ?
               WHERE id = ?";

            $paramsUpdate = [
                (float) $_POST['qty_scan'],
                (string) $_POST['pakingan_standar'],
                (string) $_POST['kategori'],
                (float) $_POST['total_scan'],
                (int) $id
            ];

            $confirm = sqlsrv_query($con, $update, $paramsUpdate);
            if ($confirm !== false) {

                // refresh header (SQL Server)
                $sqlRefresh = "UPDATE db_laborat.tbl_stock_opname_gk
                       SET
                         qty_dus = (SELECT SUM(s1.qty_dus) FROM db_laborat.tbl_scan_stock_opname_gk s1 WHERE s1.id_dt = ?),
                         total_stock = (SELECT SUM(s2.total_stock) FROM db_laborat.tbl_scan_stock_opname_gk s2 WHERE s2.id_dt = ?),
                         konfirmasi = '0'
                       WHERE id = ?";

                $idStock = (int) $_POST['id_stock_opname'];
                $paramsRefresh = [$idStock, $idStock, $idStock];

                $prepareRefresh = sqlsrv_query($con, $sqlRefresh, $paramsRefresh);

                if ($prepareRefresh === false) {
                    $response->setSuccess(false);
                    $response->addMessage("Gagal Refresh Stock : " . print_r(sqlsrv_errors(), true));
                    $response->send();
                    exit;
                }

                $response->setSuccess(true);
                $response->addMessage("Berhasil Save Scan");
                $response->send();

            } else {
                $response->setSuccess(false);
                $response->addMessage("Gagal Save Scan : " . print_r(sqlsrv_errors(), true));
                $response->send();
            }
        } else if ($_POST['status'] == "delete_scan" && $id != 0) {

            $delete = "DELETE FROM db_laborat.tbl_scan_stock_opname_gk 
               WHERE id = ?";

            $paramsDelete = [(int) $id];
            $del = sqlsrv_query($con, $delete, $paramsDelete);

            if ($del !== false) {

                $sqlRefresh = "UPDATE db_laborat.tbl_stock_opname_gk 
                       SET 
                         qty_dus = (select SUM(s1.qty_dus) from db_laborat.tbl_scan_stock_opname_gk s1 where s1.id_dt = ? ),
                         total_stock = (select SUM(s2.total_stock) from db_laborat.tbl_scan_stock_opname_gk s2 where s2.id_dt = ? ),
                         konfirmasi = '0'
                       WHERE id = ?";

                $idStock = (int) $_POST['id_stock_opname'];
                $paramsRefresh = [$idStock, $idStock, $idStock];

                $prepareRefresh = sqlsrv_query($con, $sqlRefresh, $paramsRefresh);

                if ($prepareRefresh === false) {
                    $response->setSuccess(false);
                    $response->addMessage("Gagal Refresh Stock : " . print_r(sqlsrv_errors(), true));
                    $response->send();
                    exit;
                }

                $response->setSuccess(true);
                $response->addMessage("Berhasil Delete Scan");
                $response->send();

            } else {
                $response->setSuccess(false);
                $response->addMessage("Gagal Delete Scan : " . print_r(sqlsrv_errors(), true));
                $response->send();
            }
        } else {
            $response->setSuccess(false);
            $response->addMessage("Error Status");
            $response->send();
        }

    }
  }
  if(isset($_SESSION['opname'])&&$_SESSION['opname']=="gk"){
    $id = intval($_POST['id_dt']);
    if($_POST['check']=="check_transaksi_multiple"){
        $tgl_tutup = $_POST['tgl_tutup'];
        $warehouse = $_POST['warehouse'];
        $prepare=db2_prepare ($conn1,"SELECT
                DECOSUBCODE01,
                DECOSUBCODE02,
                DECOSUBCODE03,
                LOTCODE 
            FROM
                STOCKTRANSACTION s
            WHERE 
                s.TRANSACTIONNUMBER = ? ");
        db2_execute($prepare,array(trim($_POST['val']," ")));
        
        $dataTransaksi=array();
        $ct=0;
        while($rowdb = db2_fetch_assoc($prepare)){
            $ct++;
            $dataTransaksi[$ct]['kode_obat']=trim($rowdb["DECOSUBCODE01"]," ")."-".trim($rowdb["DECOSUBCODE02"]," ")."-".trim($rowdb["DECOSUBCODE03"]," ");
            $dataTransaksi[$ct]['lot']=trim($rowdb["LOTCODE"]," ");
        }
        if ($ct > 0) {
            $sqlLot = "SELECT DISTINCT LOTCODE
               FROM  db_laborat.tblopname_11
               WHERE CAST(tgl_tutup AS date) = ?
                 AND LOGICALWAREHOUSECODE = ?
                 AND KODE_OBAT = ?
                 AND KODE_OBAT <> 'E-1-000'";

            $paramsLot = [$tgl_tutup, $warehouse, $dataTransaksi[1]['kode_obat']];
            $get_lotcode = sqlsrv_query($con, $sqlLot, $paramsLot);

            if ($get_lotcode === false) {
                $response->setSuccess(false);
                $response->addMessage("Query get lotcode gagal: " . print_r(sqlsrv_errors(), true));
                $response->send();
                exit;
            }

            $all_lotcode = array();
            while ($rowLotCode = sqlsrv_fetch_array($get_lotcode, SQLSRV_FETCH_ASSOC)) {
                $all_lotcode[] = trim($rowLotCode['LOTCODE'] ?? '', " ");
            }

            $dataTransaksiFilter = array();
            $ctFilter = 0;
            $tmp_lot = array();

            foreach ($dataTransaksi as $idt => $rdt) {
                if (in_array($rdt['lot'], $all_lotcode)) {
                    if (!in_array($rdt['lot'], $tmp_lot)) {
                        $tmp_lot[] = $rdt['lot'];
                        $ctFilter++;
                        $dataTransaksiFilter[$ctFilter]['kode_obat'] = $rdt['kode_obat'];
                        $dataTransaksiFilter[$ctFilter]['lot'] = $rdt['lot'];
                    }
                }
            }

            if ($ctFilter > 0) {
                $response->setSuccess(true);
                $response->addMessage("Berhasil Menampilkan Data Transaksi");
                $response->addMessage($ctFilter);
                $response->setData($dataTransaksiFilter);
            } else {
                $response->setSuccess(false);
                $response->addMessage("Data Transaksi Tidak Ditemukan");
                $response->addMessage($all_lotcode);
                $response->setData($dataTransaksi);
            }
        } else{
            $response->setSuccess(false);
            $response->addMessage("Data Transaksi Tidak Ditemukan");
        }
        $response->send();
    }
    else if ($_POST['check'] == "edit_data") {
        $tgl_tutup = $_POST['tgl_tutup'];
        $warehouse = $_POST['warehouse'];

        $checkSql = "SELECT TOP 1 id
                    FROM  db_laborat.tbl_stock_opname_gk
                    WHERE CAST(tgl_tutup AS date) = ?
                    AND KODE_OBAT <> 'E-1-000'";
        $checkParams = [$tgl_tutup];
        $checkStmt = sqlsrv_query($con, $checkSql, $checkParams);
        if ($checkStmt === false) {
            $response->setSuccess(false);
            $response->addMessage("Query check gagal: " . print_r(sqlsrv_errors(), true));
            $response->send();
            exit;
        }
        $row_count = (sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC) ? 1 : 0);

        if ($row_count == 0) {
            $insertSql = "INSERT INTO  db_laborat.tbl_stock_opname_gk
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
                '0'
            FROM  db_laborat.tblopname_11
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
            $insertParams = [$tgl_tutup];
            $insertStmt = sqlsrv_query($con, $insertSql, $insertParams);
            if ($insertStmt === false) {
                $response->setSuccess(false);
                $response->addMessage("Insert migrasi gagal: " . print_r(sqlsrv_errors(), true));
                $response->send();
                exit;
            }
        }

        $queryOLD = "SELECT *
            FROM  db_laborat.tbl_stock_opname_gk
            WHERE
                tgl_tutup = '$tgl_tutup'
                AND LOGICALWAREHOUSECODE = '$warehouse'
                AND KODE_OBAT = '" . $_POST['kode_obat'] . "'
                AND LOTCODE = '" . $_POST['lot'] . "'
            ORDER BY KODE_OBAT ASC";

        $sql = "SELECT o.*, d.total_qty AS total_o11
            FROM  db_laborat.tbl_stock_opname_gk o
            LEFT JOIN (
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
                    FROM  db_laborat.tblopname_11
                    WHERE CAST(tgl_tutup AS date) = ?
                      AND LOGICALWAREHOUSECODE = ?
                      AND KODE_OBAT = ?
                      AND LOTCODE = ?
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
              ON o.KODE_OBAT = d.KODE_OBAT
             AND o.LOTCODE = d.LOTCODE
             AND o.tgl_tutup = d.tgl_tutup
             AND o.LOGICALWAREHOUSECODE = d.LOGICALWAREHOUSECODE
            WHERE CAST(o.tgl_tutup AS date) = ?
              AND o.LOGICALWAREHOUSECODE = ?
              AND o.KODE_OBAT = ?
              AND o.LOTCODE = ?
            ORDER BY o.KODE_OBAT ASC";

        $params = [
            $tgl_tutup,
            $warehouse,
            $_POST['kode_obat'],
            $_POST['lot'],
            $tgl_tutup,
            $warehouse,
            $_POST['kode_obat'],
            $_POST['lot']
        ];

        $stmt = sqlsrv_query($con, $sql, $params);
        if ($stmt === false) {
            $response->setSuccess(false);
            $response->addMessage("Query gagal: " . print_r(sqlsrv_errors(), true));
            $response->send();
            exit;
        }

        $first = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if ($first) {
            $dataOpane = array();

            $rowOpname = $first;
            $dataOpane['id'] = $rowOpname['id'];
            $dataOpane['kode_obat'] = $rowOpname['KODE_OBAT'];
            $dataOpane['nama_obat'] = $rowOpname['LONGDESCRIPTION'];
            $dataOpane['lot'] = $rowOpname['LOTCODE'];
            $dataOpane['total_qty'] = ($rowOpname['total_o11'] ?? 0) * 1000;
            $dataOpane['qty_dus'] = $rowOpname['qty_dus'];
            $dataOpane['pakingan_standar'] = $rowOpname['pakingan_standar'];
            $dataOpane['total_stock'] = $rowOpname['total_stock'];
            $dataOpane['kategori'] = $rowOpname['kategori'];
            $dataOpane['c'] = $rowOpname['konfirmasi'];

            $dataOpane['total_qty_text'] = Penomoran_helper::nilaiKeRibuan(($rowOpname['total_o11'] ?? 0) * 1000);
            $dataOpane['qty_dus_text'] = Penomoran_helper::nilaiKeRibuan($rowOpname['qty_dus']);
            $dataOpane['pakingan_standar_text'] = Penomoran_helper::nilaiKeRibuan($rowOpname['pakingan_standar']);
            $dataOpane['total_stock_text'] = Penomoran_helper::nilaiKeRibuan($rowOpname['total_stock']);

            $dataOpane['ut'] = 0;
            $dataOpane['tg'] = 0;
            $dataOpane['bj'] = 0;
            $dataOpane['bp'] = 0;
            $dataOpane['bk'] = 0;

            while ($rowOpname = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $dataOpane['id'] = $rowOpname['id'];
                $dataOpane['kode_obat'] = $rowOpname['KODE_OBAT'];
                $dataOpane['nama_obat'] = $rowOpname['LONGDESCRIPTION'];
                $dataOpane['lot'] = $rowOpname['LOTCODE'];
                $dataOpane['total_qty'] = ($rowOpname['total_o11'] ?? 0) * 1000;
                $dataOpane['qty_dus'] = $rowOpname['qty_dus'];
                $dataOpane['pakingan_standar'] = $rowOpname['pakingan_standar'];
                $dataOpane['total_stock'] = $rowOpname['total_stock'];
                $dataOpane['kategori'] = $rowOpname['kategori'];
                $dataOpane['c'] = $rowOpname['konfirmasi'];

                $dataOpane['total_qty_text'] = Penomoran_helper::nilaiKeRibuan(($rowOpname['total_o11'] ?? 0) * 1000);
                $dataOpane['qty_dus_text'] = Penomoran_helper::nilaiKeRibuan($rowOpname['qty_dus']);
                $dataOpane['pakingan_standar_text'] = Penomoran_helper::nilaiKeRibuan($rowOpname['pakingan_standar']);
                $dataOpane['total_stock_text'] = Penomoran_helper::nilaiKeRibuan($rowOpname['total_stock']);

                $dataOpane['ut'] = 0;
                $dataOpane['tg'] = 0;
                $dataOpane['bj'] = 0;
                $dataOpane['bp'] = 0;
                $dataOpane['bk'] = 0;
            }

            if ($dataOpane['c'] == 1) {
                $response->setSuccess(false);
                $response->addMessage("Data : " . $dataOpane['kode_obat'] . " lot " . $dataOpane['lot'] . " Dengan Qty : " . $dataOpane['qty_dus_text'] . " dan Total Stock : " . $dataOpane['total_stock_text'] . " Sudah Di Konfirmasi");
            } else {
                $spSql = "SELECT TOP 1 * FROM  db_laborat.tbl_standar_packaging WHERE kode_erp = ?";
                $spParams = [$dataOpane['kode_obat']];
                $spResult = sqlsrv_query($con, $spSql, $spParams);
                if ($spResult === false) {
                    $response->setSuccess(false);
                    $response->addMessage("Query standar packaging gagal: " . print_r(sqlsrv_errors(), true));
                    $response->send();
                    exit;
                }
                while ($rowSP = sqlsrv_fetch_array($spResult, SQLSRV_FETCH_ASSOC)) {
                    $dataOpane['ut'] = $rowSP['pakingan_utuh'];
                    $dataOpane['tg'] = $rowSP['tinggi_pakingan'];
                    $dataOpane['bj'] = $rowSP['bj_pakingan'];
                    $dataOpane['bp'] = $rowSP['berat_pakingan'];
                    $dataOpane['bk'] = $rowSP['berat_pakingan_botol_kecil'];
                }

                $response->setSuccess(true);
                $response->addMessage("Berhasil Menampilkan Tutup Buku");
                $response->addMessage(1); 
                $response->setData($dataOpane);
            }
        } else {
            $response->setSuccess(false);
            $response->addMessage("Data Tutup Buku Untuk " . $_POST['kode_obat'] . " lot " . $_POST['lot'] . " Tidak Tersedia");
        }

        $response->send();
    }
    else if ($_POST['check'] == "simpan_stock" && $id != 0) {
        $ip = $response->get_client_ip();

        $sqlLog = "INSERT INTO  db_laborat.tbl_scan_stock_opname_gk
        (id_dt, qty_dus, pakingan_standar, kategori, total_stock, user_ip)
        VALUES (?, ?, ?, ?, ?, ?)";

        $paramsLog = [
            $id,
            $_POST['qty_scan'],
            $_POST['pakingan_standar'],
            $_POST['kategori'],
            $_POST['total_scan'],
            $ip
        ];

        $stmtLog = sqlsrv_query($con, $sqlLog, $paramsLog);
        if ($stmtLog !== false) {

            $update = "UPDATE  db_laborat.tbl_stock_opname_gk
            SET qty_dus = (SELECT ISNULL(SUM(s1.qty_dus),0) FROM db_laborat.tbl_scan_stock_opname_gk s1 WHERE s1.id_dt = ?),
                pakingan_standar = ?,
                kategori = ?,
                total_stock = (SELECT ISNULL(SUM(s2.total_stock),0) FROM db_laborat.tbl_scan_stock_opname_gk s2 WHERE s2.id_dt = ?)
            WHERE id = ?";

            $paramsUpd = [
                $id,
                $_POST['pakingan_standar'],
                $_POST['kategori'],
                $id,
                $id
            ];

            $stmtUpd = sqlsrv_query($con, $update, $paramsUpd);
            if ($stmtUpd === false) {
                $response->setSuccess(false);
                $response->addMessage("Gagal Update Stock : " . print_r(sqlsrv_errors(), true));
                $response->send();
                exit;
            }

            $response->setSuccess(true);
            $response->addMessage("Berhasil Save Stock");
            $response->addMessage($id);
            $response->send();
        } else {
            $response->setSuccess(false);
            $response->addMessage("Gagal Save Stock : " . print_r(sqlsrv_errors(), true));
            $response->send();
        }
    }

}
  $response->setSuccess(false);
  $response->addMessage("Tidak ada sesion");
  $response->send();
  
  