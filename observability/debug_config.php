<?php
return array(
    // Set false to disable all monitoring.
    'enabled' => true,

    // Save log records to SQL Server table db_laborat.dbo.tbl_debug_monitor.
    'log_db' => true,

    // Save log records to local file (JSON lines) as fallback/backup.
    'log_file' => true,

    // Dashboard access: if true, any logged-in user can view /telescope.
    'allow_all_logged_in' => true,

    // If allow_all_logged_in = false, only these roles can access.
    'viewer_roles' => array('Super admin', 'Admin', 'Spv', 'Leader', 'Lab Head'),

    // Keys masked in request payload logging.
    'masked_fields' => array(
        'password',
        'pass',
        'pwd',
        'token',
        'csrf',
        'api_key',
        'secret'
    ),

    // Truncate long payload values.
    'max_value_length' => 800,
    'error_trace_max_length' => 8000,

    // Prevent self-noise in monitor logs.
    'ignore_paths' => array(
        '/telescope*'
    ),

    // Capture warning/notice level errors with file:line + trace.
    'capture_php_warnings' => true,
    'max_errors_per_request' => 25,

    // Default days for truncate old logs action in dashboard.
    'default_truncate_days' => 14,

    // Optional manual URL prefix stripping. Keep empty to rely on auto-detection.
    'path_prefixes' => array('/project/laborat_sqlserver'),

    // Log file location for fallback mode.
    'file_log_path' => __DIR__ . '/../temp/debug-monitor.log',
);
