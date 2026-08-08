<?php declare(strict_types=1);
$s = $settings ?? [];
$v = static fn(string $key, mixed $default = '') => $s[$key] ?? $default;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Payroll Settings</h4>
        <p class="text-muted mb-0">Configure all payroll calculation rules — rates, OT defaults, and working calendar.</p>
    </div>
</div>

<?php if (!empty($companies)): ?>
<form method="get" action="<?= e(url('/payroll/settings')); ?>" class="mb-4">
    <div class="row g-2 align-items-center">
        <div class="col-auto">
            <label class="form-label mb-0 me-1">Company</label>
        </div>
        <div class="col-auto">
            <select name="company_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($companies as $c): ?>
                <option value="<?= e((string) $c['id']); ?>" <?= (int) $c['id'] === $companyId ? 'selected' : ''; ?>>
                    <?= e((string) $c['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</form>
<?php endif; ?>

<form method="post" action="<?= e(url('/payroll/settings/save')); ?>">
    <?= csrf_field(); ?>
    <input type="hidden" name="company_id" value="<?= e((string) $companyId); ?>">

    <?php /* ── DEDUCTIONS ────────────────────────────────────────────── */ ?>
    <div class="card content-card mb-4">
        <div class="card-header bg-transparent border-bottom py-3">
            <h6 class="mb-0"><i class="bi bi-percent text-primary me-2"></i>Statutory Deductions</h6>
            <small class="text-muted">Applied to each employee's basic salary each payroll run.</small>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-sm-6 col-md-4">
                    <label class="form-label">Daman (NSSF) Rate <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="daman_rate" class="form-control"
                               value="<?= e((string) $v('daman_rate', '3.00')); ?>"
                               step="0.01" min="0" max="100" required>
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text">Lebanon NSSF employer contribution on basic salary (typically 3%).</div>
                </div>
                <div class="col-sm-6 col-md-4">
                    <label class="form-label">Income Tax Rate <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="income_tax_rate" class="form-control"
                               value="<?= e((string) $v('income_tax_rate', '2.00')); ?>"
                               step="0.01" min="0" max="100" required>
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text">Withholding tax on basic salary (adjust per bracket if needed).</div>
                </div>
            </div>
        </div>
    </div>

    <?php /* ── OVERTIME ──────────────────────────────────────────────── */ ?>
    <div class="card content-card mb-4">
        <div class="card-header bg-transparent border-bottom py-3">
            <h6 class="mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Overtime Defaults</h6>
            <small class="text-muted">Company-wide OT defaults — individual OT Groups can override per group.</small>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-sm-6 col-md-4">
                    <label class="form-label">OT Block Length</label>
                    <div class="input-group">
                        <input type="number" name="ot_block_minutes" class="form-control"
                               value="<?= e((string) $v('ot_block_minutes', '90')); ?>"
                               step="1" min="15" max="480">
                        <span class="input-group-text">min</span>
                    </div>
                    <div class="form-text">Duration of one OT block (e.g. 90 min).</div>
                </div>
                <div class="col-sm-6 col-md-4">
                    <label class="form-label">Amount per Block</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="ot_amount_per_block" class="form-control"
                               value="<?= e((string) $v('ot_amount_per_block', '8.00')); ?>"
                               step="0.01" min="0">
                    </div>
                    <div class="form-text">Pay per full OT block completed.</div>
                </div>
                <div class="col-sm-6 col-md-4">
                    <label class="form-label">Minimum OT to Qualify</label>
                    <div class="input-group">
                        <input type="number" name="ot_min_minutes" class="form-control"
                               value="<?= e((string) $v('ot_min_minutes', '30')); ?>"
                               step="1" min="0">
                        <span class="input-group-text">min</span>
                    </div>
                    <div class="form-text">Minimum OT minutes before any block is counted.</div>
                </div>
            </div>
        </div>
    </div>

    <?php /* ── GENERAL ───────────────────────────────────────────────── */ ?>
    <div class="card content-card mb-4">
        <div class="card-header bg-transparent border-bottom py-3">
            <h6 class="mb-0"><i class="bi bi-sliders text-primary me-2"></i>General</h6>
            <small class="text-muted">Calendar and currency settings that affect payroll calculations.</small>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-sm-6 col-md-4">
                    <label class="form-label">Working Week</label>
                    <select name="working_week" class="form-select">
                        <option value="mon_sat" <?= $v('working_week') === 'mon_sat' ? 'selected' : ''; ?>>Monday – Saturday (6 days)</option>
                        <option value="mon_fri" <?= $v('working_week') === 'mon_fri' ? 'selected' : ''; ?>>Monday – Friday (5 days)</option>
                    </select>
                    <div class="form-text">Used to count working days for transport and leave pro-ration.</div>
                </div>
                <div class="col-sm-6 col-md-4">
                    <label class="form-label">Payroll Currency</label>
                    <select name="currency" class="form-select">
                        <option value="USD" <?= $v('currency') === 'USD' ? 'selected' : ''; ?>>USD – US Dollar</option>
                        <option value="LBP" <?= $v('currency') === 'LBP' ? 'selected' : ''; ?>>LBP – Lebanese Pound</option>
                        <option value="EUR" <?= $v('currency') === 'EUR' ? 'selected' : ''; ?>>EUR – Euro</option>
                    </select>
                    <div class="form-text">Display currency on payslips and payroll run reports.</div>
                </div>
                <div class="col-sm-6 col-md-4">
                    <label class="form-label">Late Deduction (per minute)</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="late_deduction_per_minute" class="form-control"
                               value="<?= e((string) $v('late_deduction_per_minute', '0.00')); ?>"
                               step="0.001" min="0">
                    </div>
                    <div class="form-text">Deduction per minute of lateness (0 = disabled).</div>
                </div>
                <div class="col-sm-6 col-md-4">
                    <label class="form-label">Max Advance (months)</label>
                    <input type="number" name="advance_max_months" class="form-control"
                           value="<?= e((string) $v('advance_max_months', '3')); ?>"
                           step="1" min="0" max="24">
                    <div class="form-text">Maximum number of monthly salaries an employee can draw as advance.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary" <?= $companyId === 0 ? 'disabled' : ''; ?>>
            <i class="bi bi-floppy"></i> Save Settings
        </button>
        <span class="text-muted small align-self-center">Changes take effect on the next payroll run.</span>
    </div>
</form>

<div class="card content-card mt-4 border-0 bg-light">
    <div class="card-body p-3">
        <p class="mb-1 small"><strong>Note on OT Groups:</strong> The OT block minutes, amount per block, and minimum minutes configured here are the company-wide defaults. Each OT Group can override these values independently — go to <a href="<?= e(url('/payroll/ot-groups')); ?>">OT Groups</a> to configure per-group rules.</p>
    </div>
</div>
