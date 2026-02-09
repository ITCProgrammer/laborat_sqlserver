<?php
include "koneksi.php";
$db2_errors = [];
$safeDb2 = function(string $sql) use (&$conn1, &$db2_errors) {
    if (!$conn1) {
        $msg = "DB2 connection not available for SQL: ".$sql;
        $db2_errors[] = $msg;
        error_log($msg);
        return false;
    }
    $stmt = @db2_exec($conn1, $sql);
    if (!$stmt) {
        $err = function_exists('db2_stmt_errormsg') ? db2_stmt_errormsg() : 'unknown db2 error';
        $msg = "DB2 exec failed: ".$err." SQL: ".$sql;
        $db2_errors[] = $msg;
        error_log($msg);
    }
    return $stmt;
};

function normalizeLiquorRatio($lr)
{
    $lr = trim((string)$lr);
    if ($lr === '' || $lr === '0') {
        return '0';
    }
    // Buang prefix non-numeric seperti "LR"
    $lr = preg_replace('/^LR/i', '', $lr);
    // Samakan pemisah jadi titik
    $lr = str_replace([',', ':'], '.', $lr);
    // Ambil angka desimal pertama yang valid
    if (preg_match('/\d+(?:\.\d+)?/', $lr, $m)) {
        return $m[0];
    }
    return '0';
}
$idstatus               = $_GET['idm']; // ID_STATUS_MATCHING
$idmatching             = $_GET['id']; // ID_MATCHING
$IMPORTAUTOCOUNTER      = $_GET['IMPORTAUTOCOUNTER'];
$jenis_suffix           = $_GET['suffix'];
$number_suffix          = $_GET['numbersuffix'];
$userLogin              = $_GET['userLogin'];
$insert_recipeComponentBean = true;

// PROSES EXPORT RECIPE (SQL Server)
$sqlRecipe = "SELECT TOP 1 
                    b.id AS id_matching, 
                    a.id AS id_status, 
                    b.recipe_code, 
                    a.idm AS SUFFIXCODE, 
                    b.warna, 
                    CASE 
                        WHEN a.lr = '0' THEN SUBSTRING(CONVERT(VARCHAR(50), a.second_lr), 3, LEN(CONVERT(VARCHAR(50), a.second_lr))) 
                        ELSE SUBSTRING(CONVERT(VARCHAR(50), a.lr), 3, LEN(CONVERT(VARCHAR(50), a.lr))) 
                    END AS LR,
                    PARSENAME(REPLACE(b.recipe_code,' ','.'),2) as recipe_code_1,
                    PARSENAME(REPLACE(b.recipe_code,' ','.'),1) as recipe_code_2,
                    CASE
                        WHEN b.jenis_matching IN ('LD NOW','L/D') THEN '001'
                        ELSE
                            CASE
                                WHEN LEFT(b.no_resep, 2) IN ('DR','CD','OB') THEN SUBSTRING(CONCAT(SUBSTRING(b.no_resep, 3, LEN(b.no_resep)-2), 'L'), 4, 100)
                                WHEN LEFT(b.no_resep, 2) IN ('D2','R2','A2') THEN SUBSTRING(CONCAT(SUBSTRING(b.no_resep, 2, LEN(b.no_resep)-1), 'L'), 4, 100)
                            END
                    END as no_resep_convert,
                    b.created_by
                FROM db_laborat.tbl_status_matching a
                INNER JOIN db_laborat.tbl_matching b ON a.idm = b.no_resep
                WHERE a.id = ?
                ORDER BY a.id DESC";
$recipe = sqlsrv_query($con, $sqlRecipe, [$idstatus]);
if (!$recipe) {
    die("Query recipe gagal:\n".print_r(sqlsrv_errors(), true));
}
$delimiter = ",";
$filename = "Recipe_" . $_GET['rcode'] . ".csv";

