<?php

declare(strict_types=1);

use App\Middleware\AccountStatusMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\PayrollEnabledMiddleware;
use App\Middleware\RoleMiddleware;
use App\Modules\Payroll\PayrollController;
use App\Modules\Payroll\OtGroupController;
use App\Modules\Payroll\FuelPriceController;

$router = $app->router();

$base   = [AuthMiddleware::class, AccountStatusMiddleware::class, PayrollEnabledMiddleware::class];
$hrRole = [RoleMiddleware::class, ['super_admin', 'hr_only']];

$router->get('/payroll/salary-structures',              [PayrollController::class, 'salaryStructures'],   [...$base, $hrRole]);
$router->get('/payroll/salary-structures/{id}/edit',   [PayrollController::class, 'editSalaryStructure'], [...$base, $hrRole]);
$router->post('/payroll/salary-structures/{id}/edit',  [PayrollController::class, 'saveSalaryStructure'], [...$base, $hrRole]);

$router->get('/payroll/runs',                          [PayrollController::class, 'runs'],            [...$base, $hrRole]);
$router->get('/payroll/runs/create',                   [PayrollController::class, 'createRunForm'],   [...$base, $hrRole]);
$router->post('/payroll/runs/create',                  [PayrollController::class, 'createRun'],       [...$base, $hrRole]);
$router->get('/payroll/runs/{id}',                     [PayrollController::class, 'runDetail'],       [...$base, $hrRole]);
$router->post('/payroll/runs/items/{id}',              [PayrollController::class, 'updateRunItem'],   [...$base, $hrRole]);
$router->get('/payroll/runs/{id}/finalize',            [PayrollController::class, 'finalizeRunForm'], [...$base, $hrRole]);
$router->post('/payroll/runs/{id}/finalize',           [PayrollController::class, 'finalizeRun'],     [...$base, $hrRole]);
$router->get('/payroll/runs/{runId}/payslip/{empId}',  [PayrollController::class, 'downloadPayslip'], [...$base, $hrRole]);

// ── OT Groups ─────────────────────────────────────────────────────────────────
$router->get('/payroll/ot-groups',                   [OtGroupController::class, 'index'],   [...$base, $hrRole]);
$router->post('/payroll/ot-groups',                  [OtGroupController::class, 'store'],   [...$base, $hrRole]);
$router->get('/payroll/ot-groups/{id}/edit',         [OtGroupController::class, 'edit'],    [...$base, $hrRole]);
$router->post('/payroll/ot-groups/{id}/edit',        [OtGroupController::class, 'update'],  [...$base, $hrRole]);
$router->post('/payroll/ot-groups/{id}/delete',      [OtGroupController::class, 'destroy'], [...$base, $hrRole]);

// ── Fuel Prices ───────────────────────────────────────────────────────────────
$router->get('/payroll/fuel-prices',   [FuelPriceController::class, 'index'], [...$base, $hrRole]);
$router->post('/payroll/fuel-prices/save', [FuelPriceController::class, 'save'],  [...$base, $hrRole]);
