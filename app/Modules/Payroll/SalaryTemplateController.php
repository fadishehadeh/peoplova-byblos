<?php

declare(strict_types=1);

namespace App\Modules\Payroll;

use App\Core\Application;
use App\Core\Controller;
use App\Core\Request;
use Throwable;

final class SalaryTemplateController extends Controller
{
    private SalaryTemplateRepository $repo;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->repo = new SalaryTemplateRepository($this->app->database());
    }

    public function index(Request $request): void
    {
        $companies = $this->repo->companyOptions();
        $companyId = (int) $request->input('company_id', 0);
        if ($companyId === 0 && !empty($companies)) {
            $companyId = (int) $companies[0]['id'];
        }

        $templates = $companyId > 0 ? $this->repo->listForCompany($companyId) : [];

        $this->render('payroll.salary-templates', [
            'title'     => 'Salary Templates',
            'pageTitle' => 'Salary Structure Templates',
            'companies' => $companies,
            'companyId' => $companyId,
            'templates' => $templates,
        ]);
    }

    public function store(Request $request): void
    {
        $companyId = (int) $request->input('company_id', 0);
        $this->validateCsrf($request, '/payroll/salary-templates?company_id=' . $companyId);

        $data = $this->extractData($request, $companyId);
        $error = $this->validateData($data);

        if ($error) {
            $this->app->session()->flash('error', $error);
            $this->redirect('/payroll/salary-templates?company_id=' . $companyId);
        }

        try {
            $this->repo->create($data, (int) ($this->app->auth()->id() ?? 0));
            $this->app->session()->flash('success', 'Template "' . $data['name'] . '" created.');
        } catch (Throwable $e) {
            $this->app->session()->flash('error', 'Error: ' . $e->getMessage());
        }

        $this->redirect('/payroll/salary-templates?company_id=' . $companyId);
    }

    public function edit(Request $request, string $id): void
    {
        $template = $this->repo->find((int) $id);
        if ($template === null) {
            $this->app->session()->flash('error', 'Template not found.');
            $this->redirect('/payroll/salary-templates');
        }

        $companies = $this->repo->companyOptions();

        $this->render('payroll.salary-template-edit', [
            'title'     => 'Edit Template',
            'pageTitle' => 'Edit Salary Template',
            'template'  => $template,
            'companies' => $companies,
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $template = $this->repo->find((int) $id);
        if ($template === null) {
            $this->app->session()->flash('error', 'Template not found.');
            $this->redirect('/payroll/salary-templates');
        }

        $companyId = (int) $template['company_id'];
        $this->validateCsrf($request, '/payroll/salary-templates/' . $id . '/edit');

        $data  = $this->extractData($request, $companyId);
        $error = $this->validateData($data);
        if ($error) {
            $this->app->session()->flash('error', $error);
            $this->redirect('/payroll/salary-templates/' . $id . '/edit');
        }

        try {
            $this->repo->update((int) $id, $data);
            $this->app->session()->flash('success', 'Template updated.');
        } catch (Throwable $e) {
            $this->app->session()->flash('error', 'Error: ' . $e->getMessage());
        }

        $this->redirect('/payroll/salary-templates?company_id=' . $companyId);
    }

    public function destroy(Request $request, string $id): void
    {
        $template = $this->repo->find((int) $id);
        $companyId = $template ? (int) $template['company_id'] : 0;

        $this->validateCsrf($request, '/payroll/salary-templates?company_id=' . $companyId);

        try {
            $this->repo->delete((int) $id);
            $this->app->session()->flash('success', 'Template deleted.');
        } catch (Throwable $e) {
            $this->app->session()->flash('error', 'Error: ' . $e->getMessage());
        }

        $this->redirect('/payroll/salary-templates?company_id=' . $companyId);
    }

    public function applyForm(Request $request, string $id): void
    {
        $template = $this->repo->find((int) $id);
        if ($template === null) {
            $this->app->session()->flash('error', 'Template not found.');
            $this->redirect('/payroll/salary-templates');
        }

        $employees = $this->repo->activeEmployees((int) $template['company_id']);

        $this->render('payroll.salary-template-apply', [
            'title'     => 'Apply Template',
            'pageTitle' => 'Bulk Apply Salary Template',
            'template'  => $template,
            'employees' => $employees,
        ]);
    }

    public function applySubmit(Request $request, string $id): void
    {
        $template = $this->repo->find((int) $id);
        if ($template === null) {
            $this->app->session()->flash('error', 'Template not found.');
            $this->redirect('/payroll/salary-templates');
        }

        $companyId = (int) $template['company_id'];
        $this->validateCsrf($request, '/payroll/salary-templates/' . $id . '/apply');

        $effectiveFrom = trim((string) $request->input('effective_from', ''));
        $employeeIds   = $_POST['employee_ids'] ?? [];

        if ($effectiveFrom === '') {
            $this->app->session()->flash('error', 'Effective date is required.');
            $this->redirect('/payroll/salary-templates/' . $id . '/apply');
        }

        if (empty($employeeIds)) {
            $this->app->session()->flash('error', 'Select at least one employee.');
            $this->redirect('/payroll/salary-templates/' . $id . '/apply');
        }

        try {
            $applied = $this->repo->bulkApply(
                (int) $id,
                (array) $employeeIds,
                $effectiveFrom,
                (int) ($this->app->auth()->id() ?? 0)
            );
            $this->auditLog('payroll', 'salary_template', (int) $id, 'bulk_applied');
            $this->app->session()->flash('success', "Template applied to {$applied} employee(s) effective {$effectiveFrom}.");
        } catch (Throwable $e) {
            $this->app->session()->flash('error', 'Error: ' . $e->getMessage());
        }

        $this->redirect('/payroll/salary-templates?company_id=' . $companyId);
    }

    // ── helpers ──────────────────────────────────────────────────────────

    private function extractData(Request $request, int $companyId): array
    {
        return [
            'company_id'          => $companyId,
            'name'                => trim((string) $request->input('name', '')),
            'description'         => trim((string) $request->input('description', '')),
            'basic_salary'        => $request->input('basic_salary', '0'),
            'housing_allowance'   => $request->input('housing_allowance', '0'),
            'transport_allowance' => $request->input('transport_allowance', '0'),
            'other_allowances'    => $request->input('other_allowances', '0'),
            'daman_rate'          => $request->input('daman_rate', '3.00'),
            'income_tax_rate'     => $request->input('income_tax_rate', '2.00'),
            'currency'            => trim((string) $request->input('currency', 'USD')),
            'status'              => trim((string) $request->input('status', 'active')),
        ];
    }

    private function validateData(array $data): ?string
    {
        if ($data['name'] === '') return 'Template name is required.';
        if (!is_numeric($data['basic_salary']) || (float) $data['basic_salary'] < 0) return 'Basic salary must be a valid number.';
        return null;
    }

    private function validateCsrf(Request $request, string $redirectPath): void
    {
        if (!$this->app->csrf()->validate((string) $request->input('_token'))) {
            $this->app->session()->flash('error', 'Invalid form token. Please try again.');
            $this->redirect($redirectPath);
        }
    }
}