while ($r = sqlsrv_fetch_array($recipe, SQLSRV_FETCH_ASSOC)) {
    if ($jenis_suffix == "1") {
        $RECIPESUBCODE01 = $r['recipe_code_2'];
    } elseif ($jenis_suffix == "2") {
        $RECIPESUBCODE01 = $r['recipe_code_2'];
    }
    $tgl = date('Y-m-d H:i:s');
    $warna  = str_replace("'", "`", $r['warna']);
    $lrNumeric = normalizeLiquorRatio($r['LR'] ?? '');
    $queryDataMain  = "INSERT INTO RECIPEBEAN (
                                                COMPANYCODE,
                                                IMPORTAUTOCOUNTER,
                                                DIVISIONCODE,
                                                ALLOWEDDIVISIONS,
                                                NUMBERID,
                                                RECIPETEMPLATECODE,
                                                ITEMTYPECODE,
                                                RECIPETYPE,
                                                SUBCODE01,
                                                SUBCODE02,
                                                SUBCODE03,
                                                SUBCODE04,
                                                SUBCODE05,
                                                SUBCODE06,
                                                SUBCODE07,
                                                SUBCODE08,
                                                SUBCODE09,
                                                SUBCODE10,
                                                SUFFIXCODE,
                                                GENERICRECIPE,
                                                LONGDESCRIPTION,
                                                SHORTDESCRIPTION,
                                                SEARCHDESCRIPTION,
                                                REFSUBCODE01,
                                                REFSUBCODE02,
                                                REFSUBCODE03,
                                                REFSUBCODE04,
                                                REFSUBCODE05,
                                                REFSUBCODE06,
                                                REFSUBCODE07,
                                                REFSUBCODE08,
                                                REFSUBCODE09,
                                                REFSUBCODE10,
                                                REFRECIPESUFFIXCODE,
                                                REFRECIPENUMBERID,
                                                GENERICREFERENCE,
                                                VALIDFROMDATE,
                                                VALIDTODATE,
                                                LIMITINPOBYNUMBEROFUSES,
                                                MAXNUMBEROFUSES,
                                                NUMBEROFUSES,
                                                SOLUTIONPASTEUMCODE,
                                                SOLUTIONPASTEWEIGHT,
                                                RECIPEINCIDENCE,
                                                SOLUTIONPASTEUMWEIGHTUMCODE,
                                                PRODUCTIONUMCODE,
                                                PRODUCTIONUMWEIGHT,
                                                PRODUCTIONUMWEIGHTUMCODE,
                                                BATCHSTANDARDSIZE,
                                                AVERAGELENGTH,
                                                BATCHAVERAGEUMCODE,
                                                DILUITIONPERCENTAGE,
                                                PICKUPPERCENTAGE,
                                                DRYRESIDUALPERCENTAGE,
                                                DRYRESIDUALQUANTITY,
                                                GLOBALWASTEPERCENTAGE,
                                                BATHVOLUME,
                                                RESIDUALBATHVOLUME,
                                                VOLUMEUMCODE,
                                                COMPOSITIONCODE,
                                                LIQUORRATIO,
                                                MIXVOLUME,
                                                PRODUCTIONRESERVATIONGROUPCODE,
                                                COSTGROUPCODE,
                                                USESUBRECIPEHEADERVALUES,
                                                BINDERFLUIDSRATIO,
                                                BINDERMINPERCENTAGE,
                                                BINDERITEMTYPECODE,
                                                BSUBCODE01,
                                                BSUBCODE02,
                                                BSUBCODE03,
                                                BSUBCODE04,
                                                BSUBCODE05,
                                                BSUBCODE06,
                                                BSUBCODE07,
                                                BSUBCODE08,
                                                BSUBCODE09,
                                                BSUBCODE10,
                                                FILLERITEMTYPECODE,
                                                FSUBCODE01,
                                                FSUBCODE02,
                                                FSUBCODE03,
                                                FSUBCODE04,
                                                FSUBCODE05,
                                                FSUBCODE06,
                                                FSUBCODE07,
                                                FSUBCODE08,
                                                FSUBCODE09,
                                                FSUBCODE10,
                                                BINDERGROUPNUMBER,
                                                BINDERGROUPTYPECODE,
                                                FILLERGROUPNUMBER,
                                                FILLERGROUPTYPECODE,
                                                TRANSLATEDLONGDESCRIPTION,
                                                TRANSLATEDLANGUAGECODE,
                                                TRANSLATEDSHORTDESCRIPTION,
                                                STATUS,
                                                APPROVALDATE,
                                                APPROVALUSER,
                                                CREATEHEADER,
                                                WSOPERATION,
                                                IMPOPERATIONUSER,
                                                IMPORTSTATUS,
                                                IMPCREATIONDATETIME,
                                                IMPCREATIONUSER,
                                                IMPLASTUPDATEDATETIME,
                                                IMPLASTUPDATEUSER,
                                                IMPORTDATETIME,
                                                RETRYNR,
                                                NEXTRETRY,
                                                IMPORTID,
                                                RELATEDDEPENDENTID,
                                                FATHERID,
                                                OWNEDCOMPONENT,
                                                RECIPEITEMTYPECODE,
                                                RECIPESUBCODE01,
                                                RECIPESUBCODE02,
                                                RECIPESUBCODE03,
                                                RECIPESUBCODE04,
                                                RECIPESUBCODE05,
                                                RECIPESUBCODE06,
                                                RECIPESUBCODE07,
                                                RECIPESUBCODE08,
                                                RECIPESUBCODE09,
                                                RECIPESUBCODE10,
                                                RECIPESUFFIXCODE,
                                                GROUPNUMBER,
                                                GROUPTYPECODE,
                                                LINETYPE,
                                                SEQUENCE,
                                                ALTERNATIVE,
                                                SUBSEQUENCE,
                                                COMPONENTINCIDENCE,
                                                REFRECIPEGROUPNUMBER,
                                                REFRECIPESEQUENCE,
                                                REFRECIPEALTERNATIVE,
                                                REFRECIPESUBSEQUENCE,
                                                REFRECIPESTATUS,
                                                ITEMTYPEAFICODE,
                                                COMMENTLINE,
                                                CONSUMPTIONTYPE,
                                                ASSEMBLYUOMCODE,
                                                COMPONENTUOMCODE,
                                                COMPONENTUOMTYPE,
                                                CONSUMPTION,
                                                COMPOSITIONCOMPONENTCODE,
                                                CONSFORMIXLABEL,
                                                CONSPERBATCHLABEL,
                                                CONSPERLABEL,
                                                WATERMANAGEMENT,
                                                BINDERFILLERCOMPONENT,
                                                PRODUCED,
                                                PRICELISTCODE,
                                                COSTINGPLANTCODE,
                                                INITIALENGINEERINGCHANGE,
                                                FINALENGINEERINGCHANGE,
                                                INITIALDATE,
                                                FINALDATE,
                                                UNITARYBATCHSTANDARDSIZE,
                                                ALLOWDELETEBINDERFILLER,
                                                TOTALCOSTTEXT
                                        ) VALUES (
                                                '100', -- COMPANYCODE
                                                '$IMPORTAUTOCOUNTER', --IMPORTAUTOCOUNTER
                                                '', -- DIVISIONCODE
                                                NULL, -- ALLOWEDDIVISIONS
                                                '$IMPORTAUTOCOUNTER', --NUMBERID
                                                'FD', -- RECIPETEMPLATECODE
                                                'RFD', -- ITEMTYPECODE
                                                '2', -- RECIPETYPE
                                                '$RECIPESUBCODE01', --SUBCODE01
                                                '', -- SUBCODE02
                                                '', -- SUBCODE03
                                                '', -- SUBCODE04
                                                '', -- SUBCODE05
                                                '', -- SUBCODE06
                                                '', -- SUBCODE07
                                                '', -- SUBCODE08
                                                '', -- SUBCODE09
                                                '', -- SUBCODE10
                                                '$r[no_resep_convert]', --SUFFIXCODE
                                                '0', -- GENERICRECIPE
                                                '$warna', --LONGDESCRIPTION
                                                '$warna', --SHORTDESCRIPTION
                                                '$warna', --SEARCHDESCRIPTION
                                                '', -- REFSUBCODE01
                                                '', -- REFSUBCODE02
                                                '', -- REFSUBCODE03
                                                '', -- REFSUBCODE04
                                                '', -- REFSUBCODE05
                                                '', -- REFSUBCODE06
                                                '', -- REFSUBCODE07
                                                '', -- REFSUBCODE08
                                                '', -- REFSUBCODE09
                                                '', -- REFSUBCODE10
                                                '', -- REFRECIPESUFFIXCODE
                                                '0', -- REFRECIPENUMBERID
                                                '', -- GENERICREFERENCE
                                                '1970-01-01', -- VALIDFROMDATE
                                                '2100-12-31', -- VALIDTODATE
                                                '0', -- LIMITINPOBYNUMBEROFUSES
                                                '0', -- MAXNUMBEROFUSES
                                                '0', -- NUMBEROFUSES
                                                'l', -- SOLUTIONPASTEUMCODE
                                                '1', -- SOLUTIONPASTEWEIGHT
                                                '100', -- RECIPEINCIDENCE
                                                'kg', -- SOLUTIONPASTEUMWEIGHTUMCODE
                                                'kg', -- PRODUCTIONUMCODE
                                                '1', -- PRODUCTIONUMWEIGHT
                                                'kg', -- PRODUCTIONUMWEIGHTUMCODE
                                                '1000', -- BATCHSTANDARDSIZE
                                                '0', -- AVERAGELENGTH
                                                'kg', -- BATCHAVERAGEUMCODE
                                                '0', -- DILUITIONPERCENTAGE
                                                '0', -- PICKUPPERCENTAGE
                                                '0', -- DRYRESIDUALPERCENTAGE
                                                '0', -- DRYRESIDUALQUANTITY
                                                '0', -- GLOBALWASTEPERCENTAGE
                                                '1000', -- BATHVOLUME
                                                '0', -- RESIDUALBATHVOLUME
                                                'l', -- VOLUMEUMCODE
                                                '', -- COMPOSITIONCODE
                                                '$lrNumeric', --LIQUORRATIO
                                                '0', -- MIXVOLUME
                                                '001', -- PRODUCTIONRESERVATIONGROUPCODE
                                                '', -- COSTGROUPCODE
                                                '0', -- USESUBRECIPEHEADERVALUES
                                                '0', -- BINDERFLUIDSRATIO
                                                '0', -- BINDERMINPERCENTAGE
                                                '0', -- BINDERITEMTYPECODE
                                                '', -- BSUBCODE01
                                                '', -- BSUBCODE02
                                                '', -- BSUBCODE03
                                                '', -- BSUBCODE04
                                                '', -- BSUBCODE05
                                                '', -- BSUBCODE06
                                                '', -- BSUBCODE07
                                                '', -- BSUBCODE08
                                                '', -- BSUBCODE09
                                                '', -- BSUBCODE10
                                                '', -- FILLERITEMTYPECODE
                                                '', -- FSUBCODE01
                                                '', -- FSUBCODE02
                                                '', -- FSUBCODE03
                                                '', -- FSUBCODE04
                                                '', -- FSUBCODE05
                                                '', -- FSUBCODE06
                                                '', -- FSUBCODE07
                                                '', -- FSUBCODE08
                                                '', -- FSUBCODE09
                                                '', -- FSUBCODE10
                                                '0', -- BINDERGROUPNUMBER
                                                '', -- BINDERGROUPTYPECODE
                                                '0', -- FILLERGROUPNUMBER
                                                '0', -- FILLERGROUPTYPECODE
                                                NULL, -- TRANSLATEDLONGDESCRIPTION
                                                NULL, -- TRANSLATEDLANGUAGECODE
                                                NULL, -- TRANSLATEDSHORTDESCRIPTION
                                                '2', -- STATUS
                                                NULL, -- APPROVALDATE
                                                '', -- APPROVALUSER
                                                '1', -- CREATEHEADER
                                                '5', -- WSOPERATION
                                                '$userLogin', --IMPOPERATIONUSER
                                                '0', -- IMPORTSTATUS
                                                NULL, -- IMPCREATIONDATETIME
                                                NULL, -- IMPCREATIONUSER
                                                NULL, -- IMPLASTUPDATEDATETIME
                                                NULL, -- IMPLASTUPDATEUSER
                                                '$tgl', --IMPORTDATETIME,
                                                '0', -- RETRYNR
                                                '0', -- NEXTRETRY
                                                '0', -- IMPORTID
                                                '$IMPORTAUTOCOUNTER', --RELATEDDEPENDENTID
                                                NULL, -- FATHERID
                                                NULL, -- OWNEDCOMPONENT
                                                NULL, -- RECIPEITEMTYPECODE
                                                NULL, -- RECIPESUBCODE01
                                                NULL, -- RECIPESUBCODE02
                                                NULL, -- RECIPESUBCODE03
                                                NULL, -- RECIPESUBCODE04
                                                NULL, -- RECIPESUBCODE05
                                                NULL, -- RECIPESUBCODE06
                                                NULL, -- RECIPESUBCODE07
                                                NULL, -- RECIPESUBCODE08
                                                NULL, -- RECIPESUBCODE09
                                                NULL, -- RECIPESUBCODE10
                                                NULL, -- RECIPESUFFIXCODE
                                                NULL, -- GROUPNUMBER
                                                NULL, -- GROUPTYPECODE
                                                NULL, -- LINETYPE
                                                NULL, -- SEQUENCE
                                                NULL, -- ALTERNATIVE
                                                NULL, -- SUBSEQUENCE
                                                NULL, -- COMPONENTINCIDENCE
                                                NULL, -- REFRECIPEGROUPNUMBER
                                                NULL, -- REFRECIPESEQUENCE
                                                NULL, -- REFRECIPEALTERNATIVE
                                                NULL, -- REFRECIPESUBSEQUENCE
                                                NULL, -- REFRECIPESTATUS
                                                NULL, -- ITEMTYPEAFICODE
                                                NULL, -- COMMENTLINE
                                                NULL, -- CONSUMPTIONTYPE
                                                NULL, -- ASSEMBLYUOMCODE
                                                NULL, -- COMPONENTUOMCODE
                                                NULL, -- COMPONENTUOMTYPE
                                                NULL, -- CONSUMPTION
                                                NULL, -- COMPOSITIONCOMPONENTCODE
                                                NULL, -- CONSFORMIXLABEL
                                                NULL, -- CONSPERBATCHLABEL
                                                NULL, -- CONSPERLABEL
                                                NULL, -- WATERMANAGEMENT
                                                NULL, -- BINDERFILLERCOMPONENT
                                                NULL, -- PRODUCED
                                                NULL, -- PRICELISTCODE
                                                NULL, -- COSTINGPLANTCODE
                                                NULL, -- INITIALENGINEERINGCHANGE
                                                NULL, -- FINALENGINEERINGCHANGE
                                                NULL, -- INITIALDATE
                                                NULL, -- FINALDATE
                                                NULL, -- UNITARYBATCHSTANDARDSIZE
                                                NULL, -- ALLOWDELETEBINDERFILLER
                                                NULL -- TOTALCOSTTEXT
                                            )
                                            ";
    $insert_recipeBean  = db2_exec($conn1, $queryDataMain);
    if (!$insert_recipeBean) {
        $errorMsg = db2_stmt_errormsg();
        $context = [
            'idstatus' => $idstatus,
            'idmatching' => $idmatching,
            'IMPORTAUTOCOUNTER' => $IMPORTAUTOCOUNTER,
            'suffix' => $jenis_suffix,
            'number_suffix' => $number_suffix,
            'userLogin' => $userLogin,
            'recipe_code' => $r['recipe_code'] ?? null,
            'recipe_code_1' => $r['recipe_code_1'] ?? null,
            'recipe_code_2' => $r['recipe_code_2'] ?? null,
            'suffixcode' => $r['SUFFIXCODE'] ?? null,
            'no_resep_convert' => $r['no_resep_convert'] ?? null,
            'warna' => $r['warna'] ?? null,
            'created_by' => $r['created_by'] ?? null,
            'LR_raw' => $r['LR'] ?? null,
            'LR_norm' => $lrNumeric ?? null
        ];

        // Tampilkan detail error agar tahu data mana yang gagal
        echo "<pre>Insert RECIPEBEAN gagal.\n".
             "DB2 error: ".htmlentities($errorMsg)."\n".
             "Context:\n".htmlentities(print_r($context, true))."\n".
             "</pre>";
        exit;
    }
}
// PROSES EXPORT RECIPE

