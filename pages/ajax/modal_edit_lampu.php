<?php
ini_set("error_reporting", 1);
include '../../koneksi.php';
$buyer = $_GET['id'];
$sqlFlag1 = sqlsrv_query($con,"SELECT TOP 1 lampu from db_laborat.vpot_lampbuy where buyer = ? and flag = 1", [$buyer]);
$sqlFlag2 = sqlsrv_query($con,"SELECT TOP 1 lampu from db_laborat.vpot_lampbuy where buyer = ? and flag = 2", [$buyer]);
$sqlFlag3 = sqlsrv_query($con,"SELECT TOP 1 lampu from db_laborat.vpot_lampbuy where buyer = ? and flag = 3", [$buyer]);

$flag1 = sqlsrv_fetch_array($sqlFlag1, SQLSRV_FETCH_ASSOC);
$flag2 = sqlsrv_fetch_array($sqlFlag2, SQLSRV_FETCH_ASSOC);
$flag3 = sqlsrv_fetch_array($sqlFlag3, SQLSRV_FETCH_ASSOC);
?>
<div class="modal-content">
    <form class="form-horizontal" name="modal_popup" data-toggle="validator" method="post" action="?p=update_Vpot_lampu" enctype="multipart/form-data">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">Data Lampu</h4>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="code" class="col-md-3 control-label">Buyer</label>
                <div class="col-md-6">
                    <input type="text" readonly class="form-control" id="Buyer" name="Buyer" required value="<?php echo $buyer ?>">
                    <span class="help-block with-errors"></span>
                </div>
            </div>
            <div class="form-group">
                <label for="Product_name" class="col-md-3 control-label">1. Lampu</label>
                <div class="col-md-6">
                    <?php $sqlLampu = sqlsrv_query($con,"SELECT nama_lampu from db_laborat.master_lampu"); ?>
                    <select style="width:300px" class="form-control selectLampu" name="lampu1">
                        <?php if (empty($flag1['lampu'])) { ?>
                            <option value="" selected disabled>pilih..</option>
                        <?php } else { ?>
                            <option selected value="<?php echo $flag1['lampu'] ?>" selected><?php echo $flag1['lampu'] ?></option>
                        <?php } ?>
                        <?php while ($lampu = sqlsrv_fetch_array($sqlLampu, SQLSRV_FETCH_ASSOC)) { ?>
                            <option value="<?php echo $lampu['nama_lampu'] ?>"><?php echo $lampu['nama_lampu'] ?></option>
                        <?php }  ?>
                    </select>
                    <span class="help-block with-errors"></span>
                </div>
            </div>
            <div class="form-group">
                <label for="Product_name" class="col-md-3 control-label">2. Lampu</label>
                <div class="col-md-6">
                    <select style="width:300px" class="form-control selectLampu" name="lampu2">
                        <?php if (empty($flag2['lampu'])) { ?>
                            <option value="" selected disabled>pilih..</option>
                        <?php } else { ?>
                            <option selected value="<?php echo $flag2['lampu'] ?>" selected><?php echo $flag2['lampu'] ?></option>
                        <?php } ?>
                        <?php $sqlLampu = sqlsrv_query($con,"SELECT nama_lampu from db_laborat.master_lampu"); ?>
                        <?php while ($lampu = sqlsrv_fetch_array($sqlLampu, SQLSRV_FETCH_ASSOC)) { ?>
                            <option value="<?php echo $lampu['nama_lampu'] ?>"><?php echo $lampu['nama_lampu'] ?></option>
                        <?php }  ?>
                    </select>
                    <span class="help-block with-errors"></span>
                </div>
            </div>
            <div class="form-group">
                <label for="Product_name" class="col-md-3 control-label">3. Lampu</label>
                <div class="col-md-6">
                    <select style="width:300px" class="form-control selectLampu" name="lampu3">
                        <?php if (empty($flag3['lampu'])) { ?>
                            <option value="" selected disabled>pilih..</option>
                        <?php } else { ?>
                            <option selected value="<?php echo $flag3['lampu'] ?>" selected><?php echo $flag3['lampu'] ?></option>
                        <?php } ?>
                        <?php $sqlLampu = sqlsrv_query($con,"SELECT nama_lampu from db_laborat.master_lampu"); ?>
                        <?php while ($lampu = sqlsrv_fetch_array($sqlLampu, SQLSRV_FETCH_ASSOC)) { ?>
                            <option value="<?php echo $lampu['nama_lampu'] ?>"><?php echo $lampu['nama_lampu'] ?></option>
                        <?php }  ?>
                    </select>
                    <span class="help-block with-errors"></span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
