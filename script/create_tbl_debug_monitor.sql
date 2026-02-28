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

IF COL_LENGTH('db_laborat.dbo.tbl_debug_monitor', 'error_trace') IS NULL
BEGIN
    ALTER TABLE db_laborat.dbo.tbl_debug_monitor ADD error_trace NVARCHAR(MAX) NULL;
END
