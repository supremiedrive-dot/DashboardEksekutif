CREATE TABLE IF NOT EXISTS roles (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    role_id SMALLINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(180) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role_id),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS provinces (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code CHAR(2) NOT NULL,
    name VARCHAR(120) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_provinces_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS regions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    province_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(180) NOT NULL,
    region_type ENUM('provinsi', 'kabupaten_kota', 'kecamatan', 'lainnya') NOT NULL DEFAULT 'kabupaten_kota',
    parent_region_id BIGINT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_regions_code (code),
    UNIQUE KEY uq_regions_province_code (province_id, code),
    KEY idx_regions_province (province_id),
    KEY idx_regions_type (region_type),
    KEY idx_regions_parent (parent_region_id),
    CONSTRAINT fk_regions_province FOREIGN KEY (province_id) REFERENCES provinces (id),
    CONSTRAINT fk_regions_parent FOREIGN KEY (parent_region_id) REFERENCES regions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS report_periods (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    period_code VARCHAR(50) NOT NULL,
    period_label VARCHAR(100) NOT NULL,
    period_type ENUM('monthly', 'quarterly', 'annual', 'custom') NOT NULL DEFAULT 'monthly',
    year_number SMALLINT UNSIGNED NOT NULL,
    month_number TINYINT UNSIGNED NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_report_periods_code (period_code),
    KEY idx_report_periods_year (year_number),
    KEY idx_report_periods_date (period_start, period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS data_sources (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(120) NOT NULL,
    source_type ENUM('excel', 'pemda', 'kkp', 'manual', 'system') NOT NULL,
    owner_name VARCHAR(160) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_data_sources_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS indicator_groups (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(80) NOT NULL,
    name VARCHAR(160) NOT NULL,
    parent_group_id SMALLINT UNSIGNED NULL,
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_indicator_groups_code (code),
    KEY idx_indicator_groups_parent (parent_group_id),
    CONSTRAINT fk_indicator_groups_parent FOREIGN KEY (parent_group_id) REFERENCES indicator_groups (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS indicators (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    group_id SMALLINT UNSIGNED NOT NULL,
    code VARCHAR(80) NOT NULL,
    name VARCHAR(200) NOT NULL,
    short_name VARCHAR(100) NULL,
    unit ENUM('ha', 'm2', 'rp', 'percent', 'count', 'status', 'text', 'date', 'unknown') NOT NULL DEFAULT 'count',
    data_type ENUM('decimal', 'integer', 'string', 'boolean', 'date', 'json') NOT NULL DEFAULT 'decimal',
    is_derived TINYINT(1) NOT NULL DEFAULT 0,
    formula_text TEXT NULL,
    source_field_name VARCHAR(120) NULL,
    source_owner VARCHAR(120) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_indicators_code (code),
    KEY idx_indicators_group (group_id),
    CONSTRAINT fk_indicators_group FOREIGN KEY (group_id) REFERENCES indicator_groups (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS import_batches (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_id SMALLINT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    uploaded_by BIGINT UNSIGNED NULL,
    status ENUM('queued', 'processing', 'completed', 'failed', 'rolled_back') NOT NULL DEFAULT 'queued',
    total_rows INT UNSIGNED NOT NULL DEFAULT 0,
    imported_rows INT UNSIGNED NOT NULL DEFAULT 0,
    started_at TIMESTAMP NULL DEFAULT NULL,
    finished_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_import_batches_source (source_id),
    KEY idx_import_batches_status (status),
    CONSTRAINT fk_import_batches_source FOREIGN KEY (source_id) REFERENCES data_sources (id),
    CONSTRAINT fk_import_batches_user FOREIGN KEY (uploaded_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS import_batch_rows (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    batch_id BIGINT UNSIGNED NOT NULL,
    row_number INT UNSIGNED NOT NULL,
    region_code VARCHAR(20) NULL,
    period_label VARCHAR(80) NULL,
    raw_row JSON NOT NULL,
    normalized_flag TINYINT(1) NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_import_batch_row (batch_id, row_number),
    KEY idx_import_batch_rows_region (region_code),
    KEY idx_import_batch_rows_period (period_label),
    CONSTRAINT fk_import_batch_rows_batch FOREIGN KEY (batch_id) REFERENCES import_batches (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS import_audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    batch_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(80) NOT NULL,
    log_level ENUM('info', 'warning', 'error', 'debug') NOT NULL DEFAULT 'info',
    message TEXT NOT NULL,
    context_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_import_audit_logs_batch (batch_id),
    KEY idx_import_audit_logs_level (log_level),
    CONSTRAINT fk_import_audit_logs_batch FOREIGN KEY (batch_id) REFERENCES import_batches (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS report_snapshots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    region_id BIGINT UNSIGNED NOT NULL,
    period_id BIGINT UNSIGNED NOT NULL,
    source_id SMALLINT UNSIGNED NOT NULL,
    submitted_by BIGINT UNSIGNED NULL,
    imported_batch_id BIGINT UNSIGNED NULL,
    status ENUM('draft', 'validated', 'approved', 'rejected') NOT NULL DEFAULT 'draft',
    raw_summary_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_report_snapshots_region_period_source (region_id, period_id, source_id),
    KEY idx_report_snapshots_region (region_id),
    KEY idx_report_snapshots_period (period_id),
    KEY idx_report_snapshots_source (source_id),
    CONSTRAINT fk_report_snapshots_region FOREIGN KEY (region_id) REFERENCES regions (id),
    CONSTRAINT fk_report_snapshots_period FOREIGN KEY (period_id) REFERENCES report_periods (id),
    CONSTRAINT fk_report_snapshots_source FOREIGN KEY (source_id) REFERENCES data_sources (id),
    CONSTRAINT fk_report_snapshots_submitter FOREIGN KEY (submitted_by) REFERENCES users (id),
    CONSTRAINT fk_report_snapshots_batch FOREIGN KEY (imported_batch_id) REFERENCES import_batches (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS indicator_values (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    snapshot_id BIGINT UNSIGNED NOT NULL,
    indicator_id BIGINT UNSIGNED NOT NULL,
    region_id BIGINT UNSIGNED NOT NULL,
    period_id BIGINT UNSIGNED NOT NULL,
    source_id SMALLINT UNSIGNED NOT NULL,
    value_decimal DECIMAL(18,4) NULL,
    value_percent DECIMAL(5,2) NULL,
    value_text TEXT NULL,
    unit VARCHAR(30) NULL,
    status_text VARCHAR(80) NULL,
    is_derived TINYINT(1) NOT NULL DEFAULT 0,
    raw_value TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_indicator_values_snapshot_indicator (snapshot_id, indicator_id),
    KEY idx_indicator_values_region_period (region_id, period_id),
    KEY idx_indicator_values_indicator (indicator_id),
    KEY idx_indicator_values_source (source_id),
    KEY idx_indicator_values_snapshot (snapshot_id),
    KEY idx_indicator_values_filter (region_id, period_id, indicator_id, source_id),
    CONSTRAINT fk_indicator_values_snapshot FOREIGN KEY (snapshot_id) REFERENCES report_snapshots (id),
    CONSTRAINT fk_indicator_values_indicator FOREIGN KEY (indicator_id) REFERENCES indicators (id),
    CONSTRAINT fk_indicator_values_region FOREIGN KEY (region_id) REFERENCES regions (id),
    CONSTRAINT fk_indicator_values_period FOREIGN KEY (period_id) REFERENCES report_periods (id),
    CONSTRAINT fk_indicator_values_source FOREIGN KEY (source_id) REFERENCES data_sources (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS metric_snapshots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    region_code VARCHAR(20) NOT NULL,
    report_date DATE NOT NULL,
    total_records BIGINT UNSIGNED NOT NULL,
    validated_stage_1 BIGINT UNSIGNED NOT NULL,
    validated_stage_2 BIGINT UNSIGNED NOT NULL,
    area_hectare DECIMAL(18,4) NOT NULL,
    source ENUM('pemda', 'kkp') NOT NULL,
    submitted_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_metric_region_date_source (region_code, report_date, source),
    KEY idx_metric_date_region (report_date, region_code),
    KEY idx_metric_source_date (source, report_date),
    CONSTRAINT fk_metric_region FOREIGN KEY (region_code) REFERENCES regions (code),
    CONSTRAINT fk_metric_user FOREIGN KEY (submitted_by) REFERENCES users (id),
    CONSTRAINT chk_metric_stages CHECK (validated_stage_2 <= validated_stage_1 AND validated_stage_1 <= total_records),
    CONSTRAINT chk_metric_area CHECK (area_hectare >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
