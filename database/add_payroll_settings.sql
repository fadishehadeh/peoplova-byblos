-- ============================================================
-- Payroll Company Settings table
-- Stores all configurable payroll parameters per company.
-- ============================================================

CREATE TABLE IF NOT EXISTS payroll_company_settings (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id    INT UNSIGNED NOT NULL,
    setting_key   VARCHAR(100) NOT NULL,
    setting_value TEXT         NOT NULL,
    updated_by    INT UNSIGNED NULL,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pcs (company_id, setting_key),
    INDEX idx_pcs_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed defaults for Byblos Printing SAL (company_id = 1)
INSERT INTO payroll_company_settings (company_id, setting_key, setting_value)
VALUES
(1, 'daman_rate',          '3.00'),
(1, 'income_tax_rate',     '2.00'),
(1, 'working_week',        'mon_sat'),
(1, 'currency',            'USD'),
(1, 'ot_block_minutes',    '90'),
(1, 'ot_amount_per_block', '8.00'),
(1, 'ot_min_minutes',      '30'),
(1, 'late_deduction_per_minute', '0.00'),
(1, 'advance_max_months',  '3')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
