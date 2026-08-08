<?php

declare(strict_types=1);

namespace App\Modules\Payroll;

use App\Core\Database;

final class FuelPriceRepository
{
    public function __construct(private Database $database)
    {
    }

    public function forCompanyYear(int $companyId, int $year): array
    {
        return $this->database->fetchAll(
            'SELECT * FROM fuel_prices
             WHERE company_id = :company_id AND price_year = :year
             ORDER BY price_month ASC',
            ['company_id' => $companyId, 'year' => $year]
        );
    }

    public function forMonth(int $companyId, int $month, int $year): ?array
    {
        return $this->database->fetch(
            'SELECT * FROM fuel_prices
             WHERE company_id = :cid AND price_month = :m AND price_year = :y',
            ['cid' => $companyId, 'm' => $month, 'y' => $year]
        );
    }

    public function upsert(int $companyId, int $month, int $year, float $price): void
    {
        $this->database->execute(
            'INSERT INTO fuel_prices (company_id, price_month, price_year, price_per_tank)
             VALUES (:cid, :m, :y, :price)
             ON DUPLICATE KEY UPDATE price_per_tank = VALUES(price_per_tank)',
            ['cid' => $companyId, 'm' => $month, 'y' => $year, 'price' => $price]
        );
    }

    public function companyOptions(): array
    {
        return $this->database->fetchAll(
            "SELECT id, name FROM companies WHERE status = 'active' ORDER BY name ASC"
        );
    }
}
