<?php
    include "koneksi.php";

    if (!function_exists('formatSqlsrvDateTime')) {
        function formatSqlsrvDateTime($value, $format)
        {
            if ($value instanceof DateTimeInterface) {
                return $value->format($format);
            }
            if ($value === null || $value === '') {
                return '';
            }
            $ts = strtotime($value);
            return $ts ? date($format, $ts) : '';
        }
    }

    $sql = "SELECT 
              * 
            FROM db_laborat.tbl_log_history_matching t
    ";

    $res = sqlsrv_query($con, $sql);

    $data1 = [];
    $no = 1;
    while ($r = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
        $data1[] = [
            'no'       => $no++,
            'sales_order' => $r['salesorder'],
            'orderline' => $r['orderline'],
            'warna' => $r['warna'],
            'po_greige' => $r['po_greige'],
            'benang' => $r['benang'],
            'v_pic' => $r['values_pic'],
            'v_status' => $r['values_status'],
            'ip_update'      => $r['ip_update'],
            'user_update'      => $r['user_update'],
            'process'       => $r['process'],
            'date_update' => formatSqlsrvDateTime($r['date_update'], 'Y-m-d'),
            'time_update' => formatSqlsrvDateTime($r['date_update'], 'H:i:s'),
        ];
    }
?>

<div class="row">
  <div class="col-xs-12">
    <div class="box">
      <div class="box-body">
        <h3>Log History Status Matching</h3>
        <table id="rekapTable" class="table table-bordered table-striped">
          <thead class="bg-primary">
            <tr>
              <th>No.</th>
              <th>Nomor Bon</th>
              <th>Line</th>
              <th>Benang</th>
              <th>Warna</th>
              <th>PO Greige</th>
              <th>User Update</th>
              <th>IP Update</th>
              <th>Date Update</th>
              <th>Time Update</th>
              <th>Process</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($data1 as $row): ?>
              <tr>
                <td><?= $row['no'] ?></td>
                <td><?= $row['sales_order'] ?></td>
                <td><?= $row['orderline'] ?></td>
                <td><?= $row['benang'] ?></td>
                <td><?= $row['warna'] ?></td>
                <td><?= $row['po_greige'] ?></td>
                <td><?= $row['user_update'] ?></td>
                <td><?= $row['ip_update'] ?></td>
                <td><?= $row['date_update'] ?></td>
                <td><?= $row['time_update'] ?></td>
                <td><?= $row['process'] ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($data1)): ?>
              <tr><td colspan="5" align="center">Tidak ada data.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-xs-12">
    <div class="box">
      <div class="box-body">
        <h3>Rekap Update</h3>
        <?php 
        // Get data for the last 150 days
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-150 days'));
        
        $sql = "SELECT 
                  CONVERT(date, date_update) as tanggal,
                  process,
                  user_update,
                  COUNT(*) as jumlah
                FROM db_laborat.tbl_log_history_matching
                WHERE date_update BETWEEN '$startDate' AND '$endDate 23:59:59'
                GROUP BY CONVERT(date, date_update), process, user_update
                ORDER BY tanggal DESC, process, jumlah DESC";

        $res = sqlsrv_query($con, $sql);

        $data = [];
        $total_update = 0;
        $total_insert = 0;
        
        while ($row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)) {
            $tanggal = formatSqlsrvDateTime($row['tanggal'], 'Y-m-d');
            $process = $row['process'];
            $user = $row['user_update'];
            $jumlah = $row['jumlah'];
            
            if (!isset($data[$tanggal])) {
                $data[$tanggal] = [
                    'update' => [],
                    'insert' => [],
                    'total_update' => 0,
                    'total_insert' => 0
                ];
            }
            
            if ($process == 'update') {
                $data[$tanggal]['update'][$user] = $jumlah;
                $data[$tanggal]['total_update'] += $jumlah;
                $total_update += $jumlah;
            } else {
                $data[$tanggal]['insert'][$user] = $jumlah;
                $data[$tanggal]['total_insert'] += $jumlah;
                $total_insert += $jumlah;
            }
        }
        
        // Fill in missing dates with empty data
        $allDates = [];
        $currentDate = $endDate;
        $no = 1;
        while ($currentDate >= $startDate) {
            if (!isset($data[$currentDate])) {
                $data[$currentDate] = [
                    'update' => [],
                    'insert' => [],
                    'total_update' => 0,
                    'total_insert' => 0
                ];
            }
            $allDates[] = [
                'no' => $no++,
                'tanggal' => $currentDate,
                'data' => $data[$currentDate]
            ];
            $currentDate = date('Y-m-d', strtotime($currentDate . ' -1 day'));
        }
        ?>
        <table id="rekapTable2" class="table table-bordered table-striped">
          <thead class="bg-primary">
            <tr>
              <th>No.</th>
              <th>Tanggal</th>
              <th>Jumlah Update</th>
              <th>Jumlah Insert</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($allDates as $row): ?>
              <tr>
                <td><?= $row['no'] ?></td>
                <td><?= date('Y-m-d', strtotime($row['tanggal'])) ?></td>
                <td>
                    <?php foreach ($row['data']['update'] as $user => $count): ?>
                        - <?= $user ?> : <?= $count ?><br>
                    <?php endforeach; ?>
                    <?php if (empty($row['data']['update'])): ?>
                        -
                    <?php endif; ?>
                </td>
                <td>
                    <?php foreach ($row['data']['insert'] as $user => $count): ?>
                        - <?= $user ?> : <?= $count ?><br>
                    <?php endforeach; ?>
                    <?php if (empty($row['data']['insert'])): ?>
                        -
                    <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($allDates)): ?>
              <tr><td colspan="4" align="center">Tidak ada data.</td></tr>
            <?php endif; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="2">Total</td>
              <td><?= $total_update ?></td>
              <td><?= $total_insert ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  $(document).ready(function () {
    $('#rekapTable').DataTable({
      dom: 'Bfrtip',
      buttons: ['excel', 'pdf', 'print'],
      responsive: true,
      pageLength: 10
    });
  });
</script>
<script>
  $(document).ready(function () {
    $('#rekapTable2').DataTable({
      dom: 'Bfrtip',
      buttons: ['excel', 'pdf', 'print'],
      responsive: true,
      pageLength: 5
    });
  });
</script>
