<?php

declare(strict_types=1);

namespace App\Modules\Payroll;

use App\Core\Database;

final class PayrollRepository
{
    public function __construct(private Database $database)
    {
    }

    // ------------------------------------------------------------------
    // Company helpers
    // ------------------------------------------------------------------

    public function companyOptions(): array
    {
        return $this->database->fetchAll(
            "SELECT id, name FROM companies WHERE status = 'active' ORDER BY name ASC"
        );
    }

    // ------------------------------------------------------------------
    // Salary structures
    // ------------------------------------------------------------------

    public function activeSalaryStructure(int $employeeId): ?array
    {
        return $this->database->fetch(
            'SELECT ss.*, u.email AS created_by_email
             FROM salary_structures ss
             LEFT JOIN users u ON u.id = ss.created_by
             WHERE ss.employee_id = :employee_id
               AND ss.effective_to IS NULL
             LIMIT 1',
            ['employee_id' => $employeeId]
        );
    }

    public function salaryStructureHistory(int $employeeId): array
    {
        return $this->database->fetchAll(
            'SELECT ss.*, u.email AS created_by_email
             FROM salary_structures ss
             LEFT JOIN users u ON u.id = ss.created_by
             WHERE ss.employee_id = :employee_id
             ORDER BY ss.effective_from DESC',
            ['employee_id' => $employeeId]
        );
    }

    /**
     * All active salary structures for a company — used when generating a payroll run.
     */
    public function allActiveSalaryStructures(int $companyId): array
    {
        return $this->database->fetchAll(
            "SELECT ss.employee_id,
                    ss.basic_salary,
                    COALESCE(ss.housing_allowance, 0)   AS housing_allowance,
                    COALESCE(ss.transport_allowance, 0) AS transport_allowance,
                    COALESCE(ss.other_allowances, 0)    AS other_allowances,
                    COALESCE(ss.daman_rate, 3.00)       AS daman_rate,
                    COALESCE(ss.income_tax_rate, 2.00)  AS income_tax_rate,
                    ss.currency
             FROM salary_structures ss
             INNER JOIN employees e ON e.id = ss.employee_id
             WHERE e.company_id    = :company_id
               AND e.archived_at   IS NULL
               AND e.employee_status IN ('active', 'on_leave')
               AND ss.effective_to IS NULL",
            ['company_id' => $companyId]
        );
    }

    /**
     * Save a new salary structure, closing the previous active one in the same transaction.
     */
    public function saveSalaryStructure(array $data, int $actorId): int
    {
        return $this->database->transaction(function () use ($data, $actorId): int {
            $employeeId    = (int) $data['employee_id'];
            $effectiveFrom = (string) $data['effective_from'];

            // Close the current active structure
            $this->database->execute(
                'UPDATE salary_structures
                 SET effective_to = :effective_to
                 WHERE employee_id = :employee_id
                   AND effective_to IS NULL',
                [
                    'effective_to' => date('Y-m-d', strtotime($effectiveFrom . ' -1 day')),
                    'employee_id'  => $employeeId,
                ]
            );

            $this->database->execute(
                'INSERT INTO salary_structures
                     (employee_id, basic_salary, housing_allowance, transport_allowance,
                      other_allowances, daman_rate, income_tax_rate, currency, effective_from, effective_to, created_by)
                 VALUES
                     (:employee_id, :basic_salary, :housing_allowance, :transport_allowance,
                      :other_allowances, :daman_rate, :income_tax_rate, :currency, :effective_from, NULL, :created_by)',
                [
                    'employee_id'         => $employeeId,
                    'basic_salary'        => (float) $data['basic_salary'],
                    'housing_allowance'   => isset($data['housing_allowance'])   && $data['housing_allowance']   !== '' ? (float) $data['housing_allowance']   : null,
                    'transport_allowance' => isset($data['transport_allowance']) && $data['transport_allowance'] !== '' ? (float) $data['transport_allowance'] : null,
                    'other_allowances'    => isset($data['other_allowances'])    && $data['other_allowances']    !== '' ? (float) $data['other_allowances']    : null,
                    'daman_rate'          => isset($data['daman_rate'])          && $data['daman_rate']          !== '' ? (float) $data['daman_rate']          : 3.00,
                    'income_tax_rate'     => isset($data['income_tax_rate'])     && $data['income_tax_rate']     !== '' ? (float) $data['income_tax_rate']     : 2.00,
                    'currency'            => (string) ($data['currency'] ?? 'USD'),
                    'effective_from'      => $effectiveFrom,
                    'created_by'          => $actorId,
                ]
            );

            return (int) $this->database->lastInsertId();
        });
    }

