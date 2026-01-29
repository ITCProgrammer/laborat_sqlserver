<?php
include '../../koneksi.php';

if (isset($_POST['no_counter'])) {
    $no_counter = $_POST['no_counter'];

    $sql = "SELECT * FROM db_laborat.log_qc_test WHERE no_counter = ?";
    $query = sqlsrv_query($con, $sql, [$no_counter]);
    if (!$query) {
        $errors = sqlsrv_errors();
        echo $errors ? $errors[0]['message'] : 'Query gagal';
        exit;
    }

    $html = '';
    $no = 1;
    while ($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)) {
        $doAt = $row['do_at'];
        if ($doAt instanceof DateTimeInterface) {
            $doAt = $doAt->format('Y-m-d H:i:s');
        } elseif ($doAt === null) {
            $doAt = '';
        }
        $html .= '<tr>';
        $html .= '<td>' . $no++ . '</td>';
        $html .= '<td>' . $row['status'] . '</td>';
        $html .= '<td>' . $row['info'] . '</td>';
        $html .= '<td>' . $row['do_by'] . '</td>';
        $html .= '<td>' . $doAt . '</td>';
        $html .= '<td>' . $row['ip_address'] . '</td>';
        $html .= '</tr>';
    }

    sqlsrv_free_stmt($query);
    echo $html;
}
