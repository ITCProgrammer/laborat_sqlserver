<?PHP
  ini_set("error_reporting", 1);
  session_start();
  include "../../koneksi.php";
  include "../../includes/Penomoran_helper.php";
  include('Response.php');

  $username = $_SESSION['userLAB']??"";
  $tanggal=date('Y-m-d H:i:s');
  $button="<button class='btn btn-warning btn-sm edit_sp' title='Edit' data-toggle='tooltip' ><i class='fa fa-pencil'></i></button> 
           <button class='btn btn-danger btn-sm delete_sp' title='Delete' data-toggle='tooltip' ><i class='fa fa-trash'></i></button> 
           <button class='btn btn-success btn-sm preview_sp' title='Preview' data-toggle='tooltip' ><i class='fa fa-list'></i></button>";

  $response = new Response();
  $response->setHTTPStatusCode(201);
  if (isset($_SESSION['userLAB'])) {
    if (isset($_POST['status'])) {
        $id = intval($_POST['id_dt']??0);
        $sp_id = intval($_POST['sp_id']??"");
        if($_POST['status']=="add_sp" && $_POST['sp_kode_erp'] !="" && $_POST['sp_kode_erp']){
            $konfirm="1";
            $update =  "INSERT db_laborat.tbl_standar_packaging (kode_obat,kode_erp,nama_obat,pakingan_utuh,pakingan_utuh_keterangan,tinggi_pakingan,bj_pakingan,berat_pakingan,
            berat_pakingan_botol_kecil,keterangan,jenis) VALUES (? , ? ,? ,? ,? ,? ,? ,? ,? ,? ,?); SELECT @@IDENTITY as id;";
            $params = [
                $_POST['sp_kode_obat'],
                $_POST['sp_kode_erp'],
                $_POST['sp_nama_obat'],
                $_POST['sp_pakingan_utuh'],
                $_POST['sp_pakingan_utuh_keterangan'],
                $_POST['sp_tinggi_pakingan'],
                $_POST['sp_bj_pakingan'],
                $_POST['sp_berat_pakingan'],
                $_POST['sp_berat_pakingan_botol_kecil'],
                $_POST['sp_keterangan'],
                $_POST['sp_jenis']
                ];
            $confirm = sqlsrv_query($con, $update, $params);
            if($confirm){ 
                $next_result = sqlsrv_next_result($confirm);
                $row_next = sqlsrv_fetch_array($confirm, SQLSRV_FETCH_ASSOC);
                $last_id = $row_next["id"];
                $row="<tr id='tr_$last_id' data-id='".$last_id."' data-no='" .$_POST['last_no']. "'>
                        <td class='text-center'>".$_POST['last_no']."</td>
                        <td>" . htmlspecialchars($_POST['sp_kode_erp']) . "</td>
                        <td>" . htmlspecialchars($_POST['sp_nama_obat']) . "</td>
                        <td>" . Penomoran_helper::nilaiKeRibuanStandarPackage($_POST['sp_pakingan_utuh']) . "</td>
                        <td>" . Penomoran_helper::nilaiKeRibuanStandarPackage($_POST['sp_tinggi_pakingan']) . "</td>
                        <td>" . Penomoran_helper::nilaiKeRibuanStandarPackage($_POST['sp_bj_pakingan']) . "</td>
                        <td>" . Penomoran_helper::nilaiKeRibuanStandarPackage($_POST['sp_berat_pakingan']) . "</td>
                        <td>" . Penomoran_helper::nilaiKeRibuanStandarPackage($_POST['sp_berat_pakingan_botol_kecil']) . "</td>
                        <td>" . htmlspecialchars($_POST['sp_jenis']) . "</td>
                        <td>" . $button . "</td>
                    </tr>";
                $response->setSuccess(true);
                $response->addMessage("Berhasil Menambahkan Standar Packaging");
                $response->addMessage($row);
                $response->addMessage($last_id);
                $response->addMessage($_POST['status']);
                $response->send();
            }
            else {
                $response->setSuccess(false);
                $response->addMessage("Gagal Menambahkan Standar Packaging : ".sqlsrv_errors());
                $response->send();
            }
        }
        else if($_POST['status']=="get_sp" && $id != 0){
            $data=array();
            $get="SELECT TOP (1) * FROM db_laborat.tbl_standar_packaging WHERE id = ? ";
            $getData = sqlsrv_query($con, $get, [$id]);
            while ($row = sqlsrv_fetch_array($getData, SQLSRV_FETCH_ASSOC)) {
                $data=$row;
            }
            $response->setSuccess(true);
            $response->addMessage("Berhasil Get Standar Packaging");
            $response->setData($data);
            $response->send();
        }
        else if($_POST['status']=="edit_sp" && $sp_id != 0 && $_POST['sp_kode_erp'] !="" && $_POST['sp_kode_erp']){
            $update = "UPDATE TOP (1) db_laborat.tbl_standar_packaging 
                 SET kode_obat =? ,
                 kode_erp = ? ,
                 nama_obat =? ,
                 pakingan_utuh =? ,
                 pakingan_utuh_keterangan =? ,
                 tinggi_pakingan =? ,
                 bj_pakingan =? ,
                 berat_pakingan =? ,
                 berat_pakingan_botol_kecil =? ,
                 keterangan =? ,
                 jenis =? 
                 WHERE id = ?";
            $params = [
                $_POST['sp_kode_obat'],
                $_POST['sp_kode_erp'],
                $_POST['sp_nama_obat'],
                $_POST['sp_pakingan_utuh'],
                $_POST['sp_pakingan_utuh_keterangan'],
                $_POST['sp_tinggi_pakingan'],
                $_POST['sp_bj_pakingan'],
                $_POST['sp_berat_pakingan'],
                $_POST['sp_berat_pakingan_botol_kecil'],
                $_POST['sp_keterangan'],
                $_POST['sp_jenis'],
                $sp_id 
                ];
            $confirm = sqlsrv_query($con, $update, $params);
            if($confirm){ 
                $last_id = $sp_id;
                $row="
                        <td class='text-center'>".$_POST['last_no']."</td>
                        <td>" . htmlspecialchars($_POST['sp_kode_erp']) . "</td>
                        <td>" . htmlspecialchars($_POST['sp_nama_obat']) . "</td>
                        <td>" . Penomoran_helper::nilaiKeRibuanStandarPackage($_POST['sp_pakingan_utuh']) . "</td>
                        <td>" . Penomoran_helper::nilaiKeRibuanStandarPackage($_POST['sp_tinggi_pakingan']) . "</td>
                        <td>" . Penomoran_helper::nilaiKeRibuanStandarPackage($_POST['sp_bj_pakingan']) . "</td>
                        <td>" . Penomoran_helper::nilaiKeRibuanStandarPackage($_POST['sp_berat_pakingan']) . "</td>
                        <td>" . Penomoran_helper::nilaiKeRibuanStandarPackage($_POST['sp_berat_pakingan_botol_kecil']) . "</td>
                        <td>" . htmlspecialchars($_POST['sp_jenis']) . "</td>
                        <td>" . $button . "</td>
                    ";
                $response->setSuccess(true);
                $response->addMessage("Berhasil Mengubah Standar Packaging");
                $response->addMessage($row);
                $response->addMessage($last_id);
                $response->addMessage($_POST['status']);
                $response->send();
            } 
            else {
                $response->setSuccess(false);
                $response->addMessage("Gagal Mengubah Standar Packaging : ".sqlsrv_errors());
                $response->send();
            }
        }
        else if($_POST['status']=="delete_sp" && $id != 0 && intval($_POST['last_no']??0) != 0){
            $update = "DELETE TOP (1) FROM db_laborat.tbl_standar_packaging 
                WHERE id = ? ";
            $confirm=sqlsrv_query( $con, $update ,[$id]);
            if($confirm){ 
                $response->setSuccess(true);
                $response->addMessage("Berhasil Menghapus Standar Packaging");
                $response->addMessage($id);
                $response->addMessage($_POST['status']);
                $response->send();
            }
            else {
                $response->setSuccess(false);
                $response->addMessage("Gagal Menghapus Standar Packaging : ".sqlsrv_errors());
                $response->send();
            }
        }
        else if($_POST['status']=="preview_sp" && $id != 0){
            $dataSp=array();
            
            $sp = "SELECT TOP 1 * FROM db_laborat.tbl_standar_packaging s WHERE s.id = ? ";
            $spResult = sqlsrv_query($con, $sp,[$id]);
            while ($rowSP = sqlsrv_fetch_array($spResult, SQLSRV_FETCH_ASSOC)) {
                $dataSp['id']=$rowSP['id'];
                $dataSp['kode_obat']=$rowSP['kode_erp'];
                $dataSp['nama_obat']=$rowSP['nama_obat'];
                $dataSp['lot']='';
                $dataSp['ut']=$rowSP['pakingan_utuh'];
                $dataSp['tg']=$rowSP['tinggi_pakingan'];
                $dataSp['bj']=$rowSP['bj_pakingan'];
                $dataSp['bp']=$rowSP['berat_pakingan'];
                $dataSp['bk']=$rowSP['berat_pakingan_botol_kecil'];
                $dataSp['total_qty']=0;
                $dataSp['qty_dus']=0;
                $dataSp['pakingan_standar']=0;
                $dataSp['total_stock']=0;
                $dataSp['kategori']='utuhan';
                $dataSp['c']=0;
                $dataSp['total_qty_text']=0;
                $dataSp['qty_dus_text']=0;
                $dataSp['pakingan_standar_text']=0;
                $dataSp['total_stock_text']=0;
            }
            $response->setSuccess(true);
            $response->addMessage("Berhasil Menampilkan Standar Packaging");
            $response->setData($dataSp);
            $response->send();
        }
        else if($_POST['status']=="get_data_obat" && $id != 0){
            $spData=array();
            $get="select kode_erp from db_laborat.tbl_standar_packaging ";
            $getData= sqlsrv_query($con,$get);
            while ($row = sqlsrv_fetch_array($getData, SQLSRV_FETCH_ASSOC)) {
                $spData[]=$row['kode_erp'];
            }
            $nowData=array();
            $yangBelum=array();
            $query_get_data_now="SELECT trim(p.SUBCODE01)||'-'||trim(p.SUBCODE02)||'-'||trim(p.SUBCODE03) KODEOBAT, p.LONGDESCRIPTION DESC
                FROM 
                    PRODUCT p
                LEFT JOIN 
                    adstorage c on p.ABSUNIQUEID = c.UNIQUEID and c.FIELDNAME='ShowChemical' AND c.NAMEENTITYNAME ='Product'
                WHERE 
                    c.VALUEBOOLEAN = 1
                    AND P.ITEMTYPECODE ='DYC'
                    AND (
                        p.SUBCODE01 = 'C'
                        OR p.SUBCODE01 = 'D'
                        OR p.SUBCODE01 = 'R'
                        OR p.SUBCODE01 = 'E'
                        OR p.SUBCODE01 = 'N'
                        OR p.SUBCODE01 = 'P'
                    )";
            $result_now = db2_exec($conn1, $query_get_data_now);
            while($rowdb = db2_fetch_assoc($result_now)){
                $nowData[]=$rowdb["KODEOBAT"];
                if(!in_array($rowdb["KODEOBAT"],$spData)){
                    $tmp=[
                        'kode'=>$rowdb["KODEOBAT"],
                        'desc'=>$rowdb["DESC"]
                    ];
                    $yangBelum[]=$tmp;
                }
            }

            $response->setSuccess(true);
            $response->addMessage("Berhasil Get Standar Packaging");
            $response->addMessage($spData);
            $response->addMessage($nowData);
            $response->setData($yangBelum);
            $response->send();
        }
        else{
            $response->setSuccess(false);
            $response->addMessage("Error Status");
            $response->send();
        }
    }
  }
  $response->setSuccess(false);
  $response->addMessage("Tidak ada sesion");
  $response->send();
  
  