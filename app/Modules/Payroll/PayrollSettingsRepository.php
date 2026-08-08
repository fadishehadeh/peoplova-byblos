<?php

declare(strict_types=1);

namespace App\Modules\Payroll;

use App\Core\Database;

final class PayrollSettingsRepository
{
    private array $defaults = [
        'daman_rate'               => '3.00',
        'income_tax_rate'          => '2.00',
        'working_week'             => 'mon_sat',
        'currency'                 => 'USD',
        'ot_block_minutes'         => '90',
        'ot_amount_per_block'      => '8.00',
        'ot_min_minutes'           => '30',
        'late_deduction_per_minute'=> '0.00',
        'advance_max_months'       => '3',
    ];

    public function __construct(private Database $database)
    {
    }

    public function companyOptions(): array
    {
        return $this->database->fetchAll(
            "SELECT id, name FROM companies WHERE status = 'active' ORDER BY name ASC"
        );
    }

    /** Returns a key→value array for the given company, merged with defaults. */
    public function get(int $companyId): array
    {
        $rows = $this->database->fetchAll(
            'SELECT setting_key, setting_value FROM payroll_company_settings WHERE company_id = :cid',
            ['cid' => $companyId]
        );
        $stored = [];
        foreach ($rows as $r) {
            $stored[$r['setting_key']] = $r['setting_value'];
        }
        return array_merge($this->defaults, $stored);
    }

    /** Upserts every key in $data for the given company. */
    public function save(int $companyId, array $data, int $userId): void
    {
        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $this->defaults)) {
                continue; // ignore unknown keys
            }
            $value = trim((string) $value);
            $this->database->execute(
                'INSERT INTO payroll_company_settings (company_id, setting_key, setting_value, updated_by)
                 VALUES (:cid, :key, :val, :uid)
                 ON DUPLICATE KEY UPDATE setting_value = :val2, updated_by = :uid2',
                [
                    'cid'  => $companyId,
                    'key'  => $key,
                    'val'  => $value,
                    'val2' => $value,
                    'uid'  => $userId,
                    'uid2' => $userId,
                ]
            );
        }
    }
}
