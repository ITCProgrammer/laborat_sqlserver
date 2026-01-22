<?php
function checkStockAvailability($con, $assignments)
{
    // Ambil unique no_resep dari assignment (hanya yang ada no_resep)
    $uniqueResep = [];
    foreach ($assignments as $item) {
        $no_resep = trim($item['no_resep'] ?? '');
        if ($no_resep !== '') {
            $uniqueResep[$no_resep] = true;
        }
    }
    $uniqueResep = array_keys($uniqueResep);

    // Hitung kebutuhan qty per resep dan element
    $resepGroups = [];
    $elementMap  = [];
    $sql = "SELECT 
                ps.no_resep,
                pse.element_id,
                SUM(pse.qty) as total_qty
            FROM db_laborat.tbl_preliminary_schedule ps
            LEFT JOIN db_laborat.tbl_preliminary_schedule_element pse
                   ON ps.id = pse.tbl_preliminary_schedule_id
            WHERE ps.no_resep = ?
            GROUP BY ps.no_resep, pse.element_id";

    foreach ($uniqueResep as $no_resep) {
        $resStmt = sqlsrv_query($con, $sql, [$no_resep]);
        $res = $resStmt ? sqlsrv_fetch_array($resStmt, SQLSRV_FETCH_ASSOC) : null;
        if ($res) {
            $elementMap[$no_resep]  = (int)$res['element_id'];
            $resepGroups[$no_resep] = (float)$res['total_qty'];
        }
    }

    // Cek stok balance
    $insufficient = [];
    foreach ($resepGroups as $no_resep => $need) {
        $element_id = $elementMap[$no_resep];
        $rowBal = sqlsrv_query($con, "SELECT TOP 1 BASEPRIMARYQUANTITYUNIT FROM db_laborat.balance WHERE NUMBERID = ?", [$element_id]);
        $row = $rowBal ? sqlsrv_fetch_array($rowBal, SQLSRV_FETCH_ASSOC) : null;

        $qty_before_kg = $row ? (float)$row['BASEPRIMARYQUANTITYUNIT'] : 0;
        $qty_before_gr = $qty_before_kg * 1000;

        if ($qty_before_gr < $need) {
            $insufficient[] = [
                'no_resep' => $no_resep,
                'element_id' => $element_id,
                'stock_available_gr' => $qty_before_gr,
                'needed_gr' => $need
            ];
        }
    }

    if (!empty($insufficient)) {
        return [
            'ok' => false,
            'message' => "Stok tidak mencukupi",
            'failed' => $insufficient,
            'grouped_qty' => $resepGroups
        ];
    }

    return [
        'ok' => true,
        'message' => "Semua stok cukup",
        'grouped_qty' => $resepGroups
    ];
}