// PROSES EXPORT RECIPE COMPONENT
if ($jenis_suffix == "1") {
    $remark = "and (remark = 'from Co-power'";
} elseif ($jenis_suffix == "2") {
    $remark = "and (remark = 'from merge Co-power'";
}
if (substr($number_suffix, 0, 1) == 'D') {
    $garam = ")";
} else {
    $garam = "or kode = 'E-1-010')";
}
$query_laborat  = "SELECT a.id AS id_matching_detail,
                                            a.id_matching as id_matching,
                                            a.id_status as is_status,
                                            PARSENAME(REPLACE(b.recipe_code,' ','.'),2) as recipe_code_1,
                                            PARSENAME(REPLACE(b.recipe_code,' ','.'),1) as recipe_code_2,
                                            case
                                                when SUBSTRING(b.no_resep, 1,2) IN ('DR','CD','OB') then SUBSTRING(CONCAT(SUBSTRING(b.no_resep, 3, LEN(b.no_resep)-2), 'L'), 4, 100)
                                                when SUBSTRING(b.no_resep, 1,2) IN ('D2','R2','A2') then SUBSTRING(CONCAT(SUBSTRING(b.no_resep, 2, LEN(b.no_resep)-1), 'L'), 4, 100)
                                            end as no_resep_convert,
                                            case
                                                when tds.code_new is null then a.kode 
                                                else tds.code_new
                                            end as kode,
                                            tds.ket,
                                            tds.product_name as nama,
                                            case
                                                when conc10 != 0 then conc10
                                                when conc9 != 0 then conc9
                                                when conc8 != 0 then conc8
                                                when conc7 != 0 then conc7
                                                when conc6 != 0 then conc6
                                                when conc5 != 0 then conc5
                                                when conc4 != 0 then conc4
                                                when conc3 != 0 then conc3
                                                when conc2 != 0 then conc2
                                                when conc1 != 0 then conc1
                                            end as conc,
                                            remark as remark,
	                                        a.doby1,
                                            b.jenis_matching
                                        FROM db_laborat.tbl_matching_detail a 
                                        LEFT JOIN db_laborat.tbl_matching b ON b.id = a.id_matching
                                        left join db_laborat.tbl_status_matching tsm on tsm.idm = b.no_resep 
                                        LEFT JOIN db_laborat.tbl_dyestuff tds ON tds.code = a.kode 
                                        WHERE a.id_matching = '$idmatching' AND a.id_status = '$idstatus' $remark $garam order by a.flag ASC";
$recipe_cmp = sqlsrv_query($con, $query_laborat);
$recipe_cmp_utk_scouring = sqlsrv_query($con, $query_laborat);
if (!$recipe_cmp) {
    die("Query laborat gagal:\n".print_r(sqlsrv_errors(), true));
}
if (!$recipe_cmp_utk_scouring) {
    die("Query laborat (scouring) gagal:\n".print_r(sqlsrv_errors(), true));
}
$ab = isset($_GET['ab']) ? $_GET['ab'] : '0';
$delimiter = ",";
$filename = "RC_" . $_GET['rcode'] . ".csv";

//autonumber for IMPORTAUTOCOUNTER
$q_iac = sqlsrv_query($con, "SELECT nomor_urut FROM db_laborat.importautocounter");
$d_IMPORTAUTOCOUNTER = sqlsrv_fetch_array($q_iac, SQLSRV_FETCH_ASSOC);

