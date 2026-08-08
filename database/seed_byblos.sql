-- ============================================================
-- Byblos Printing SAL — Fresh demo seed
-- Wipes existing employee data and seeds 7 people (2 managers)
-- with full payroll, OT, transport, leave, and Daman/tax data.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── Clear employee-dependent tables ─────────────────────────
TRUNCATE TABLE payroll_run_items;
TRUNCATE TABLE payroll_runs;
TRUNCATE TABLE ot_calculations;
TRUNCATE TABLE attendance_imports;
TRUNCATE TABLE attendance_records;
TRUNCATE TABLE fuel_prices;
TRUNCATE TABLE salary_structures;
TRUNCATE TABLE leave_request_attachments;
TRUNCATE TABLE leave_approvals;
TRUNCATE TABLE leave_requests;
TRUNCATE TABLE leave_balances;
TRUNCATE TABLE leave_accrual_logs;
TRUNCATE TABLE employee_documents;
TRUNCATE TABLE employee_document_versions;
TRUNCATE TABLE document_access_logs;
TRUNCATE TABLE document_alerts;
TRUNCATE TABLE employee_emergency_contacts;
TRUNCATE TABLE employee_identifications;
TRUNCATE TABLE employee_insurance;
TRUNCATE TABLE employee_intake_submissions;
TRUNCATE TABLE employee_intake_contacts;
TRUNCATE TABLE employee_intake_documents;
TRUNCATE TABLE intake_submission_identifications;
TRUNCATE TABLE employee_notes;
TRUNCATE TABLE employee_onboarding;
TRUNCATE TABLE employee_onboarding_tasks;
TRUNCATE TABLE employee_reporting_lines;
TRUNCATE TABLE employee_schedule_assignments;
TRUNCATE TABLE employee_status_history;
TRUNCATE TABLE employee_history_logs;
TRUNCATE TABLE offboarding_records;
TRUNCATE TABLE offboarding_tasks;
TRUNCATE TABLE letter_requests;
TRUNCATE TABLE policy_acknowledgements;
TRUNCATE TABLE api_tokens;
TRUNCATE TABLE refresh_tokens;
DELETE FROM notifications WHERE 1=1;
DELETE FROM employees WHERE 1=1;
-- Remove non-admin users (keep id=1)
DELETE FROM users WHERE id != 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ── Employees ────────────────────────────────────────────────
-- IDs 1-7: 2 managers then 5 staff
INSERT INTO employees
    (id, company_id, department_id, manager_employee_id,
     employee_code, first_name, last_name,
     work_email, gender, nationality,
     employment_type, joining_date, employee_status,
     ot_group_id, transport_tanks, created_by)
VALUES
-- Managers (phone/personal_email/dob omitted — stored encrypted by app)
(1, 1, 4, NULL,    'BYB001', 'Karim',   'Nassar',     'karim.nassar@byblos.com.lb',  'male',   'Lebanon', 'full_time', '2022-03-01', 'active', 1, 2, 1),
(2, 1, 3, NULL,    'BYB002', 'Lina',    'Khoury',     'lina.khoury@byblos.com.lb',   'female', 'Lebanon', 'full_time', '2021-06-15', 'active', 2, 1, 1),
-- Staff
(3, 1, 4, 1,       'BYB003', 'Georges', 'Saade',      'georges.saade@byblos.com.lb', 'male',   'Lebanon', 'full_time', '2023-01-10', 'active', 1, 2, 1),
(4, 1, 2, 2,       'BYB004', 'Nadia',   'Rahhal',     'nadia.rahhal@byblos.com.lb',  'female', 'Lebanon', 'full_time', '2023-04-01', 'active', 3, 1, 1),
(5, 1, 4, 1,       'BYB005', 'Elie',    'Gemayel',    'elie.gemayel@byblos.com.lb',  'male',   'Lebanon', 'full_time', '2022-09-05', 'active', 1, 2, 1),
(6, 1, 5, 1,       'BYB006', 'Maya',    'Haddad',     'maya.haddad@byblos.com.lb',   'female', 'Lebanon', 'full_time', '2023-07-01', 'active', 2, 1, 1),
(7, 1, 4, 1,       'BYB007', 'Charbel', 'Abi Nader',  'charbel.abn@byblos.com.lb',   'male',   'Lebanon', 'full_time', '2022-11-15', 'active', 1, 2, 1);

-- ── Salary Structures ────────────────────────────────────────
-- daman_rate=3.00%, income_tax_rate=2.00%, currency=USD
INSERT INTO salary_structures
    (employee_id, basic_salary, housing_allowance, transport_allowance, other_allowances,
     daman_rate, income_tax_rate, currency, effective_from, created_by)
VALUES
(1, 1800.00, 450.00, 0.00, 0.00, 3.00, 2.00, 'USD', '2022-03-01', 1),
(2, 1500.00, 350.00, 0.00, 0.00, 3.00, 2.00, 'USD', '2021-06-15', 1),
(3,  900.00, 200.00, 0.00, 0.00, 3.00, 2.00, 'USD', '2023-01-10', 1),
(4,  750.00, 150.00, 0.00, 0.00, 3.00, 2.00, 'USD', '2023-04-01', 1),
(5,  850.00, 180.00, 0.00, 0.00, 3.00, 2.00, 'USD', '2022-09-05', 1),
(6,  950.00, 200.00, 0.00, 0.00, 3.00, 2.00, 'USD', '2023-07-01', 1),
(7,  800.00, 160.00, 0.00, 0.00, 3.00, 2.00, 'USD', '2022-11-15', 1);

