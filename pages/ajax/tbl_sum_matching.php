<?php
ini_set("error_reporting", 1);
include "../../koneksi.php";
session_start();

if (! $con) {
    die('Koneksi SQL Server db_laborat gagal.');
}

// Helper count scalar
function count_query($sql, array $params = [])
{
    global $con;
    $stmt = sqlsrv_query($con, $sql, $params, ['Scrollable' => SQLSRV_CURSOR_KEYSET]);
    if (! $stmt) {
        return 0;
    }
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);
    return isset($row['count']) ? (int)$row['count'] : 0;
}

/////////////////// QUERY TABLE !
function Masuk($jenis_matching)
{
    $start = date('Y-m-01 23:00:00');
    $end = date('Y-m-d 23:00:00');
    return count_query(
        "SELECT COUNT(id) AS count FROM db_laborat.tbl_matching
         WHERE jenis_matching = ?
           AND tgl_buat >= ?
           AND tgl_buat <= ?",
        [$jenis_matching, $start, $end]
    );
}
function SiapBagi($jenis_matching)
{
    return count_query(
        "SELECT COUNT(a.id) AS count
         FROM db_laborat.tbl_matching a
         LEFT JOIN db_laborat.tbl_status_matching b ON a.no_resep = b.idm
         WHERE b.approve_at IS NULL AND b.status IS NULL
           AND a.status_bagi = 'siap bagi'
           AND a.jenis_matching = ?",
        [$jenis_matching]
    );
}
function SedangJalan($jenis_matching)
{
    return count_query(
        "SELECT COUNT(b.id) AS count
         FROM db_laborat.tbl_status_matching a
         JOIN db_laborat.tbl_matching b ON a.idm = b.no_resep
         WHERE a.status IN ('buka','mulai','hold','revisi','tunggu')
           AND b.jenis_matching = ?",
        [$jenis_matching]
    );
}
function WaitingApprove($jenis_matching)
{
    return count_query(
        "SELECT COUNT(b.id) AS count
         FROM db_laborat.tbl_status_matching a
         INNER JOIN db_laborat.tbl_matching b ON a.idm = b.no_resep
         WHERE a.status IN ('selesai','batal')
           AND a.approve = 'NONE'
           AND b.jenis_matching = ?",
        [$jenis_matching]
    );
}

function Delete($jenis_matching)
{
    $start = date('Y-m-01 23:00:00');
    $end = date('Y-m-d 23:00:00');
    return count_query(
        "SELECT COUNT(id) AS count FROM db_laborat.historical_delete_matching
         WHERE jenis_matching = ?
           AND delete_at >= ?
           AND delete_at <= ?",
        [$jenis_matching, $start, $end]
    );
}

function Tunggu($jenis_matching)
{
    return count_query(
        "SELECT COUNT(a.id) AS count
         FROM db_laborat.tbl_matching a
         LEFT JOIN db_laborat.tbl_status_matching b ON a.no_resep = b.idm
         WHERE b.approve_at IS NULL AND b.status IS NULL
           AND a.status_bagi = 'tunggu'
           AND a.jenis_matching = ?",
        [$jenis_matching]
    );
}
function belum_bagi($jenis_matching)
{
    return count_query(
        "SELECT COUNT(a.id) AS count
         FROM db_laborat.tbl_matching a
         LEFT JOIN db_laborat.tbl_status_matching b ON a.no_resep = b.idm
         WHERE b.approve_at IS NULL AND b.status IS NULL
           AND a.status_bagi IS NULL
           AND a.jenis_matching = ?",
        [$jenis_matching]
    );
}

function Selesai($jenis_matching)
{
    $start = date('Y-m-01 23:00:00');
    $end = date('Y-m-d 23:00:00');
    return count_query(
        "SELECT COUNT(b.id) AS count
         FROM db_laborat.tbl_status_matching a
         JOIN db_laborat.tbl_matching b ON b.no_resep = a.idm
         WHERE b.jenis_matching = ?
           AND a.approve = 'TRUE'
           AND a.approve_at >= ?
           AND a.approve_at <= ?",
        [$jenis_matching, $start, $end]
    );
}

