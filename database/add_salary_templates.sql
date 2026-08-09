-- ============================================================
-- Salary structure templates
-- Reusable pay packages that can be bulk-applied to employees.
-- ============================================================

CREATE TABLE IF NOT EXISTS salary_structure_templates (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id         INT UNSIGNED NOT NULL,
    name               VARCHAR(150) NOT NULL,
    description        VARCHAR(255) NULL,
    basic_salary       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    housing_allowance  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    transport_allowance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    other_allowances   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    daman_rate         DECIMAL(5,2)  NOT NULL DEFAULT 3.00,
    income_tax_rate    DECIMAL(5,2)  NOT NULL DEFAULT 2.00,
    currency           VARCHAR(10)   NOT NULL DEFAULT 'USD',
    status             ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by         INT UNSIGNED NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sst_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed starter templates for Byblos Printing SAL
INSERT INTO salary_structure_templates
    (company_id, name, description, basic_salary, housing_allowance, transport_allowance, other_allowances, daman_rate, income_tax_rate, currency)
VALUES
(1, 'Management Package',    'Senior management salary package',            1800.00, 450.00, 0.00, 0.00, 3.00, 2.00, 'USD'),
(1, 'Press & Production',    'Standard package for press/production staff', 900.00,  200.00, 0.00, 0.00, 3.00, 2.00, 'USD'),
(1, 'Admin & Support',       'Admin and support staff package',             750.00,  150.00, 0.00, 0.00, 3.00, 2.00, 'USD');
