<?php declare(strict_types=1); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Salary Structure Templates</h4>
        <p class="text-muted mb-0">Define reusable pay packages and apply them to multiple employees at once.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTemplateModal">
        <i class="bi bi-plus-lg"></i> New Template
    </button>
</div>

<?php if (!empty($companies)): ?>
<form method="get" action="<?= e(url('/payroll/salary-templates')); ?>" class="mb-4">
    <div class="row g-2 align-items-center">
        <div class="col-auto"><label class="form-label mb-0">Company</label></div>
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

<div class="card content-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Template Name</th>
                        <th>Basic</th>
                        <th>Housing</th>
                        <th>Transport</th>
                        <th>Other</th>
                        <th>Currency</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($templates)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <?= $companyId === 0 ? 'Select a company.' : 'No templates yet — create one above.'; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($templates as $t): ?>
                    <tr class="<?= $t['status'] === 'inactive' ? 'text-muted' : ''; ?>">
                        <td>
                            <div class="fw-semibold"><?= e((string) $t['name']); ?></div>
                            <?php if ($t['description']): ?>
                            <small class="text-muted"><?= e((string) $t['description']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format((float) $t['basic_salary'], 2); ?></td>
                        <td><?= number_format((float) $t['housing_allowance'], 2); ?></td>
                        <td><?= number_format((float) $t['transport_allowance'], 2); ?></td>
                        <td><?= number_format((float) $t['other_allowances'], 2); ?></td>
                        <td><?= e((string) $t['currency']); ?></td>
                        <td>
                            <?php if ($t['status'] === 'active'): ?>
                            <span class="badge bg-success">Active</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($t['status'] === 'active'): ?>
                            <a href="<?= e(url('/payroll/salary-templates/' . $t['id'] . '/apply')); ?>"
                               class="btn btn-sm btn-primary me-1" title="Bulk apply this template">
                                <i class="bi bi-people"></i> Apply
                            </a>
                            <?php endif; ?>
                            <a href="<?= e(url('/payroll/salary-templates/' . $t['id'] . '/edit')); ?>"
                               class="btn btn-sm btn-outline-secondary me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="post" action="<?= e(url('/payroll/salary-templates/' . $t['id'] . '/delete')); ?>"
                                  class="d-inline" onsubmit="return confirm('Delete template \'<?= e(addslashes((string) $t['name'])); ?>\'?')">
                                <?= csrf_field(); ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card content-card mt-4 border-0 bg-light">
    <div class="card-body p-3 small text-muted">
        <strong>How it works:</strong>
        Create a template with a pay package (basic, housing, allowances, rates).
        Click <strong>Apply</strong> on any template to assign it to one or more employees with a chosen effective date.
        The employee's previous salary structure is automatically closed on that date.
    </div>
</div>

<?php /* ── Create Template Modal ─────────────────────────────────────────── */ ?>
<div class="modal fade" id="createTemplateModal" tabindex="-1" aria-labelledby="createTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="<?= e(url('/payroll/salary-templates')); ?>">
                <?= csrf_field(); ?>
                <input type="hidden" name="company_id" value="<?= e((string) $companyId); ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="createTemplateModalLabel">New Salary Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Template Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Press Operator Standard">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control" placeholder="Optional short description">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Basic Salary <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="basic_salary" class="form-control" step="0.01" min="0" required value="0.00">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Housing Allowance</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="housing_allowance" class="form-control" step="0.01" min="0" value="0.00">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Transport Allowance</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="transport_allowance" class="form-control" step="0.01" min="0" value="0.00">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Other Allowances</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="other_allowances" class="form-control" step="0.01" min="0" value="0.00">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Daman Rate</label>
                            <div class="input-group">
                                <input type="number" name="daman_rate" class="form-control" step="0.01" min="0" max="100" value="3.00">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Income Tax Rate</label>
                            <div class="input-group">
                                <input type="number" name="income_tax_rate" class="form-control" step="0.01" min="0" max="100" value="2.00">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Currency</label>
                            <select name="currency" class="form-select">
                                <option value="USD" selected>USD</option>
                                <option value="LBP">LBP</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy"></i> Save Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