?>
<div class="col-md-6">
    <div class="box">
        <h4 class="text-center" style="font-weight: bold;">Status Matching <br /> <?php echo date('F-Y') ?></h4>
        <table class="table table-chart">
            <thead>
                <tr>
                    <th>&nbsp;</th>
                    <th>LAB DIP</th>
                    <th>MATCHING ULG</th>
                    <th>PERBAIKAN</th>
                    <th>DEVELOPMENT</th>
                    <th>TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <tr title="Data di bagi berdasarkan jenis matching dari awal bulan sampai akhir bulan (Bulan ini)">
                    <td>Masuk (Bulan <?php echo date('F') ?>)</td>
                    <td><?php $masukLD = Masuk('L/D') + Masuk('LD NOW');
                            echo $masukLD ?></td>
                    <td><?php $masukMU = Masuk('Matching Ulang') +  Masuk('Matching Ulang NOW');
                        echo $masukMU ?></td>
                    <td><?php $masukP = Masuk('Perbaikan') + Masuk('Perbaikan NOW');
                        echo $masukP ?></td>
                    <td><?php $masukMD = Masuk('Matching Development');
                        echo $masukMD ?></td>
                    <td><?php echo $masukLD + $masukMU + $masukP + $masukMD ?></td>
                </tr>
                <tr>
                    <td>Siap Bagi</td>
                    <td class="bg-warning"><?php $sbLD = SiapBagi('L/D') + SiapBagi('LD NOW');
                                            echo $sbLD ?></td>
                    <td class="bg-success"><?php $sbMU = SiapBagi('Matching Ulang') + SiapBagi('Matching Ulang NOW');
                                            echo $sbMU ?></td>
                    <td class="bg-danger"><?php $sbP = SiapBagi('Perbaikan') + SiapBagi('Perbaikan NOW');
                                            echo $sbP ?></td>
                    <td class="bg-info"><?php $sbMD = SiapBagi('Matching Development');
                                        echo $sbMD ?></td>
                    <td><?php echo $sbLD +  $sbMU + $sbP + $sbMD ?></td>
                </tr>
                <tr>
                    <td>Sedang Jalan</td>
                    <td class="bg-warning"><?php $sjLD = SedangJalan('L/D') + SedangJalan('LD NOW');
                                            echo $sjLD ?></td>
                    <td class="bg-success"><?php $sjMU = SedangJalan('Matching Ulang') + SedangJalan('Matching Ulang NOW');
                                            echo $sjMU ?></td>
                    <td class="bg-danger"><?php $sjP = SedangJalan('Perbaikan') + SedangJalan('Perbaikan NOW');
                                            echo $sjP ?></td>
                    <td class="bg-info"><?php $sjMD = SedangJalan('Matching Development');
                                        echo $sjMD ?></td>
                    <td><?php echo $sjLD + $sjMU + $sjP + $sjMD ?> *</td>
                </tr>
                <tr>
                    <td>Waiting Approve</td>
                    <td class="bg-warning"><?php $waLD = WaitingApprove('L/D') + WaitingApprove('LD NOW');
                                            echo $waLD ?></td>
                    <td class="bg-success"><?php $waMU = WaitingApprove('Matching Ulang') + WaitingApprove('Matching Ulang NOW');
                                            echo $waMU ?></td>
                    <td class="bg-danger"><?php $waP = WaitingApprove('Perbaikan') + WaitingApprove('Perbaikan NOW');
                                            echo $waP ?></td>
                    <td class="bg-info"><?php $waMD = WaitingApprove('Matching Development');
                                        echo $waMD ?></td>
                    <td><?php echo $waLD + $waMU + $waP + $waMD ?></td>
                </tr>
                <tr>
                    <td>Tunggu (list schedule)</td>
                    <td class="bg-warning"><?php $tgLD = Tunggu('L/D') + Tunggu('LD NOW');
                                            echo $tgLD ?></td>
                    <td class="bg-success"><?php $tgMU = Tunggu('Matching Ulang') + Tunggu('Matching Ulang NOW');
                                            echo $tgMU ?></td>
                    <td class="bg-danger"><?php $tgP = Tunggu('Perbaikan') + Tunggu('Perbaikan NOW');
                                            echo $tgP ?></td>
                    <td class="bg-info"><?php $tgMD = Tunggu('Matching Development');
                                        echo $tgMD ?></td>
                    <td><?php echo $tgLD +  $tgMU + $tgP + $tgMD ?></td>
                </tr>
                <tr>
                    <td>Belum Bagi</td>
                    <td class="bg-warning"><?php $bbLD = belum_bagi('L/D') + belum_bagi('LD NOW');
                                            echo $bbLD ?></td>
                    <td class="bg-success"><?php $bbMU = belum_bagi('Matching Ulang') + belum_bagi('Matching Ulang NOW');
                                            echo $bbMU ?></td>
                    <td class="bg-danger"><?php $bbP = belum_bagi('Perbaikan') + belum_bagi('Perbaikan NOW');
                                            echo $bbP?></td>
                    <td class="bg-info"><?php $bbMD = belum_bagi('Matching Development');
                                        echo $bbMD ?></td>
                    <td><?php echo $bbLD + $bbMU + $bbP + $bbMD ?></td>
                </tr>
                <tr>
                    <td>Cancel/Delete</td>
                    <td><?php $dltLD = Delete('L/D') + Delete('LD NOW');
                        echo $dltLD ?></td>
                    <td><?php $dltMU = Delete('Matching Ulang') + Delete('Matching Ulang NOW');
                        echo $dltMU ?></td>
                    <td><?php $dltP = Delete('Perbaikan') + Delete('Perbaikan NOW');
                        echo $dltP ?></td>
                    <td><?php $dltMD = Delete('Matching Development');
                        echo $dltMD ?></td>
                    <td><?php echo $dltLD + $dltMU + $dltP + $dltMD ?></td>
                </tr>
                <tr>
                    <td>Selesai (Bulan <?php echo date('F') ?>)</td>
                    <td><?php $selesaiLD = Selesai('L/D') + Selesai('LD NOW');
                        echo $selesaiLD ?></td>
                    <td><?php $selesaiMU = Selesai('Matching Ulang') + Selesai('Matching Ulang NOW');
                        echo $selesaiMU ?></td>
                    <td><?php $selesaiP = Selesai('Perbaikan') + Selesai('Perbaikan NOW');
                        echo $selesaiP ?></td>
                    <td><?php $selesaiMD = Selesai('Matching Development');
                        echo $selesaiMD ?></td>
                    <td><?php echo $selesaiLD +  $selesaiMU + $selesaiP + $selesaiMD ?></td>
                </tr>
                <tr>
                    <td>`SISA `(Real Time)</td>
                    <td class="bg-warning"><?php $LD =  $sbLD +  $sjLD + $waLD + $tgLD + $bbLD;
                                            echo $LD; ?></td>
                    <td class="bg-success"><?php $MU =  $sbMU +  $sjMU + $waMU + $tgMU + $bbMU;
                                            echo $MU; ?></td>
                    <td class="bg-danger"><?php $P = $sbP +  $sjP + $waP + $tgP + $bbP;
                                            echo $P; ?></td>
                    <td class="bg-info"><?php $MD =  $sbMD +  $sjMD + $waMD + $tgMD + $bbMD;
                                        echo $MD; ?></td>
                    <td><?php echo $LD + $MU + $P + $MD; ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php
