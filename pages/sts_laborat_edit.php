<?php
ini_set("error_reporting", 1);
session_start();
include("../koneksi.php");
$modal_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$modal = sqlsrv_query($con, "SELECT * FROM db_laborat.tbl_test_qc WHERE id = ?", [$modal_id]);
while ($modal && ($r = sqlsrv_fetch_array($modal, SQLSRV_FETCH_ASSOC))) {
?>
  <div class="modal-dialog ">
    <div class="modal-content">
      <form class="form-horizontal" name="modal_popup" data-toggle="validator" method="post" action="?p=edit_sts_laborat" enctype="multipart/form-data">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title">Approval Laborat</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" id="id" name="id" value="<?php echo $r['id']; ?>">
          <input type="hidden" id="no_counter" name="no_counter" value="<?php echo $r['no_counter']; ?>">
          <input type="hidden" id="sts_laborat" name="sts_laborat" value="<?php echo $r['sts_laborat']; ?>">
          <?php if ($r['sts_laborat'] == "Waiting Approval Full") {
            echo "Approved Full";
          } else if ($r['sts_laborat'] == "Waiting Approval Parsial") {
            echo "Approved Parsial ?";
          } ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">OK</button>
        </div>
      </form>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
<?php } ?>
