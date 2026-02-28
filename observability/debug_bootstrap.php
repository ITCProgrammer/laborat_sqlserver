<?php
if (defined('QCF_DEBUG_BOOTSTRAPPED')) {
    return;
}
define('QCF_DEBUG_BOOTSTRAPPED', true);

if (!function_exists('qcf_debug_config')) {
    function qcf_debug_config() {
        static $config = null;

        if ($config !== null) {
            return $config;
        }

        $default = array(
            'enabled' => true,
            'log_db' => true,
            'log_file' => true,
            'allow_all_logged_in' => true,
            'viewer_roles' => array('Super admin', 'Admin', 'Spv', 'Leader', 'Lab Head'),
            'masked_fields' => array('password', 'pass', 'pwd', 'token', 'csrf', 'api_key', 'secret'),
            'max_value_length' => 800,
            'error_trace_max_length' => 8000,
            'ignore_paths' => array('/telescope*'),
            'capture_php_warnings' => true,
            'max_errors_per_request' => 25,
            'default_truncate_days' => 14,
            // Optional additional URL prefixes to strip from REQUEST_URI path.
            'path_prefixes' => array(),
            'file_log_path' => __DIR__ . '/../temp/debug-monitor.log',
        );

        $configFile = __DIR__ . '/debug_config.php';
        if (is_file($configFile)) {
            $loaded = include $configFile;
            if (is_array($loaded)) {
                $config = array_merge($default, $loaded);
            }
        }

        if (!is_array($config)) {
            $config = $default;
        }

        return $config;
    }
}

if (!function_exists('qcf_debug_is_enabled')) {
    function qcf_debug_is_enabled() {
        $config = qcf_debug_config();
        return !empty($config['enabled']);
    }
}

if (!function_exists('qcf_debug_sqlsrv_connection')) {
    function qcf_debug_sqlsrv_connection() {
        if (function_exists('qcf_get_db_laborat_conn')) {
            return qcf_get_db_laborat_conn();
        }

        global $con_db_laborat_sqlsrv;
        if (isset($con_db_laborat_sqlsrv)) {
            return $con_db_laborat_sqlsrv;
        }
        return null;
    }
}

if (!function_exists('qcf_debug_connection_ready')) {
    function qcf_debug_connection_ready() {
        $conn = qcf_debug_sqlsrv_connection();
        return is_resource($conn) || is_object($conn);
    }
}

