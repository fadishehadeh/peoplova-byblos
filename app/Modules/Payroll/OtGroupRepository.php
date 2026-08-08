<?php

declare(strict_types=1);

namespace App\Modules\Payroll;

use App\Core\Database;

final class OtGroupRepository
{
    public function __construct(private Database $database)
    {
    }

    public function allForCompany(int $companyId): array
    {
        return $this->database->fetchAll(
            'SELECT g.*,
                    COUNT(e.id) AS employee_count
             FROM ot_groups g
             LEFT JOIN employees e ON e.ot_group_id = g.id AND e.employee_status = \'active\'
             WHERE g.company_id = :company_id
             GROUP BY g.id
             ORDER BY g.name ASC',
            ['company_id' => $companyId]
        );
    }

    public function find(int $id): ?array
    {
        return $this->database->fetch(
            'SELECT * FROM ot_groups WHERE id = :id',
            ['id' => $id]
        );
    }

    public function optionsForCompany(int $companyId): array
    {
        return $this->database->fetchAll(
            'SELECT id, name FROM ot_groups WHERE company_id = :company_id AND is_active = 1 ORDER BY name ASC',
            ['company_id' => $companyId]
        );
    }

    public function create(int $companyId, array $data): int
    {
        $this->database->execute(
            'INSERT INTO ot_groups (company_id, name, ot_start_hour, ot_start_minute, amount_per_block, block_minutes, min_ot_minutes)
             VALUES (:company_id, :name, :ot_start_hour, :ot_start_minute, :amount_per_block, :block_minutes, :min_ot_minutes)',
            [
                'company_id'       => $companyId,
                'name'             => $data['name'],
                'ot_start_hour'    => (int) $data['ot_start_hour'],
                'ot_start_minute'  => (int) $data['ot_start_minute'],
                'amount_per_block' => (float) $data['amount_per_block'],
                'block_minutes'    => (int) $data['block_minutes'],
                'min_ot_minutes'   => (int) $data['min_ot_minutes'],
            ]
        );
        return (int) $this->database->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->database->execute(
            'UPDATE ot_groups
             SET name             = :name,
                 ot_start_hour   = :ot_start_hour,
                 ot_start_minute = :ot_start_minute,
                 amount_per_block = :amount_per_block,
                 block_minutes   = :block_minutes,
                 min_ot_minutes  = :min_ot_minutes
             WHERE id = :id',
            [
                'id'               => $id,
                'name'             => $data['name'],
                'ot_start_hour'    => (int) $data['ot_start_hour'],
                'ot_start_minute'  => (int) $data['ot_start_minute'],
                'amount_per_block' => (float) $data['amount_per_block'],
                'block_minutes'    => (int) $data['block_minutes'],
                'min_ot_minutes'   => (int) $data['min_ot_minutes'],
            ]
        );
    }

    public function delete(int $id): void
    {
        $this->database->execute('DELETE FROM ot_groups WHERE id = :id', ['id' => $id]);
    }

    public function companyOptions(): array
    {
        return $this->database->fetchAll(
            "SELECT id, name FROM companies WHERE status = 'active' ORDER BY name ASC"
        );
    }

    /** Bulk assign OT group to employees */
    public function assignGroupToEmployee(int $employeeId, ?int $groupId): void
    {
        $this->database->execute(
            'UPDATE employees SET ot_group_id = :gid WHERE id = :eid',
            ['gid' => $groupId, 'eid' => $employeeId]
        );
    }
}
