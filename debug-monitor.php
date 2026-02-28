<?php
ini_set("error_reporting", 1);

if (function_exists('session_status')) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} elseif (!isset($_SESSION)) {
    session_start();
}

include_once "koneksi.php";

function qcf_dm_escape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function qcf_dm_is_internal_path($path) {
    if (function_exists('qcf_debug_should_ignore_path')) {
        return qcf_debug_should_ignore_path($path);
    }
    return stripos((string)$path, '/telescope') === 0;
}

function qcf_dm_csrf_token() {
    if (!isset($_SESSION['qcf_dm_csrf']) || !is_string($_SESSION['qcf_dm_csrf']) || $_SESSION['qcf_dm_csrf'] === '') {
        try {
            $_SESSION['qcf_dm_csrf'] = bin2hex(random_bytes(16));
        } catch (Exception $e) {
            $_SESSION['qcf_dm_csrf'] = md5(uniqid('', true));
        }
    }
    return $_SESSION['qcf_dm_csrf'];
}

function qcf_dm_pretty_json($value) {
    if ($value === null || $value === '') {
        return '';
    }

    if (is_array($value)) {
        $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json !== false ? $json : '';
    }

    if (is_string($value)) {
        $decoded = json_decode($value, true);
        if (is_array($decoded) || is_object($decoded)) {
            $json = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $json !== false ? $json : $value;
        }
        return $value;
    }

    return '';
}

function qcf_dm_has_column($conn, $columnName) {
    $columnName = preg_replace('/[^A-Za-z0-9_]/', '', (string)$columnName);
    if ($columnName === '') {
        return false;
    }

    $sql = "SELECT COL_LENGTH('db_laborat.dbo.tbl_debug_monitor', '{$columnName}') AS col_len";
    $stmt = @sqlsrv_query($conn, $sql);
    if ($stmt === false) {
        return false;
    }
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    @sqlsrv_free_stmt($stmt);
    return is_array($row) && isset($row['col_len']) && $row['col_len'] !== null;
}

function qcf_dm_session_value($keys, $default = null) {
    if (!is_array($keys)) {
        $keys = array($keys);
    }

    foreach ($keys as $key) {
        if (!is_string($key) || $key === '') {
            continue;
        }
        if (isset($_SESSION[$key]) && $_SESSION[$key] !== '') {
            return $_SESSION[$key];
        }
    }

    return $default;
}

function qcf_dm_get_debug_conn() {
    if (function_exists('qcf_get_db_laborat_conn')) {
        return qcf_get_db_laborat_conn();
    }
    return null;
}

function qcf_dm_access_state($config) {
    $sessionUser = qcf_dm_session_value(array('userLAB', 'usrid', 'user_id10'));
    if ($sessionUser === null) {
        return 'need_login';
    }

    $sessionPass = qcf_dm_session_value(array('passLAB', 'pasid', 'pass_id10'));
    if ($sessionPass === null) {
        return 'need_unlock';
    }

    if (!empty($config['allow_all_logged_in'])) {
        return 'ok';
    }

    $allowedRoles = isset($config['viewer_roles']) && is_array($config['viewer_roles']) ? $config['viewer_roles'] : array();
    $currentRole = (string)qcf_dm_session_value(array('jabatanLAB', 'lvlLAB', 'lvl_id', 'lvl_id10'), '');
    if (in_array($currentRole, $allowedRoles, true)) {
        return 'ok';
    }

    return 'forbidden';
}

$config = function_exists('qcf_debug_config') ? qcf_debug_config() : array();
$accessState = qcf_dm_access_state($config);

if ($accessState === 'need_login') {
    header("Location: login");
    exit;
}

if ($accessState === 'need_unlock') {
    header("Location: lockscreen");
    exit;
}

