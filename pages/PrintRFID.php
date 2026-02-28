<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "koneksi.php";

if (!function_exists('qcf_printrfid_send_api')) {
    function qcf_printrfid_send_api($docNumber)
    {
        $url = "http://10.0.0.121:8080/api/v1/document/create";
        $payload = json_encode([
            "doc_number" => $docNumber,
            "ip_address" => '10.0.6.225'
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => 0,
                'message' => "CURL Error: " . $error,
                'response' => $response
            ];
        }

        $result = json_decode($response, true);
        return [
            'success' => (isset($result['success']) && $result['success']) ? 1 : 0,
            'message' => isset($result['message']) ? (string)$result['message'] : 'Unknown response',
            'response' => $response
        ];
    }
}

if (!function_exists('qcf_printrfid_has_recent_log')) {
    function qcf_printrfid_has_recent_log($conn, $docNumber, $seconds = 8)
    {
        $stmt = sqlsrv_query(
            $conn,
            "SELECT TOP 1 1 AS found
             FROM db_laborat.log_printing
             WHERE no_resep = ?
               AND created_at >= DATEADD(SECOND, ?, GETDATE())",
            [$docNumber, -abs((int)$seconds)]
        );
        if (! $stmt) {
            return false;
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        return $row ? true : false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['no_resep'])) {
    $no_resep = strtoupper(trim((string)$_POST['no_resep']));
    $ip_num = $_SERVER['REMOTE_ADDR'];
    $createdBy = $_SESSION['userLAB'] ?? '';

    if ($no_resep === '') {
        echo "<script>alert('No resep kosong');window.location.href='?p=PrintRFID';</script>";
        exit;
    }

    if (qcf_printrfid_has_recent_log($con, $no_resep, 8)) {
        echo "<script>alert('Permintaan print duplikat terdeteksi, abaikan klik berulang.');window.location.href='?p=form-matching-detail&noresep=$no_resep';</script>";
        exit;
    }

    $api = qcf_printrfid_send_api($no_resep);
    $logMessage = addslashes((string)$api['message']);
    $logSuccess = (int)$api['success'];
    $response = isset($api['response']) ? $api['response'] : null;

    $insertSql = "INSERT INTO db_laborat.log_printing
        (no_resep, ip_address, success, message, response_raw, created_at, created_by)
        VALUES (?, ?, ?, ?, ?, GETDATE(), ?)";
    $insertParams = [$no_resep, $ip_num, $logSuccess, $logMessage, $response, $createdBy];
    sqlsrv_query($con, $insertSql, $insertParams);

    // === FEEDBACK KE USER ===
    if ($logSuccess) {
        echo "<script>alert('Data tersimpan & print berhasil dikirim!');window.location.href='?p=form-matching-detail&noresep=$no_resep';</script>";
    } else {
        echo "<script>alert('Data tersimpan, tapi print gagal: " . addslashes($logMessage) . "');window.location.href='?p=form-matching-detail&noresep=$no_resep';</script>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Print RFID</title>
</head>
<body>
    <h2>Print RFID</h2>
    <form method="post" id="printRfidForm">
        <label>No Resep:</label>
        <input type="text" name="no_resep" required autofocus>
        <button type="submit" id="btnSubmitPrint">Print</button>
    </form>

    <h3>Log Printing</h3>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css"/>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <table id="logPrintingTable" class="display" style="width:100%">
        <thead>
            <tr>
                <th>No</th>
                <th>No Resep</th>
                <th>IP Address</th>
                <th>Success</th>
                <th>Message</th>
                <th>Created At</th>
                <th>Created By</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $q = sqlsrv_query($con, "SELECT TOP 100 * FROM db_laborat.log_printing ORDER BY created_at DESC");
            $no = 1;
            while ($q && ($row = sqlsrv_fetch_array($q, SQLSRV_FETCH_ASSOC))) {
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . htmlspecialchars($row['no_resep']) . "</td>";
                echo "<td>" . htmlspecialchars($row['ip_address']) . "</td>";
                echo "<td>" . ($row['success'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . htmlspecialchars($row['message']) . "</td>";
                $createdAt = $row['created_at'];
                if ($createdAt instanceof DateTimeInterface) {
                    $createdAt = $createdAt->format('Y-m-d H:i:s');
                }
                echo "<td>" . htmlspecialchars($createdAt) . "</td>";
                echo "<td>" . htmlspecialchars($row['created_by']) . "</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
    <script>
        $(document).ready(function() {
            $('#logPrintingTable').DataTable();
            $('#printRfidForm').on('submit', function () {
                var $btn = $('#btnSubmitPrint');
                if ($btn.prop('disabled')) {
                    return false;
                }
                $btn.prop('disabled', true).text('Proses...');
                return true;
            });
        });
    </script>
</body>
</html>
