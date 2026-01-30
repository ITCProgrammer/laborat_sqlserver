<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../koneksi.php';

$results = [];

// Accept search parameter (from select2) via GET 'search' or 'term'
$search = '';
if (isset($_GET['search'])) $search = trim($_GET['search']);
if (empty($search) && isset($_GET['term'])) $search = trim($_GET['term']);

if ($search !== '') {
    $like = "%" . $search . "%";
    $sql = "SELECT TOP 10 LTRIM(RTRIM(bao.[option])) AS opt
            FROM db_laborat.balance_additional_option bao
            WHERE LTRIM(RTRIM(bao.[type])) = 'G_B' AND LTRIM(RTRIM(bao.[option])) LIKE ?";
    $stmt = sqlsrv_query($con, $sql, [$like]);
} else {
    $sql = "SELECT TOP 10 LTRIM(RTRIM(bao.[option])) AS opt
            FROM db_laborat.balance_additional_option bao
            WHERE LTRIM(RTRIM(bao.[type])) = 'G_B'";
    $stmt = sqlsrv_query($con, $sql);
}

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $opt = $row['opt'] ?? '';
        $id = htmlspecialchars($opt, ENT_QUOTES);
        $results[] = [ 'id' => $id, 'text' => $opt, 'option' => $opt ];
    }
    sqlsrv_free_stmt($stmt);
}

echo json_encode($results);

?>