if (!function_exists('qcf_debug_session_first')) {
    function qcf_debug_session_first($keys, $default = null) {
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
}

if (!function_exists('qcf_debug_normalize_slashes')) {
    function qcf_debug_normalize_slashes($value) {
        return str_replace('\\', '/', (string)$value);
    }
}

if (!function_exists('qcf_debug_detect_base_prefix')) {
    function qcf_debug_detect_base_prefix() {
        static $detected = null;
        if ($detected !== null) {
            return $detected;
        }

        $detected = '';
        if (empty($_SERVER['SCRIPT_FILENAME']) || empty($_SERVER['SCRIPT_NAME'])) {
            return $detected;
        }

        $appRoot = realpath(__DIR__ . '/..');
        $scriptFile = realpath((string)$_SERVER['SCRIPT_FILENAME']);
        if ($appRoot === false || $scriptFile === false) {
            return $detected;
        }

        $appRootNorm = qcf_debug_normalize_slashes($appRoot);
        $scriptFileNorm = qcf_debug_normalize_slashes($scriptFile);
        $appRootNormLower = strtolower(rtrim($appRootNorm, '/'));
        $scriptFileNormLower = strtolower($scriptFileNorm);

        if (strpos($scriptFileNormLower, $appRootNormLower . '/') !== 0 && $scriptFileNormLower !== $appRootNormLower) {
            return $detected;
        }

        $relative = substr($scriptFileNorm, strlen($appRootNormLower));
        if (!is_string($relative) || $relative === '') {
            $relative = '/';
        } else {
            $relative = '/' . ltrim($relative, '/');
        }

        $scriptName = qcf_debug_normalize_slashes((string)$_SERVER['SCRIPT_NAME']);
        $basePrefix = '';
        $relativeLength = strlen($relative);
        if ($relativeLength > 0 && strlen($scriptName) >= $relativeLength) {
            $scriptNameTail = substr($scriptName, -$relativeLength);
            if (strcasecmp($scriptNameTail, $relative) === 0) {
                $basePrefix = substr($scriptName, 0, strlen($scriptName) - $relativeLength);
            }
        }

        if ($basePrefix === '') {
            $basePrefix = dirname($scriptName);
        }

        $basePrefix = qcf_debug_normalize_slashes($basePrefix);
        if ($basePrefix === '/' || $basePrefix === '.') {
            $basePrefix = '';
        }

        $detected = rtrim($basePrefix, '/');
        return $detected;
    }
}

if (!function_exists('qcf_debug_normalize_path')) {
    function qcf_debug_normalize_path($path) {
        $path = qcf_debug_normalize_slashes($path);
        if ($path === '') {
            return '/';
        }

        $path = '/' . ltrim($path, '/');
        $config = qcf_debug_config();
        $prefixes = array();

        if (isset($config['path_prefixes'])) {
            if (is_array($config['path_prefixes'])) {
                $prefixes = $config['path_prefixes'];
            } elseif (is_string($config['path_prefixes']) && trim($config['path_prefixes']) !== '') {
                $prefixes = array($config['path_prefixes']);
            }
        }

        $autoPrefix = qcf_debug_detect_base_prefix();
        if ($autoPrefix !== '') {
            $prefixes[] = $autoPrefix;
        }

        foreach ($prefixes as $prefix) {
            $prefix = qcf_debug_normalize_slashes($prefix);
            $prefix = '/' . trim($prefix, '/');
            if ($prefix === '/' || $prefix === '') {
                continue;
            }

            if (strcasecmp($path, $prefix) === 0) {
                return '/';
            }

            if (stripos($path, $prefix . '/') === 0) {
                $trimmed = substr($path, strlen($prefix));
                return $trimmed === '' ? '/' : $trimmed;
            }
        }

        return $path;
    }
}

if (!function_exists('qcf_debug_current_path')) {
    function qcf_debug_current_path() {
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
        }
        return qcf_debug_normalize_path($path);
    }
}

if (!function_exists('qcf_debug_path_matches_pattern')) {
    function qcf_debug_path_matches_pattern($path, $pattern) {
        $path = qcf_debug_normalize_path($path);
        $pattern = qcf_debug_normalize_path($pattern);
        if ($pattern === '') {
            return false;
        }

        if (substr($pattern, -1) === '*') {
            $prefix = rtrim(substr($pattern, 0, -1), '/');
            if ($prefix === '') {
                return true;
            }
            return stripos($path, $prefix) === 0;
        }

        return strcasecmp($path, $pattern) === 0;
    }
}

