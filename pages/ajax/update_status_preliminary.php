<?php
include "../../koneksi.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user   = $_SESSION['userLAB'] ?? "Guest";
$status = $_POST['status'] ?? 'unknown';
$ip_num = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$stmt = sqlsrv_query($con, "INSERT INTO db_laborat.log_preliminary (username, status, ip_comp) VALUES (?, ?, ?)", [$user, $status, $ip_num]);

if (! $stmt) {
    http_response_code(500);
    echo json_encode(['error' => sqlsrv_errors()]);
    exit;
}

echo json_encode(['ok' => true]);
?>