if ($accessState !== 'ok') {
    http_response_code(403);
    echo "Access denied: role not allowed";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dm_action']) && $_POST['dm_action'] === 'truncate') {
    $postedToken = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    $sessionToken = qcf_dm_csrf_token();
    if (!hash_equals($sessionToken, $postedToken)) {
        http_response_code(419);
        echo "Invalid CSRF token";
        exit;
    }

    $mode = isset($_POST['truncate_mode']) ? strtolower(trim($_POST['truncate_mode'])) : 'old';
    if (!in_array($mode, array('old', 'all'), true)) {
        $mode = 'old';
    }

    $days = isset($_POST['days']) ? (int)$_POST['days'] : 14;
    if ($days < 1) {
        $days = 1;
    }
    if ($days > 3650) {
        $days = 3650;
    }

    $tableReadyNow = function_exists('qcf_debug_table_ready') ? qcf_debug_table_ready() : false;
    $dbConnNow = qcf_dm_get_debug_conn();
    $affectedRows = 0;
    $messageParts = array();

    if ($tableReadyNow && (is_resource($dbConnNow) || is_object($dbConnNow))) {
        if ($mode === 'all') {
            $sqlDelete = "DELETE FROM db_laborat.dbo.tbl_debug_monitor";
            $stmtDelete = @sqlsrv_query($dbConnNow, $sqlDelete);
        } else {
            $sqlDelete = "DELETE FROM db_laborat.dbo.tbl_debug_monitor WHERE created_at < DATEADD(DAY, -?, SYSDATETIME())";
            $stmtDelete = @sqlsrv_query($dbConnNow, $sqlDelete, array($days));
        }

        if ($stmtDelete !== false) {
            $stmtCount = @sqlsrv_query($dbConnNow, "SELECT @@ROWCOUNT AS affected");
            if ($stmtCount !== false) {
                $countRow = sqlsrv_fetch_array($stmtCount, SQLSRV_FETCH_ASSOC);
                if (is_array($countRow) && isset($countRow['affected'])) {
                    $affectedRows = (int)$countRow['affected'];
                }
                @sqlsrv_free_stmt($stmtCount);
            }
            @sqlsrv_free_stmt($stmtDelete);
            $messageParts[] = 'DB: ' . $affectedRows . ' row(s) deleted';
        } else {
            $messageParts[] = 'DB delete failed';
        }
    }

    $logFile = isset($config['file_log_path']) ? $config['file_log_path'] : (__DIR__ . '/temp/debug-monitor.log');
    if (is_file($logFile)) {
        if ($mode === 'all') {
            @file_put_contents($logFile, '');
            $messageParts[] = 'File log cleared';
        } else {
            $lines = @file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($lines)) {
                $keepSince = time() - ($days * 86400);
                $kept = array();
                foreach ($lines as $line) {
                    $decoded = json_decode($line, true);
                    if (!is_array($decoded)) {
                        continue;
                    }
                    $ts = isset($decoded['created_at']) ? strtotime($decoded['created_at']) : false;
                    if ($ts === false || $ts >= $keepSince) {
                        $kept[] = $line;
                    }
                }
                $out = count($kept) > 0 ? implode(PHP_EOL, $kept) . PHP_EOL : '';
                @file_put_contents($logFile, $out);
                $messageParts[] = 'File log pruned (' . count($kept) . ' kept)';
            }
        }
    }

    $_SESSION['qcf_dm_flash'] = implode(' | ', $messageParts);
    header("Location: telescope");
    exit;
}

$flash = null;
if (isset($_SESSION['qcf_dm_flash'])) {
    $flash = (string)$_SESSION['qcf_dm_flash'];
    unset($_SESSION['qcf_dm_flash']);
}

$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : 'all';
if (!in_array($type, array('all', 'request', 'error', 'slow'), true)) {
    $type = 'all';
}

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$showInternal = isset($_GET['show_internal']) && $_GET['show_internal'] === '1';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
if ($limit < 10) {
    $limit = 10;
}
if ($limit > 200) {
    $limit = 200;
}
$slowThreshold = 1000;
$defaultTruncateDays = isset($config['default_truncate_days']) ? (int)$config['default_truncate_days'] : 14;
if ($defaultTruncateDays < 1) {
    $defaultTruncateDays = 14;
}

$rows = array();
$summary = array('total' => 0, 'errors' => 0, 'slow' => 0);
$source = 'database';
$tableReady = function_exists('qcf_debug_table_ready') ? qcf_debug_table_ready() : false;
$dbConn = qcf_dm_get_debug_conn();