if (!function_exists('qcf_debug_should_ignore_path')) {
    function qcf_debug_should_ignore_path($path = null) {
        $path = $path === null ? qcf_debug_current_path() : qcf_debug_normalize_path($path);
        $config = qcf_debug_config();
        $patterns = isset($config['ignore_paths']) && is_array($config['ignore_paths']) ? $config['ignore_paths'] : array();

        foreach ($patterns as $pattern) {
            if (!is_string($pattern) || trim($pattern) === '') {
                continue;
            }
            if (qcf_debug_path_matches_pattern($path, $pattern)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('qcf_debug_build_error_trace')) {
    function qcf_debug_build_error_trace($trace = null) {
        $config = qcf_debug_config();
        $maxLength = isset($config['error_trace_max_length']) ? (int)$config['error_trace_max_length'] : 8000;

        if (is_string($trace) && trim($trace) !== '') {
            return qcf_debug_safe_text($trace, $maxLength);
        }

        $frames = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);
        $lines = array();
        foreach ($frames as $idx => $frame) {
            $file = isset($frame['file']) ? $frame['file'] : '[internal]';
            $line = isset($frame['line']) ? $frame['line'] : 0;
            $func = isset($frame['function']) ? $frame['function'] : 'unknown';
            $class = isset($frame['class']) ? $frame['class'] . '::' : '';
            $lines[] = '#' . $idx . ' ' . $file . '(' . $line . '): ' . $class . $func . '()';
        }

        return qcf_debug_safe_text(implode("\n", $lines), $maxLength);
    }
}

if (!function_exists('qcf_debug_error_type_text')) {
    function qcf_debug_error_type_text($type) {
        if (is_int($type) || (is_string($type) && ctype_digit($type))) {
            return qcf_debug_error_name((int)$type);
        }
        return qcf_debug_safe_text($type, 50);
    }
}

if (!function_exists('qcf_debug_error_budget_available')) {
    function qcf_debug_error_budget_available() {
        static $errorCount = 0;
        $config = qcf_debug_config();
        $maxErrors = isset($config['max_errors_per_request']) ? (int)$config['max_errors_per_request'] : 25;
        if ($maxErrors <= 0) {
            return false;
        }
        if ($errorCount >= $maxErrors) {
            return false;
        }
        $errorCount++;
        return true;
    }
}

if (!function_exists('qcf_debug_capture_error')) {
    function qcf_debug_capture_error($errorInfo) {
        static $inProgress = false;

        if ($inProgress) {
            return;
        }
        if (!qcf_debug_is_enabled() || PHP_SAPI === 'cli') {
            return;
        }
        if (!qcf_debug_error_budget_available()) {
            return;
        }

        $path = qcf_debug_current_path();
        if (qcf_debug_should_ignore_path($path)) {
            return;
        }

        $record = qcf_debug_build_record($errorInfo);
        $record['log_type'] = 'error';
        $inProgress = true;
        try {
            qcf_debug_store_record($record);
        } catch (Throwable $e) {
            // Ignore monitor exceptions to avoid breaking application flow.
        } catch (Exception $e) {
            // Ignore monitor exceptions to avoid breaking application flow.
        }
        $inProgress = false;
    }
}

if (!function_exists('qcf_debug_request_id')) {
    function qcf_debug_request_id() {
        try {
            return bin2hex(random_bytes(8));
        } catch (Exception $e) {
            return str_replace('.', '', uniqid('', true));
        }
    }
}

if (!function_exists('qcf_debug_safe_text')) {
    function qcf_debug_safe_text($value, $maxLength = 800) {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            $text = (string)$value;
            if ($maxLength > 0 && strlen($text) > $maxLength) {
                return substr($text, 0, $maxLength) . '...';
            }
            return $text;
        }

        if (is_array($value)) {
            return '[array]';
        }

        if (is_object($value)) {
            return '[object ' . get_class($value) . ']';
        }

        return '[unsupported]';
    }
}

if (!function_exists('qcf_debug_encode_json')) {
    function qcf_debug_encode_json($value) {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return '{}';
        }
        return $json;
    }
}

if (!function_exists('qcf_debug_sanitize')) {
    function qcf_debug_sanitize($value, $depth = 0) {
        $config = qcf_debug_config();
        $maxLength = isset($config['max_value_length']) ? (int)$config['max_value_length'] : 800;

        if ($depth > 5) {
            return '[max-depth]';
        }

        if (is_array($value)) {
            $masked = array();
            $maskKeys = isset($config['masked_fields']) && is_array($config['masked_fields']) ? $config['masked_fields'] : array();
            $lowerMaskKeys = array_map('strtolower', $maskKeys);

            foreach ($value as $key => $item) {
                $keyText = strtolower((string)$key);
                if (in_array($keyText, $lowerMaskKeys, true)) {
                    $masked[$key] = '[MASKED]';
                    continue;
                }
                $masked[$key] = qcf_debug_sanitize($item, $depth + 1);
            }
            return $masked;
        }

        return qcf_debug_safe_text($value, $maxLength);
    }
}

if (!function_exists('qcf_debug_client_ip')) {
    function qcf_debug_client_ip() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($parts[0]);
        }
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return null;
    }
}

if (!function_exists('qcf_debug_is_ajax')) {
    function qcf_debug_is_ajax() {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return 1;
        }
        return 0;
    }
}

if (!function_exists('qcf_debug_fatal_error_type')) {
    function qcf_debug_fatal_error_type($type) {
        $fatalTypes = array(
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_USER_ERROR,
            E_RECOVERABLE_ERROR
        );

        return in_array($type, $fatalTypes, true);
    }
}

