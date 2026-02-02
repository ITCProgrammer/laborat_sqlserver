<!-- Editable table -->
<?php
ini_set("error_reporting", 1);
include '../../koneksi.php';
$id_status = $_GET['id'];

$sql_Time = sqlsrv_query(
    $con,
    "SELECT kode, time_1, time_2, time_3, time_4, time_5, time_6,
            time_7, time_8, time_9, time_10, doby1, doby2, doby3, doby4, doby5, doby6, doby7, doby8, doby9, doby10, inserted_by, last_edit_by
     FROM db_laborat.tbl_matching_detail
     WHERE id_status = ?
     ORDER BY flag",
    [$id_status]
);
// var_dump($id_status);
// die;
function fmt_time_cell($value, $by) {
    if ($value instanceof DateTimeInterface) {
        $ts = $value->format('Y-m-d H:i');
    } else {
        $ts = substr((string)$value, 0, 16);
    }
    if ($ts === '' || $ts === '0000-00-00 00:00') {
        return '';
    }
    return $ts . '<br>' . $by;
}
?>
<div class="card">
    <div class="card-body">
        <div id="table" class="table-editable">
            <table id="example" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr class="bg-success">
                        <th class="text-center" style="border: 1px solid gray;">#</th>
                        <th class="text-center" style="border: 1px solid gray;">Code</th>
                        <th class="text-center" style="border: 1px solid gray;">Lab</th>
                        <th class="text-center" style="border: 1px solid gray;">Adjust-1</th>
                        <th class="text-center" style="border: 1px solid gray;">Adjust-2</th>
                        <th class="text-center" style="border: 1px solid gray;">Adjust-3</th>
                        <th class="text-center" style="border: 1px solid gray;">Adjust-4</th>
                        <th class="text-center" style="border: 1px solid gray;">Adjust-5</th>
                        <th class="text-center" style="border: 1px solid gray;">Adjust-6</th>
                        <th class="text-center" style="border: 1px solid gray;">Adjust-7</th>
                        <th class="text-center" style="border: 1px solid gray;">Adjust-8</th>
                        <th class="text-center" style="border: 1px solid gray;">Adjust-9</th>
                        <th class="text-center" style="border: 1px solid gray;">Last-edited</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1;
                    while ($data = sqlsrv_fetch_array($sql_Time, SQLSRV_FETCH_ASSOC)) { ?>
                        <tr style="border: 1px solid gray;">
                            <td class="text-center" style="border: 1px solid gray;"><?php echo $i++; ?></td>
                            <td class="text-center" style="border: 1px solid gray;"><?php echo $data['kode'] ?></td>
                            <td class="text-center" style="border: 1px solid gray;"><?php echo fmt_time_cell($data['time_1'], $data['doby1']); ?></td>
                            <td class="text-center" style="border: 1px solid gray;"><?php echo fmt_time_cell($data['time_2'], $data['doby2']); ?></td>
                            <td class="text-center" style="border: 1px solid gray;"><?php echo fmt_time_cell($data['time_3'], $data['doby3']); ?></td>
                            <td class="text-center" style="border: 1px solid gray;"><?php echo fmt_time_cell($data['time_4'], $data['doby4']); ?></td>
                            <td class="text-center" style="border: 1px solid gray;"><?php echo fmt_time_cell($data['time_5'], $data['doby5']); ?></td>
                            <td class="text-center" style="border: 1px solid gray;"><?php echo fmt_time_cell($data['time_6'], $data['doby6']); ?></td>
                            <td class="text-center" style="border: 1px solid gray;"><?php echo fmt_time_cell($data['time_7'], $data['doby7']); ?></td>
                            <td class="text-center" style="border: 1px solid gray;"><?php echo fmt_time_cell($data['time_8'], $data['doby8']); ?></td>
                            <td class="text-center" style="border: 1px solid gray;"><?php echo fmt_time_cell($data['time_9'], $data['doby9']); ?></td>
                            <td class="text-center" style="border: 1px solid gray;"><?php echo fmt_time_cell($data['time_10'], $data['doby10']); ?></td>
                            <td class="text-center" style="border: 1px solid gray;"><?php if ($data['last_edit_by'] == "") {
                                                                                        echo $data['inserted_by'];
                                                                                    } else {
                                                                                        echo $data['last_edit_by'];
                                                                                    }  ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- Editable table -->
