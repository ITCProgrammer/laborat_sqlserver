<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../koneksi.php';

$results = [];

// get search term from select2
$search = '';
if (isset($_GET['search'])) $search = trim($_GET['search']);
if (empty($search) && isset($_GET['term'])) $search = trim($_GET['term']);

if ($search !== '') {
  $like = "%" . $search . "%";
  $sql = "SELECT TOP 10
      LTRIM(RTRIM(zl.location)) AS location_code, 
      LTRIM(RTRIM(zl.zone)) AS location_zone 
    FROM db_laborat.tbl_master_zone_location zl 
    WHERE LTRIM(RTRIM(zl.location)) LIKE ?";
  $stmt = sqlsrv_query($con, $sql, [$like]);
} else {
  $sql = "SELECT TOP 10
      LTRIM(RTRIM(zl.location)) AS location_code, 
      LTRIM(RTRIM(zl.zone)) AS location_zone 
    FROM db_laborat.tbl_master_zone_location zl";
  $stmt = sqlsrv_query($con, $sql);
}

if ($stmt) {
  while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $code = $row['location_code'] ?? '';
    $zone = $row['location_zone'] ?? '';
    $text = trim($code . ' (' . $zone . ')');
    $results[] = ['id' => $code, 'text' => $text, 'location_code' => $code, 'location_zone' => $zone];
  }
  sqlsrv_free_stmt($stmt);
}

echo json_encode($results);
