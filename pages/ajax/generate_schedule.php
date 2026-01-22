<?php
session_start();
include '../../koneksi.php';

if (!isset($_POST['schedules'])) {
    echo '<div class="alert alert-warning mt-4">Data tidak tersedia.</div>';
    exit;
}

$schedules = json_decode($_POST['schedules'], true);
$maxRows = 24;

// Bagi resep per group jadi chunk-chunk per 24 baris
$scheduleChunks = [];
foreach ($schedules as $groupName => $noReseps) {
    $scheduleChunks[$groupName] = array_chunk($noReseps, $maxRows);
}

// Ambil id/id_map untuk setiap resep yang ready
$idMap = [];
foreach ($scheduleChunks as $groupName => $chunks) {
    $idMap[$groupName] = [];
    foreach ($chunks as $chunkIndex => $chunk) {
        $idMap[$groupName][$chunkIndex] = [];
        foreach ($chunk as $no_resep) {
            $sqlFetch = "SELECT TOP 1 ps.id, ps.is_old_data, ps.is_test, ps.is_bonresep, tm.jenis_matching
                         FROM db_laborat.tbl_preliminary_schedule ps
                         LEFT JOIN db_laborat.tbl_matching tm ON 
                             CASE 
                                 WHEN LEFT(ps.no_resep, 2) = 'DR' 
                                     THEN LEFT(ps.no_resep, LEN(ps.no_resep) - 2)
                                 ELSE ps.no_resep
                             END = tm.no_resep
                         WHERE ps.no_resep = ?
                           AND ps.status = 'ready'
                           AND (ps.no_machine IS NULL OR ps.no_machine = '')
                         ORDER BY ps.id DESC";
            $stmt = sqlsrv_query($con, $sqlFetch, [$no_resep]);

            if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
                $idMap[$groupName][$chunkIndex][] = [
                    'id'         => $row['id'],
                    'is_old'     => $row['is_old_data'],
                    'is_test'    => (int)$row['is_test'],
                    'is_bonresep'=> (int)$row['is_bonresep'],
                    'matching'   => $row['jenis_matching']
                ];
                // tandai sementara
                sqlsrv_query($con, "UPDATE db_laborat.tbl_preliminary_schedule SET no_machine = 'TEMP_USED' WHERE id = ?", [$row['id']]);
            } else {
                $idMap[$groupName][$chunkIndex][] = null;
            }
        }
    }
}
?>
<div style="display: flex; gap: 10px; padding: 10px 0px;">
    <h4 style="margin-left: 5px;">Schedule Celup</h4>
    <button id="undo" class="btn btn-primary" title="undo" style="border-radius: 50%;"><i class="fa fa-undo" aria-hidden="true"></i></button>