if (!function_exists('qcf_debug_error_name')) {
    function qcf_debug_error_name($type) {
        $map = array(
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR'
        );

        return isset($map[$type]) ? $map[$type] : (string)$type;
    }
}

if (!function_exists('qcf_debug_upgrade_table_schema')) {
    function qcf_debug_upgrade_table_schema($conn) {
        static $upgraded = false;
        if ($upgraded) {
            return;
        }
        $upgraded = true;

        $upgradeSql = "
IF COL_LENGTH('db_laborat.dbo.tbl_debug_monitor', 'error_trace') IS NULL
BEGIN
    ALTER TABLE db_laborat.dbo.tbl_debug_monitor ADD error_trace NVARCHAR(MAX) NULL;
END
";
        @sqlsrv_query($conn, $upgradeSql);
    }
}

if (!function_exists('qcf_debug_table_ready')) {
    function qcf_debug_table_ready() {
        static $checked = false;
        static $ready = false;

        if ($checked) {
            return $ready;
        }
        $checked = true;

        if (!qcf_debug_connection_ready()) {
            return false;
        }

        $conn = qcf_debug_sqlsrv_connection();

        $checkSql = "SELECT 1 FROM db_laborat.sys.tables WHERE name = 'tbl_debug_monitor'";
        $stmt = @sqlsrv_query($conn, $checkSql);
        if ($stmt !== false) {
            $row = @sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
            @sqlsrv_free_stmt($stmt);
            if ($row) {
                qcf_debug_upgrade_table_schema($conn);
                $ready = true;
                return true;
            }
        }

        $createSql = "
IF OBJECT_ID('db_laborat.dbo.tbl_debug_monitor', 'U') IS NULL
BEGIN
    CREATE TABLE db_laborat.dbo.tbl_debug_monitor (
        id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        created_at DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
        log_type VARCHAR(20) NOT NULL,
        request_id VARCHAR(40) NULL,
        method VARCHAR(10) NULL,
        path NVARCHAR(500) NULL,
        query_string NVARCHAR(MAX) NULL,
        status_code INT NULL,
        duration_ms INT NULL,
        ip_address VARCHAR(45) NULL,
        user_name NVARCHAR(100) NULL,
        session_user_id INT NULL,
        is_ajax BIT NOT NULL DEFAULT(0),
        payload_json NVARCHAR(MAX) NULL,
        error_type VARCHAR(50) NULL,
        error_message NVARCHAR(MAX) NULL,
        error_file NVARCHAR(300) NULL,
        error_line INT NULL,
        error_trace NVARCHAR(MAX) NULL,
        memory_peak_kb INT NULL,
        user_agent NVARCHAR(400) NULL
    );

    CREATE INDEX IX_tbl_debug_monitor_created_at ON db_laborat.dbo.tbl_debug_monitor(created_at DESC);
    CREATE INDEX IX_tbl_debug_monitor_log_type ON db_laborat.dbo.tbl_debug_monitor(log_type);
END
";
        @sqlsrv_query($conn, $createSql);

        $stmt2 = @sqlsrv_query($conn, $checkSql);
        if ($stmt2 !== false) {
            $row2 = @sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_NUMERIC);
            @sqlsrv_free_stmt($stmt2);
            if ($row2) {
                qcf_debug_upgrade_table_schema($conn);
                $ready = true;
            }
        }

        return $ready;
    }
}

if (!function_exists('qcf_debug_store_file')) {
    function qcf_debug_store_file($record) {
        $config = qcf_debug_config();
        if (empty($config['log_file'])) {
            return false;
        }

        $path = isset($config['file_log_path']) ? $config['file_log_path'] : (__DIR__ . '/../temp/debug-monitor.log');
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $line = qcf_debug_encode_json($record) . PHP_EOL;
        return @file_put_contents($path, $line, FILE_APPEND | LOCK_EX) !== false;
    }
}

