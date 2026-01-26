<?php include __DIR__ . '/../koneksi.php'; ?>
<style>
    table#resepTable > tbody > tr.active > td {
        background-color: #337ab7 !important;
        color: #fff;
        font-weight: bold;
    }
    .resep-item:hover {
        cursor: pointer;
    }
    th {
        font-size: 10pt;
    }

    td {
        font-size: 10pt;
    }
    .input-xs {
        height: 22px !important;
        padding: 1px 2px;
        font-size: 12px;
        line-height: 1.5;
        border-radius: 3px;
    }

    .text-bold {
        font-weight: bold;
        font-style: italic;
        font-family: sans-serif;
    }

    .input-group-xs>.form-control,
    .input-group-xs>.input-group-addon,
    .input-group-xs>.input-group-btn>.btn {
        height: 22px;
        padding: 1px 5px;
        font-size: 12px;
        line-height: 1.5;
    }
</style>

<div class="box box-info">
    <div class="row">
        <!-- KIRI: Daftar No. Resep -->
        <div class="col-sm-3" style="margin-top: 15px;">
            <!-- <h4><strong>Daftar No. Resep</strong></h4> -->
            <table id="resepTable" class="table table-sm table-bordered table-sm display compact">
                <thead>
                    <tr class="bg-success">
                    <th width="5%">#</th>
                    <th>No. Resep</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = sqlsrv_query($con, "SELECT DISTINCT no_resep FROM db_laborat.tbl_preliminary_schedule ORDER BY no_resep ASC");
                    $no = 1;
                    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                        $resep = htmlspecialchars($row['no_resep']);
                        echo "<tr class='resep-item' data-resep='{$resep}'>
                                <td>$no</td>
                                <td>{$resep}</td>
                            </tr>";
                        $no++;
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- KANAN: Detail History -->
        <div class="col-sm-9" style="margin-top: 15px;">
            <!-- <h4>Detail History</h4> -->
            <div id="resep-detail">
            </div>
        </div>
    </div>
</div>


<script>
$(document).ready(function () {
  $('#resepTable').DataTable({
    pageLength: 25,
    pagingType: "simple_numbers",
    language: {
      paginate: {
        previous: '<i class="fa fa-angle-left"></i>',
        next: '<i class="fa fa-angle-right"></i>'
      }
    }
  });

  $(document).on('click', '.resep-item', function () {
    var noResep = $(this).data('resep');

    $('.resep-item').removeClass('active');
    $(this).addClass('active');

    // Load detail
    // $.post('pages/ajax/detail_history_ct.php', { no_resep: noResep }, function (data) {
    //   $('#resep-detail').html(data);
    // });
    $.ajax({
      url: 'pages/ajax/detail_history_ct.php',
      type: 'POST',
      data: { no_resep: noResep },
      success: function (data) {
        $('#resep-detail').html(data);
      }
    });
  });
});
</script>