function MasukYesterday($jenis_matching)
{
//  $ystrdy = date('Y-m-d', strtotime("-1 days"));
//	$tody = date('Y-m-d');
	$ystrdy = date('Y-m-d', strtotime("-2 days"));
	$tody = date('Y-m-d', strtotime("-1 days"));
    return count_query(
        "SELECT COUNT(id) AS count FROM db_laborat.tbl_matching
         WHERE jenis_matching = ?
           AND tgl_buat BETWEEN ? AND ?",
        [$jenis_matching, "$ystrdy 23:00:00", "$tody 23:00:00"]
    );
}

function Selesai_Y($jenis_matching)
{
//  $ystrdy = date('Y-m-d', strtotime("-1 days"));
//	$tody = date('Y-m-d');
	$ystrdy = date('Y-m-d', strtotime("-2 days"));
	$tody = date('Y-m-d', strtotime("-1 days"));
    return count_query(
        "SELECT COUNT(b.id) AS count
         FROM db_laborat.tbl_status_matching a 
         JOIN db_laborat.tbl_matching b ON b.no_resep = a.idm
         WHERE b.jenis_matching = ?
           AND a.approve = 'TRUE'
           AND (a.koreksi_resep <> '' OR a.koreksi_resep2 <> '')
           AND a.status = 'selesai'
           AND a.approve_at BETWEEN ? AND ?",
        [$jenis_matching, "$ystrdy 23:00:00", "$tody 23:00:00"]
    );
}