if (!function_exists('qcf_debug_store_db')) {
    function qcf_debug_store_db($record) {
        $config = qcf_debug_config();
        if (empty($config['log_db'])) {
            return false;
        }

        if (!qcf_debug_connection_ready() || !qcf_debug_table_ready()) {
            return false;
        }

        $conn = qcf_debug_sqlsrv_connection();
        $sql = "INSERT INTO db_laborat.dbo.tbl_debug_monitor
            (log_type, request_id, method, path, query_string, status_code, duration_ms, ip_address, user_name, session_user_id, is_ajax, payload_json, error_type, error_message, error_file, error_line, error_trace, memory_peak_kb, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = array(
            isset($record['log_type']) ? $record['log_type'] : 'request',
            isset($record['request_id']) ? $record['request_id'] : null,
            isset($record['method']) ? $record['method'] : null,
            isset($record['path']) ? $record['path'] : null,
            isset($record['query_string']) ? $record['query_string'] : null,
            isset($record['status_code']) ? (int)$record['status_code'] : null,
            isset($record['duration_ms']) ? (int)$record['duration_ms'] : null,
            isset($record['ip_address']) ? $record['ip_address'] : null,
            isset($record['user_name']) ? $record['user_name'] : null,
            isset($record['session_user_id']) ? (int)$record['session_user_id'] : null,
            !empty($record['is_ajax']) ? 1 : 0,
            isset($record['payload']) ? qcf_debug_encode_json($record['payload']) : null,
            isset($record['error_type']) ? $record['error_type'] : null,
            isset($record['error_message']) ? $record['error_message'] : null,
            isset($record['error_file']) ? $record['error_file'] : null,
            isset($record['error_line']) ? (int)$record['error_line'] : null,
            isset($record['error_trace']) ? $record['error_trace'] : null,
            isset($record['memory_peak_kb']) ? (int)$record['memory_peak_kb'] : null,
            isset($record['user_agent']) ? $record['user_agent'] : null
        );

        $stmt = @sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            return false;
        }

        @sqlsrv_free_stmt($stmt);
        return true;
    }
}

if (!function_exists('qcf_debug_build_record')) {
    function qcf_debug_build_record($fatalError = null) {
        if (!defined('QCF_DEBUG_REQUEST_START')) {
            define('QCF_DEBUG_REQUEST_START', microtime(true));
        }
        if (!defined('QCF_DEBUG_REQUEST_ID')) {
            define('QCF_DEBUG_REQUEST_ID', qcf_debug_request_id());
        }

        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $path = qcf_debug_current_path();

        $queryString = parse_url($uri, PHP_URL_QUERY);
        if (!is_string($queryString) && isset($_SERVER['QUERY_STRING'])) {
            $queryString = (string)$_SERVER['QUERY_STRING'];
        }

        $statusCode = function_exists('http_response_code') ? http_response_code() : 200;
        if (!is_int($statusCode) || $statusCode < 100) {
            $statusCode = 200;
        }

        $duration = (int)round((microtime(true) - QCF_DEBUG_REQUEST_START) * 1000);
        $memoryPeak = (int)round(memory_get_peak_usage(true) / 1024);
        $userName = qcf_debug_safe_text(qcf_debug_session_first(array('userLAB', 'usrid', 'user_id10')), 100);
        $sessionUserRaw = qcf_debug_session_first(array('id', 'user_id', 'id10'));
        $sessionUserId = is_numeric($sessionUserRaw) ? (int)$sessionUserRaw : null;

        $payload = array(
            'get' => qcf_debug_sanitize(isset($_GET) ? $_GET : array()),
            'post' => qcf_debug_sanitize(isset($_POST) ? $_POST : array()),
            'server' => array(
                'script' => isset($_SERVER['SCRIPT_NAME']) ? qcf_debug_safe_text($_SERVER['SCRIPT_NAME'], 250) : null,
                'referer' => isset($_SERVER['HTTP_REFERER']) ? qcf_debug_safe_text($_SERVER['HTTP_REFERER'], 500) : null
            )
        );

        $record = array(
            'created_at' => date('Y-m-d H:i:s'),
            'log_type' => $fatalError ? 'error' : 'request',
            'request_id' => QCF_DEBUG_REQUEST_ID,
            'method' => isset($_SERVER['REQUEST_METHOD']) ? qcf_debug_safe_text($_SERVER['REQUEST_METHOD'], 10) : 'CLI',
            'path' => qcf_debug_safe_text($path, 500),
            'query_string' => qcf_debug_safe_text($queryString, 1000),
            'status_code' => $statusCode,
            'duration_ms' => $duration,
            'ip_address' => qcf_debug_safe_text(qcf_debug_client_ip(), 45),
            'user_name' => $userName,
            'session_user_id' => $sessionUserId,
            'is_ajax' => qcf_debug_is_ajax(),
            'payload' => $payload,
            'error_type' => null,
            'error_message' => null,
            'error_file' => null,
            'error_line' => null,
            'error_trace' => null,
            'memory_peak_kb' => $memoryPeak,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? qcf_debug_safe_text($_SERVER['HTTP_USER_AGENT'], 400) : null,
        );

        if (is_array($fatalError)) {
            $record['error_type'] = qcf_debug_error_type_text(isset($fatalError['type']) ? $fatalError['type'] : null);
            $record['error_message'] = qcf_debug_safe_text(isset($fatalError['message']) ? $fatalError['message'] : null, 4000);
            $record['error_file'] = qcf_debug_safe_text(isset($fatalError['file']) ? $fatalError['file'] : null, 300);
            $record['error_line'] = isset($fatalError['line']) ? (int)$fatalError['line'] : null;
            $record['error_trace'] = qcf_debug_build_error_trace(isset($fatalError['trace']) ? $fatalError['trace'] : null);
        }

        return $record;
    }
}