$SEQUENCE = 1;
// NGECEK SCOURING di RECIPE 001, KALAU ADA INSERT DULUAN DATA PALING ATAS
    $suffix = sqlsrv_fetch_array($recipe_cmp_utk_scouring, SQLSRV_FETCH_ASSOC);
    if($suffix && ($suffix['jenis_matching'] == 'Matching Ulang' OR $suffix['jenis_matching'] == 'Matching Ulang NOW' OR $suffix['jenis_matching'] == 'Matching Development')){
        $rcode_sc   = substr($_GET['rcode'], 0,11);
        if($ab == '1'){
            $groupnumber_sc     = '45';
        }else{
            $groupnumber_sc     = '10';
        }
        $q_scouring         = ($conn1) ? db2_exec($conn1, "SELECT
                                                    r2.GROUPNUMBER,
                                                    r2.GROUPTYPECODE,
                                                    r2.LINETYPE,
                                                    r2.SEQUENCE,
                                                    r2.SUBSEQUENCE,
                                                    r2.ITEMTYPEAFICODE,
                                                    r2.SUBCODE01,
                                                    r2.SUFFIXCODE
                                                FROM	
                                                    RECIPE r 
                                                LEFT JOIN RECIPECOMPONENT r2 ON r2.RECIPENUMBERID = r.NUMBERID
                                                LEFT JOIN RECIPE r3 ON r3.SUBCODE01 = r2.SUBCODE01 AND r3.SUFFIXCODE = r2.SUFFIXCODE 
                                                WHERE 
                                                    r.SUBCODE01 = '$_GET[rcode]'
                                                AND r.SUFFIXCODE = '001'
                                                AND r2.SUBCODE01 LIKE '%SC%'") : false;
        $data_scouring      = $q_scouring ? db2_fetch_assoc($q_scouring) : [];

        if(!empty($data_scouring['SUBCODE01'])){
            $no_urut = $d_IMPORTAUTOCOUNTER['nomor_urut']++;
$insert_recipeComponentBean = true;
$insert_recipeComponentBean = $insert_recipeComponentBean && $safeDb2("INSERT INTO RECIPECOMPONENTBEAN(FATHERID,
                                                                    IMPORTAUTOCOUNTER,
                                                                    OWNEDCOMPONENT,
                                                                    RECIPEITEMTYPECODE,
                                                                    RECIPESUBCODE01,
                                                                    RECIPESUFFIXCODE,
                                                                    GROUPNUMBER,
                                                                    GROUPTYPECODE,
                                                                    LINETYPE,
                                                                    SEQUENCE,
                                                                    SUBSEQUENCE,
                                                                    COMPONENTINCIDENCE,
                                                                    REFRECIPEGROUPNUMBER,
                                                                    REFRECIPESEQUENCE,
                                                                    REFRECIPESUBSEQUENCE,
                                                                    REFRECIPESTATUS,
                                                                    ITEMTYPEAFICODE,
                                                                    SUBCODE01,
                                                                    SUFFIXCODE,
                                                                    CONSUMPTIONTYPE,
                                                                    ASSEMBLYUOMCODE,
                                                                    COMPONENTUOMCODE,
                                                                    COMPONENTUOMTYPE,
                                                                    CONSUMPTION,
                                                                    WATERMANAGEMENT,
                                                                    BINDERFILLERCOMPONENT,
                                                                    PRODUCED,
                                                                    COSTINGPLANTCODE,
                                                                    FINALENGINEERINGCHANGE,
                                                                    INITIALDATE,
                                                                    FINALDATE,
                                                                    ALLOWDELETEBINDERFILLER,
                                                                    WSOPERATION,
                                                                    IMPORTSTATUS,
                                                                    IMPORTDATETIME,
                                                                    RETRYNR,
                                                                    NEXTRETRY,
                                                                    IMPORTID,
                                                                    RELATEDDEPENDENTID,
                                                                    IMPOPERATIONUSER) 
                                                            VALUES(
                                                                '$IMPORTAUTOCOUNTER',
                                                                '$no_urut',
                                                                '0',
                                                                'RFD',
                                                                '$RECIPESUBCODE01', 
                                                                '$suffix[no_resep_convert]',
                                                                '$data_scouring[GROUPNUMBER]', 
                                                                '201',
                                                                '2',
                                                                '10',
                                                                '10',
                                                                '100', 
                                                                '0',
                                                                '0', 
                                                                '0', 
                                                                '0', 
                                                                'RFF', 
                                                                '$data_scouring[SUBCODE01]', 
                                                                '$data_scouring[SUFFIXCODE]', 
                                                                '', 
                                                                'l', 
                                                                '', 
                                                                '', 
                                                                '0', 
                                                                '1', 
                                                                '0', 
                                                                '0', 
                                                                '001', 
                                                                '9999999999', 
                                                                '1970-01-01', 
                                                                '2100-12-31', 
                                                                '0', 
                                                                '1', 
                                                                '0', 
                                                                '$tgl', 
                                                                '3', 
                                                                '0', 
                                                                '0', 
                                                                '$no_urut', 
                                                                '$userLogin')");
        }
    }
// NGECEK SCOURING di RECIPE 001, KALAU ADA INSERT DULUAN DATA PALING ATAS
while ($r_cmp = sqlsrv_fetch_array($recipe_cmp, SQLSRV_FETCH_ASSOC)) {
    $concVal = $r_cmp['conc'];
    if ($concVal !== null) {
        if (is_numeric($concVal)) {
            $concVal = rtrim(rtrim(sprintf('%.4f', (float)$concVal), '0'), '.');
        }
        if (is_string($concVal) && strlen($concVal) && $concVal[0] === '.') {
            $concVal = '0' . $concVal;
        }
    }
    $dyestuffStmt = sqlsrv_query($con, "SELECT TOP 1 * FROM db_laborat.tbl_dyestuff WHERE code = ?", [$r_cmp['kode']]);
    $r_code = $dyestuffStmt ? sqlsrv_fetch_array($dyestuffStmt, SQLSRV_FETCH_ASSOC) : null;

    if ($r_code['Product_Unit'] == 1) {
        $CONSUMPTIONTYPE = 2;
    } else {
        $CONSUMPTIONTYPE = 1;
    }

    $no_urut = $d_IMPORTAUTOCOUNTER['nomor_urut']++;
    if ($r_cmp['kode'] == 'B-L-C') {
        // TAMBAH UNTUK BLEACHING
        $insert_recipeComponentBean = $insert_recipeComponentBean && $safeDb2("INSERT INTO RECIPECOMPONENTBEAN(FATHERID,
                                                                                                        IMPORTAUTOCOUNTER,
                                                                                                        OWNEDCOMPONENT,
                                                                                                        RECIPEITEMTYPECODE,
                                                                                                        RECIPESUBCODE01,
                                                                                                        RECIPESUFFIXCODE,
                                                                                                        GROUPNUMBER,
                                                                                                        GROUPTYPECODE,
                                                                                                        LINETYPE,
                                                                                                        SEQUENCE,
                                                                                                        SUBSEQUENCE,
                                                                                                        COMPONENTINCIDENCE,
                                                                                                        REFRECIPEGROUPNUMBER,
                                                                                                        REFRECIPESEQUENCE,
                                                                                                        REFRECIPESUBSEQUENCE,
                                                                                                        REFRECIPESTATUS,
                                                                                                        ITEMTYPEAFICODE,
                                                                                                        COMMENTLINE,
                                                                                                        ASSEMBLYUOMCODE,
                                                                                                        CONSUMPTION,
                                                                                                        WATERMANAGEMENT,
                                                                                                        BINDERFILLERCOMPONENT,
                                                                                                        PRODUCED,
                                                                                                        COSTINGPLANTCODE,
                                                                                                        FINALENGINEERINGCHANGE,
                                                                                                        INITIALDATE,
                                                                                                        FINALDATE,
                                                                                                        ALLOWDELETEBINDERFILLER,
                                                                                                        WSOPERATION,
                                                                                                        IMPORTSTATUS,
                                                                                                        IMPORTDATETIME,
                                                                                                        RETRYNR,
                                                                                                        NEXTRETRY,
                                                                                                        IMPORTID,
                                                                                                        RELATEDDEPENDENTID) 
                VALUES('$IMPORTAUTOCOUNTER',
                        '$no_urut',
                        '0',
                        'RFD',
                        '$RECIPESUBCODE01',
                        '$r_cmp_suhu_bleaching_rc_soaping[no_resep_convert]',
                        '5',
                        '100',
                        '3',
                        '10',
                        '10',
                        '100',
                        '0',
                        '0',
                        '0',
                        '0',
                        'DYC',
                        'BLEACHING LAB',
                        'l',
                        '0',
                        '0',
                        '0',
                        '0',
                        '001',
                        '9999999999',
                        '1970-01-01',
                        '2100-12-31',
                        '0',
                        '1',
                        '0',
                        '$tgl',
                        '3',
                        '0',
                        '0',
                        '$no_urut')");
        // TAMBAH UNTUK BLEACHING
    } elseif ($r_cmp['ket'] == 'Suhu') {
        // TAMBAH UNTUK COMMENT DITENGAH-TENGAH RESEP
        $_SEQUENCE = $SEQUENCE++ . '0';
        $commentname = str_replace("'", "`", $r_cmp['nama']);
        $insert_recipeComponentBean = $insert_recipeComponentBean && $safeDb2("INSERT INTO RECIPECOMPONENTBEAN(FATHERID,
                                                                                                    IMPORTAUTOCOUNTER,
                                                                                                    OWNEDCOMPONENT,
                                                                                                    RECIPEITEMTYPECODE,
                                                                                                    RECIPESUBCODE01,
                                                                                                    RECIPESUFFIXCODE,
                                                                                                    GROUPNUMBER,
                                                                                                    GROUPTYPECODE,
                                                                                                    LINETYPE,
                                                                                                    SEQUENCE,
                                                                                                    SUBSEQUENCE,
                                                                                                    COMPONENTINCIDENCE,
                                                                                                    REFRECIPEGROUPNUMBER,
                                                                                                    REFRECIPESEQUENCE,
                                                                                                    REFRECIPESUBSEQUENCE,
                                                                                                    REFRECIPESTATUS,
                                                                                                    ITEMTYPEAFICODE,
                                                                                                    COMMENTLINE,
                                                                                                    CONSUMPTIONTYPE,
                                                                                                    ASSEMBLYUOMCODE,
                                                                                                    CONSUMPTION,
                                                                                                    WATERMANAGEMENT,
                                                                                                    BINDERFILLERCOMPONENT,
                                                                                                    PRODUCED,
                                                                                                    COSTINGPLANTCODE,
                                                                                                    FINALENGINEERINGCHANGE,
                                                                                                    INITIALDATE,
                                                                                                    FINALDATE,
                                                                                                    ALLOWDELETEBINDERFILLER,
                                                                                                    WSOPERATION,
                                                                                                    IMPORTSTATUS,
                                                                                                    IMPORTDATETIME,
                                                                                                    RETRYNR,
                                                                                                    NEXTRETRY,
                                                                                                    IMPORTID,
                                                                                                    RELATEDDEPENDENTID) 
                VALUES('$IMPORTAUTOCOUNTER',
                        '$no_urut',
                        '0',
                        'RFD',
                        '$RECIPESUBCODE01',
                        '$r_cmp_suhu_bleaching_rc_soaping[no_resep_convert]',
                        '20',
                        '100',
                        '3',
                        '$_SEQUENCE',
                        '10',
                        '100',
                        '0',
                        '0',
                        '0',
                        '0',
                        'DYC',
                        '$commentname',
                        '$CONSUMPTIONTYPE',
                        'l',
                        '0',
                        '0',
                        '0',
                        '0',
                        '001',
                        '9999999999',
                        '1970-01-01',
                        '2100-12-31',
                        '0',
                        '1',
                        '0',
                        '$tgl',
                        '3',
                        '0',
                        '0',
                        '$no_urut')");
        // TAMBAH UNTUK COMMENT
    } else {
        if ($jenis_suffix == "1") {
            $RECIPESUBCODE01 = $r_cmp['recipe_code_1'];
        } elseif ($jenis_suffix == "2") {
            $RECIPESUBCODE01 = $r_cmp['recipe_code_2'];
        }
        $_SEQUENCE = $SEQUENCE++ . '0';
        $subcode01 = substr($r_code['code'], 0, 1);
        $subcode02 = substr($r_code['code'], 2, 1);
        $subcode03 = substr($r_code['code'], 4);
        $insert_recipeComponentBean = $insert_recipeComponentBean && $safeDb2("INSERT INTO RECIPECOMPONENTBEAN(FATHERID,
                                                                                    IMPORTAUTOCOUNTER,
                                                                                    OWNEDCOMPONENT,
                                                                                    RECIPEITEMTYPECODE,
                                                                                    RECIPESUBCODE01,
                                                                                    RECIPESUFFIXCODE,
                                                                                    GROUPNUMBER,
                                                                                    GROUPTYPECODE,
                                                                                    LINETYPE,
                                                                                    SEQUENCE,
                                                                                    SUBSEQUENCE,
                                                                                    COMPONENTINCIDENCE,
                                                                                    REFRECIPEGROUPNUMBER,
                                                                                    REFRECIPESEQUENCE,
                                                                                    REFRECIPESUBSEQUENCE,
                                                                                    REFRECIPESTATUS,
                                                                                    ITEMTYPEAFICODE,
                                                                                    SUBCODE01,
                                                                                    SUBCODE02,
                                                                                    SUBCODE03,
                                                                                    CONSUMPTIONTYPE,
                                                                                    ASSEMBLYUOMCODE,
                                                                                    COMPONENTUOMCODE,
                                                                                    COMPONENTUOMTYPE,
                                                                                    CONSUMPTION,
                                                                                    WATERMANAGEMENT,
                                                                                    BINDERFILLERCOMPONENT,
                                                                                    PRODUCED,
                                                                                    COSTINGPLANTCODE,
                                                                                    FINALENGINEERINGCHANGE,
                                                                                    INITIALDATE,
                                                                                    FINALDATE,
                                                                                    ALLOWDELETEBINDERFILLER,
                                                                                    WSOPERATION,
                                                                                    IMPORTSTATUS,
                                                                                    IMPORTDATETIME,
                                                                                    RETRYNR,
                                                                                    NEXTRETRY,
                                                                                    IMPORTID,
                                                                                    RELATEDDEPENDENTID,
                                                                                    IMPOPERATIONUSER) 
                VALUES(
                        '$IMPORTAUTOCOUNTER',
                        '$no_urut',
                        '0',
                        'RFD',
                        '$RECIPESUBCODE01', 
                        '$r_cmp[no_resep_convert]',
                        '15', 
                        '001',
                        '1',
                        '$_SEQUENCE',
                        '10',
                        '100', 
                        '0',
                        '0', 
                        '0', 
                        '0', 
                        'DYC', 
                        '$subcode01', 
                        '$subcode02', 
                        '$subcode03', 
                        '$CONSUMPTIONTYPE', 
                        'l', 
                        'g', 
                        '1', 
                        '$concVal', 
                        '1', 
                        '0', 
                        '0', 
                        '001', 
                        '9999999999', 
                        '1970-01-01', 
                        '2100-12-31', 
                        '0', 
                        '1', 
                        '0', 
                        '$tgl', 
                        '3', 
                        '0', 
                        '0', 
                        '$no_urut', 
                        '$userLogin')");
    }
}

if ($jenis_suffix == "1") {
    $where_suhu = "CASE
                        WHEN LEFT(tsm.idm, 2) = 'DR' THEN CONCAT(TRIM(MAX(tsm.tside_c)),'`C X ', TRIM(MAX(tsm.tside_min)), ' MNT')
                        WHEN LEFT(tsm.idm, 2) = 'R2' THEN CONCAT(TRIM(MAX(tsm.cside_c)),'`C X ', TRIM(MAX(tsm.cside_min)), ' MNT')
                        WHEN LEFT(tsm.idm, 2) = 'CD' THEN CONCAT(TRIM(MAX(tsm.tside_c)),'`C X ', TRIM(MAX(tsm.tside_min)), ' MNT')
                        WHEN LEFT(tsm.idm, 2) = 'D2' THEN CONCAT(TRIM(MAX(tsm.tside_c)),'`C X ', TRIM(MAX(tsm.tside_min)), ' MNT')
                        WHEN LEFT(tsm.idm, 2) = 'A2' THEN
                            CASE
                                WHEN MAX(tsm.tside_c) = 0 AND MAX(tsm.tside_min) = 0 THEN CONCAT(TRIM(MAX(tsm.cside_c)),'`C X ', TRIM(MAX(tsm.cside_min)), ' MNT')
                                ELSE CONCAT(TRIM(MAX(tsm.tside_c)),'`C X ', TRIM(MAX(tsm.tside_min)), ' MNT')
                            END
                        WHEN LEFT(tsm.idm, 2) = 'OB' THEN
                            CASE
                                WHEN MAX(tsm.tside_c) = 0 AND MAX(tsm.tside_min) = 0 THEN CONCAT(TRIM(MAX(tsm.cside_c)),'`C X ', TRIM(MAX(tsm.cside_min)), ' MNT')
                                ELSE CONCAT(TRIM(MAX(tsm.tside_c)),'`C X ', TRIM(MAX(tsm.tside_min)), ' MNT')
                            END
                    END AS COMMENTLINE";
    $where_soaping   = "and (left(tsm.idm, 2) = 'R2' or left(tsm.idm, 2) = 'A2') and (not left(tsm.idm, 2) = 'D2' or not left(tsm.idm, 2) = 'DR')";
    $where_rc        = "and (left(tsm.idm, 2) = 'CD' or left(tsm.idm, 2) = 'D2' or left(tsm.idm, 2) = 'DR' or left(tsm.idm, 2) = 'A2') and not tsm.rc_tm = 0";
    $where_bleaching = "and (left(tsm.idm, 2) = 'CD' or left(tsm.idm, 2) = 'D2' or left(tsm.idm, 2) = 'DR') and not tsm.bleaching_tm = 0";

} elseif ($jenis_suffix == "2") {
    $where_suhu = "CONCAT(TRIM(MAX(tsm.cside_c)),'`C X ', TRIM(MAX(tsm.cside_min)), ' MNT') AS COMMENTLINE";
    $where_soaping   = "and (left(tsm.idm, 2) = 'R2' or left(tsm.idm, 3) = 'DR2' or left(tsm.idm, 2) = 'A2')";
    $where_rc        = "and not (left(tsm.idm, 2) = 'CD' or left(tsm.idm, 2) = 'D2' or left(tsm.idm, 2) = 'DR') and not tsm.rc_tm = 0";
    $where_bleaching = "and not (left(tsm.idm, 2) = 'CD' or left(tsm.idm, 2) = 'D2' or left(tsm.idm, 2) = 'DR') and not tsm.bleaching_tm = 0";
}


// EXPORT COMMENT
$sql_suhu_menit = sqlsrv_query($con, "SELECT 
                                        MAX(b.recipe_code) as recipe_code,
                                        -- recipe_code_1 (kata pertama)
                                        CASE
                                            WHEN CHARINDEX(' ', MAX(b.recipe_code) + ' ') > 0
                                                THEN LEFT(MAX(b.recipe_code), CHARINDEX(' ', MAX(b.recipe_code) + ' ') - 1)
                                            ELSE MAX(b.recipe_code)
                                        END as recipe_code_1,
                                        -- recipe_code_2 (kata kedua)
                                        CASE
                                            WHEN CHARINDEX(' ', MAX(b.recipe_code) + ' ') > 0 THEN
                                                CASE
                                                    WHEN CHARINDEX(' ', SUBSTRING(MAX(b.recipe_code) + ' ', CHARINDEX(' ', MAX(b.recipe_code) + ' ') + 1, 8000)) > 0
                                                        THEN LEFT(
                                                            SUBSTRING(MAX(b.recipe_code) + ' ', CHARINDEX(' ', MAX(b.recipe_code) + ' ') + 1, 8000),
                                                            CHARINDEX(' ', SUBSTRING(MAX(b.recipe_code) + ' ', CHARINDEX(' ', MAX(b.recipe_code) + ' ') + 1, 8000)) - 1
                                                        )
                                                    ELSE SUBSTRING(MAX(b.recipe_code) + ' ', CHARINDEX(' ', MAX(b.recipe_code) + ' ') + 1, 8000)
                                                END
                                            ELSE ''
                                        END as recipe_code_2,
                                        -- no_resep_convert (tetap logika kamu)
                                        CASE
                                            WHEN SUBSTRING(MAX(b.no_resep), 1,2) = 'DR' OR SUBSTRING(MAX(b.no_resep), 1,2) = 'CD' OR SUBSTRING(MAX(b.no_resep), 1,2) = 'OB'
                                                THEN SUBSTRING(CONCAT(SUBSTRING(MAX(b.no_resep), 3, LEN(MAX(b.no_resep))), 'L'), 4, 8000)
                                            WHEN SUBSTRING(MAX(b.no_resep), 1,2) = 'D2' OR SUBSTRING(MAX(b.no_resep), 1,2) = 'R2' OR SUBSTRING(MAX(b.no_resep), 1,2) = 'A2'
                                                THEN SUBSTRING(CONCAT(SUBSTRING(MAX(b.no_resep), 2, LEN(MAX(b.no_resep))), 'L'), 4, 8000)
                                        END as no_resep_convert,
                                        $where_suhu
                                    FROM db_laborat.tbl_status_matching tsm 
                                    LEFT JOIN db_laborat.tbl_matching b ON b.no_resep = tsm.idm
                                    LEFT JOIN db_laborat.tbl_matching_detail a ON a.id_matching = b.id
                                    WHERE tsm.idm = '$number_suffix'
                                    GROUP BY tsm.idm
                                    UNION
                                    SELECT
                                        MAX(b.recipe_code) as recipe_code,
                                        CASE
                                            WHEN CHARINDEX(' ', MAX(b.recipe_code) + ' ') > 0
                                                THEN LEFT(MAX(b.recipe_code), CHARINDEX(' ', MAX(b.recipe_code) + ' ') - 1)
                                            ELSE MAX(b.recipe_code)
                                        END as recipe_code_1,
                                        CASE
                                            WHEN CHARINDEX(' ', MAX(b.recipe_code) + ' ') > 0 THEN
                                                CASE
                                                    WHEN CHARINDEX(' ', SUBSTRING(MAX(b.recipe_code) + ' ', CHARINDEX(' ', MAX(b.recipe_code) + ' ') + 1, 8000)) > 0
                                                        THEN LEFT(
                                                            SUBSTRING(MAX(b.recipe_code) + ' ', CHARINDEX(' ', MAX(b.recipe_code) + ' ') + 1, 8000),
                                                            CHARINDEX(' ', SUBSTRING(MAX(b.recipe_code) + ' ', CHARINDEX(' ', MAX(b.recipe_code) + ' ') + 1, 8000)) - 1
                                                        )
                                                    ELSE SUBSTRING(MAX(b.recipe_code) + ' ', CHARINDEX(' ', MAX(b.recipe_code) + ' ') + 1, 8000)
                                                END
                                            ELSE ''
                                        END as recipe_code_2,
                                        CASE
                                            WHEN SUBSTRING(MAX(b.no_resep), 1,2) = 'DR' OR SUBSTRING(MAX(b.no_resep), 1,2) = 'CD' OR SUBSTRING(MAX(b.no_resep), 1,2) = 'OB'
                                                THEN SUBSTRING(CONCAT(SUBSTRING(MAX(b.no_resep), 3, LEN(MAX(b.no_resep))), 'L'), 4, 8000)
                                            WHEN SUBSTRING(MAX(b.no_resep), 1,2) = 'D2' OR SUBSTRING(MAX(b.no_resep), 1,2) = 'R2' OR SUBSTRING(MAX(b.no_resep), 1,2) = 'A2'
                                                THEN SUBSTRING(CONCAT(SUBSTRING(MAX(b.no_resep), 2, LEN(MAX(b.no_resep))), 'L'), 4, 8000)
                                        END as no_resep_convert,
                                        CASE
                                            WHEN TRIM(MAX(tsm.soaping_sh)) = '80' THEN CONCAT('CUCI PANAS ', TRIM(MAX(tsm.soaping_sh)),'`C X ', TRIM(MAX(tsm.soaping_tm)), ' MNT')
                                            ELSE CONCAT('SOAPING ', TRIM(MAX(tsm.soaping_sh)),'`C X ', TRIM(MAX(tsm.soaping_tm)), ' MNT')
                                        END AS COMMENTLINE
                                    FROM db_laborat.tbl_status_matching tsm 
                                    LEFT JOIN db_laborat.tbl_matching b ON b.no_resep = tsm.idm
                                    LEFT JOIN db_laborat.tbl_matching_detail a ON a.id_matching = b.id
                                    WHERE tsm.idm = '$number_suffix' $where_soaping
                                    GROUP BY b.no_resep
                                    UNION
                                    SELECT
                                        MAX(b.recipe_code) as recipe_code,
                                        CASE
                                            WHEN CHARINDEX(' ', MAX(b.recipe_code) + ' ') > 0
                                                THEN LEFT(MAX(b.recipe_code), CHARINDEX(' ', MAX(b.recipe_code) + ' ') - 1)
                                            ELSE MAX(b.recipe_code)
                                        END as recipe_code_1,
                                        CASE
                                            WHEN CHARINDEX(' ', MAX(b.recipe_code) + ' ') > 0 THEN
                                                CASE
                                                    WHEN CHARINDEX(' ', SUBSTRING(MAX(b.recipe_code) + ' ', CHARINDEX(' ', MAX(b.recipe_code) + ' ') + 1, 8000)) > 0
                                                        THEN LEFT(
                                                            SUBSTRING(MAX(b.recipe_code) + ' ', CHARINDEX(' ', MAX(b.recipe_code) + ' ') + 1, 8000),
                                                            CHARINDEX(' ', SUBSTRING(MAX(b.recipe_code) + ' ', CHARINDEX(' ', MAX(b.recipe_code) + ' ') + 1, 8000)) - 1
                                                        )
                                                    ELSE SUBSTRING(MAX(b.recipe_code) + ' ', CHARINDEX(' ', MAX(b.recipe_code) + ' ') + 1, 8000)
                                                END
                                            ELSE ''
                                        END as recipe_code_2,
                                        CASE
                                            WHEN SUBSTRING(MAX(b.no_resep), 1,2) = 'DR' OR SUBSTRING(MAX(b.no_resep), 1,2) = 'CD' OR SUBSTRING(MAX(b.no_resep), 1,2) = 'OB'
                                                THEN SUBSTRING(CONCAT(SUBSTRING(MAX(b.no_resep), 3, LEN(MAX(b.no_resep))), 'L'), 4, 8000)
                                            WHEN SUBSTRING(MAX(b.no_resep), 1,2) = 'D2' OR SUBSTRING(MAX(b.no_resep), 1,2) = 'R2' OR SUBSTRING(MAX(b.no_resep), 1,2) = 'A2'
                                                THEN SUBSTRING(CONCAT(SUBSTRING(MAX(b.no_resep), 2, LEN(MAX(b.no_resep))), 'L'), 4, 8000)
                                        END as no_resep_convert,
                                        CONCAT('RC ', TRIM(MAX(tsm.rc_sh)),'`C X ', TRIM(MAX(tsm.rc_tm)), ' MNT') as COMMENTLINE
                                    FROM db_laborat.tbl_status_matching tsm 
                                    LEFT JOIN db_laborat.tbl_matching b ON b.no_resep = tsm.idm
                                    LEFT JOIN db_laborat.tbl_matching_detail a ON a.id_matching = b.id
                                    WHERE tsm.idm = '$number_suffix' $where_rc
                                    GROUP BY b.no_resep
                                    UNION
                                    SELECT
                                        MAX(b.recipe_code) as recipe_code,
                                        CASE
                                            WHEN CHARINDEX(' ', MAX(b.recipe_code) + ' ') > 0
                                                THEN LEFT(MAX(b.recipe_code), CHARINDEX(' ', MAX(b.recipe_code) + ' ') - 1)
                                            ELSE MAX(b.recipe_code)
                                        END as recipe_code_1,
                                        CASE
                                            WHEN CHARINDEX(' ', MAX(b.recipe_code) + ' ') > 0 THEN
                                                CASE
                                                    WHEN CHARINDEX(' ', SUBSTRING(MAX(b.recipe_code) + ' ', CHARINDEX(' ', MAX(b.recipe_code) + ' ') + 1, 8000)) > 0
                                                        THEN LEFT(
                                                            SUBSTRING(MAX(b.recipe_code) + ' ', CHARINDEX(' ', MAX(b.recipe_code) + ' ') + 1, 8000),
                                                            CHARINDEX(' ', SUBSTRING(MAX(b.recipe_code) + ' ', CHARINDEX(' ', MAX(b.recipe_code) + ' ') + 1, 8000)) - 1
                                                        )
                                                    ELSE SUBSTRING(MAX(b.recipe_code) + ' ', CHARINDEX(' ', MAX(b.recipe_code) + ' ') + 1, 8000)
                                                END
                                            ELSE ''
                                        END as recipe_code_2,
                                        CASE
                                            WHEN SUBSTRING(MAX(b.no_resep), 1,2) = 'DR' OR SUBSTRING(MAX(b.no_resep), 1,2) = 'CD' OR SUBSTRING(MAX(b.no_resep), 1,2) = 'OB'
                                                THEN CONCAT(SUBSTRING(MAX(b.no_resep), 3, LEN(MAX(b.no_resep))), 'L')
                                            WHEN SUBSTRING(MAX(b.no_resep), 1,2) = 'D2' OR SUBSTRING(MAX(b.no_resep), 1,2) = 'R2' OR SUBSTRING(MAX(b.no_resep), 1,2) = 'A2'
                                                THEN CONCAT(SUBSTRING(MAX(b.no_resep), 2, LEN(MAX(b.no_resep))), 'L')
                                        END as no_resep_convert,
                                        CONCAT('BLEACHING ', TRIM(MAX(tsm.bleaching_sh)),'`C X ', TRIM(MAX(tsm.bleaching_tm)), ' MNT') as COMMENTLINE
                                    FROM db_laborat.tbl_status_matching tsm 
                                    LEFT JOIN db_laborat.tbl_matching b ON b.no_resep = tsm.idm
                                    LEFT JOIN db_laborat.tbl_matching_detail a ON a.id_matching = b.id
                                    WHERE tsm.idm = '$number_suffix' $where_bleaching 
                                    GROUP BY b.no_resep");
$sql_suhu_menit or die("Query suhu/menit gagal:\n".print_r(sqlsrv_errors(),true));


$GROUPNUMBER = 2;
while ($r_cmp_suhu_bleaching_rc_soaping = sqlsrv_fetch_array($sql_suhu_menit, SQLSRV_FETCH_ASSOC)) {
    $no_urut = $d_IMPORTAUTOCOUNTER['nomor_urut']++;

    if ($jenis_suffix == "1") {
        $RECIPESUBCODE01 = $r_cmp_suhu_bleaching_rc_soaping['recipe_code_1'];
    } elseif ($jenis_suffix == "2") {
        $RECIPESUBCODE01 = $r_cmp_suhu_bleaching_rc_soaping['recipe_code_2'];
    }
    $_GROUPNUMBER = $GROUPNUMBER++ . '0';

    if ($r_cmp_suhu_bleaching_rc_soaping['COMMENTLINE'] != '0`C X 0 MNT' or $r_cmp_suhu_bleaching_rc_soaping['COMMENTLINE'] != 'SOAPING 0`C X 0 MNT' or $r_cmp_suhu_bleaching_rc_soaping['COMMENTLINE'] != 'BLEACHING 0`C X 0 MNT') { //kalau suhu dan menitnya kosong maka tidak usah di export
        $insert_recipeComponentBean = $insert_recipeComponentBean && $safeDb2("INSERT INTO RECIPECOMPONENTBEAN(FATHERID,
                                                                                                            IMPORTAUTOCOUNTER,
                                                                                                            OWNEDCOMPONENT,
                                                                                                            RECIPEITEMTYPECODE,
                                                                                                            RECIPESUBCODE01,
                                                                                                            RECIPESUFFIXCODE,
                                                                                                            GROUPNUMBER,
                                                                                                            GROUPTYPECODE,
                                                                                                            LINETYPE,
                                                                                                            SEQUENCE,
                                                                                                            SUBSEQUENCE,
                                                                                                            COMPONENTINCIDENCE,
                                                                                                            REFRECIPEGROUPNUMBER,
                                                                                                            REFRECIPESEQUENCE,
                                                                                                            REFRECIPESUBSEQUENCE,
                                                                                                            REFRECIPESTATUS,
                                                                                                            ITEMTYPEAFICODE,
                                                                                                            COMMENTLINE,
                                                                                                            CONSUMPTIONTYPE,
                                                                                                            ASSEMBLYUOMCODE,
                                                                                                            CONSUMPTION,
                                                                                                            WATERMANAGEMENT,
                                                                                                            BINDERFILLERCOMPONENT,
                                                                                                            PRODUCED,
                                                                                                            COSTINGPLANTCODE,
                                                                                                            FINALENGINEERINGCHANGE,
                                                                                                            INITIALDATE,
                                                                                                            FINALDATE,
                                                                                                            ALLOWDELETEBINDERFILLER,
                                                                                                            WSOPERATION,
                                                                                                            IMPORTSTATUS,
                                                                                                            IMPORTDATETIME,
                                                                                                            RETRYNR,
                                                                                                            NEXTRETRY,
                                                                                                            IMPORTID,
                                                                                                            RELATEDDEPENDENTID) 
                    VALUES('$IMPORTAUTOCOUNTER',
                            '$no_urut',
                            '0',
                            'RFD',
                            '$RECIPESUBCODE01',
                            '$r_cmp_suhu_bleaching_rc_soaping[no_resep_convert]',
                            '$_GROUPNUMBER',
                            '100',
                            '3',
                            '10',
                            '10',
                            '100',
                            '0',
                            '0',
                            '0',
                            '0',
                            'DYC',
                            '$r_cmp_suhu_bleaching_rc_soaping[COMMENTLINE]',
                            '$CONSUMPTIONTYPE',
                            'l',
                            '0',
                            '0',
                            '0',
                            '0',
                            '001',
                            '9999999999',
                            '1970-01-01',
                            '2100-12-31',
                            '0',
                            '1',
                            '0',
                            '$tgl',
                            '3',
                            '0',
                            '0',
                            '$no_urut')");
    }
}

$no_urut_terakhir = $no_urut + 1;
$q_update_no_urut = sqlsrv_query($con, "UPDATE db_laborat.importautocounter SET nomor_urut=? WHERE id=1", [$no_urut_terakhir]);


// EXPORT COMMENT
// PROSES EXPORT RECIPE COMPONENT

// PROSES EXPORT RECIPE ADDITIONAL DATA
$recipe_add = sqlsrv_query($con,"SELECT 
                                        CONCAT(10000, a.id) AS id_matching_detail,
                                        c.approve_at,
                                        SUBSTRING(b.no_warna, 1, 16) AS no_warna_substring,
                                        LEFT(b.no_item, 3) AS no_item2,
                                        SUBSTRING(b.benang, 1, 250) AS benang_substring,
                                        b.no_resep,
                                        a.*, b.*, c.*
                                    FROM db_laborat.tbl_matching_detail a
                                    RIGHT JOIN db_laborat.tbl_matching b ON b.id = a.id_matching
                                    LEFT JOIN db_laborat.tbl_status_matching c ON c.idm = b.no_resep 
                                    WHERE a.id_matching = ? AND a.id_status = ?", [$idmatching,$idstatus]);
$d_add = sqlsrv_fetch_array($recipe_add, SQLSRV_FETCH_ASSOC);
$tgl_approve = null;
if ($d_add && isset($d_add['approve_at'])) {
    if ($d_add['approve_at'] instanceof DateTime) {
        $tgl_approve = $d_add['approve_at']->format('Y-m-d');
    } else {
        $tgl_approve = date('Y-m-d', strtotime((string)$d_add['approve_at']));
    }
}

$insert_adstoragebean1 = $safeDb2("INSERT INTO ADSTORAGEBEAN(FATHERID,
                                                                                IMPORTAUTOCOUNTER,
                                                                                NAMEENTITYNAME,
                                                                                NAMENAME,
                                                                                FIELDNAME,
                                                                                VALUESTRING,
                                                                                VALUEINT,
                                                                                VALUEBOOLEAN,
                                                                                VALUEDATE,
                                                                                VALUEDECIMAL,
                                                                                VALUELONG,
                                                                                VALUETIME,
                                                                                VALUETIMESTAMP,
                                                                                WSOPERATION,
                                                                                IMPOPERATIONUSER,
                                                                                IMPORTSTATUS,
                                                                                IMPCREATIONDATETIME,
                                                                                IMPCREATIONUSER,
                                                                                IMPLASTUPDATEDATETIME,
                                                                                IMPLASTUPDATEUSER,
                                                                                IMPORTDATETIME,
                                                                                RETRYNR,
                                                                                NEXTRETRY,
                                                                                IMPORTID)
                                                            VALUES($IMPORTAUTOCOUNTER, 
                                                                    $d_add[id_matching_detail],
                                                                    'Recipe',
                                                                    'ApprovalDate',
                                                                    'ApprovalDate',
                                                                    NULL,
                                                                    0,
                                                                    0,
                                                                    '$tgl_approve',
                                                                    NULL,
                                                                    0,
                                                                    NULL,
                                                                    NULL,
                                                                    5,
                                                                    NULL,
                                                                    0,
                                                                    NULL,
                                                                    NULL,
                                                                    NULL,
                                                                    NULL,
                                                                    NULL,
                                                                    3,
                                                                    0,
                                                                    0)");
$insert_adstoragebean2 = $safeDb2("INSERT INTO ADSTORAGEBEAN(FATHERID,
                                                                                IMPORTAUTOCOUNTER,
                                                                                NAMEENTITYNAME,
                                                                                NAMENAME,
                                                                                FIELDNAME,
                                                                                VALUESTRING,
                                                                                VALUEINT,
                                                                                VALUEBOOLEAN,
                                                                                VALUEDATE,
                                                                                VALUEDECIMAL,
                                                                                VALUELONG,
                                                                                VALUETIME,
                                                                                VALUETIMESTAMP,
                                                                                WSOPERATION,
                                                                                IMPOPERATIONUSER,
                                                                                IMPORTSTATUS,
                                                                                IMPCREATIONDATETIME,
                                                                                IMPCREATIONUSER,
                                                                                IMPLASTUPDATEDATETIME,
                                                                                IMPLASTUPDATEUSER,
                                                                                IMPORTDATETIME,
                                                                                RETRYNR,
                                                                                NEXTRETRY,
                                                                                IMPORTID)
                                                            VALUES($IMPORTAUTOCOUNTER, 
                                                                    $d_add[id_matching_detail]+1,
                                                                    'Recipe',
                                                                    'ArticleGroup',
                                                                    'ArticleGroupCode',
                                                                    '$d_add[no_item2]',
                                                                    0,
                                                                    0,
                                                                    NULL,
                                                                    NULL,
                                                                    0,
                                                                    NULL,
                                                                    NULL,
                                                                    5,
                                                                    NULL,
                                                                    0,
                                                                    NULL,
                                                                    NULL,
                                                                    NULL,
                                                                    NULL,
                                                                    NULL,
                                                                    3,
                                                                    0,
                                                                    0)");

$insert_adstoragebean3 = $safeDb2("INSERT INTO ADSTORAGEBEAN(FATHERID,
                                                                                IMPORTAUTOCOUNTER,
                                                                                NAMEENTITYNAME,
                                                                                NAMENAME,
                                                                                FIELDNAME,
                                                                                VALUESTRING,
                                                                                VALUEINT,
                                                                                VALUEBOOLEAN,
                                                                                VALUEDATE,
                                                                                VALUEDECIMAL,
                                                                                VALUELONG,
                                                                                VALUETIME,
                                                                                VALUETIMESTAMP,
                                                                                WSOPERATION,
                                                                                IMPOPERATIONUSER,
                                                                                IMPORTSTATUS,
                                                                                IMPCREATIONDATETIME,
                                                                                IMPCREATIONUSER,
                                                                                IMPLASTUPDATEDATETIME,
                                                                                IMPLASTUPDATEUSER,
                                                                                IMPORTDATETIME,
                                                                                RETRYNR,
                                                                                NEXTRETRY,
                                                                                IMPORTID)
                                                            VALUES($IMPORTAUTOCOUNTER,
                                                                    $d_add[id_matching_detail]+2,
                                                                    'Recipe',
                                                                    'CarryOver',
                                                                    'CarryOver',
                                                                    NULL,
                                                                    0,
                                                                    0,
                                                                    NULL,
                                                                    NULL,
                                                                    0,
                                                                    NULL,
                                                                    NULL,
                                                                    5,
                                                                    NULL,
                                                                    0,
                                                                    NULL,
                                                                    NULL,
                                                                    NULL,
                                                                    NULL,
                                                                    NULL, 
                                                                    3,
                                                                    0,
                                                                    0)");

$insert_adstoragebean4 = $safeDb2("INSERT INTO ADSTORAGEBEAN(FATHERID,
                                                                                IMPORTAUTOCOUNTER,
                                                                                NAMEENTITYNAME,
                                                                                NAMENAME,
                                                                                FIELDNAME,
                                                                                VALUESTRING,
                                                                                VALUEINT,
                                                                                VALUEBOOLEAN,
                                                                                VALUEDATE,
                                                                                VALUEDECIMAL,
                                                                                VALUELONG,
                                                                                VALUETIME,
                                                                                VALUETIMESTAMP,
                                                                                WSOPERATION,
                                                                                IMPOPERATIONUSER,
                                                                                IMPORTSTATUS,
                                                                                IMPCREATIONDATETIME,
                                                                                IMPCREATIONUSER,
                                                                                IMPLASTUPDATEDATETIME,
                                                                                IMPLASTUPDATEUSER,
                                                                                IMPORTDATETIME,
                                                                                RETRYNR,
                                                                                NEXTRETRY,
                                                                                IMPORTID)
                                                                    VALUES($IMPORTAUTOCOUNTER, 
                                                                            $d_add[id_matching_detail]+3,
                                                                            'Recipe',
                                                                            'Chroma',
                                                                            'Chroma',
                                                                            NULL,
                                                                            0,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            5,
                                                                            NULL,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            3,
                                                                            0,
                                                                            0)");

$insert_adstoragebean5 = $safeDb2("INSERT INTO ADSTORAGEBEAN(FATHERID,
                                                                                IMPORTAUTOCOUNTER,
                                                                                NAMEENTITYNAME,
                                                                                NAMENAME,
                                                                                FIELDNAME,
                                                                                VALUESTRING,
                                                                                VALUEINT,
                                                                                VALUEBOOLEAN,
                                                                                VALUEDATE,
                                                                                VALUEDECIMAL,
                                                                                VALUELONG,
                                                                                VALUETIME,
                                                                                VALUETIMESTAMP,
                                                                                WSOPERATION,
                                                                                IMPOPERATIONUSER,
                                                                                IMPORTSTATUS,
                                                                                IMPCREATIONDATETIME,
                                                                                IMPCREATIONUSER,
                                                                                IMPLASTUPDATEDATETIME,
                                                                                IMPLASTUPDATEUSER,
                                                                                IMPORTDATETIME,
                                                                                RETRYNR,
                                                                                NEXTRETRY,
                                                                                IMPORTID)
                                                                    VALUES($IMPORTAUTOCOUNTER,
                                                                            $d_add[id_matching_detail]+4,
                                                                            'Recipe',
                                                                            'LDGrouping',
                                                                            'LDGrouping',
                                                                            '$d_add[no_warna_substring]',
                                                                            0,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            5,
                                                                            NULL,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            3,
                                                                            0,
                                                                            0)");

$insert_adstoragebean6 = $safeDb2("INSERT INTO ADSTORAGEBEAN(FATHERID,
                                                                                IMPORTAUTOCOUNTER,
                                                                                NAMEENTITYNAME,
                                                                                NAMENAME,
                                                                                FIELDNAME,
                                                                                VALUESTRING,
                                                                                VALUEINT,
                                                                                VALUEBOOLEAN,
                                                                                VALUEDATE,
                                                                                VALUEDECIMAL,
                                                                                VALUELONG,
                                                                                VALUETIME,
                                                                                VALUETIMESTAMP,
                                                                                WSOPERATION,
                                                                                IMPOPERATIONUSER,
                                                                                IMPORTSTATUS,
                                                                                IMPCREATIONDATETIME,
                                                                                IMPCREATIONUSER,
                                                                                IMPLASTUPDATEDATETIME,
                                                                                IMPLASTUPDATEUSER,
                                                                                IMPORTDATETIME,
                                                                                RETRYNR,
                                                                                NEXTRETRY,
                                                                                IMPORTID)
                                                                    VALUES($IMPORTAUTOCOUNTER,
                                                                            $d_add[id_matching_detail]+5,
                                                                            'Recipe',
                                                                            'OptionApproved',
                                                                            'OptionApproved',
                                                                            NULL,
                                                                            0,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            5,
                                                                            NULL,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            3,
                                                                            0,
                                                                            0)");

$insert_adstoragebean7 = $safeDb2("INSERT INTO ADSTORAGEBEAN(FATHERID,
                                                                                IMPORTAUTOCOUNTER,
                                                                                NAMEENTITYNAME,
                                                                                NAMENAME,
                                                                                FIELDNAME,
                                                                                VALUESTRING,
                                                                                VALUEINT,
                                                                                VALUEBOOLEAN,
                                                                                VALUEDATE,
                                                                                VALUEDECIMAL,
                                                                                VALUELONG,
                                                                                VALUETIME,
                                                                                VALUETIMESTAMP,
                                                                                WSOPERATION,
                                                                                IMPOPERATIONUSER,
                                                                                IMPORTSTATUS,
                                                                                IMPCREATIONDATETIME,
                                                                                IMPCREATIONUSER,
                                                                                IMPLASTUPDATEDATETIME,
                                                                                IMPLASTUPDATEUSER,
                                                                                IMPORTDATETIME,
                                                                                RETRYNR,
                                                                                NEXTRETRY,
                                                                                IMPORTID)
                                                                    VALUES($IMPORTAUTOCOUNTER,
                                                                            $d_add[id_matching_detail]+6,
                                                                            'Recipe',
                                                                            'OriginalCustomer',
                                                                            'OriginalCustomerType',
                                                                            '',
                                                                            0,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            5,
                                                                            NULL,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            3,
                                                                            0,
                                                                            0)");

$insert_adstoragebean8 = $safeDb2("INSERT INTO ADSTORAGEBEAN(FATHERID,
                                                                                IMPORTAUTOCOUNTER,
                                                                                NAMEENTITYNAME,
                                                                                NAMENAME,
                                                                                FIELDNAME,
                                                                                VALUESTRING,
                                                                                VALUEINT,
                                                                                VALUEBOOLEAN,
                                                                                VALUEDATE,
                                                                                VALUEDECIMAL,
                                                                                VALUELONG,
                                                                                VALUETIME,
                                                                                VALUETIMESTAMP,
                                                                                WSOPERATION,
                                                                                IMPOPERATIONUSER,
                                                                                IMPORTSTATUS,
                                                                                IMPCREATIONDATETIME,
                                                                                IMPCREATIONUSER,
                                                                                IMPLASTUPDATEDATETIME,
                                                                                IMPLASTUPDATEUSER,
                                                                                IMPORTDATETIME,
                                                                                RETRYNR,
                                                                                NEXTRETRY,
                                                                                IMPORTID)
                                                                    VALUES($IMPORTAUTOCOUNTER,
                                                                            $d_add[id_matching_detail]+7,
                                                                            'Recipe',
                                                                            'OriginalCustomer',
                                                                            'OriginalCustomerCode',
                                                                            '',
                                                                            0,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            5,
                                                                            NULL,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            3,
                                                                            0,
                                                                            0)");

$benang = addslashes($d_add['benang_substring']);
$benang2 = db2_escape_string($benang);
$insert_adstoragebean9 = $safeDb2("INSERT INTO ADSTORAGEBEAN(FATHERID,
                                                                                IMPORTAUTOCOUNTER,
                                                                                NAMEENTITYNAME,
                                                                                NAMENAME,
                                                                                FIELDNAME,
                                                                                VALUESTRING,
                                                                                VALUEINT,
                                                                                VALUEBOOLEAN,
                                                                                VALUEDATE,
                                                                                VALUEDECIMAL,
                                                                                VALUELONG,
                                                                                VALUETIME,
                                                                                VALUETIMESTAMP,
                                                                                WSOPERATION,
                                                                                IMPOPERATIONUSER,
                                                                                IMPORTSTATUS,
                                                                                IMPCREATIONDATETIME,
                                                                                IMPCREATIONUSER,
                                                                                IMPLASTUPDATEDATETIME,
                                                                                IMPLASTUPDATEUSER,
                                                                                IMPORTDATETIME,
                                                                                RETRYNR,
                                                                                NEXTRETRY,
                                                                                IMPORTID)
                                                                    VALUES($IMPORTAUTOCOUNTER,
                                                                            $d_add[id_matching_detail]+8,
                                                                            'Recipe',
                                                                            'YarnDescription',
                                                                            'YarnDescription',
                                                                            '$benang2',
                                                                            0,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            5,
                                                                            NULL,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            3,
                                                                            0,
                                                                            0)");
$insert_adstoragebean10 = $safeDb2("INSERT INTO ADSTORAGEBEAN(FATHERID,
                                                                                IMPORTAUTOCOUNTER,
                                                                                NAMEENTITYNAME,
                                                                                NAMENAME,
                                                                                FIELDNAME,
                                                                                VALUESTRING,
                                                                                VALUEINT,
                                                                                VALUEBOOLEAN,
                                                                                VALUEDATE,
                                                                                VALUEDECIMAL,
                                                                                VALUELONG,
                                                                                VALUETIME,
                                                                                VALUETIMESTAMP,
                                                                                WSOPERATION,
                                                                                IMPOPERATIONUSER,
                                                                                IMPORTSTATUS,
                                                                                IMPCREATIONDATETIME,
                                                                                IMPCREATIONUSER,
                                                                                IMPLASTUPDATEDATETIME,
                                                                                IMPLASTUPDATEUSER,
                                                                                IMPORTDATETIME,
                                                                                RETRYNR,
                                                                                NEXTRETRY,
                                                                                IMPORTID)
                                                                    VALUES($IMPORTAUTOCOUNTER,
                                                                            $d_add[id_matching_detail]+9,
                                                                            'Recipe',
                                                                            'RCode',
                                                                            'RCode',
                                                                            '$d_add[no_resep]',
                                                                            0,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            5,
                                                                            NULL,
                                                                            0,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            NULL,
                                                                            3,
                                                                            0,
                                                                            0)");
// PROSES EXPORT RECIPE ADDITIONAL DATA

// STATUS EXPORT SAMPAI TAHAP APA
if ($insert_recipeBean && $insert_recipeComponentBean && $insert_adstoragebean1 && $insert_adstoragebean2 && $insert_adstoragebean3 && $insert_adstoragebean4 && $insert_adstoragebean5 && $insert_adstoragebean6 && $insert_adstoragebean7 && $insert_adstoragebean8 && $insert_adstoragebean9) {
    header("location: index1.php?p=Detail-status-approved&idm=$idstatus&upload=1&available=$warning"); // RECIPE & RECIPE COMPONENT & ADSTORAGE
} elseif ($insert_recipeBean) {
    // jika RECIPE ok tapi komponen gagal, tampilkan errornya
    if (!$insert_recipeComponentBean && !empty($db2_errors)) {
        echo "Insert RECIPECOMPONENTBEAN gagal.<br>Detail:<pre>".htmlentities(print_r($db2_errors,true))."</pre>";
    } else {
        header("location: index1.php?p=Detail-status-approved&idm=$idstatus&upload=2&available=$warning"); // RECIPE
    }
} elseif ($insert_recipeComponentBean) {
    header("location: index1.php?p=Detail-status-approved&idm=$idstatus&upload=3&available=$warning"); // RECIPE COMPONENT
} else {
    // tampilkan alasan gagalnya komponen agar tidak silent failure
    if (!empty($db2_errors)) {
        echo "Insert RECIPECOMPONENTBEAN gagal.<br>Detail:<pre>".htmlentities(print_r($db2_errors,true))."</pre>";
    } else {
        header("location: index1.php?p=Detail-status-approved&idm=$idstatus&upload=0&available=$warning");
    }
}