function grpA($jenis_matching)
{
	$tomrw = date('Y-m-d', strtotime("+1 days"));
	$tody = date('Y-m-d');
    return count_query(
        "SELECT COUNT(a.id) AS count
         FROM db_laborat.tbl_status_matching a
         JOIN db_laborat.tbl_matching b ON a.idm = b.no_resep
         WHERE a.status IN ('buka','mulai','hold','revisi','tunggu')
           AND a.grp = 'A'
           AND b.jenis_matching = ?",
        [$jenis_matching]
    );
}
function grpB($jenis_matching)
{
	$tomrw = date('Y-m-d', strtotime("+1 days"));
	$tody = date('Y-m-d');
    return count_query(
        "SELECT COUNT(a.id) AS count
         FROM db_laborat.tbl_status_matching a
         JOIN db_laborat.tbl_matching b ON a.idm = b.no_resep
         WHERE a.status IN ('buka','mulai','hold','revisi','tunggu')
           AND a.grp = 'B'
           AND b.jenis_matching = ?",
        [$jenis_matching]
    );
}
function grpC($jenis_matching)
{
	$tomrw = date('Y-m-d', strtotime("+1 days"));
	$tody = date('Y-m-d');
    return count_query(
        "SELECT COUNT(a.id) AS count
         FROM db_laborat.tbl_status_matching a
         JOIN db_laborat.tbl_matching b ON a.idm = b.no_resep
         WHERE a.status IN ('buka','mulai','hold','revisi','tunggu')
           AND a.grp = 'C'
           AND b.jenis_matching = ?",
        [$jenis_matching]
    );
}
function grpD($jenis_matching)
{
	$tomrw = date('Y-m-d', strtotime("+1 days"));
	$tody = date('Y-m-d');
    return count_query(
        "SELECT COUNT(a.id) AS count
         FROM db_laborat.tbl_status_matching a
         JOIN db_laborat.tbl_matching b ON a.idm = b.no_resep
         WHERE a.status IN ('buka','mulai','hold','revisi','tunggu')
           AND a.grp = 'D'
           AND b.jenis_matching = ?",
        [$jenis_matching]
    );
}
function grpE($jenis_matching)
{
	$tomrw = date('Y-m-d', strtotime("+1 days"));
	$tody = date('Y-m-d');
    return count_query(
        "SELECT COUNT(a.id) AS count
         FROM db_laborat.tbl_status_matching a
         JOIN db_laborat.tbl_matching b ON a.idm = b.no_resep
         WHERE a.status IN ('buka','mulai','hold','revisi','tunggu')
           AND a.grp = 'E'
           AND b.jenis_matching = ?",
        [$jenis_matching]
    );
}
function grpF($jenis_matching)
{
	$tomrw = date('Y-m-d', strtotime("+1 days"));
	$tody = date('Y-m-d');
    return count_query(
        "SELECT COUNT(a.id) AS count
         FROM db_laborat.tbl_status_matching a
         JOIN db_laborat.tbl_matching b ON a.idm = b.no_resep
         WHERE a.status IN ('buka','mulai','hold','revisi','tunggu')
           AND a.grp = 'F'
           AND b.jenis_matching = ?",
        [$jenis_matching]
    );
}
function SA($jenis_matching)
{
	$tomrw = date('Y-m-d', strtotime("+1 days"));
	$tody = date('Y-m-d');
    return count_query(
        "SELECT COUNT(a.id) AS count
         FROM db_laborat.tbl_status_matching a
         JOIN db_laborat.tbl_matching b ON a.idm = b.no_resep
         WHERE a.status IN ('buka','mulai','hold','revisi','tunggu')
           AND a.grp = 'SA'
           AND b.jenis_matching = ?",
        [$jenis_matching]
    );
}
function SB($jenis_matching)
{
	$tomrw = date('Y-m-d', strtotime("+1 days"));
	$tody = date('Y-m-d');
    return count_query(
        "SELECT COUNT(a.id) AS count
         FROM db_laborat.tbl_status_matching a
         JOIN db_laborat.tbl_matching b ON a.idm = b.no_resep
         WHERE a.status IN ('buka','mulai','hold','revisi','tunggu')
           AND a.grp = 'SB'
           AND b.jenis_matching = ?",
        [$jenis_matching]
    );
}
function SC($jenis_matching)
{
	$tomrw = date('Y-m-d', strtotime("+1 days"));
	$tody = date('Y-m-d');
    return count_query(
        "SELECT COUNT(a.id) AS count
         FROM db_laborat.tbl_status_matching a
         JOIN db_laborat.tbl_matching b ON a.idm = b.no_resep
         WHERE a.status IN ('buka','mulai','hold','revisi','tunggu')
           AND a.grp = 'SC'
           AND b.jenis_matching = ?",
        [$jenis_matching]
    );
}
?>
<div class="col-md-6">
    <div class="box">
        <h4 class="text-center" style="font-weight: bold;">KM Sedang Jalan <?php echo date('Y-m-d') ?><br />Real Time</h4>
        <table class="table table-chart">
            <thead>
                <tr>
                    <th>&nbsp;</th>
                    <th style="font-size: small;">Lab-Dip</th>
                    <th style="font-size: small;">Match Ulg</th>
                    <th style="font-size: small;">Perbaikan</th>
                    <th style="font-size: small;">Development</th>
                    <th style="font-size: small;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-orange">
                    <td>MASUK (H-1/Kemarin)</td>
                    <td><?php $myLD = MasukYesterday('L/D') + MasukYesterday('LD NOW');
                        echo $myLD ?></td>
                    <td><?php $myMU = MasukYesterday('Matching Ulang') + MasukYesterday('Matching Ulang NOW');
                        echo $myMU ?></td>
                    <td><?php $myP = MasukYesterday('Perbaikan') + MasukYesterday('Perbaikan NOW');
                        echo $myP ?></td>
                    <td><?php $myMD = MasukYesterday('Matching Development');
                        echo $myMD ?></td>
                    <td><?php $my = $myLD +  $myMU + $myP + $myMD;
                        echo  $my; ?></td>
                </tr>
                <tr class="bg-orange">
                    <td>SELESAI (H-1/Kemarin)</td>
                    <td><?php $syLD = Selesai_Y('L/D') + Selesai_Y('LD NOW');
                        echo $syLD ?></td>
                    <td><?php $syMU = Selesai_Y('Matching Ulang') + Selesai_Y('Matching Ulang NOW');
                        echo $syMU ?></td>
                    <td><?php $syP = Selesai_Y('Perbaikan') + Selesai_Y('Perbaikan NOW');
                        echo $syP ?></td>
                    <td><?php $syMD = Selesai_Y('Matching Development');
                        echo $syMD ?></td>
                    <td><?php $sy = $syLD +  $syMU + $syP + $syMD;
                        echo  $sy; ?></td>
                </tr>
                <tr>
                    <td>Group A</td>
                    <td><?php $gaLD = grpA('L/D') + grpA('LD NOW');
                        echo $gaLD ?></td>
                    <td><?php $gaMU = grpA('Matching Ulang') + grpA('Matching Ulang NOW');
                        echo $gaMU ?></td>
                    <td><?php $gaP = grpA('Perbaikan') + grpA('Perbaikan NOW');
                        echo $gaP ?></td>
                    <td><?php $gaMD = grpA('Matching Development');
                        echo $gaMD ?></td>
                    <td class="bg-purple"><?php $ga = $gaLD +  $gaMU + $gaP + $gaMD;
                                            echo  $ga; ?></td>
                </tr>
                <tr>
                    <td>Group B</td>
                    <td><?php $gbLD = grpB('L/D') + grpB('LD NOW');
                        echo $gbLD ?></td>
                    <td><?php $gbMU = grpB('Matching Ulang') + grpB('Matching Ulang NOW');
                        echo $gbMU ?></td>
                    <td><?php $gbP = grpB('Perbaikan') + grpB('Perbaikan NOW');
                        echo $gbP ?></td>
                    <td><?php $gbMD = grpB('Matching Development');
                        echo $gbMD ?></td>
                    <td class="bg-purple"><?php $gb = $gbLD +  $gbMU + $gbP + $gbMD;
                                            echo  $gb; ?></td>
                </tr>
                <tr>
                    <td>Group C</td>
                    <td><?php $gcLD = grpC('L/D') + grpC('LD NOW');
                        echo $gcLD ?></td>
                    <td><?php $gcMU = grpC('Matching Ulang') + grpC('Matching Ulang NOW');
                        echo $gcMU ?></td>
                    <td><?php $gcP = grpC('Perbaikan') + grpC('Perbaikan NOW');
                        echo $gcP ?></td>
                    <td><?php $gcMD = grpC('Matching Development');
                        echo $gcMD ?></td>
                    <td class="bg-purple"><?php $gc = $gcLD +  $gcMU + $gcP + $gcMD;
                                            echo  $gc; ?></td>
                </tr>
                <tr>
                    <td>Group D</td>
                    <td><?php $gdLD = grpD('L/D') + grpD('LD NOW');
                        echo $gdLD ?></td>
                    <td><?php $gdMU = grpD('Matching Ulang') + grpD('Matching Ulang NOW');
                        echo $gdMU ?></td>
                    <td><?php $gdP = grpD('Perbaikan') + grpD('Perbaikan NOW');
                        echo $gdP ?></td>
                    <td><?php $gdMD = grpD('Matching Development');
                        echo $gdMD ?></td>
                    <td class="bg-purple"><?php $gd = $gdLD +  $gdMU + $gdP + $gdMD;
                                            echo  $gd; ?></td>
                </tr>
                <tr>
                    <td>Group E</td>
                    <td><?php $geLD = grpE('L/D') + grpE('LD NOW');
                        echo $geLD ?></td>
                    <td><?php $geMU = grpE('Matching Ulang') + grpE('Matching Ulang NOW');
                        echo $geMU ?></td>
                    <td><?php $geP = grpE('Perbaikan') + grpE('Perbaikan NOW');
                        echo $geP ?></td>
                    <td><?php $geMD = grpE('Matching Development');
                        echo $geMD ?></td>
                    <td class="bg-purple"><?php $ge = $geLD +  $geMU + $geP + $geMD;
                                            echo  $ge; ?></td>
                </tr>
                <tr>
                    <td>Group F</td>
                    <td><?php $gfLD = grpF('L/D') + grpF('LD NOW') ;
                        echo $gfLD ?></td>
                    <td><?php $gfMU = grpF('Matching Ulang') + grpF('Matching Ulang NOW');
                        echo $gfMU ?></td>
                    <td><?php $gfP = grpF('Perbaikan') + grpF('Perbaikan NOW');
                        echo $gfP ?></td>
                    <td><?php $gfMD = grpF('Matching Development');
                        echo $gfMD ?></td>
                    <td class="bg-purple"><?php $gf = $gfLD +  $gfMU + $gfP + $gfMD;
                                            echo  $gf; ?></td>
                </tr>
                <tr>
                    <td>SHIFT A</td>
                    <td><?php $SA_LD = SA('L/D') + SA('LD NOW') ;
                        echo $SA_LD ?></td>
                    <td><?php $SA_MU = SA('Matching Ulang') + SA('Matching Ulang NOW');
                        echo $SA_MU ?></td>
                    <td><?php $SA_P = SA('Perbaikan') + SA('Perbaikan NOW');
                        echo $SA_P ?></td>
                    <td><?php $SA_MD = SA('Matching Development');
                        echo $SA_MD ?></td>
                    <td class="bg-purple"><?php $SA = $SA_LD +  $SA_MU + $SA_P + $SA_MD;
                                            echo  $SA; ?></td>
                </tr>
                <tr>
                    <td>SHIFT B</td>
                    <td><?php $SB_LD = SB('L/D') + SB('LD NOW') ;
                        echo $SB_LD ?></td>
                    <td><?php $SB_MU = SB('Matching Ulang') + SB('Matching Ulang NOW');
                        echo $SB_MU ?></td>
                    <td><?php $SB_P = SB('Perbaikan') + SB('Perbaikan NOW');
                        echo $SB_P ?></td>
                    <td><?php $SB_MD = SB('Matching Development');
                        echo $SB_MD ?></td>
                    <td class="bg-purple"><?php $SB = $SB_LD +  $SB_MU + $SB_P + $SB_MD;
                                            echo  $SB; ?></td>
                </tr>
                <tr>
                    <td>SHIFT C</td>
                    <td><?php $SC_LD = SC('L/D') + SC('LD NOW') ;
                        echo $SC_LD ?></td>
                    <td><?php $SC_MU = SC('Matching Ulang') + SC('Matching Ulang NOW');
                        echo $SC_MU ?></td>
                    <td><?php $SC_P = SC('Perbaikan') + SC('Perbaikan NOW');
                        echo $SC_P ?></td>
                    <td><?php $SC_MD = SC('Matching Development');
                        echo $SC_MD ?></td>
                    <td class="bg-purple"><?php $SC = $SC_LD +  $SC_MU + $SC_P + $SC_MD;
                                            echo  $SC; ?></td>
                </tr>
                <tr>
                    <td>SUB TOTAL</td>
                    <td class="text-center"><?php echo $gaLD + $gbLD + $gcLD + $gdLD + $geLD + $gfLD + $SA_LD + $SB_LD + $SC_LD; ?></td>
                    <td class="text-center"><?php echo $gaMU + $gbMU + $gcMU + $gdMU + $geMU + $gfMU + $SA_MU + $SB_MU + $SC_MU; ?></td>
                    <td class="text-center"><?php echo $gaP + $gbP + $gcP + $gdP + $geP + $gfP + $SA_P + $SB_P + $SC_P; ?></td>
                    <td class="text-center"><?php echo $gaMD + $gbMD + $gcMD + $gdMD + $geMD + $gfMD + $SA_MD + $SB_MD + $SC_MD; ?></td>
                    <td class="bg-purple"><?php echo $ga + $gb + $gc + $gd + $ge + $gf + $SA + $SB + $SC; ?> *</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
