<?php

declare(strict_types=1);

use App\Middleware\AccountStatusMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\PayrollEnabledMiddleware;
use App\Middleware\RoleMiddleware;
use App\Modules\Attendance\AttendanceImportController;

$router = $app->router();

$base   = [AuthMiddleware::class, AccountStatusMiddleware::class, PayrollEnabledMiddleware::class];
$hrRole = [RoleMiddleware::class, ['super_admin', 'hr_only', 'hr_admin']];

$router->get('/attendance/import',             [AttendanceImportController::class, 'index'],     [...$base, $hrRole]);
$router->post('/attendance/import/upload',     [AttendanceImportController::class, 'upload'],    [...$base, $hrRole]);
$router->post('/attendance/import/recalc-ot', [AttendanceImportController::class, 'recalcOt'],  [...$base, $hrRole]);
