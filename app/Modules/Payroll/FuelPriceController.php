<?php

declare(strict_types=1);

namespace App\Modules\Payroll;

use App\Core\Application;
use App\Core\Controller;
use App\Core\Request;
use Throwable;

final class FuelPriceController extends Controller
{
    private FuelPriceRepository $repo;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->repo = new FuelPriceRepository($this->app->database());
    }

    public function index(Request $request): void
    {
        $companyId = (int) $request->input('company_id', 0);
        $year      = (int) $request->input('year', (int) date('Y'));
        $companies = $this->repo->companyOptions();

        if ($companyId === 0 && !empty($companies)) {
            $companyId = (int) $companies[0]['id'];
        }

        $prices = $companyId > 0 ? $this->repo->forCompanyYear($companyId, $year) : [];
        // Index by month for easy lookup in view
        $byMonth = [];
        foreach ($prices as $p) {
            $byMonth[(int) $p['price_month']] = $p;
        }

        $this->render('payroll.fuel-prices', [
            'title'     => 'Fuel Prices',
            'pageTitle' => 'Monthly Fuel Prices',
            'companies' => $companies,
            'companyId' => $companyId,
            'year'      => $year,
            'byMonth'   => $byMonth,
        ]);
    }

    public function save(Request $request): void
    {
        $companyId = (int) $request->input('company_id', 0);
        $year      = (int) $request->input('year', (int) date('Y'));

        if ($companyId === 0) {
            $this->app->session()->flash('error', 'Select a company.');
            $this->redirect('/payroll/fuel-prices');
        }

        // Form posts prices[1..12] — save each non-empty entry
        $prices = $_POST['prices'] ?? [];
        $saved  = 0;

        try {
            foreach ($prices as $month => $rawPrice) {
                $month = (int) $month;
                if ($month < 1 || $month > 12) continue;
                $rawPrice = trim((string) $rawPrice);
                if ($rawPrice === '' || $rawPrice === '—') continue;
                $price = (float) $rawPrice;
                if ($price < 0) continue;
                $this->repo->upsert($companyId, $month, $year, $price);
                $saved++;
            }
            $this->app->session()->flash('success', "Saved {$saved} fuel price entr" . ($saved === 1 ? 'y' : 'ies') . " for {$year}.");
        } catch (Throwable $e) {
            $this->app->session()->flash('error', 'Error: ' . $e->getMessage());
        }

        $this->redirect('/payroll/fuel-prices?company_id=' . $companyId . '&year=' . $year);
    }
}
