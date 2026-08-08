<?php

declare(strict_types=1);

namespace App\Modules\Payroll;

use App\Core\Application;
use App\Core\Controller;
use App\Core\Request;
use Throwable;

final class OtGroupController extends Controller
{
    private OtGroupRepository $repo;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->repo = new OtGroupRepository($this->app->database());
    }

    public function index(Request $request): void
    {
        $companyId = (int) $request->input('company_id', 0);
        $companies = $this->repo->companyOptions();
        if ($companyId === 0 && !empty($companies)) {
            $companyId = (int) $companies[0]['id'];
        }
        $groups = $companyId > 0 ? $this->repo->allForCompany($companyId) : [];

        $this->render('payroll.ot-groups', [
            'title'     => 'OT Groups',
            'pageTitle' => 'OT Groups',
            'companies' => $companies,
            'groups'    => $groups,
            'companyId' => $companyId,
        ]);
    }

    public function store(Request $request): void
    {
        $companyId = (int) $request->input('company_id', 0);
        if ($companyId === 0) {
            $this->app->session()->flash('error', 'Select a company.');
            $this->redirect('/payroll/ot-groups');
        }

        try {
            $this->repo->create($companyId, $this->inputData($request));
            $this->app->session()->flash('success', 'OT group created.');
        } catch (Throwable $e) {
            $this->app->session()->flash('error', 'Error: ' . $e->getMessage());
        }

        $this->redirect('/payroll/ot-groups?company_id=' . $companyId);
    }

    public function edit(Request $request, string $id): void
    {
        $group = $this->repo->find((int) $id);
        if ($group === null) {
            $this->app->session()->flash('error', 'OT group not found.');
            $this->redirect('/payroll/ot-groups');
        }
        $companies = $this->repo->companyOptions();

        $this->render('payroll.ot-group-edit', [
            'title'     => 'Edit OT Group',
            'pageTitle' => 'Edit OT Group',
            'group'     => $group,
            'companies' => $companies,
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $group = $this->repo->find((int) $id);
        if ($group === null) {
            $this->app->session()->flash('error', 'OT group not found.');
            $this->redirect('/payroll/ot-groups');
        }

        try {
            $this->repo->update((int) $id, $this->inputData($request));
            $this->app->session()->flash('success', 'OT group updated.');
        } catch (Throwable $e) {
            $this->app->session()->flash('error', 'Error: ' . $e->getMessage());
        }

        $this->redirect('/payroll/ot-groups?company_id=' . $group['company_id']);
    }

    public function destroy(Request $request, string $id): void
    {
        $group = $this->repo->find((int) $id);
        $companyId = $group ? (int) $group['company_id'] : 0;
        try {
            $this->repo->delete((int) $id);
            $this->app->session()->flash('success', 'OT group deleted.');
        } catch (Throwable $e) {
            $this->app->session()->flash('error', 'Cannot delete: ' . $e->getMessage());
        }
        $this->redirect('/payroll/ot-groups?company_id=' . $companyId);
    }

    private function inputData(Request $request): array
    {
        $time = explode(':', (string) $request->input('ot_start_time', '17:00'));
        return [
            'name'             => trim((string) $request->input('name', '')),
            'ot_start_hour'    => (int) ($time[0] ?? 17),
            'ot_start_minute'  => (int) ($time[1] ?? 0),
            'amount_per_block' => (float) $request->input('amount_per_block', 8.00),
            'block_minutes'    => (int) $request->input('block_minutes', 90),
            'min_ot_minutes'   => (int) $request->input('min_ot_minutes', 30),
        ];
    }
}
