<form class="form-horizontal" method="POST" action="">
    <div class="col-lg-6">
        <div class="form-group">
            <label class="col-lg-6">Password</label>
            <input type="password" class="form-control" required autocomplete="off" name="oldpw" placeholder="Password...." aria-describedby="sizing-addon1">
        </div>
        <div class="form-group">
            <label class="col-lg-6">New-password</label>
            <input type="password" class="form-control" required autocomplete="off" name="newpw" placeholder="Password...." aria-describedby="sizing-addon1">
        </div>
        <div class="form-group">
            <label class="col-lg-6">Retype New-password</label>
            <input type="password" minlength="5" class="form-control" required autocomplete="off" name="newpw2" placeholder="Password...." aria-describedby="sizing-addon1">
        </div>
        <div class="form-group">
            <div class="col-sm-6">
                <button type="submit" minlength="5" name="submit" value="submit" class="btn btn-primary">Change!</button>
            </div>
        </div>
    </div>
</form>

<?php
ini_set("error_reporting", 1);
session_start();
include "koneksi.php";
$time = date('Y-m-d H:i:s');
$submit = $_POST['submit'] ?? '';
if ($submit == 'submit') {
    $oldpw = $_POST['oldpw'] ?? '';
    $newpw = $_POST['newpw'] ?? '';
    $newpw2 = $_POST['newpw2'] ?? '';
    if ($oldpw == ($_SESSION['passLAB'] ?? '')) {
        if ($newpw == $newpw2) {
            sqlsrv_query(
                $con,
                "UPDATE db_laborat.tbl_user SET [password] = ? WHERE id = ?",
                [$newpw, $_SESSION['id'] ?? '']
            );
            sqlsrv_query(
                $con,
                "INSERT INTO db_laborat.tbl_log ([what], [what_do], [do_by], [do_at], [ip], [os], [remark])
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$_SESSION['id'] ?? '', 'Update tbl_user', $_SESSION['userLAB'] ?? '', $time, $_SESSION['ip'] ?? '', $_SESSION['os'] ?? '', 'Change Password']
            );
            echo '<script>window.location.href = "logout"</script>';
        } else {
            echo '<script>alert("Password tidak sesuai !")</script>';
        }
    } else {
        echo '<script>alert("Password yang anda masukan salah !")</script>';
    }
}
?>
