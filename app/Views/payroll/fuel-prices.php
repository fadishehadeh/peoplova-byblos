<?php declare(strict_types=1); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Monthly Fuel Prices</h4>
        <p class="text-muted mb-0">Set the fuel price per tank each month — used to calculate employee transportation allowance.</p>
    </div>
</div>

<form method="get" action="<?= e(url('/payroll/fuel-prices')); ?>" class="mb-4">
    <div class="row g-2 align-items-center">
        <?php if (!empty($companies)): ?>
        <div class="col-auto">
            <select name="company_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($companies as $c): ?>
                <option value="<?= e((string) $c['id']); ?>" <?= (int) $c['id'] === $companyId ? 'selected' : ''; ?>>
                    <?= e((string) $c['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="col-auto">
            <select name="year" class="form-select" onchange="this.form.submit()">
                <?php for ($y = (int) date('Y') + 1; $y >= 2023; $y--): ?>
                <option value="<?= $y; ?>" <?= $y === $year ? 'selected' : ''; ?>><?= $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>
</form>

<div class="card content-card">
    <div class="card-body p-4">
        <form method="post" action="<?= e(url('/payroll/fuel-prices/save')); ?>">
            <?= csrf_field(); ?>
            <input type="hidden" name="company_id" value="<?= e((string) $companyId); ?>">
            <input type="hidden" name="year" value="<?= e((string) $year); ?>">

            <div class="row g-3">
                <?php
                $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June',
                               'July', 'August', 'September', 'October', 'November', 'December'];
                for ($m = 1; $m <= 12; $m++):
                    $entry = $byMonth[$m] ?? null;
                    $currentMonth = (int) date('n');
                    $currentYear  = (int) date('Y');
                    $isPast = ($year < $currentYear) || ($year === $currentYear && $m < $currentMonth);
                    $isCurrent = ($year === $currentYear && $m === $currentMonth);
                ?>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <label class="form-label d-flex align-items-center gap-1">
                        <?= $monthNames[$m]; ?>
                        <?php if ($isCurrent): ?><span class="badge bg-primary" style="font-size:.65rem">Current</span><?php endif; ?>
                        <?php if ($isPast && $entry): ?><span class="badge bg-success" style="font-size:.65rem">Set</span><?php endif; ?>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="prices[<?= $m; ?>]"
                               class="form-control<?= $isPast && !$entry ? ' bg-light text-muted' : ''; ?>"
                               value="<?= $entry ? number_format((float) $entry['price_per_tank'], 2) : ''; ?>"
                               step="0.01" min="0" placeholder="—">
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <div class="mt-4 d-flex gap-2 align-items-center">
                <button type="submit" class="btn btn-primary" <?= $companyId === 0 ? 'disabled' : ''; ?>>
                    <i class="bi bi-floppy"></i> Save All
                </button>
                <span class="text-muted small">Enter the Ministry fuel price per tank for each month. Leave blank if not applicable.</span>
            </div>
        </form>
    </div>
</div>

<div class="card content-card mt-4">
    <div class="card-body p-4">
        <h6 class="mb-1">Transport Calculation</h6>
        <p class="text-muted mb-0 small">
            Monthly transport = <strong>tanks × price/tank</strong> (set on each employee profile).
            Deduction for leave/absent days = (monthly transport ÷ working days in month) × absent days.
        </p>
    </div>
</div>