    /**
     * Employees with their current salary structure (or NULL if none configured).
     */
    public function employeeSalaryList(int $companyId, string $search = ''): array
    {
        $sql = "SELECT e.id AS employee_id,
                       e.employee_code,
                       CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS full_name,
                       e.employee_status,
                       d.name AS department_name,
                       jt.name AS job_title_name,
                       ss.basic_salary,
                       ss.currency,
                       ss.effective_from,
                       ss.id AS structure_id
                FROM employees e
                LEFT JOIN departments d  ON d.id = e.department_id
                LEFT JOIN job_titles jt  ON jt.id = e.job_title_id
                LEFT JOIN salary_structures ss
                    ON ss.employee_id = e.id AND ss.effective_to IS NULL
                WHERE e.company_id  = :company_id
                  AND e.archived_at IS NULL";
        $params = ['company_id' => $companyId];

        if ($search !== '') {
            $like = '%' . $search . '%';
            $sql .= " AND (e.first_name LIKE :s1 OR e.last_name LIKE :s2 OR e.employee_code LIKE :s3
                          OR CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) LIKE :s4)";
            $params['s1'] = $like;
            $params['s2'] = $like;
            $params['s3'] = $like;
            $params['s4'] = $like;
        }

        $sql .= ' ORDER BY e.first_name ASC, e.last_name ASC';

        return $this->database->fetchAll($sql, $params);
    }

    public function findEmployee(int $employeeId): ?array
    {
        return $this->database->fetch(
            "SELECT e.id, e.employee_code, e.company_id,
                    CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS full_name,
                    e.employee_status,
                    d.name AS department_name,
                    jt.name AS job_title_name,
                    c.name AS company_name
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN job_titles jt ON jt.id = e.job_title_id
             LEFT JOIN companies c   ON c.id = e.company_id
             WHERE e.id = :id AND e.archived_at IS NULL
             LIMIT 1",
            ['id' => $employeeId]
        );
    }

    // ------------------------------------------------------------------
    // Payroll runs
    // ------------------------------------------------------------------

    public function listRuns(int $companyId): array
    {
        return $this->database->fetchAll(
            "SELECT pr.id, pr.period_month, pr.period_year, pr.status,
                    pr.finalized_at, pr.created_at,
                    u.email AS run_by_email,
                    (SELECT COUNT(*) FROM payroll_run_items pri WHERE pri.payroll_run_id = pr.id) AS item_count
             FROM payroll_runs pr
             LEFT JOIN users u ON u.id = pr.run_by
             WHERE pr.company_id = :company_id
             ORDER BY pr.period_year DESC, pr.period_month DESC",
            ['company_id' => $companyId]
        );
    }

    public function findRun(int $runId): ?array
    {
        return $this->database->fetch(
            'SELECT pr.*, c.name AS company_name, u.email AS run_by_email
             FROM payroll_runs pr
             LEFT JOIN companies c ON c.id = pr.company_id
             LEFT JOIN users u     ON u.id = pr.run_by
             WHERE pr.id = :id
             LIMIT 1',
            ['id' => $runId]
        );
    }

    public function findRunWithItems(int $runId): array
    {
        $run = $this->findRun($runId);

        if ($run === null) {
            return [];
        }

        $items = $this->database->fetchAll(
            "SELECT pri.*,
                    CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name,
                    e.employee_code,
                    d.name AS department_name,
                    jt.name AS job_title_name
             FROM payroll_run_items pri
             INNER JOIN employees e   ON e.id = pri.employee_id
             LEFT JOIN departments d  ON d.id = e.department_id
             LEFT JOIN job_titles jt  ON jt.id = e.job_title_id
             WHERE pri.payroll_run_id = :run_id
             ORDER BY e.first_name ASC, e.last_name ASC",
            ['run_id' => $runId]
        );

        return ['run' => $run, 'items' => $items];
    }

    /**
     * Create a draft payroll run and populate its line items.
     * Throws if a run for the same company/month/year already exists.
     */
    public function createDraftRun(int $companyId, int $month, int $year, int $actorId): int
    {
        return $this->database->transaction(function () use ($companyId, $month, $year, $actorId): int {
            $existing = $this->database->fetchValue(
                'SELECT id FROM payroll_runs
                 WHERE company_id = :company_id AND period_month = :month AND period_year = :year
                 LIMIT 1',
                ['company_id' => $companyId, 'month' => $month, 'year' => $year]
            );

            if ($existing !== null) {
                throw new \RuntimeException('A payroll run already exists for this company and period.');
            }

            $this->database->execute(
                'INSERT INTO payroll_runs (company_id, period_month, period_year, status, run_by)
                 VALUES (:company_id, :month, :year, :status, :run_by)',
                ['company_id' => $companyId, 'month' => $month, 'year' => $year, 'status' => 'draft', 'run_by' => $actorId]
            );

            $runId = (int) $this->database->lastInsertId();

            $this->generateRunItems($runId, $companyId, $month, $year);

            return $runId;
        });
    }

    /**
     * Populate payroll_run_items for a given run.
     * Salary values are snapshotted; unpaid leave, OT, transport (fuel-based), Daman, and income tax computed automatically.
     *
     * Cross-month note: only leaves whose start_date falls in the target month are counted.
     * HR can manually adjust any line item before finalising.
     */
    private function generateRunItems(int $runId, int $companyId, int $month, int $year): void
    {
        $structures = $this->allActiveSalaryStructures($companyId);

        // Pre-fetch OT totals by employee for the period
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate   = date('Y-m-t', strtotime($startDate));
        $otRows    = $this->database->fetchAll(
            'SELECT oc.employee_id, SUM(oc.ot_amount) AS total_ot
             FROM ot_calculations oc
             JOIN employees e ON e.id = oc.employee_id
             WHERE e.company_id = :cid AND oc.work_date >= :s AND oc.work_date <= :e
             GROUP BY oc.employee_id',
            ['cid' => $companyId, 's' => $startDate, 'e' => $endDate]
        );
        $otByEmp = [];
        foreach ($otRows as $r) { $otByEmp[(int) $r['employee_id']] = (float) $r['total_ot']; }

        // Fuel price for this month (USD per tank)
        $fuelRow     = $this->database->fetch(
            'SELECT price_per_tank FROM fuel_prices
             WHERE company_id = :cid AND price_month = :m AND price_year = :y LIMIT 1',
            ['cid' => $companyId, 'm' => $month, 'y' => $year]
        );
        $fuelPerTank = $fuelRow ? (float) $fuelRow['price_per_tank'] : 0.0;

        // Working days in month: Mon–Sat (Lebanon 6-day week)
        $workingDays = $this->countWorkingDays($month, $year);

        foreach ($structures as $s) {
            $employeeId = (int) $s['employee_id'];

            // Employee tank count for transport calculation
            $empRow = $this->database->fetch(
                'SELECT transport_tanks FROM employees WHERE id = :id LIMIT 1',
                ['id' => $employeeId]
            );
            $tanks = $empRow ? (int) $empRow['transport_tanks'] : 0;

            // Unpaid leave deduction: days where leave starts in the target month
            $unpaidDays = (float) ($this->database->fetchValue(
                "SELECT COALESCE(SUM(lr.days_requested), 0)
                 FROM leave_requests lr
                 INNER JOIN leave_types lt ON lt.id = lr.leave_type_id
                 WHERE lr.employee_id = :eid
                   AND lt.is_paid     = 0
                   AND lr.status      = 'approved'
                   AND YEAR(lr.start_date)  = :year
                   AND MONTH(lr.start_date) = :month",
                ['eid' => $employeeId, 'year' => $year, 'month' => $month]
            ) ?? 0);

            $basic   = (float) $s['basic_salary'];
            $housing = (float) $s['housing_allowance'];
            $other   = (float) $s['other_allowances'];

            // Unpaid leave deduction on basic (daily rate = basic / 30)
            $deductions = round($unpaidDays * ($basic / 30), 2);

            // OT amount from ot_calculations (pre-computed by BioTime import)
            $otAmount = $otByEmp[$employeeId] ?? 0.0;

            // Transport: tanks × fuel price, minus pro-rated leave deduction
            $monthlyTransport      = round($tanks * $fuelPerTank, 2);
            $transportDeductPerDay = $workingDays > 0 ? ($monthlyTransport / $workingDays) : 0.0;
            $transportAmount       = max(0.0, round($monthlyTransport - ($unpaidDays * $transportDeductPerDay), 2));

            // Daman and income tax on basic salary
            $damanRate  = isset($s['daman_rate'])     ? (float) $s['daman_rate']     : 3.0;
            $taxRate    = isset($s['income_tax_rate']) ? (float) $s['income_tax_rate'] : 2.0;
            $daman      = round($basic * $damanRate / 100, 2);
            $incomeTax  = round($basic * $taxRate   / 100, 2);

            $gross = round($basic + $housing + $other + $otAmount + $transportAmount, 2);
            $net   = round($gross - $deductions - $daman - $incomeTax, 2);

            $this->database->execute(
                'INSERT INTO payroll_run_items
                     (payroll_run_id, employee_id, basic_salary, housing_allowance,
                      transport_allowance, other_allowances, deductions, gross_total, net_total,
                      ot_amount, transport_amount, daman_deduction, income_tax_deduction)
                 VALUES
                     (:run_id, :employee_id, :basic, :housing, 0, :other, :deductions, :gross, :net,
                      :ot, :trans, :daman, :tax)',
                [
                    'run_id'      => $runId,
                    'employee_id' => $employeeId,
                    'basic'       => $basic,
                    'housing'     => $housing,
                    'other'       => $other,
                    'deductions'  => $deductions,
                    'gross'       => $gross,
                    'net'         => $net,
                    'ot'          => round($otAmount, 2),
                    'trans'       => $transportAmount,
                    'daman'       => $daman,
                    'tax'         => $incomeTax,
                ]
            );
        }
    }

    /** Count Mon–Sat days in the given month (Lebanon 6-day week) */
    private function countWorkingDays(int $month, int $year): int
    {
        $days  = 0;
        $total = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
        for ($d = 1; $d <= $total; $d++) {
            $dow = (int) date('N', mktime(0, 0, 0, $month, $d, $year)); // 1=Mon … 7=Sun
            if ($dow !== 7) { // exclude Sunday only
                $days++;
            }
        }
        return $days;
    }

    /**
     * Adjust a single line item (draft runs only).
     * Recalculates gross and net from the updated fields and marks the row as manually adjusted.
     */
    public function updateRunItem(int $itemId, array $fields, int $actorId): void
    {
        $item = $this->database->fetch(
            'SELECT pri.*, pr.status
             FROM payroll_run_items pri
             INNER JOIN payroll_runs pr ON pr.id = pri.payroll_run_id
             WHERE pri.id = :id LIMIT 1',
            ['id' => $itemId]
        );

        if ($item === null) {
            throw new \RuntimeException('Payroll line item not found.');
        }

        if ($item['status'] !== 'draft') {
            throw new \RuntimeException('Cannot edit a finalised payroll run.');
        }

        $basic      = isset($fields['basic_salary'])         ? (float) $fields['basic_salary']         : (float) $item['basic_salary'];
        $housing    = isset($fields['housing_allowance'])    ? (float) $fields['housing_allowance']    : (float) $item['housing_allowance'];
        $other      = isset($fields['other_allowances'])     ? (float) $fields['other_allowances']     : (float) $item['other_allowances'];
        $deductions = isset($fields['deductions'])           ? (float) $fields['deductions']           : (float) $item['deductions'];
        $otAmount   = isset($fields['ot_amount'])            ? (float) $fields['ot_amount']            : (float) ($item['ot_amount'] ?? 0);
        $transAmount= isset($fields['transport_amount'])     ? (float) $fields['transport_amount']     : (float) ($item['transport_amount'] ?? 0);
        $daman      = isset($fields['daman_deduction'])      ? (float) $fields['daman_deduction']      : (float) ($item['daman_deduction'] ?? 0);
        $incomeTax  = isset($fields['income_tax_deduction']) ? (float) $fields['income_tax_deduction'] : (float) ($item['income_tax_deduction'] ?? 0);
        $advance    = isset($fields['advance_deduction'])    ? (float) $fields['advance_deduction']    : (float) ($item['advance_deduction'] ?? 0);
        $notes      = isset($fields['notes']) ? (string) $fields['notes'] : (string) ($item['notes'] ?? '');

        $gross = round($basic + $housing + $other + $otAmount + $transAmount, 2);
        $net   = round($gross - $deductions - $daman - $incomeTax - $advance, 2);

        $this->database->execute(
            'UPDATE payroll_run_items
             SET basic_salary           = :basic,
                 housing_allowance      = :housing,
                 other_allowances       = :other,
                 deductions             = :deductions,
                 ot_amount              = :ot,
                 transport_amount       = :trans,
                 daman_deduction        = :daman,
                 income_tax_deduction   = :tax,
                 advance_deduction      = :advance,
                 gross_total            = :gross,
                 net_total              = :net,
                 is_manually_adjusted   = 1,
                 notes                  = :notes
             WHERE id = :id',
            [
                'basic'      => $basic,
                'housing'    => $housing,
                'other'      => $other,
                'deductions' => $deductions,
                'ot'         => round($otAmount, 2),
                'trans'      => round($transAmount, 2),
                'daman'      => round($daman, 2),
                'tax'        => round($incomeTax, 2),
                'advance'    => round($advance, 2),
                'gross'      => $gross,
                'net'        => $net,
                'notes'      => $notes !== '' ? $notes : null,
                'id'         => $itemId,
            ]
        );
    }

    /**
     * Lock a draft run as finalised. Throws if already finalised.
     */
    public function finalizeRun(int $runId, int $actorId): void
    {
        $run = $this->findRun($runId);

        if ($run === null) {
            throw new \RuntimeException('Payroll run not found.');
        }

        if ($run['status'] === 'finalized') {
            throw new \RuntimeException('This payroll run is already finalised.');
        }

        $this->database->execute(
            "UPDATE payroll_runs
             SET status = 'finalized', finalized_at = NOW()
             WHERE id = :id",
            ['id' => $runId]
        );
    }

    public function findRunItem(int $itemId): ?array
    {
        return $this->database->fetch(
            "SELECT pri.*,
                    CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name,
                    e.employee_code,
                    d.name AS department_name,
                    jt.name AS job_title_name,
                    c.name AS company_name,
                    pr.period_month, pr.period_year, pr.status AS run_status
             FROM payroll_run_items pri
             INNER JOIN employees e   ON e.id = pri.employee_id
             INNER JOIN payroll_runs pr ON pr.id = pri.payroll_run_id
             INNER JOIN companies c   ON c.id = pr.company_id
             LEFT JOIN departments d  ON d.id = e.department_id
             LEFT JOIN job_titles jt  ON jt.id = e.job_title_id
             WHERE pri.id = :id
             LIMIT 1",
            ['id' => $itemId]
        );
    }

    public function findRunItemByEmployeeAndRun(int $runId, int $employeeId): ?array
    {
        return $this->database->fetch(
            "SELECT pri.*,
                    CONCAT_WS(' ', e.first_name, e.middle_name, e.last_name) AS employee_name,
                    e.employee_code,
                    d.name AS department_name,
                    jt.name AS job_title_name,
                    c.name AS company_name,
                    pr.period_month, pr.period_year, pr.status AS run_status, pr.finalized_at
             FROM payroll_run_items pri
             INNER JOIN employees e   ON e.id = pri.employee_id
             INNER JOIN payroll_runs pr ON pr.id = pri.payroll_run_id
             INNER JOIN companies c   ON c.id = pr.company_id
             LEFT JOIN departments d  ON d.id = e.department_id
             LEFT JOIN job_titles jt  ON jt.id = e.job_title_id
             WHERE pri.payroll_run_id = :run_id AND pri.employee_id = :employee_id
             LIMIT 1",
            ['run_id' => $runId, 'employee_id' => $employeeId]
        );
    }
}
