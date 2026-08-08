<?php

declare(strict_types=1);

namespace App\Modules\Payroll;

use App\Core\Application;
use App\Core\Controller;
use App\Core\Request;
use Throwable;

final class PayrollSettingsController extends Controller
{
    private PayrollSettingsRepository $repo;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->repo = new PayrollSettingsRepository($this->app->database());
    }

    public function index(Request $request): void
    {
        $companies = $this->repo->companyOptions();
        $companyId = (int) $request->input('company_id', 0);
        if ($companyId === 0 && !empty($companies)) {
            $companyId = (int) $companies[0]['id'];
        }

        $settings = $companyId > 0 ? $this->repo->get($companyId) : [];

        $this->render('payroll.settings', [
            'title'     => 'Payroll Settings',
            'pageTitle' => 'Payroll Settings',
            'companies' => $companies,
            'companyId' => $companyId,
            'settings'  => $settings,
        ]);
    }

    public function save(Request $request): void
    {
        $companyId = (int) $request->input('company_id', 0);
        $userId    = (int) (auth()->id() ?? 0);

        if ($companyId === 0) {
            $this->app->session()->flash('error', 'No company selected.');
            $this->redirect('/payroll/settings');
        }

        $fields = [
            'daman_rate',
            'income_tax_rate',
            'working_week',
            'currency',
            'ot_block_minutes',
            'ot_amount_per_block',
            'ot_min_minutes',
            'late_deduction_per_minute',
            'advance_max_months',
        ];

        $data = [];
        foreach ($fields as $field) {
            $raw = $request->input($field, '');
            if ($raw !== '') {
                $data[$field] = $raw;
            }
        }

        try {
            $this->repo->save($companyId, $data, $userId);
            $this->app->session()->flash('success', 'Payroll settings saved.');
        } catch (Throwable $e) {
            $this->app->session()->flash('error', 'Error saving settings: ' . $e->getMessage());
        }

        $this->redirect('/payroll/settings?company_id=' . $companyId);
    }
}
