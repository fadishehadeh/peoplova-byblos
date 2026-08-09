<?php

declare(strict_types=1);

namespace App\Modules\Payroll;

use App\Core\Database;

final class SalaryTemplateRepository
{
    public function __construct(private Database $database)
    {
    }

    public function companyOptions(): array
    {
        return $this->database->fetchAll(
            "SELECT id, name FROM companies WHERE status = 'active' ORDER BY name ASC"
        );
    }

    public function listForCompany(int $companyId): array
    {
        return $this->database->fetchAll(
            "SELECT * FROM salary_structure_templates
             WHERE company_id = :cid
             ORDER BY status ASC, name ASC",
            ['cid' => $companyId]
        );
    }

    public function find(int $id): ?array
    {
        return $this->database->fetch(
            'SELECT * FROM salary_structure_templates WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function create(array $data, int $userId): int
    {
        $this->database->execute(
            'INSERT INTO salary_structure_templates
                 (company_id, name, description, basic_salary, housing_allowance,
                  transport_allowance, other_allowances, daman_rate, income_tax_rate,
                  currency, status, created_by)
             VALUES
                 (:cid, :name, :desc, :basic, :housing, :transport, :other,
                  :daman, :tax, :currency, :status, :uid)',
            [
                'cid'       => $data['company_id'],
                'name'      => $data['name'],
                'desc'      => $data['description'] ?: null,
                'basic'     => $data['basic_salary'],
                'housing'   => $data['housing_allowance'],
                'transport' => $data['transport_allowance'],
                'other'     => $data['other_allowances'],
                'daman'     => $data['daman_rate'],
                'tax'       => $data['income_tax_rate'],
                'currency'  => $data['currency'],
                'status'    => $data['status'] ?? 'active',
                'uid'       => $userId,
            ]
        );
        return (int) $this->database->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->database->execute(
            'UPDATE salary_structure_templates
             SET name               = :name,
                 description        = :desc,
                 basic_salary       = :basic,
                 housing_allowance  = :housing,
                 transport_allowance= :transport,
                 other_allowances   = :other,
                 daman_rate         = :daman,
                 income_tax_rate    = :tax,
                 currency           = :currency,
                 status             = :status
             WHERE id = :id',
            [
                'id'        => $id,
                'name'      => $data['name'],
                'desc'      => $data['description'] ?: null,
                'basic'     => $data['basic_salary'],
                'housing'   => $data['housing_allowance'],
                'transport' => $data['transport_allowance'],
                'other'     => $data['other_allowances'],
                'daman'     => $data['daman_rate'],
                'tax'       => $data['income_tax_rate'],
                'currency'  => $data['currency'],
                'status'    => $data['status'] ?? 'active',
            ]
        );
    }

    public function delete(int $id): void
    {
        $this->database->execute(
            'DELETE FROM salary_structure_templates WHERE id = :id',
            ['id' => $id]
        );
    }

    /** Employees for a company that can receive a salary structure. */
    public function activeEmployees(int $companyId): array
    {
        return $this->database->fetchAll(
            "SELECT e.id, e.employee_code,
                    CONCAT(e.first_name, ' ', e.last_name) AS full_name,
                    d.name AS department_name,
                    jt.name AS job_title_name,
                    ss.basic_salary AS current_basic,
                    ss.currency AS current_currency,
                    ss.effective_from AS current_from
             FROM employees e
             LEFT JOIN departments d  ON d.id  = e.department_id
             LEFT JOIN job_titles jt  ON jt.id = e.job_title_id
             LEFT JOIN salary_structures ss ON ss.employee_id = e.id
                 AND ss.effective_to IS NULL
             WHERE e.company_id = :cid
               AND e.employee_status = 'active'
             ORDER BY e.first_name, e.last_name ASC",
            ['cid' => $companyId]
        );
    }

    /**
     * Bulk-apply a template to a list of employee IDs.
     * Closes any open salary structure for each employee, then inserts a new one.
     */
    public function bulkApply(int $templateId, array $employeeIds, string $effectiveFrom, int $userId): int
    {
        $tpl = $this->find($templateId);
        if ($tpl === null) {
            throw new \RuntimeException('Template not found.');
        }

        $applied = 0;
        foreach ($employeeIds as $empId) {
            $empId = (int) $empId;
            if ($empId <= 0) continue;

            // Close the existing open structure
            $this->database->execute(
                "UPDATE salary_structures
                 SET effective_to = :eto
                 WHERE employee_id = :eid AND effective_to IS NULL",
                ['eto' => $effectiveFrom, 'eid' => $empId]
            );

            // Insert new structure from template
            $this->database->execute(
                'INSERT INTO salary_structures
                     (employee_id, basic_salary, housing_allowance, transport_allowance,
                      other_allowances, daman_rate, income_tax_rate, currency,
                      effective_from, created_by)
                 VALUES
                     (:eid, :basic, :housing, :transport, :other,
                      :daman, :tax, :currency, :eff, :uid)',
                [
                    'eid'       => $empId,
                    'basic'     => $tpl['basic_salary'],
                    'housing'   => $tpl['housing_allowance'],
                    'transport' => $tpl['transport_allowance'],
                    'other'     => $tpl['other_allowances'],
                    'daman'     => $tpl['daman_rate'],
                    'tax'       => $tpl['income_tax_rate'],
                    'currency'  => $tpl['currency'],
                    'eff'       => $effectiveFrom,
                    'uid'       => $userId,
                ]
            );
            $applied++;
        }

        return $applied;
    }
}
