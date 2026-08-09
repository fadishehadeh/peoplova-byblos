<?php declare(strict_types=1);
$t = $template;
$totalBasic   = number_format((float) $t['basic_salary'], 2);
$totalHousing = number_format((float) $t['housing_allowance'], 2);
$gross = number_format((float) $t['basic_salary'] + (float) $t['housing_allowance'] + (float) $t['transport_allowance'] + (float) $t['other_allowances'], 2);
?>

<div class="mb-4">
    <h4 class="mb-1">Bulk Apply: <?= e((string) $t['name']); ?></h4>
    <p class="text-muted mb-0">Select employees and an effective date. Each employee's current salary structure will be closed on that date and replaced with this template.</p>
</div>

<div class="card content-card mb-4">
    <div class="card-body p-4">
        <h6 class="mb-3">Template Summary</h6>
        <div class="row g-3">
            <div class="col-sm-3 col-6">
                <div class="text-muted small">Basic Salary</div>
                <div class="fw-semibold"><?= $t['currency']; ?> <?= $totalBasic; ?></div>
            </div>
            <div class="col-sm-3 col-6">
                <div class="text-muted small">Housing</div>
                <div class="fw-semibold"><?= $t['currency']; ?> <?= $totalHousing; ?></div>
            </div>
            <div class="col-sm-3 col-6">
                <div class="text-muted small">Gross (excl. OT/transport)</div>
                <div class="fw-semibold"><?= $t['currency']; ?> <?= $gross; ?></div>
            </div>
            <div class="col-sm-3 col-6">
                <div class="text-muted small">Daman / Tax</div>
                <div class="fw-semibold"><?= e((string) $t['daman_rate']); ?>% / <?= e((string) $t['income_tax_rate']); ?>%</div>
            </div>
        </div>
    </div>
</div>

<form method="post" action="<?= e(url('/payroll/salary-templates/' . $t['id'] . '/apply')); ?>">
    <?= csrf_field(); ?>

    <div class="card content-card mb-4">
        <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Effective Date <span class="text-danger">*</span></h6>
        </div>
        <div class="card-body p-4">
            <div class="col-md-3">
                <input type="date" name="effective_from" class="form-control" required
                       value="<?= e(date('Y-m-d')); ?>">
            </div>
            <div class="form-text mt-2">The new salary structure will be effective from this date. Any existing structure is closed on the same date.</div>
        </div>
    </div>

    <div class="card content-card mb-4">
        <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Select Employees</h6>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAll">Select All</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="clearAll">Clear</button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px"><input type="checkbox" id="checkAll" class="form-check-input"></th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Job Title</th>
                            <th>Current Basic</th>
                            <th>Effective From</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No active employees found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="employee_ids[]"
                                       value="<?= e((string) $emp['id']); ?>"
                                       class="form-check-input emp-checkbox">
                            </td>
                            <td>
                                <div class="fw-semibold"><?= e((string) $emp['full_name']); ?></div>
                                <small class="text-muted"><?= e((string) $emp['employee_code']); ?></small>
                            </td>
                            <td><?= e((string) ($emp['department_name'] ?? '—')); ?></td>
                            <td><?= e((string) ($emp['job_title_name'] ?? '—')); ?></td>
                            <td>
                                <?php if ($emp['current_basic'] !== null): ?>
                                <span class="text-muted"><?= e((string) ($emp['current_currency'] ?? '')); ?> <?= number_format((float) $emp['current_basic'], 2); ?></span>
                                <?php else: ?>
                                <span class="text-muted">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $emp['current_from'] ? e(date('d M Y', strtotime((string) $emp['current_from']))) : '—'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 align-items-center">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-people"></i> Apply to Selected Employees
        </button>
        <a href="<?= e(url('/payroll/salary-templates?company_id=' . $t['company_id'])); ?>"
           class="btn btn-outline-secondary">Cancel</a>
        <span class="text-muted small ms-2" id="selectedCount">0 employees selected</span>
    </div>
</form>

<script>
(function () {
    var checkAll  = document.getElementById('checkAll');
    var selectBtn = document.getElementById('selectAll');
    var clearBtn  = document.getElementById('clearAll');
    var boxes     = document.querySelectorAll('.emp-checkbox');
    var countEl   = document.getElementById('selectedCount');

    function updateCount() {
        var n = document.querySelectorAll('.emp-checkbox:checked').length;
        countEl.textContent = n + ' employee' + (n === 1 ? '' : 's') + ' selected';
        checkAll.checked = n === boxes.length && boxes.length > 0;
    }

    checkAll.addEventListener('change', function () {
        boxes.forEach(function (b) { b.checked = checkAll.checked; });
        updateCount();
    });
    selectBtn.addEventListener('click', function () {
        boxes.forEach(function (b) { b.checked = true; });
        updateCount();
    });
    clearBtn.addEventListener('click', function () {
        boxes.forEach(function (b) { b.checked = false; });
        updateCount();
    });
    boxes.forEach(function (b) {
        b.addEventListener('change', updateCount);
    });
    updateCount();
})();
</script>