if ($tableReady && (is_resource($dbConn) || is_object($dbConn))) {
    $whereParts = array("1=1");
    $params = array();

    if ($type === 'slow') {
        $whereParts[] = "duration_ms >= ?";
        $params[] = $slowThreshold;
    } elseif ($type !== 'all') {
        $whereParts[] = "log_type = ?";
        $params[] = $type;
    }

    if (!$showInternal) {
        $whereParts[] = "(path NOT LIKE ? AND path NOT LIKE ?)";
        $params[] = '/telescope%';
        $params[] = '%/telescope%';
    }

    if ($search !== '') {
        $whereParts[] = "(path LIKE ? OR ISNULL(user_name, '') LIKE ? OR ISNULL(error_message, '') LIKE ? OR ISNULL(error_file, '') LIKE ?)";
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $hasErrorTrace = qcf_dm_has_column($dbConn, 'error_trace');
    $errorTraceSelect = $hasErrorTrace ? "error_trace" : "CAST(NULL AS NVARCHAR(MAX)) AS error_trace";

    $whereSql = implode(' AND ', $whereParts);
    $sql = "SELECT TOP {$limit}
                id,
                created_at,
                request_id,
                log_type,
                method,
                path,
                query_string,
                status_code,
                duration_ms,
                user_name,
                ip_address,
                is_ajax,
                error_type,
                error_message,
                error_file,
                error_line,
                {$errorTraceSelect},
                payload_json
            FROM db_laborat.dbo.tbl_debug_monitor
            WHERE {$whereSql}
            ORDER BY id DESC";

    $stmt = @sqlsrv_query($dbConn, $sql, $params);
    if ($stmt !== false) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (isset($row['created_at']) && $row['created_at'] instanceof DateTimeInterface) {
                $row['created_at'] = $row['created_at']->format('Y-m-d H:i:s');
            }
            if (isset($row['path']) && function_exists('qcf_debug_normalize_path')) {
                $row['path'] = qcf_debug_normalize_path($row['path']);
            }
            if (!empty($row['payload_json'])) {
                $row['payload_pretty'] = qcf_dm_pretty_json($row['payload_json']);
            } else {
                $row['payload_pretty'] = '';
            }
            $rows[] = $row;
        }
        @sqlsrv_free_stmt($stmt);
    }

    $summaryWhere = "created_at >= DATEADD(HOUR, -1, SYSDATETIME())";
    if (!$showInternal) {
        $summaryWhere .= " AND path NOT LIKE '/telescope%' AND path NOT LIKE '%/telescope%'";
    }
    $summarySql = "SELECT
                        COUNT(*) AS total,
                        SUM(CASE WHEN log_type = 'error' THEN 1 ELSE 0 END) AS errors,
                        SUM(CASE WHEN duration_ms >= {$slowThreshold} THEN 1 ELSE 0 END) AS slow
                   FROM db_laborat.dbo.tbl_debug_monitor
                   WHERE {$summaryWhere}";
    $summaryStmt = @sqlsrv_query($dbConn, $summarySql);
    if ($summaryStmt !== false) {
        $summaryRow = sqlsrv_fetch_array($summaryStmt, SQLSRV_FETCH_ASSOC);
        if (is_array($summaryRow)) {
            $summary['total'] = isset($summaryRow['total']) ? (int)$summaryRow['total'] : 0;
            $summary['errors'] = isset($summaryRow['errors']) ? (int)$summaryRow['errors'] : 0;
            $summary['slow'] = isset($summaryRow['slow']) ? (int)$summaryRow['slow'] : 0;
        }
        @sqlsrv_free_stmt($summaryStmt);
    }
} else {
    $source = 'file';
    $logFile = isset($config['file_log_path']) ? $config['file_log_path'] : (__DIR__ . '/temp/debug-monitor.log');

    if (is_file($logFile)) {
        $lines = @file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($lines)) {
            $now = time();
            $displayed = 0;

            for ($i = count($lines) - 1; $i >= 0; $i--) {
                $decoded = json_decode($lines[$i], true);
                if (!is_array($decoded)) {
                    continue;
                }

                $logType = isset($decoded['log_type']) ? $decoded['log_type'] : 'request';
                $path = isset($decoded['path']) ? (string)$decoded['path'] : '';
                if (function_exists('qcf_debug_normalize_path')) {
                    $path = qcf_debug_normalize_path($path);
                }
                $errorMessage = isset($decoded['error_message']) ? (string)$decoded['error_message'] : '';
                $userName = isset($decoded['user_name']) ? (string)$decoded['user_name'] : '';
                $durationVal = isset($decoded['duration_ms']) ? (int)$decoded['duration_ms'] : 0;

                if ($type === 'slow' && $durationVal < $slowThreshold) {
                    continue;
                }
                if ($type !== 'all' && $type !== 'slow' && $type !== $logType) {
                    continue;
                }
                if (!$showInternal && qcf_dm_is_internal_path($path)) {
                    continue;
                }

                if ($search !== '') {
                    $haystack = strtolower(
                        $path . ' ' .
                        $errorMessage . ' ' .
                        $userName . ' ' .
                        (isset($decoded['error_file']) ? (string)$decoded['error_file'] : '')
                    );
                    if (strpos($haystack, strtolower($search)) === false) {
                        continue;
                    }
                }

                $row = array(
                    'id' => isset($decoded['request_id']) ? $decoded['request_id'] : ($i + 1),
                    'request_id' => isset($decoded['request_id']) ? $decoded['request_id'] : ($i + 1),
                    'created_at' => isset($decoded['created_at']) ? $decoded['created_at'] : '',
                    'log_type' => $logType,
                    'method' => isset($decoded['method']) ? $decoded['method'] : '',
                    'path' => $path,
                    'query_string' => isset($decoded['query_string']) ? $decoded['query_string'] : '',
                    'status_code' => isset($decoded['status_code']) ? (int)$decoded['status_code'] : null,
                    'duration_ms' => $durationVal,
                    'user_name' => $userName,
                    'ip_address' => isset($decoded['ip_address']) ? $decoded['ip_address'] : '',
                    'is_ajax' => !empty($decoded['is_ajax']) ? 1 : 0,
                    'error_type' => isset($decoded['error_type']) ? $decoded['error_type'] : null,
                    'error_message' => $errorMessage,
                    'error_file' => isset($decoded['error_file']) ? $decoded['error_file'] : '',
                    'error_line' => isset($decoded['error_line']) ? (int)$decoded['error_line'] : 0,
                    'error_trace' => isset($decoded['error_trace']) ? $decoded['error_trace'] : '',
                    'payload_pretty' => isset($decoded['payload']) ? qcf_dm_pretty_json($decoded['payload']) : ''
                );
                $rows[] = $row;

                $rowTs = isset($decoded['created_at']) ? strtotime($decoded['created_at']) : false;
                if ($rowTs !== false && ($now - $rowTs) <= 3600 && ($showInternal || !qcf_dm_is_internal_path($path))) {
                    $summary['total']++;
                    if ($logType === 'error') {
                        $summary['errors']++;
                    }
                    if (isset($decoded['duration_ms']) && (int)$decoded['duration_ms'] >= $slowThreshold) {
                        $summary['slow']++;
                    }
                }

                $displayed++;
                if ($displayed >= $limit) {
                    break;
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>LABORAT Debug Monitor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
    <style>
        body { background: #f4f6f9; }
        .wrap { max-width: 1400px; margin: 20px auto; padding: 0 15px; }
        .panel-card { background: #fff; border: 1px solid #d9e0e7; border-radius: 8px; padding: 14px; margin-bottom: 12px; }
        .muted { color: #6b7280; }
        .badge-soft { display: inline-block; padding: 4px 8px; border-radius: 999px; font-size: 12px; }
        .badge-req { background: #e8f5ff; color: #1f5f9b; }
        .badge-err { background: #ffe8e8; color: #9b1f1f; }
        .badge-ok { background: #e8f8ef; color: #166534; }
        .badge-warn { background: #fff8e6; color: #92400e; }
        .badge-int { background: #e5e7eb; color: #374151; }
        .table > tbody > tr > td { vertical-align: middle; }
        .toolbar a { margin-right: 8px; }
        .small-text { font-size: 12px; }
        .path-cell { max-width: 360px; word-break: break-all; }
        pre.trace-box {
            max-height: 220px;
            overflow: auto;
            background: #111827;
            color: #f9fafb;
            border-radius: 6px;
            padding: 10px;
            font-size: 12px;
        }
        .flash-ok {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #166534;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 10px;
        }
        .truncate-group {
            display: flex;
            gap: 6px;
            align-items: center;
            justify-content: flex-end;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="panel-card">
        <?php if ($flash !== null && $flash !== ''): ?>
            <div class="flash-ok"><?php echo qcf_dm_escape($flash); ?></div>
        <?php endif; ?>
        <div class="row">
            <div class="col-sm-7">
                <h3 style="margin-top:0;margin-bottom:8px;">LABORAT Debug Monitor</h3>
                <div class="toolbar">
                    <a href="telescope?type=all<?php echo $showInternal ? '&show_internal=1' : ''; ?>" class="btn btn-default btn-sm">All</a>
                    <a href="telescope?type=request<?php echo $showInternal ? '&show_internal=1' : ''; ?>" class="btn btn-default btn-sm">Requests</a>
                    <a href="telescope?type=error<?php echo $showInternal ? '&show_internal=1' : ''; ?>" class="btn btn-default btn-sm">Errors</a>
                    <a href="telescope?type=slow<?php echo $showInternal ? '&show_internal=1' : ''; ?>" class="btn btn-default btn-sm">Slow</a>
                    <?php if ($showInternal): ?>
                        <a href="telescope?type=<?php echo qcf_dm_escape($type); ?>" class="btn btn-default btn-sm">Hide Internal</a>
                    <?php else: ?>
                        <a href="telescope?type=<?php echo qcf_dm_escape($type); ?>&show_internal=1" class="btn btn-default btn-sm">Show Internal</a>
                    <?php endif; ?>
                    <a href="telescope" class="btn btn-primary btn-sm">Refresh</a>
                </div>
                <div class="small-text muted" style="margin-top:8px;">
                    Source: <?php echo qcf_dm_escape($source); ?> |
                    Last 1 hour: <?php echo (int)$summary['total']; ?> total,
                    <?php echo (int)$summary['errors']; ?> error,
                    <?php echo (int)$summary['slow']; ?> slow (>=<?php echo (int)$slowThreshold; ?>ms)
                </div>
            </div>
            <div class="col-sm-5">
                <form method="get" class="form-inline" style="margin-top:6px;">
                    <input type="hidden" name="type" value="<?php echo qcf_dm_escape($type); ?>">
                    <?php if ($showInternal): ?>
                        <input type="hidden" name="show_internal" value="1">
                    <?php endif; ?>
                    <div class="form-group" style="width: 70%;">
                        <input type="text" name="q" class="form-control input-sm" style="width:100%;" placeholder="Search path / user / error..." value="<?php echo qcf_dm_escape($search); ?>">
                    </div>
                    <div class="form-group" style="width: 28%;">
                        <input type="number" min="10" max="200" name="limit" class="form-control input-sm" style="width:100%;" value="<?php echo (int)$limit; ?>">
                    </div>
                    <button type="submit" class="btn btn-info btn-sm" style="margin-top:8px;">Apply</button>
                </form>
                <form method="post" class="truncate-group" onsubmit="return confirm('Yakin truncate log?');">
                    <input type="hidden" name="dm_action" value="truncate">
                    <input type="hidden" name="csrf_token" value="<?php echo qcf_dm_escape(qcf_dm_csrf_token()); ?>">
                    <select class="form-control input-sm" name="truncate_mode" style="width:90px;">
                        <option value="old">Older Than</option>
                        <option value="all">Delete All</option>
                    </select>
                    <input type="number" class="form-control input-sm" name="days" min="1" max="3650" value="<?php echo (int)$defaultTruncateDays; ?>" style="width:90px;">
                    <button type="submit" class="btn btn-danger btn-sm">Truncate</button>
                </form>
            </div>
        </div>
    </div>

    <div class="panel-card">
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th style="width:170px;">Time</th>
                        <th style="width:90px;">Type</th>
                        <th style="width:75px;">Method</th>
                        <th>Path</th>
                        <th style="width:90px;">Status</th>
                        <th style="width:100px;">Duration</th>
                        <th style="width:120px;">User</th>
                        <th style="width:130px;">IP</th>
                        <th style="width:220px;">Error</th>
                        <th style="width:80px;">Detail</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($rows) === 0): ?>
                    <tr>
                        <td colspan="10" class="text-center muted">No data</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $idx => $row): ?>
                        <?php
                        $isError = isset($row['log_type']) && $row['log_type'] === 'error';
                        $duration = isset($row['duration_ms']) ? (int)$row['duration_ms'] : 0;
                        $statusCode = isset($row['status_code']) ? (int)$row['status_code'] : 0;
                        $rowIdBase = isset($row['id']) ? (string)$row['id'] : (isset($row['request_id']) ? (string)$row['request_id'] : uniqid('row', true));
                        $rowId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $rowIdBase) . '_' . (string)$idx;
                        $errorLocation = '';
                        if (!empty($row['error_file'])) {
                            $errorLocation = (string)$row['error_file'];
                            if (!empty($row['error_line'])) {
                                $errorLocation .= ':' . (int)$row['error_line'];
                            }
                        }
                        $payloadPretty = isset($row['payload_pretty']) ? (string)$row['payload_pretty'] : '';
                        $traceText = isset($row['error_trace']) ? (string)$row['error_trace'] : '';
                        ?>
                        <tr>
                            <td><?php echo qcf_dm_escape(isset($row['created_at']) ? $row['created_at'] : ''); ?></td>
                            <td>
                                <?php
                                $badgeClass = $isError ? 'badge-err' : 'badge-req';
                                if (!$isError && qcf_dm_is_internal_path(isset($row['path']) ? $row['path'] : '')) {
                                    $badgeClass = 'badge-int';
                                }
                                ?>
                                <span class="badge-soft <?php echo $badgeClass; ?>">
                                    <?php echo qcf_dm_escape(isset($row['log_type']) ? strtoupper($row['log_type']) : 'REQUEST'); ?>
                                </span>
                            </td>
                            <td><?php echo qcf_dm_escape(isset($row['method']) ? $row['method'] : ''); ?></td>
                            <td class="path-cell"><?php echo qcf_dm_escape(isset($row['path']) ? $row['path'] : ''); ?></td>
                            <td>
                                <?php if ($statusCode > 0): ?>
                                    <span class="badge-soft <?php echo $statusCode >= 500 ? 'badge-err' : 'badge-ok'; ?>">
                                        <?php echo $statusCode; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($duration > 0): ?>
                                    <span class="badge-soft <?php echo $duration >= 1000 ? 'badge-warn' : 'badge-ok'; ?>">
                                        <?php echo $duration; ?>ms
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo qcf_dm_escape(isset($row['user_name']) ? $row['user_name'] : '-'); ?></td>
                            <td><?php echo qcf_dm_escape(isset($row['ip_address']) ? $row['ip_address'] : '-'); ?></td>
                            <td class="small-text">
                                <?php
                                $err = isset($row['error_message']) ? $row['error_message'] : '';
                                if ($err !== '') {
                                    echo qcf_dm_escape($err);
                                    if ($errorLocation !== '') {
                                        echo '<br><span class="muted">' . qcf_dm_escape($errorLocation) . '</span>';
                                    }
                                } elseif (!empty($row['error_type'])) {
                                    echo qcf_dm_escape($row['error_type']);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-default btn-xs dm-toggle" data-target="d-<?php echo qcf_dm_escape($rowId); ?>">View</button>
                            </td>
                        </tr>
                        <tr id="d-<?php echo qcf_dm_escape($rowId); ?>" style="display:none;">
                            <td colspan="10">
                                <div class="small-text">
                                    <strong>Request ID:</strong> <?php echo qcf_dm_escape(isset($row['request_id']) ? $row['request_id'] : '-'); ?><br>
                                    <strong>Path:</strong> <?php echo qcf_dm_escape(isset($row['path']) ? $row['path'] : '-'); ?><br>
                                    <strong>Query:</strong> <?php echo qcf_dm_escape(isset($row['query_string']) ? $row['query_string'] : '-'); ?><br>
                                    <strong>Error Type:</strong> <?php echo qcf_dm_escape(isset($row['error_type']) ? $row['error_type'] : '-'); ?><br>
                                    <strong>Error File:</strong> <?php echo qcf_dm_escape($errorLocation !== '' ? $errorLocation : '-'); ?>
                                </div>
                                <?php if ($traceText !== ''): ?>
                                    <div class="small-text" style="margin-top:8px;"><strong>Trace</strong></div>
                                    <pre class="trace-box"><?php echo qcf_dm_escape($traceText); ?></pre>
                                <?php endif; ?>
                                <?php if ($payloadPretty !== ''): ?>
                                    <div class="small-text" style="margin-top:8px;"><strong>Payload</strong></div>
                                    <pre class="trace-box"><?php echo qcf_dm_escape($payloadPretty); ?></pre>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="small-text muted">
            Note: request `/telescope*` disembunyikan default. Error sekarang simpan `file:line` + `trace` untuk debugging.
        </div>
    </div>
</div>
<script>
document.addEventListener('click', function (event) {
    if (!event.target.classList.contains('dm-toggle')) {
        return;
    }
    var targetId = event.target.getAttribute('data-target');
    var row = document.getElementById(targetId);
    if (!row) {
        return;
    }
    if (row.style.display === 'none' || row.style.display === '') {
        row.style.display = 'table-row';
    } else {
        row.style.display = 'none';
    }
});
</script>
</body>
</html>
