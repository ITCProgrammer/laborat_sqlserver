<?php
function insertBalanceTransaction($con, $schedule_id)
{
    // Ambil data preliminary (no_resep, element, qty)
    $sqlPrelim = "SELECT TOP 1 
                      ps.no_resep,
                      pse.element_id,
                      pse.qty
                  FROM db_laborat.tbl_preliminary_schedule ps
                  LEFT JOIN db_laborat.tbl_preliminary_schedule_element pse 
                         ON ps.id = pse.tbl_preliminary_schedule_id
                  WHERE ps.id = ?";
    $rowPrelim = sqlsrv_query($con, $sqlPrelim, [$schedule_id]);
    $prelim = $rowPrelim ? sqlsrv_fetch_array($rowPrelim, SQLSRV_FETCH_ASSOC) : null;

    if (!$prelim) {
        return false;
    }

    $no_resep   = $prelim['no_resep'] ?? '';
    $element_id = (int)($prelim['element_id'] ?? 0);
    $qty        = (float)($prelim['qty'] ?? 0);

    // Jika qty 0 atau element kosong, tidak perlu transaksi
    if ($element_id <= 0 || $qty <= 0) {
        return false;
    }

    // Jika DR* hanya proses suffix A
    if ($no_resep && str_starts_with($no_resep, 'DR')) {
        $lastChar = substr($no_resep, -1);
        if ($lastChar !== 'A') {
            return true; // di-skip tanpa error
        }
    }

    // Ambil stok balance saat ini
    $rowBal = sqlsrv_query($con, "SELECT TOP 1 BASEPRIMARYQUANTITYUNIT FROM db_laborat.balance WHERE NUMBERID = ?", [$element_id]);
    $bal    = $rowBal ? sqlsrv_fetch_array($rowBal, SQLSRV_FETCH_ASSOC) : null;
    $qty_before_kg = $bal ? (float)$bal['BASEPRIMARYQUANTITYUNIT'] : 0;
    $qty_before_gr = $qty_before_kg * 1000;

    $qty_after_gr = $qty_before_gr - $qty;
    $qty_after_kg = $qty_after_gr / 1000;

    $action      = 'Preliminary-Cycle';
    $uom         = 'gr';
    $uom_balance = 'kg';

    if (!sqlsrv_begin_transaction($con)) {
        return false;
    }

    try {
        // Update balance summary
        $stmtSum = sqlsrv_query($con,
            "UPDATE db_laborat.balance
             SET BASEPRIMARYQUANTITYUNIT = ?, LASTUPDATEDATETIME = GETDATE()
             WHERE NUMBERID = ?",
            [$qty_after_kg, $element_id]
        );
        if (!$stmtSum) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        // Insert log transaksi
        $stmtIns = sqlsrv_query($con,
            "INSERT INTO db_laborat.balance_transactions
             (element_id, no_resep, action, uom, qty, uom_balance, qty_element_before, qty_element_after, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, GETDATE())",
            [$element_id, $no_resep, $action, $uom, $qty, $uom_balance, $qty_before_kg, $qty_after_kg]
        );
        if (!$stmtIns) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        // Hapus detail preliminary element
        $stmtDel = sqlsrv_query($con,
            "DELETE FROM db_laborat.tbl_preliminary_schedule_element WHERE tbl_preliminary_schedule_id = ?",
            [$schedule_id]
        );
        if (!$stmtDel) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        sqlsrv_commit($con);
        return true;
    } catch (Exception $e) {
        sqlsrv_rollback($con);

        // Catat error ke log_general (jika ada tabelnya)
        $payload = json_encode([
            'error'          => $e->getMessage(),
            'schedule_id'    => $schedule_id,
            'no_resep'       => $no_resep,
            'element_id'     => $element_id,
            'qty'            => $qty,
            'qty_before_kg'  => $qty_before_kg,
            'qty_after_kg'   => $qty_after_kg
        ]);
        sqlsrv_query($con,
            "INSERT INTO db_laborat.log_general (entity, entity_id, action, data) VALUES (?, ?, ?, ?)",
            ['balance_transaction', null, 'error', $payload]
        );
        return false;
    }
}
