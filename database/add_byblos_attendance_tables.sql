-- ============================================================
-- Byblos Printing SAL — Payroll & Attendance Extension
-- Run ONCE on the target database.
-- ============================================================

-- ── 1. OT Groups ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ot_groups (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id       INT UNSIGNED NOT NULL,
    name             VARCHAR(50)  NOT NULL,                -- e.g. "Group 1", "G2"
    ot_start_hour    TINYINT UNSIGNED NOT NULL DEFAULT 17, -- 24-h hour OT starts (e.g. 17 = 5 PM)
    ot_start_minute  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    amount_per_block DECIMAL(8,2) NOT NULL DEFAULT 8.00,   -- USD per 90-min block
    block_minutes    SMALLINT UNSIGNED NOT NULL DEFAULT 90,
    min_ot_minutes   SMALLINT UNSIGNED NOT NULL DEFAULT 30, -- must work ≥ 30 min to count any block
    is_active        TINYINT(1) NOT NULL DEFAULT 1,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ot_groups_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Employee OT & Transport assignments ──────────────────
-- Simple: one row per employee, updated in place.
-- Added as columns on employees to avoid N+1 joins.
ALTER TABLE employees
    ADD COLUMN IF NOT EXISTS ot_group_id       INT UNSIGNED NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS transport_tanks   TINYINT UNSIGNED NOT NULL DEFAULT 0;

-- ── 3. Monthly Fuel Price ────────────────────────────────────
CREATE TABLE IF NOT EXISTS fuel_prices (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id      INT UNSIGNED NOT NULL,
    price_month     TINYINT UNSIGNED NOT NULL,  -- 1–12
    price_year      SMALLINT UNSIGNED NOT NULL,
    price_per_tank  DECIMAL(10,2) NOT NULL,     -- USD
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_fuel_price_company_period (company_id, price_month, price_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. Attendance Imports log ────────────────────────────────
CREATE TABLE IF NOT EXISTS attendance_imports (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id   INT UNSIGNED NOT NULL,
    import_month TINYINT UNSIGNED NOT NULL,
    import_year  SMALLINT UNSIGNED NOT NULL,
    filename     VARCHAR(255) NOT NULL,
    row_count    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    matched      SMALLINT UNSIGNED NOT NULL DEFAULT 0,   -- rows matched to employee
    unmatched    SMALLINT UNSIGNED NOT NULL DEFAULT 0,   -- rows not matched
    status       ENUM('pending','processing','done','failed') NOT NULL DEFAULT 'done',
    imported_by  INT UNSIGNED NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_att_import_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. OT Calculations ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS ot_calculations (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id    INT UNSIGNED NOT NULL,
    work_date      DATE         NOT NULL,
    ot_group_id    INT UNSIGNED NOT NULL,
    raw_ot_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0, -- actual OT worked (min)
    complete_blocks SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ot_amount      DECIMAL(8,2) NOT NULL DEFAULT 0.00,  -- USD
    is_night_ot    TINYINT(1) NOT NULL DEFAULT 0,
    import_id      INT UNSIGNED NULL,                   -- which import produced this
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ot_calc_emp_date (employee_id, work_date),
    INDEX idx_ot_calc_employee (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 6. Extend payroll_run_items ──────────────────────────────
ALTER TABLE payroll_run_items
    ADD COLUMN IF NOT EXISTS ot_amount            DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER net_total,
    ADD COLUMN IF NOT EXISTS transport_amount     DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER ot_amount,
    ADD COLUMN IF NOT EXISTS daman_deduction      DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER transport_amount,
    ADD COLUMN IF NOT EXISTS income_tax_deduction DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER daman_deduction,
    ADD COLUMN IF NOT EXISTS advance_deduction    DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER income_tax_deduction,
    ADD COLUMN IF NOT EXISTS late_deduction       DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER advance_deduction;

-- ── 7. Extend salary_structures ─────────────────────────────
ALTER TABLE salary_structures
    ADD COLUMN IF NOT EXISTS daman_rate       DECIMAL(5,2) NOT NULL DEFAULT 3.00 AFTER other_allowances,
    ADD COLUMN IF NOT EXISTS income_tax_rate  DECIMAL(5,2) NOT NULL DEFAULT 2.00 AFTER daman_rate;

-- Update currency default to USD
ALTER TABLE salary_structures
    MODIFY COLUMN currency VARCHAR(3) NOT NULL DEFAULT 'USD';

-- ── 8. Seed default OT groups for first company ──────────────
-- These are updated later via the OT Groups UI; seeded as a starting point.
INSERT IGNORE INTO ot_groups (company_id, name, ot_start_hour, ot_start_minute, amount_per_block, block_minutes, min_ot_minutes)
SELECT
    (SELECT id FROM companies WHERE status = 'active' ORDER BY id LIMIT 1),
    name, ot_start_hour, ot_start_minute, amount_per_block, block_minutes, min_ot_minutes
FROM (
    SELECT 'Group 1' AS name, 17 AS ot_start_hour, 0 AS ot_start_minute, 8.00 AS amount_per_block, 90 AS block_minutes, 30 AS min_ot_minutes
    UNION ALL
    SELECT 'Group 2', 17, 0, 8.00, 90, 30
    UNION ALL
    SELECT 'Group 3', 17, 0, 8.00, 90, 30
) seeds
WHERE EXISTS (SELECT 1 FROM companies WHERE status = 'active');
