<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Form Testing</title>
</head>

<body>
    <?php
    ini_set("error_reporting", 1);
    session_start();
    include "../koneksi.php";

    ?>
    <?php
    if (isset($_POST['simpan'])) {
        function get_client_ip()
        {
            $ipaddress = '';
            if (isset($_SERVER['HTTP_CLIENT_IP']))
                $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
            else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
                $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
            else if (isset($_SERVER['HTTP_X_FORWARDED']))
                $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
            else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
                $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
            else if (isset($_SERVER['HTTP_FORWARDED']))
                $ipaddress = $_SERVER['HTTP_FORWARDED'];
            else if (isset($_SERVER['REMOTE_ADDR']))
                $ipaddress = $_SERVER['REMOTE_ADDR'];
            else
                $ipaddress = 'UNKNOWN';
            return $ipaddress;
        }

        $ip_num = get_client_ip();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $no_counter = $_POST['no_resep'];
        $buyer = $_POST['buyer'];
        $nowarna = $_POST['nowarna'];
        $warna = $_POST['warna'];
        $item = $_POST['noitem'];
        $kain = $_POST['jenis_kain'];
        $nama = $_POST['nama'];
        $sts = $_POST['sts'];
        $cck_warna = $_POST['cck_warna'];
        $note_lab = $_POST['note_lab'];
        $userLAB = $_SESSION['userLAB'] ?? '';

        $chkc = '';
        if (!empty($_POST['colorfastness']) && is_array($_POST['colorfastness'])) {
            $chkc = implode(',', $_POST['colorfastness']);
        }

        sqlsrv_begin_transaction($con);

        $success = true;

        $query = "UPDATE db_laborat.tbl_test_qc SET 
              buyer = ?,
              no_warna = ?,
              warna = ?,
              no_item = ?,
              jenis_kain = ?,
              nama_personil_test = ?,
              sts = ?,
              cocok_warna = ?,
              note_laborat = ?,
              permintaan_testing = ?
              WHERE id = ?";

        $qry_update = sqlsrv_query($con, $query, [
            $buyer,
            $nowarna,
            $warna,
            $item,
            $kain,
            $nama,
            $sts,
            $cck_warna,
            $note_lab,
            $chkc,
            $id
        ]);

        if (!$qry_update) {
            $success = false;
        }


        $qry_log = sqlsrv_query(
            $con,
            "INSERT INTO db_laborat.log_qc_test (no_counter, status, info, do_by, do_at, ip_address)
             VALUES (?, 'Open', ?, ?, GETDATE(), ?)",
            [$no_counter, "Perubahan data untuk $no_counter", $userLAB, $ip_num]
        );

        if (!$qry_log) {
            $success = false;
        }


        if ($success) {
            sqlsrv_commit($con);

            echo "<script>Swal.fire({
            title: 'Sukses',
            text: 'Data berhasil diubah.',
            type: 'success',
            }).then((result) => {
            if (result.value) {
            window.location='index1.php?p=TestQCFinal';
            }
            });</script>";
        } else {
            sqlsrv_rollback($con);

            echo "<script>Swal.fire({
            title: 'Gagal',
            text: 'Data gagal diubah.',
            type: 'error',
            }).then((result) => {
            if (result.value) {
            window.location='index1.php?p=TestQCFinal';
            }
            });</script>";
        }
    }

    ?>


    <div class="row">
        <div class="col-md-12">
            <!-- Custom Tabs -->
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#tab_1" data-toggle="tab">Input Order</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="tab_1">
                        <?php
                        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

                        $sql = sqlsrv_query($con, "SELECT * FROM db_laborat.tbl_test_qc WHERE id = ?", [$id]);
                        $data = $sql ? sqlsrv_fetch_array($sql, SQLSRV_FETCH_ASSOC) : null;
                        if ($data) {

                            $jenis_testing = $data['jenis_testing'];
                            $no_counter = $data['no_counter'];
                            $suffix = $data['suffix'];
                            $treatment = $data['treatment'];
                            $buyer = $data['buyer'];
                            $no_warna = $data['no_warna'];
                            $warna = $data['warna'];
                            $no_item = $data['no_item'];
                            $jenis_kain = $data['jenis_kain'];
                            $nama_personil_test = $data['nama_personil_test'];
                            $sts = $data['sts'];
                            $cck_warna = $data['cocok_warna'];
                            $note_lab = $data['note_laborat'];

                            $permintaan_testing = $data['permintaan_testing'];
                            $detail2 = explode(",", $permintaan_testing);

                        ?>
                            <form class="form-horizontal" action="" method="post" enctype="multipart/form-data" name="form1">
                                <div class="box-body">
                                    <div class="form-group">
                                        <label for="order" class="col-sm-2 control-label">Jenis Testing</label>
                                        <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#staticBackdrop">
                                            ...
                                        </button>
                                        <div class="col-sm-2">
                                            <select disabled value="<?php echo $jenis_testing; ?>" type="text" class="form-control select2" id="Dyestuff" name="Dyestuff" required>
                                                <?php
                                                $sqlmstrcd = sqlsrv_query($con, "SELECT kode, value FROM db_laborat.tbl_mstrjnstesting ORDER BY kode ASC;");
                                                while ($sqlmstrcd && ($li = sqlsrv_fetch_array($sqlmstrcd, SQLSRV_FETCH_ASSOC))) { ?>
                                                    <option value="<?php echo $li['value'] ?>" <?php if ($li['value'] == $jenis_testing) {
                                                                                                    echo 'selected';
                                                                                                } ?>><?php echo $li['kode'] ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- <input type="hidden" value="<?php echo $nourut; ?>" id="shadow_no_resep" name="shadow_no_resep"> -->
                                    <div class=" form-group">
                                        <label for="no_resep" class="col-sm-2 control-label">Counter</label>
                                        <div class="col-sm-2">
                                            <input name="no_resep" type="text" class="form-control" id="no_resep" value="<?php echo $no_counter; ?>" required readonly>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="suffix" class="col-sm-2 control-label">Suffix</label>
                                        <div class="col-sm-4">
                                            <input name="suffix" type="text" class="form-control suffixcuy" id="order" value="<?php echo $suffix; ?>" required readonly>
                                        </div>
                                    </div>
                                    <div class=" form-group">
                                        <label for="jen_matching" class="col-sm-2 control-label">Treatment</label>
                                        <div class="col-sm-3">
                                            <input name="jen_matching" type="text" class="form-control suffixcuy" id="jen_matching" value="<?php echo $treatment; ?>" required readonly>
                                        </div>
                                    </div>
                                    <!--/////////////////////////////////////////////////////////////// inputanTest -->
                                    <div class="form-group">
                                        <label for="buyer" class="col-sm-2 control-label">Buyer</label>
                                        <div class="col-sm-8">
                                            <input name="buyer" type="text" class="form-control" id="buyer" placeholder="buyer" readonly value="<?php echo $buyer; ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="nowarna" class="col-sm-2 control-label">No Warna</label>
                                        <div class="col-sm-6">
                                            <input name="nowarna" type="text" class="form-control" id="nowarna" placeholder="No Warna" value="<?php echo $no_warna; ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="warna" class="col-sm-2 control-label">Nama Warna</label>
                                        <div class="col-sm-6">
                                            <input name="warna" type="text" class="form-control" id="warna" placeholder="Nama Warna" value="<?php echo $warna; ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="noitem" class="col-sm-2 control-label">Item</label>
                                        <div class="col-sm-6">
                                            <input name="noitem" type="text" class="form-control" id="noitem" placeholder="No Item" value="<?php echo $no_item; ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="jenis_kain" class="col-sm-2 control-label">Jenis Kain</label>
                                        <div class="col-sm-8">
                                            <input name="jenis_kain" type="text" class="form-control" id="jenis_kain" placeholder="Jenis Kain" value="<?php echo $jenis_kain; ?>">
                                        </div>
                                    </div>
                                     <div class="form-group">
                                        <label for="cck_warna" class="col-sm-2 control-label">Cocok Warna</label>
                                        <div class="col-sm-6">
                                            <input name="cck_warna" type="text" class="form-control" id="cck_warna" placeholder="Cocok Warna" value="<?php echo $cck_warna; ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="nama" class="col-sm-2 control-label">Nama Personil Testing</label>
                                        <div class="col-sm-6">
                                            <input name="nama" type="text" class="form-control" id="nama" placeholder="nama" value="<?php echo $nama_personil_test; ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="permintaan_testing" class="col-sm-2 control-label">Permintaan Testing</label>
                                        <div class="col-sm-2">
                                            <label><input type="checkbox" class="minimal" name="colorfastness[]" value="WASHING" <?php if (in_array("WASHING", $detail2)) {
                                                                                                                                        echo "checked";
                                                                                                                                    } ?>> Washing Fastness
                                            </label>
                                            <br>
                                            <label><input type="checkbox" class="minimal" name="colorfastness[]" value="PERSPIRATION ACID" <?php if (in_array("PERSPIRATION ACID", $detail2)) {
                                                                                                                                                echo "checked";
                                                                                                                                            } ?>> Perpiration Fastness ACID
                                            </label>
                                            <br>
                                            <label><input type="checkbox" class="minimal" name="colorfastness[]" value="PERSPIRATION ALKALINE" <?php if (in_array("PERSPIRATION ACID", $detail2)) {
                                                                                                                                                    echo "checked";
                                                                                                                                                } ?>> Perpiration Fastness ALKALINE
                                            </label>
                                            <br>
                                            <label><input type="checkbox" class="minimal" name="colorfastness[]" value="WATER" <?php if (in_array("WATER", $detail2)) {
                                                                                                                                    echo "checked";
                                                                                                                                } ?>> Water Fastness
                                            </label>

                                            <br>
                                            <label><input type="checkbox" class="minimal" name="colorfastness[]" value="CROCKING" <?php if (in_array("CROCKING", $detail2)) {
                                                                                                                                        echo "checked";
                                                                                                                                    } ?>> Crocking Fastness
                                            </label>
                                            <br>
                                            <label><input type="checkbox" class="minimal" name="colorfastness[]" value="COLOR MIGRATION-OVEN TEST" <?php if (in_array("COLOR MIGRATION-OVEN TEST", $detail2)) {
                                                                                                                                                        echo "checked";
                                                                                                                                                    } ?>> Color Migration - Oven Test
                                            </label>
                                            <br>
                                            <label><input type="checkbox" class="minimal" name="colorfastness[]" value="COLOR MIGRATION" <?php if (in_array("COLOR MIGRATION", $detail2)) {
                                                                                                                                                echo "checked";
                                                                                                                                            } ?>> Color Migration Fastness
                                                <br>
                                                <label><input type="checkbox" class="minimal" name="colorfastness[]" value="CHLORIN & NON-CHLORIN" <?php if (in_array("CHLORIN & NON-CHLORIN", $detail2)) {
                                                                                                                                                        echo "checked";
                                                                                                                                                    } ?>> Chlorin &amp; Non-Chlorin
                                                </label>
                                                <br>
                                                <label><input type="checkbox" class="minimal" name="colorfastness[]" value="BLEEDING <?php if (in_array("BLEEDING", $detail2)) {
                                                                                                                                            echo "checked";
                                                                                                                                        } ?>"> Bleeding
                                                </label>
                                                <br>
                                                <label><input type="checkbox" class="minimal" name="colorfastness[]" value="PHENOLIC YELLOWING" <?php if (in_array("PHENOLIC YELLOWING", $detail2)) {
                                                                                                                                                    echo "checked";
                                                                                                                                                } ?>> Phenolic Yellowing
                                                </label>

                                        </div>
                                        <div class="col-sm-2">
                                            <label><input type="checkbox" class="minimal" name="colorfastness[]" value="LIGHT" <?php if (in_array("LIGHT", $detail2)) {
                                                                                                                                    echo "checked";
                                                                                                                                } ?>> Light Fastness
                                            </label> <br>
                                            <label><input type="checkbox" class="minimal" name="colorfastness[]" value="LIGHT PERSPIRATION" <?php if (in_array("LIGHT PERSPIRATION", $detail2)) {
                                                                                                                                                echo "checked";
                                                                                                                                            } ?>> Light Perspiration
                                            </label> <br>
                                            <label><input type="checkbox" class="minimal" name="colorfastness[]" value="PH" <?php if (in_array("PH", $detail2)) {
                                                                                                                                echo "checked";
                                                                                                                            } ?>> PH3 &amp; PH4
                                            </label> <br>
                                            <label><input type="checkbox" class="minimal" name="colorfastness[]" value="SUHU" <?php if (in_array("SUHU", $detail2)) {
                                                                                                                                    echo "checked";
                                                                                                                                } ?>> SUHU 30'C &amp; 40'C
                                            </label> <br>
                                            <label><input type="checkbox" class="minimal" name="colorfastness[]" value="APPEARANCE AFTER WASH" <?php if (in_array("APPEARANCE AFTER WASH", $detail2)) {
                                                                                                                                    echo "checked";
                                                                                                                                } ?>> Appearance After Wash
                                            </label> <br>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="sts" class="col-sm-2 control-label"></label>
                                        <div class="col-sm-6">
                                            <a href="#" class="btn btn-xs btn-danger" onclick="uncheckAll()">Full Test</a>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="sts" class="col-sm-2 control-label">Status</label>
                                        <div class="col-sm-6">
                                            <select class="form-control select2" id="sts" name="sts" required>
                                                <option value="normal" <?php echo ($sts == 'normal') ? 'selected' : ''; ?>>Normal</option>
                                                <option value="urgent" <?php echo ($sts == 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                                                <option value="request" <?php echo ($sts == 'request') ? 'selected' : ''; ?>>Request</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="note_lab" class="col-sm-2 control-label">Note Lab</label>
                                        <div class="col-sm-6">
                                            <input name="note_lab" type="text" class="form-control" id="note_lab" placeholder="Note Lab" value="<?php echo $note_lab; ?>">
                                        </div>
                                    </div>
                                    <div class="box-footer">
                                        <div class="col-sm-2">
                                            <button type="submit" class="btn btn-block btn-social btn-linkedin" name="simpan" style="width: 80%">Simpan Perubahan <i class="fa fa-save"></i></button>
                                        </div>
                                    </div>

                                </div>
                            </form>

                        <?php
                        } else {
                            echo "Data tidak ditemukan.";
                        }
                        ?>
                        <!-- /.box-body -->
                    </div>
                    <!-- /.tab-pane -->

                </div>
                <!-- /.tab-content -->
            </div>
            <!-- nav-tabs-custom -->
        </div>
        <!-- /.col -->
    </div>
</body>
<!-- Modal -->
<div class="modal fade" id="staticBackdrop" data-backdrop="static" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-center" id="staticBackdropLabel">Rincian Kode</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="container-fluid bg-light">
                    <table id="tablee" class="display compact nowrap" style="width:100%">
                        <thead>
                            <th>No.</th>
                            <th>Kode</th>
                            <th class="text-center">Keterangan</th>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            $sqlmstrcd = sqlsrv_query($con, "SELECT kode, keterangan FROM db_laborat.tbl_mstrjnstesting;");
                            while ($sqlmstrcd && ($title = sqlsrv_fetch_array($sqlmstrcd, SQLSRV_FETCH_ASSOC))) {
                                echo '<tr><td>' . $i++ . '.</td>
									<td>' . $title['kode'] . '</td>
									<td>' . $title['keterangan'] . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-info" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<!-- end modal -->

<div style="display: none;" id="hidding-choice">
</div>




<script>
    function uncheckAll() {
        var checkboxes = document.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = false;
        });
    }
    $(document).ready(function() {
        // $('.datepicker').datepicker({
        //     autoclose: true,
        //     format: 'yyyy-mm-dd',
        //     todayHighlight: true,
        // })

        // if ($('.form-control.suffixcuy').val().length >= 2) {


        //     $("#echoing_the_choice").children(":first").appendTo('#hidding-choice');
        //     $('#inputanTest').appendTo('#echoing_the_choice');
        //     $("#inputanTest").show()

        // }

        // let antrian = $('#shadow_no_resep').val();
        // var no_resep_fix = antrian + $(this).find(":selected").val();
        // $('#no_resep').val(no_resep_fix);

        // $('#Dyestuff').change(function() {
        //     var Q = $('#shadow_no_resep').val();
        //     var no_resep_fix = Q + $(this).find(":selected").val();
        //     $('#no_resep').val(no_resep_fix);
        // })

        // $('#jen_matching').change(function() {
        //     if ($(this).find(":selected").val() != '') {
        //         $("#echoing_the_choice").children(":first").appendTo('#hidding-choice');
        //         $('#inputanTest').appendTo('#echoing_the_choice');
        //         $("#inputanTest").show()
        //     }
        // })

    });
</script>

</html>