-- ── Fuel Price — July 2026 ───────────────────────────────────
INSERT INTO fuel_prices (company_id, price_month, price_year, price_per_tank)
VALUES (1, 7, 2026, 45.00);

-- ── OT Calculations — July 2026 ─────────────────────────────
-- Group 1 employees: Karim(1), Georges(3), Elie(5), Charbel(7)
-- Group 2 employees: Lina(2), Maya(6)
-- Group 3: Nadia(4) — no OT for this seed

-- Karim (Group 1, 14 blocks total)
INSERT INTO ot_calculations (employee_id, work_date, ot_group_id, raw_ot_minutes, complete_blocks, ot_amount, is_night_ot) VALUES
(1, '2026-07-02', 1, 135, 1, 8.00, 0),
(1, '2026-07-07', 1, 210, 2, 16.00, 0),
(1, '2026-07-09', 1, 135, 1, 8.00, 0),
(1, '2026-07-14', 1, 300, 3, 24.00, 0),
(1, '2026-07-16', 1, 210, 2, 16.00, 0),
(1, '2026-07-21', 1, 135, 1, 8.00, 0),
(1, '2026-07-23', 1, 300, 3, 24.00, 0),
(1, '2026-07-28', 1, 135, 1, 8.00, 0);

-- Lina (Group 2, 6 blocks total)
INSERT INTO ot_calculations (employee_id, work_date, ot_group_id, raw_ot_minutes, complete_blocks, ot_amount, is_night_ot) VALUES
(2, '2026-07-07', 2, 180, 2, 16.00, 0),
(2, '2026-07-14', 2, 135, 1, 8.00, 0),
(2, '2026-07-21', 2, 180, 2, 16.00, 0),
(2, '2026-07-28', 2, 135, 1, 8.00, 0);

-- Georges (Group 1, 9 blocks total)
INSERT INTO ot_calculations (employee_id, work_date, ot_group_id, raw_ot_minutes, complete_blocks, ot_amount, is_night_ot) VALUES
(3, '2026-07-03', 1, 135, 1, 8.00, 0),
(3, '2026-07-08', 1, 210, 2, 16.00, 0),
(3, '2026-07-15', 1, 210, 2, 16.00, 0),
(3, '2026-07-22', 1, 300, 3, 24.00, 0),
(3, '2026-07-29', 1, 135, 1, 8.00, 0);

-- Elie (Group 1, 7 blocks total)
INSERT INTO ot_calculations (employee_id, work_date, ot_group_id, raw_ot_minutes, complete_blocks, ot_amount, is_night_ot) VALUES
(5, '2026-07-07', 1, 210, 2, 16.00, 0),
(5, '2026-07-14', 1, 135, 1, 8.00, 0),
(5, '2026-07-21', 1, 210, 2, 16.00, 0),
(5, '2026-07-28', 1, 210, 2, 16.00, 0);

-- Maya (Group 2, 4 blocks total)
INSERT INTO ot_calculations (employee_id, work_date, ot_group_id, raw_ot_minutes, complete_blocks, ot_amount, is_night_ot) VALUES
(6, '2026-07-09', 2, 135, 1, 8.00, 0),
(6, '2026-07-16', 2, 180, 2, 16.00, 0),
(6, '2026-07-23', 2, 135, 1, 8.00, 0);

-- Charbel (Group 1, 8 blocks total)
INSERT INTO ot_calculations (employee_id, work_date, ot_group_id, raw_ot_minutes, complete_blocks, ot_amount, is_night_ot) VALUES
(7, '2026-07-07', 1, 210, 2, 16.00, 0),
(7, '2026-07-10', 1, 135, 1, 8.00, 0),
(7, '2026-07-17', 1, 210, 2, 16.00, 0),
(7, '2026-07-24', 1, 300, 3, 24.00, 0);

-- ── Payroll Run — July 2026 (finalized) ──────────────────────
INSERT INTO payroll_runs (id, company_id, period_month, period_year, status, run_by, finalized_at)
VALUES (1, 1, 7, 2026, 'finalized', 1, '2026-08-01 09:00:00');

-- ── Payroll Run Items ────────────────────────────────────────
-- Fuel: $45/tank. Working days July 2026 = 27 (Mon-Sat).
-- Transport = tanks × 45 (no leave deductions for this seed).
-- Daman = basic × 3%.  Tax = basic × 2%.
-- Gross = basic + housing + OT + transport.
-- Net   = gross − daman − tax.
--
-- emp  basic   housing  OT     transport  daman   tax    gross    net
-- 1    1800    450      112.00  90.00     54.00   36.00  2452.00  2362.00
-- 2    1500    350       48.00  45.00     45.00   30.00  1943.00  1868.00
-- 3     900    200       72.00  90.00     27.00   18.00  1262.00  1217.00
-- 4     750    150        0.00  45.00     22.50   15.00   945.00   907.50
-- 5     850    180       56.00  90.00     25.50   17.00  1176.00  1133.50
-- 6     950    200       32.00  45.00     28.50   19.00  1227.00  1179.50
-- 7     800    160       64.00  90.00     24.00   16.00  1114.00  1074.00