if (!function_exists('qcf_debug_store_record')) {
    function qcf_debug_store_record($record) {
        // Keep file logging as backup if DB insertion fails.
        qcf_debug_store_db($record);
        qcf_debug_store_file($record);
    }
}

if (!function_exists('qcf_debug_handle_php_error')) {
    function qcf_debug_handle_php_error($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $config = qcf_debug_config();
        if (empty($config['capture_php_warnings'])) {
            return false;
        }

        qcf_debug_capture_error(array(
            'type' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line
        ));

        // Keep native PHP error flow unchanged.
        return false;
    }
}

if (!function_exists('qcf_debug_handle_exception')) {
    function qcf_debug_handle_exception($exception) {
        $errorInfo = array(
            'type' => 'UNCAUGHT_' . get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        );
        qcf_debug_capture_error($errorInfo);

        if (!headers_sent()) {
            http_response_code(500);
        }

        if (!ini_get('display_errors')) {
            echo 'Internal Server Error';
            return;
        }

        echo '<pre>';
        echo htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . "\n";
        echo htmlspecialchars($exception->getFile(), ENT_QUOTES, 'UTF-8') . ':' . (int)$exception->getLine() . "\n";
        echo htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8');
        echo '</pre>';
    }
}

if (!function_exists('qcf_debug_shutdown')) {
    function qcf_debug_shutdown() {
        if (defined('QCF_DEBUG_SHUTDOWN_DONE')) {
            return;
        }
        define('QCF_DEBUG_SHUTDOWN_DONE', true);

        if (!qcf_debug_is_enabled()) {
            return;
        }

        if (PHP_SAPI === 'cli') {
            return;
        }

        $path = qcf_debug_current_path();
        if (qcf_debug_should_ignore_path($path)) {
            return;
        }

        $fatal = error_get_last();
        $fatalPayload = null;
        if (is_array($fatal) && isset($fatal['type']) && qcf_debug_fatal_error_type((int)$fatal['type'])) {
            $fatalPayload = $fatal;
        }

        $record = qcf_debug_build_record($fatalPayload);
        qcf_debug_store_record($record);
    }
}

if (qcf_debug_is_enabled() && PHP_SAPI !== 'cli' && isset($_SERVER['REQUEST_METHOD'])) {
    if (!defined('QCF_DEBUG_REQUEST_START')) {
        define('QCF_DEBUG_REQUEST_START', microtime(true));
    }
    if (!defined('QCF_DEBUG_REQUEST_ID')) {
        define('QCF_DEBUG_REQUEST_ID', qcf_debug_request_id());
    }
    set_exception_handler('qcf_debug_handle_exception');
    set_error_handler('qcf_debug_handle_php_error');
    register_shutdown_function('qcf_debug_shutdown');
}