</div>
<div class="table-responsive" style="overflow-x: auto;">
    <table class="table table-bordered table-striped align-middle text-center" id="schedule-mesin" style="table-layout: auto; width: 100%;">
        <thead class="table-dark">
            <tr>
                <th rowspan="2" style="min-width: 50px;" class="sticky-col">No</th>
                <?php foreach ($scheduleChunks as $groupName => $chunks): ?>
                    <?php
                        // Ambil keterangan dari master_suhu berdasarkan group
                        $keterangan = '';
                        $suhu = null;
                        if ($groupName === 'BON_RESEP') {
                            $machines  = [];
                            $tempGroup = 'BON RESEP';
                        } else {
                            $stmt = sqlsrv_query($con, "SELECT TOP 1 dyeing, suhu FROM db_laborat.master_suhu WHERE [group] = ?", [$groupName]);
                            $rowDS = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;
                            $dyeingValue = $rowDS['dyeing'] ?? null;
                            $suhu = $rowDS['suhu'] ?? null;

                            if ($dyeingValue == "1") {
                                $keterangan = 'POLY';
                            } elseif ($dyeingValue == "2") {
                                $keterangan = 'COTTON';
                            }

                            $machines = [];
                            if ($keterangan === 'COTTON' && $suhu == 80) {
                                $machines = ['A6', 'C1'];
                            } elseif ($keterangan) {
                                $stmtMesin = sqlsrv_query($con, "
                                    SELECT no_machine 
                                    FROM db_laborat.master_mesin 
                                    WHERE keterangan = ? AND no_machine NOT IN ('A6', 'C1')
                                ", [$keterangan]);
                                while ($rowM = sqlsrv_fetch_array($stmtMesin, SQLSRV_FETCH_ASSOC)) {
                                    $machines[] = $rowM['no_machine'];
                                }
                            }
                        }

                        // Mesin yang sedang sibuk (old data =1)
                        $excludedMachines = [];
                        $stmtExclude = sqlsrv_query($con, "
                            SELECT DISTINCT no_machine 
                            FROM db_laborat.tbl_preliminary_schedule 
                            WHERE is_old_data = 1 AND is_old_cycle = 0 AND status IN ('scheduled', 'in_progress_dispensing', 'in_progress_dyeing')
                        ");
                        while ($rowEx = sqlsrv_fetch_array($stmtExclude, SQLSRV_FETCH_ASSOC)) {
                            $excludedMachines[] = $rowEx['no_machine'];
                        }
                        $machines = array_values(array_diff($machines, $excludedMachines));

                        if ($groupName !== 'BON_RESEP') {
                            $stmtTemp = sqlsrv_query($con, "SELECT TOP 1 program, suhu, product_name FROM db_laborat.master_suhu WHERE [group] = ?", [$groupName]);
                            $rowT = $stmtTemp ? sqlsrv_fetch_array($stmtTemp, SQLSRV_FETCH_ASSOC) : null;
                            if ($rowT) {
                                if ((int)$rowT['program'] === 1) {
                                    $tempGroup = 'Constant ' . $rowT['suhu'];
                                } elseif ((int)$rowT['program'] === 2) {
                                    $tempGroup = 'Raising ' . $rowT['product_name'];
                                } else {
                                    $tempGroup = $groupName;
                                }
                            } else {
                                $tempGroup = $groupName;
                            }
                        }
                    ?>

                    <?php foreach ($chunks as $chunkIndex => $chunk): ?>
                        <th colspan="1" style="min-width: 115px;">
                            <?php if ($groupName === 'BON_RESEP'): ?>
                                <div class="form-group dropdown-container" style="display: table; margin: 0 auto;">
                                    <select class="form-control input-sm" disabled data-group="BON_RESEP">
                                        <option value="BONRESEP" selected>BON RESEP</option>
                                    </select>
                                </div>
                                <small class="text-danger">BON RESEP</small>
                            <?php else: ?>
                                <div class="form-group dropdown-container" style="display: table; margin: 0 auto;">
                                    <select class="form-control input-sm" aria-label="Pilih Mesin untuk <?= htmlspecialchars($groupName) ?>" data-group="<?= htmlspecialchars($groupName) ?>">
                                        <option value="">Pilih Mesin</option>
                                        <?php foreach ($machines as $machine): ?>
                                            <option value="<?= htmlspecialchars($machine) ?>"><?= htmlspecialchars($machine) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <small class="text-danger"><?= htmlspecialchars($tempGroup) ?></small>
                            <?php endif; ?>
                        </th>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tr>
        </thead>

        <tbody>
            <?php for ($i = 0; $i < $maxRows; $i++): ?>
                <tr>
                    <td class="sticky-col"><?= $i + 1 ?></td>
                    <?php foreach ($scheduleChunks as $groupName => $chunks): ?>
                        <?php foreach ($chunks as $chunkIndex => $chunk): ?>
                            <?php
                                $id_info     = $idMap[$groupName][$chunkIndex][$i] ?? null;
                                $id_schedule = $id_info['id'] ?? null;
                                $is_old_data = $id_info['is_old'] ?? 0;
                                $is_test     = (int)($id_info['is_test'] ?? 0);
                                $is_bonresep = (int)($id_info['is_bonresep'] ?? 0);

                                $tdStyle = $is_old_data == 1 ? 'background-color: pink;' : '';
                                $badge   = $is_test === 1 ? ' <span class="label label-warning">TEST REPORT</span>' : '';
                            ?>
                            <td style="<?= $tdStyle ?>">
                                <?php if (isset($chunk[$i])): ?>
                                    <?php $no_resep = $chunk[$i]; ?>
                                    <?php if ($id_schedule): ?>
                                        <span class="resep-item"
                                            data-id="<?= $id_schedule ?>"
                                            data-resep="<?= htmlspecialchars($no_resep) ?>"
                                            data-group="<?= htmlspecialchars($groupName) ?>"
                                            data-bonresep="<?= $is_bonresep ?>">
                                            <?= htmlspecialchars($no_resep) ?> <?= $badge ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <div class="text-center" style="margin-bottom: 20px;">
        <button id="submitForDisp" class="btn btn-primary"><i class="fa fa-save"></i> Submit For Dispensing List</button>
    </div>
</div>
<?php
// Bersihkan tanda sementara
sqlsrv_query($con, "UPDATE db_laborat.tbl_preliminary_schedule SET no_machine = NULL WHERE no_machine = 'TEMP_USED'");
