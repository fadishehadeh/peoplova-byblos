<?php declare(strict_types=1);
$t = $template;
?>

<div class="mb-4">
    <h4 class="mb-1">Edit Salary Template</h4>
    <p class="text-muted mb-0">Update the template values. Existing salary structures already applied are not changed.</p>
</div>

<form method="post" action="<?= e(url('/payroll/salary-templates/' . $t['id'] . '/edit')); ?>">
    <?= csrf_field(); ?>

    <div class="card content-card mb-4">
        <div class="card-header bg-transparent border-bottom py-3">
            <h6 class="mb-0">Template Details</h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Template Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?= e((string) $t['name']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active"   <?= $t['status'] === 'active'   ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?= $t['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control" value="<?= e((string) ($t['description'] ?? '')); ?>" placeholder="Optional short description">
                </div>
            </div>
        </div>
    </div>

    <div class="card content-card mb-4">
        <div class="card-header bg-transparent border-bottom py-3">
            <h6 class="mb-0">Pay Components</h6>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Basic Salary <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="basic_salary" class="form-control" step="0.01" min="0" required
                               value="<?= e(number_format((float) $t['basic_salary'], 2)); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Housing Allowance</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="housing_allowance" class="form-control" step="0.01" min="0"
                               value="<?= e(number_format((float) $t['housing_allowance'], 2)); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Transport Allowance</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="transport_allowance" class="form-control" step="0.01" min="0"
                               value="<?= e(number_format((float) $t['transport_allowance'], 2)); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Other Allowances</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="other_allowances" class="form-control" step="0.01" min="0"
                               value="<?= e(number_format((float) $t['other_allowances'], 2)); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Daman Rate</label>
                    <div class="input-group">
                        <input type="number" name="daman_rate" class="form-control" step="0.01" min="0" max="100"
                               value="<?= e((string) $t['daman_rate']); ?>">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Income Tax Rate</label>
                    <div class="input-group">
                        <input type="number" name="income_tax_rate" class="form-control" step="0.01" min="0" max="100"
                               value="<?= e((string) $t['income_tax_rate']); ?>">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Currency</label>
                    <select name="currency" class="form-select">
                        <option value="USD" <?= $t['currency'] === 'USD' ? 'selected' : ''; ?>>USD</option>
                        <option value="LBP" <?= $t['currency'] === 'LBP' ? 'selected' : ''; ?>>LBP</option>
                        <option value="EUR" <?= $t['currency'] === 'EUR' ? 'selected' : ''; ?>>EUR</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-floppy"></i> Save Changes</button>
        <a href="<?= e(url('/payroll/salary-templates?company_id=' . $t['company_id'])); ?>"
           class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
