<?php
declare(strict_types=1);
$user = auth()->user();
$profileUrl  = !empty($user['employee_id']) ? url('/employees/' . $user['employee_id']) : '#';
$documentUrl = !empty($user['employee_id']) ? url('/employees/' . $user['employee_id'] . '/documents/upload') : '#';
$currentPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$currentPath = $currentPath === false ? '/' : $currentPath;

$groupOpen = static function (array $paths) use ($currentPath): string {
    foreach ($paths as $p) {
        if ($p !== '/' && str_starts_with($currentPath, $p)) return 'show';
    }
    return '';
};

$isActive = static function (string $path, bool $exact = false) use ($currentPath): string {
    if ($exact) return $currentPath === $path ? ' active' : '';
    return str_starts_with($currentPath, $path) ? ' active' : '';
};

$railActive = static function (array $paths) use ($currentPath): string {
    foreach ($paths as $p) {
        if ($p !== '/' && str_starts_with($currentPath, $p)) return ' active';
    }
    return '';
};

$collapseId = 0;
$nextId = static function () use (&$collapseId): string {
    return 'sidebarGroup' . (++$collapseId);
};

$userInitial = strtoupper(substr($user['first_name'] ?? 'U', 0, 1));
$userName    = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
?>
<aside class="sidebar" id="appSidebar">
    <button class="sidebar-close" id="sidebarClose" aria-label="Close menu"><i class="bi bi-x-lg"></i></button>

    <?php /* LEFT RAIL */ ?>
    <div class="sidebar-rail">
        <div class="sidebar-rail-top">
            <a class="sidebar-rail-brand" href="<?= e(url('/dashboard')); ?>">
                <img src="<?= e(\App\Support\Branding::logoWhiteUrl()); ?>" alt="<?= e(\App\Support\Branding::name()); ?>">
            </a>

            <a class="sidebar-rail-icon<?= $currentPath === '/dashboard' ? ' active' : ''; ?>" href="<?= e(url('/dashboard')); ?>" title="Dashboard">
                <i class="bi bi-grid-fill"></i>
            </a>

            <?php if (has_role(['super_admin', 'hr_only', 'hr_admin'])): ?>
            <a class="sidebar-rail-icon<?= $railActive(['/employees', '/onboarding', '/offboarding', '/employee-registration']); ?>" href="<?= e(url('/employees')); ?>" title="People">
                <i class="bi bi-people-fill"></i>
            </a>
            <a class="sidebar-rail-icon<?= $railActive(['/leave']); ?>" href="<?= e(url('/leave/approvals')); ?>" title="Leave">
                <i class="bi bi-calendar-check-fill"></i>
            </a>
            <a class="sidebar-rail-icon<?= $railActive(['/documents']); ?>" href="<?= e(url('/documents')); ?>" title="Documents">
                <i class="bi bi-folder2-open"></i>
            </a>
            <a class="sidebar-rail-icon<?= $railActive(['/admin/jobs', '/careers']); ?>" href="<?= e(url('/admin/jobs')); ?>" title="Recruitment">
                <i class="bi bi-briefcase-fill"></i>
            </a>
            <?php endif; ?>

            <?php if (can('leave.approve_team') && !has_role(['super_admin', 'hr_only', 'hr_admin'])): ?>
            <a class="sidebar-rail-icon<?= $railActive(['/leave']); ?>" href="<?= e(url('/leave/approvals')); ?>" title="Approvals">
                <i class="bi bi-check2-square"></i>
            </a>
            <?php endif; ?>

            <?php if (can('reports.view_hr') || can('reports.view_team')): ?>
            <a class="sidebar-rail-icon<?= $railActive(['/reports']); ?>" href="<?= e(url('/reports')); ?>" title="Reports">
                <i class="bi bi-bar-chart-fill"></i>
            </a>
            <?php endif; ?>
        </div>

        <div class="sidebar-rail-bottom">
            <?php if (can('settings.manage')): ?>
            <a class="sidebar-rail-icon<?= $railActive(['/settings', '/admin']); ?>" href="<?= e(url('/settings')); ?>" title="Settings">
                <i class="bi bi-gear-fill"></i>
            </a>
            <?php endif; ?>
            <div class="sidebar-rail-user" title="<?= e($userName); ?>"><?= e($userInitial); ?></div>
        </div>
    </div>

    <?php /* RIGHT PANEL */ ?>
    <div class="sidebar-panel">
        <div class="sidebar-panel-header">
            <span class="sidebar-panel-title"><?= e(\App\Support\Branding::name()); ?></span>
            <small class="sidebar-panel-tagline"><?= e(\App\Support\Branding::tagline()); ?></small>
        </div>

        <nav class="sidebar-nav" id="sidebarNavAccordion">

            <a href="<?= e(url('/dashboard')); ?>" class="sidebar-link<?= $currentPath === '/dashboard' ? ' active' : ''; ?>">
                <i class="bi bi-grid"></i> Dashboard
            </a>

            <?php if (has_role(['super_admin', 'hr_only', 'hr_admin'])): ?>

            <?php $peopleId = $nextId(); $peopleOpen = $groupOpen(['/employees', '/onboarding', '/offboarding', '/employee-registration']); ?>
            <div class="sidebar-group">
                <a class="sidebar-group-toggle<?= $peopleOpen ? '' : ' collapsed'; ?>" href="#<?= e($peopleId); ?>" data-bs-toggle="collapse" aria-expanded="<?= $peopleOpen ? 'true' : 'false'; ?>">
                    <i class="bi bi-people"></i> People
                    <i class="bi bi-chevron-down sidebar-chevron"></i>
                </a>
                <div class="collapse <?= e($peopleOpen); ?>" id="<?= e($peopleId); ?>" data-bs-parent="#sidebarNavAccordion">
                    <div class="sidebar-collapse-body">
                        <a href="<?= e(url('/employees')); ?>" class="sidebar-sublink<?= (str_starts_with($currentPath, '/employees') && !str_starts_with($currentPath, '/employees/org')) ? ' active' : ''; ?>">Employees</a>
                        <a href="<?= e(url('/employees/org-chart')); ?>" class="sidebar-sublink<?= str_starts_with($currentPath, '/employees/org') ? ' active' : ''; ?>">Org Chart</a>
                        <?php if (can('onboarding.manage')): ?>
                        <a href="<?= e(url('/onboarding')); ?>" class="sidebar-sublink<?= str_starts_with($currentPath, '/onboarding') ? ' active' : ''; ?>">Onboarding</a>
                        <?php endif; ?>
                        <?php if (can('offboarding.manage')): ?>
                        <a href="<?= e(url('/offboarding')); ?>" class="sidebar-sublink<?= str_starts_with($currentPath, '/offboarding') ? ' active' : ''; ?>">Offboarding</a>
                        <?php endif; ?>
                        <a href="<?= e(url('/employee-registration/review')); ?>" class="sidebar-sublink<?= str_starts_with($currentPath, '/employee-registration') ? ' active' : ''; ?>">Employee Registration</a>
                    </div>
                </div>
            </div>

            <?php $accessId = $nextId(); $accessOpen = $groupOpen(['/admin/users', '/admin/roles', '/admin/companies', '/admin/structure', '/admin/resilience']); ?>
            <div class="sidebar-group">
                <a class="sidebar-group-toggle<?= $accessOpen ? '' : ' collapsed'; ?>" href="#<?= e($accessId); ?>" data-bs-toggle="collapse" aria-expanded="<?= $accessOpen ? 'true' : 'false'; ?>">
                    <i class="bi bi-shield-lock"></i> Access &amp; Org
                    <i class="bi bi-chevron-down sidebar-chevron"></i>
                </a>
                <div class="collapse <?= e($accessOpen); ?>" id="<?= e($accessId); ?>" data-bs-parent="#sidebarNavAccordion">
                    <div class="sidebar-collapse-body">
                        <a href="<?= e(url('/admin/users')); ?>"     class="sidebar-sublink<?= str_starts_with($currentPath, '/admin/users') ? ' active' : ''; ?>">User Access</a>
                        <a href="<?= e(url('/admin/roles')); ?>"     class="sidebar-sublink<?= str_starts_with($currentPath, '/admin/roles') ? ' active' : ''; ?>">Roles &amp; Permissions</a>
                        <a href="<?= e(url('/admin/companies')); ?>" class="sidebar-sublink<?= str_starts_with($currentPath, '/admin/companies') ? ' active' : ''; ?>">Companies</a>
                        <a href="<?= e(url('/admin/structure')); ?>" class="sidebar-sublink<?= str_starts_with($currentPath, '/admin/structure') ? ' active' : ''; ?>">Structure</a>
                        <?php if (has_role('super_admin')): ?>
                        <a href="<?= e(url('/admin/resilience')); ?>" class="sidebar-sublink<?= str_starts_with($currentPath, '/admin/resilience') ? ' active' : ''; ?>">Resilience</a>
                        <a href="<?= e(url('/settings/system')); ?>"  class="sidebar-sublink<?= str_starts_with($currentPath, '/settings/system') ? ' active' : ''; ?>">System Settings</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php $leaveHrId = $nextId(); $leaveHrOpen = $groupOpen(['/leave']); ?>
            <div class="sidebar-group">
                <a class="sidebar-group-toggle<?= $leaveHrOpen ? '' : ' collapsed'; ?>" href="#<?= e($leaveHrId); ?>" data-bs-toggle="collapse" aria-expanded="<?= $leaveHrOpen ? 'true' : 'false'; ?>">
                    <i class="bi bi-calendar-check"></i> Leave
                    <i class="bi bi-chevron-down sidebar-chevron"></i>
                </a>
                <div class="collapse <?= e($leaveHrOpen); ?>" id="<?= e($leaveHrId); ?>" data-bs-parent="#sidebarNavAccordion">
                    <div class="sidebar-collapse-body">
                        <a href="<?= e(url('/leave/approvals')); ?>" class="sidebar-sublink<?= str_starts_with($currentPath, '/leave/approvals') ? ' active' : ''; ?>">Leave Management</a>
                    </div>
                </div>
            </div>

            <?php $docsHrId = $nextId(); $docsHrOpen = $groupOpen(['/documents']); ?>
            <div class="sidebar-group">
                <a class="sidebar-group-toggle<?= $docsHrOpen ? '' : ' collapsed'; ?>" href="#<?= e($docsHrId); ?>" data-bs-toggle="collapse" aria-expanded="<?= $docsHrOpen ? 'true' : 'false'; ?>">
                    <i class="bi bi-folder2-open"></i> Documents
                    <i class="bi bi-chevron-down sidebar-chevron"></i>
                </a>
                <div class="collapse <?= e($docsHrOpen); ?>" id="<?= e($docsHrId); ?>" data-bs-parent="#sidebarNavAccordion">
                    <div class="sidebar-collapse-body">
                        <a href="<?= e(url('/documents')); ?>"            class="sidebar-sublink<?= $currentPath === '/documents' ? ' active' : ''; ?>">HR Documents</a>
                        <a href="<?= e(url('/documents/categories')); ?>" class="sidebar-sublink<?= str_starts_with($currentPath, '/documents/categories') ? ' active' : ''; ?>">Categories</a>
                        <a href="<?= e(url('/documents/expiring')); ?>"   class="sidebar-sublink<?= str_starts_with($currentPath, '/documents/expiring') ? ' active' : ''; ?>">Expiring</a>
                    </div>
                </div>
            </div>

            <?php $recruId = $nextId(); $recruOpen = $groupOpen(['/admin/jobs', '/careers']); ?>
            <div class="sidebar-group">
                <a class="sidebar-group-toggle<?= $recruOpen ? '' : ' collapsed'; ?>" href="#<?= e($recruId); ?>" data-bs-toggle="collapse" aria-expanded="<?= $recruOpen ? 'true' : 'false'; ?>">
                    <i class="bi bi-briefcase"></i> Recruitment
                    <i class="bi bi-chevron-down sidebar-chevron"></i>
                </a>
                <div class="collapse <?= e($recruOpen); ?>" id="<?= e($recruId); ?>" data-bs-parent="#sidebarNavAccordion">
                    <div class="sidebar-collapse-body">
                        <a href="<?= e(url('/admin/jobs')); ?>" class="sidebar-sublink<?= str_starts_with($currentPath, '/admin/jobs') ? ' active' : ''; ?>">Jobs &amp; Careers</a>
                    </div>
                </div>
            </div>

            <?php endif; ?>

            <?php if (can('leave.approve_team') && !has_role(['super_admin', 'hr_only', 'hr_admin'])): ?>
            <a href="<?= e(url('/leave/approvals')); ?>" class="sidebar-link<?= str_starts_with($currentPath, '/leave/approvals') ? ' active' : ''; ?>">
                <i class="bi bi-check2-square"></i> Approvals
            </a>
            <?php endif; ?>

            <?php
                $selfLinks = [];
                if (!empty($user['employee_id']) && can('employee.view_self'))  { $selfLinks[] = true; }
                if (!empty($user['employee_id']) && can('leave.view_self'))      { $selfLinks[] = true; }
                if (can('leave.submit'))                                          { $selfLinks[] = true; }
                if (!empty($user['employee_id']) && (can('documents.view_self') || can('documents.upload_self'))) { $selfLinks[] = true; }
            ?>
            <?php if (!empty($selfLinks)): ?>
            <?php $myId = $nextId(); $myOpen = $groupOpen(['/leave/my', '/leave/request', '/employees/' . ($user['employee_id'] ?? 0)]); ?>
            <div class="sidebar-group">
                <a class="sidebar-group-toggle<?= $myOpen ? '' : ' collapsed'; ?>" href="#<?= e($myId); ?>" data-bs-toggle="collapse" aria-expanded="<?= $myOpen ? 'true' : 'false'; ?>">
                    <i class="bi bi-person-circle"></i> My Space
                    <i class="bi bi-chevron-down sidebar-chevron"></i>
                </a>
                <div class="collapse <?= e($myOpen); ?>" id="<?= e($myId); ?>" data-bs-parent="#sidebarNavAccordion">
                    <div class="sidebar-collapse-body">
                        <?php if (!empty($user['employee_id']) && can('employee.view_self')): ?>
                        <a href="<?= e($profileUrl); ?>" class="sidebar-sublink">My Profile</a>
                        <?php endif; ?>
                        <?php if (!empty($user['employee_id']) && can('leave.view_self')): ?>
                        <a href="<?= e(url('/leave/my')); ?>" class="sidebar-sublink<?= str_starts_with($currentPath, '/leave/my') ? ' active' : ''; ?>">My Leave</a>
                        <?php endif; ?>
                        <?php if (can('leave.submit')): ?>
                        <a href="<?= e(url('/leave/request')); ?>" class="sidebar-sublink<?= str_starts_with($currentPath, '/leave/request') ? ' active' : ''; ?>">Request Leave</a>
                        <?php endif; ?>
                        <?php if (!empty($user['employee_id']) && (can('documents.view_self') || can('documents.upload_self'))): ?>
                        <a href="<?= e($documentUrl); ?>" class="sidebar-sublink">My Documents</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (can('letters.request') || can('letters.manage') || can('announcements.view') || can('announcements.manage') || can('notifications.view_self')): ?>
            <?php $commsId = $nextId(); $commsOpen = $groupOpen(['/letters', '/announcements', '/notifications']); ?>
            <div class="sidebar-group">
                <a class="sidebar-group-toggle<?= $commsOpen ? '' : ' collapsed'; ?>" href="#<?= e($commsId); ?>" data-bs-toggle="collapse" aria-expanded="<?= $commsOpen ? 'true' : 'false'; ?>">
                    <i class="bi bi-chat-dots"></i> Communications
                    <i class="bi bi-chevron-down sidebar-chevron"></i>
                </a>
                <div class="collapse <?= e($commsOpen); ?>" id="<?= e($commsId); ?>" data-bs-parent="#sidebarNavAccordion">
                    <div class="sidebar-collapse-body">
                        <?php if (can('letters.request') || can('letters.manage')): ?>
                        <a href="<?= e(url(can('letters.manage') ? '/letters/admin' : '/letters/my')); ?>" class="sidebar-sublink<?= str_starts_with($currentPath, '/letters') ? ' active' : ''; ?>">Letters</a>
                        <?php endif; ?>
                        <?php if (can('announcements.view') || can('announcements.manage')): ?>
                        <a href="<?= e(url('/announcements')); ?>" class="sidebar-sublink<?= str_starts_with($currentPath, '/announcements') ? ' active' : ''; ?>">Announcements</a>
                        <?php endif; ?>
                        <?php if (can('notifications.view_self')): ?>
                        <a href="<?= e(url('/notifications')); ?>" class="sidebar-sublink<?= str_starts_with($currentPath, '/notifications') ? ' active' : ''; ?>">Notifications</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (is_payroll_enabled() && has_role(['super_admin', 'hr_only', 'hr_admin'])): ?>
            <?php $payrollId = $nextId(); $payrollOpen = $groupOpen(['/payroll', '/attendance']); ?>
            <div class="sidebar-group">
                <a class="sidebar-group-toggle<?= $payrollOpen ? '' : ' collapsed'; ?>" href="#<?= e($payrollId); ?>" data-bs-toggle="collapse" aria-expanded="<?= $payrollOpen ? 'true' : 'false'; ?>">
                    <i class="bi bi-cash-stack"></i> Payroll
                    <i class="bi bi-chevron-down sidebar-chevron"></i>
                </a>
                <div class="collapse <?= e($payrollOpen); ?>" id="<?= e($payrollId); ?>" data-bs-parent="#sidebarNavAccordion">
                    <div class="sidebar-collapse-body">
                        <a href="<?= e(url('/payroll/salary-structures')); ?>" class="sidebar-sublink<?= str_starts_with($currentPath, '/payroll/salary') ? ' active' : ''; ?>">Salary Structures</a>
                        <a href="<?= e(url('/payroll/runs')); ?>"              class="sidebar-sublink<?= str_starts_with($currentPath, '/payroll/runs') ? ' active' : ''; ?>">Payroll Runs</a>
                        <a href="<?= e(url('/payroll/ot-groups')); ?>"         class="sidebar-sublink<?= str_starts_with($currentPath, '/payroll/ot-groups') ? ' active' : ''; ?>">OT Groups</a>
                        <a href="<?= e(url('/payroll/fuel-prices')); ?>"       class="sidebar-sublink<?= str_starts_with($currentPath, '/payroll/fuel-prices') ? ' active' : ''; ?>">Fuel Prices</a>
                        <a href="<?= e(url('/attendance/import')); ?>"         class="sidebar-sublink<?= str_starts_with($currentPath, '/attendance') ? ' active' : ''; ?>">Attendance Import</a>
                        <a href="<?= e(url('/payroll/settings')); ?>"          class="sidebar-sublink<?= str_starts_with($currentPath, '/payroll/settings') ? ' active' : ''; ?>">Payroll Settings</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (can('reports.view_hr') || can('reports.view_team')): ?>
            <a href="<?= e(url('/reports')); ?>" class="sidebar-link<?= str_starts_with($currentPath, '/reports') ? ' active' : ''; ?>">
                <i class="bi bi-bar-chart"></i> Reports
            </a>
            <?php endif; ?>

            <?php if (can('settings.manage')): ?>
            <a href="<?= e(url('/settings')); ?>" class="sidebar-link<?= str_starts_with($currentPath, '/settings') ? ' active' : ''; ?>">
                <i class="bi bi-gear"></i> Settings
            </a>
            <?php endif; ?>

        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user"><?= e($userName); ?></div>
            <form method="post" action="<?= e(url('/logout')); ?>" class="w-100" id="sidebarLogoutForm">
                <?= csrf_field(); ?>
                <button type="submit" class="btn btn-sm w-100 btn-logout">
                    <i class="bi bi-box-arrow-right"></i> Sign out
                </button>
            </form>
        </div>
    </div>
</aside>
