<?php

declare(strict_types=1);

namespace App\Modules\Attendance;

use App\Core\Application;
use App\Core\Controller;
use App\Core\Request;
use Throwable;

final class AttendanceImportController extends Controller
{
    private AttendanceImportRepository $repo;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->repo = new AttendanceImportRepository($this->app->database());
    }

    public function index(Request $request): void
    {
        $companyId = (int) $request->input('company_id', 0);
        $companies = $this->repo->companyOptions();

        if ($companyId === 0 && !empty($companies)) {
            $companyId = (int) $companies[0]['id'];
        }

        $imports = $companyId > 0 ? $this->repo->recentImports($companyId) : [];

        $this->render('attendance.import', [
            'title'     => 'Attendance Import',
            'pageTitle' => 'BioTime Attendance Import',
            'companies' => $companies,
            'companyId' => $companyId,
            'imports'   => $imports,
            'result'    => $this->app->session()->getFlash('import_result'),
        ]);
    }

    public function upload(Request $request): void
    {
        $companyId = (int) $request->input('company_id', 0);
        $month     = (int) $request->input('month', (int) date('m'));
        $year      = (int) $request->input('year', (int) date('Y'));
        $userId    = (int) auth()->user()['id'];

        if ($companyId === 0) {
            $this->app->session()->flash('error', 'Please select a company.');
            $this->redirect('/attendance/import');
        }

        $file = $_FILES['attendance_file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK || empty($file['name'])) {
            $this->app->session()->flash('error', 'No file uploaded or upload error.');
            $this->redirect('/attendance/import?company_id=' . $companyId);
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'], true)) {
            $this->app->session()->flash('error', 'Only .xlsx and .xls files are accepted.');
            $this->redirect('/attendance/import?company_id=' . $companyId);
        }

        // Move to temp location
        $tmpPath = sys_get_temp_dir() . '/att_import_' . uniqid() . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $tmpPath)) {
            $this->app->session()->flash('error', 'Could not save uploaded file.');
            $this->redirect('/attendance/import?company_id=' . $companyId);
        }

        try {
            $result = $this->repo->import($tmpPath, (string) $file['name'], $companyId, $month, $year, $userId);
            $this->app->session()->flash('import_result', $result);
        } catch (Throwable $e) {
            $this->app->session()->flash('error', 'Import failed: ' . $e->getMessage());
        } finally {
            @unlink($tmpPath);
        }

        $this->redirect('/attendance/import?company_id=' . $companyId);
    }

    /** Re-run OT calculation for a period without re-uploading */
    public function recalcOt(Request $request): void
    {
        $companyId = (int) $request->input('company_id', 0);
        $month     = (int) $request->input('month', (int) date('m'));
        $year      = (int) $request->input('year', (int) date('Y'));

        if ($companyId === 0) {
            $this->app->session()->flash('error', 'Select a company.');
            $this->redirect('/attendance/import');
        }

        try {
            [$rows, $total] = $this->repo->calculateOtForPeriod($companyId, $month, $year);
            $this->app->session()->flash('success', "OT recalculated: {$rows} day entries, total \${$total}.");
        } catch (Throwable $e) {
            $this->app->session()->flash('error', 'Recalculation failed: ' . $e->getMessage());
        }

        $this->redirect('/attendance/import?company_id=' . $companyId);
    }
}
