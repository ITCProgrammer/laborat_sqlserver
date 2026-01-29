<?php
ini_set("error_reporting", 1);
session_start();
include("../koneksi.php");
$sql = sqlsrv_query($con, "SELECT * FROM db_laborat.announcement");
$announ = $sql ? sqlsrv_fetch_array($sql, SQLSRV_FETCH_ASSOC) : null;
?>
<div class="tab-content">
    <div class="tab-pane active" id="tab_1">
        <form class="form-horizontal" method="POST" action="">
            <div class="col-lg-12">
                <div class="form-group row">
                    <!-- <label class="col-lg-4">Announcement</label> -->
                    <div class="col-lg-9">
                        <textarea class="form-control" required autocomplete="off" name="announcement" id="announcement"><?php echo $announ['ann'] ?></textarea>
                    </div>
                </div>
                <div class="form-group row">
                    <!-- <label class="col-lg-4">Announcement</label> -->
                    <div class="col-lg-9">
                        <Select class="form-control" name="is_active">
                            <option <?php if ($announ['is_active'] == '1') echo 'selected' ?> value="1">Enabled</option>
                            <option <?php if ($announ['is_active'] == '0') echo 'selected' ?> value="0">Disabled</option>
                        </Select>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-6">
                        <button type="submit" name="submit" value="submit" class="btn btn-primary btn-block">Change!</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
if (isset($_POST['submit'])) {
    $ann = isset($_POST['announcement']) ? $_POST['announcement'] : '';
    $isActive = isset($_POST['is_active']) ? $_POST['is_active'] : '0';
    sqlsrv_query(
        $con,
        "UPDATE db_laborat.announcement SET is_active = ?, ann = ? WHERE id = 1",
        [$isActive, $ann]
    );
    echo '<script>window.location="index1.php?p=announcement"</script>';
}
?>