INSERT INTO payroll_run_items
    (payroll_run_id, employee_id,
     basic_salary, housing_allowance, transport_allowance, other_allowances,
     deductions, gross_total, net_total,
     ot_amount, transport_amount, daman_deduction, income_tax_deduction,
     advance_deduction, late_deduction, is_manually_adjusted)
VALUES
(1, 1, 1800.00, 450.00, 0.00, 0.00, 90.00, 2452.00, 2362.00, 112.00, 90.00, 54.00, 36.00, 0.00, 0.00, 0),
(1, 2, 1500.00, 350.00, 0.00, 0.00, 75.00, 1943.00, 1868.00,  48.00, 45.00, 45.00, 30.00, 0.00, 0.00, 0),
(1, 3,  900.00, 200.00, 0.00, 0.00, 45.00, 1262.00, 1217.00,  72.00, 90.00, 27.00, 18.00, 0.00, 0.00, 0),
(1, 4,  750.00, 150.00, 0.00, 0.00, 37.50,  945.00,  907.50,   0.00, 45.00, 22.50, 15.00, 0.00, 0.00, 0),
(1, 5,  850.00, 180.00, 0.00, 0.00, 42.50, 1176.00, 1133.50,  56.00, 90.00, 25.50, 17.00, 0.00, 0.00, 0),
(1, 6,  950.00, 200.00, 0.00, 0.00, 47.50, 1227.00, 1179.50,  32.00, 45.00, 28.50, 19.00, 0.00, 0.00, 0),
(1, 7,  800.00, 160.00, 0.00, 0.00, 40.00, 1114.00, 1074.00,  64.00, 90.00, 24.00, 16.00, 0.00, 0.00, 0);

-- ── Leave Balances — 2026 ────────────────────────────────────
-- Leave type 1=Annual, 2=Sick for all 7 employees
INSERT INTO leave_balances (employee_id, leave_type_id, balance_year, opening_balance, accrued, used_amount, closing_balance) VALUES
-- Annual leave (15 days opening, accrued pro-rata through July)
(1, 1, 2026, 15.00,  8.75, 2.00, 21.75),
(2, 1, 2026, 15.00,  8.75, 0.00, 23.75),
(3, 1, 2026, 12.00,  8.75, 3.00, 17.75),
(4, 1, 2026, 12.00,  8.75, 1.00, 19.75),
(5, 1, 2026, 12.00,  8.75, 0.00, 20.75),
(6, 1, 2026,  6.00,  8.75, 2.00, 12.75),
(7, 1, 2026, 12.00,  8.75, 1.00, 19.75),
-- Sick leave (10 days flat per year)
(1, 2, 2026, 10.00, 0.00, 0.00, 10.00),
(2, 2, 2026, 10.00, 0.00, 1.00,  9.00),
(3, 2, 2026, 10.00, 0.00, 0.00, 10.00),
(4, 2, 2026, 10.00, 0.00, 2.00,  8.00),
(5, 2, 2026, 10.00, 0.00, 0.00, 10.00),
(6, 2, 2026, 10.00, 0.00, 0.00, 10.00),
(7, 2, 2026, 10.00, 0.00, 1.00,  9.00);

-- ── Leave Requests (a few approved requests) ──────────────────
INSERT INTO leave_requests
    (employee_id, leave_type_id, start_date, end_date, days_requested, reason, status, submitted_at, decided_at)
VALUES
(3, 1, '2026-07-22', '2026-07-24', 3.00, 'Annual family vacation', 'approved', '2026-07-15 10:00:00', '2026-07-16 09:00:00'),
(4, 2, '2026-06-10', '2026-06-11', 2.00, 'Medical appointment',   'approved', '2026-06-09 08:30:00', '2026-06-09 12:00:00'),
(7, 1, '2026-06-30', '2026-06-30', 1.00, 'Personal errand',       'approved', '2026-06-28 11:00:00', '2026-06-29 10:00:00');

-- ── Update OT Groups names to be more descriptive ─────────────
UPDATE ot_groups SET
    name = 'Group 1 - Press & Production',
    ot_start_hour = 17, ot_start_minute = 0,
    amount_per_block = 8.00, block_minutes = 90, min_ot_minutes = 30
WHERE id = 1;

UPDATE ot_groups SET
    name = 'Group 2 - Management',
    ot_start_hour = 18, ot_start_minute = 0,
    amount_per_block = 8.00, block_minutes = 90, min_ot_minutes = 30
WHERE id = 2;

UPDATE ot_groups SET
    name = 'Group 3 - Admin & Support',
    ot_start_hour = 17, ot_start_minute = 30,
    amount_per_block = 8.00, block_minutes = 90, min_ot_minutes = 30
WHERE id = 3;
