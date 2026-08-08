<?php declare(strict_types=1);
$isDraft      = $run['status'] === 'draft';
$monthName    = date('F', mktime(0, 0, 0, (int) $run['period_month'], 1));
$periodLabel  = $monthName . ' ' . $run['period_year'];
$grossTotal   = array_sum(array_column($items, 'gross_total'));
$netTotal     = array_sum(array_column($items, 'net_total'));
$deductTotal  = array_sum(array_column($items, 'deductions'));
$otTotal      = array_sum(array_column($items, 'ot_amount'));
$transTotal   = array_sum(array_column($items, 'transport_amount'));
$damanTotal   = array_sum(array_column($items, 'daman_deduction'));
$taxTotal     = array_sum(array_column($items, 'income_tax_deduction'));
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><?= e($periodLabel); ?> Payroll</h4>
        <p class="text-muted mb-0">
            <?= e((string) $run['company_name']); ?>
            &middot;
            <?php if ($isDraft): ?>
            <span class="badge bg-warning text-dark">Draft</span>
            <?php else: ?>
            <span class="badge bg-success">Finalised <?= $run['finalized_at'] ? e(date('d M Y', strtotime((string) $run['finalized_at']))) : ''; ?></span>
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(url('/payroll/runs')); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> All Runs
        </a>
        <?php if ($isDraft): ?>
        <a href="<?= e(url('/payroll/runs/' . $run['id'] . '/finalize')); ?>" class="btn btn-success btn-sm">
            <i class="bi bi-lock"></i> Finalise
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Summary cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card content-card text-center py-3">
            <div class="fs-4 fw-bold"><?= count($items); ?></div>
            <div class="text-muted small">Employees</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card content-card text-center py-3">
            <div class="fw-bold">$<?= e(number_format($otTotal, 2)); ?></div>
            <div class="text-muted small">Total OT</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card content-card text-center py-3">
            <div class="fw-bold">$<?= e(number_format($transTotal, 2)); ?></div>
            <div class="text-muted small">Transport</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card content-card text-center py-3">
            <div class="fw-bold text-danger">$<?= e(number_format($damanTotal + $taxTotal, 2)); ?></div>
            <div class="text-muted small">Daman + Tax</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card content-card text-center py-3">
            <div class="fw-bold">$<?= e(number_format($grossTotal, 2)); ?></div>
            <div class="text-muted small">Gross Total</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card content-card text-center py-3">
            <div class="fw-bold text-success">$<?= e(number_format($netTotal, 2)); ?></div>
            <div class="text-muted small">Net Payable</div>
        </div>
    </div>
</div>

<!-- Line items table -->
<div class="card content-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th class="text-end">Basic</th>
                        <th class="text-end">OT</th>
                        <th class="text-end">Transport</th>
                        <th class="text-end">Housing</th>
                        <th class="text-end">Other</th>
                        <th class="text-end text-danger">Leave Ded.</th>
                        <th class="text-end text-danger">Daman</th>
                        <th class="text-end text-danger">Tax</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end fw-bold">Net</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                    <tr><td colspan="12" class="text-center py-4 text-muted">No employees found in this run.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($items as $item): ?>
                    <tr <?= $item['is_manually_adjusted'] ? 'class="table-warning"' : ''; ?>>
                        <td>
                            <div class="fw-semibold"><?= e((string) $item['employee_name']); ?></div>
                            <small class="text-muted"><?= e((string) $item['employee_code']); ?></small>
                            <?php if ($item['is_manually_adjusted']): ?>
                            <span class="badge bg-warning text-dark ms-1" title="<?= e((string) ($item['notes'] ?? '')); ?>">Adjusted</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?= e(number_format((float) $item['basic_salary'], 2)); ?></td>
                        <td class="text-end"><?= (float) ($item['ot_amount'] ?? 0) > 0 ? '$' . e(number_format((float) $item['ot_amount'], 2)) : '<span class="text-muted">—</span>'; ?></td>
                        <td class="text-end"><?= (float) ($item['transport_amount'] ?? 0) > 0 ? '$' . e(number_format((float) $item['transport_amount'], 2)) : '<span class="text-muted">—</span>'; ?></td>
                        <td class="text-end"><?= e(number_format((float) $item['housing_allowance'], 2)); ?></td>
                        <td class="text-end"><?= (float) $item['other_allowances'] > 0 ? e(number_format((float) $item['other_allowances'], 2)) : '<span class="text-muted">—</span>'; ?></td>
                        <td class="text-end text-danger"><?= (float) $item['deductions'] > 0 ? '(' . e(number_format((float) $item['deductions'], 2)) . ')' : '<span class="text-muted">—</span>'; ?></td>
                        <td class="text-end text-danger"><?= (float) ($item['daman_deduction'] ?? 0) > 0 ? '(' . e(number_format((float) $item['daman_deduction'], 2)) . ')' : '<span class="text-muted">—</span>'; ?></td>
                        <td class="text-end text-danger"><?= (float) ($item['income_tax_deduction'] ?? 0) > 0 ? '(' . e(number_format((float) $item['income_tax_deduction'], 2)) . ')' : '<span class="text-muted">—</span>'; ?></td>
                        <td class="text-end"><?= '$' . e(number_format((float) $item['gross_total'], 2)); ?></td>
                        <td class="text-end fw-bold text-success"><?= '$' . e(number_format((float) $item['net_total'], 2)); ?></td>
                        <td class="text-end">
                            <a href="<?= e(url('/payroll/runs/' . $run['id'] . '/payslip/' . $item['employee_id'])); ?>"
                               class="btn btn-xs btn-outline-secondary" title="Download payslip">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                            <?php if ($isDraft): ?>
                            <button type="button"
                                    class="btn btn-xs btn-outline-primary ms-1"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editItemModal"
                                    data-item-id="<?= e((string) $item['id']); ?>"
                                    data-basic="<?= e((string) $item['basic_salary']); ?>"
                                    data-housing="<?= e((string) $item['housing_allowance']); ?>"
                                    data-other="<?= e((string) $item['other_allowances']); ?>"
                                    data-deductions="<?= e((string) $item['deductions']); ?>"
                                    data-ot="<?= e((string) ($item['ot_amount'] ?? 0)); ?>"
                                    data-trans="<?= e((string) ($item['transport_amount'] ?? 0)); ?>"
                                    data-daman="<?= e((string) ($item['daman_deduction'] ?? 0)); ?>"
                                    data-tax="<?= e((string) ($item['income_tax_deduction'] ?? 0)); ?>"
                                    data-advance="<?= e((string) ($item['advance_deduction'] ?? 0)); ?>"
                                    data-notes="<?= e((string) ($item['notes'] ?? '')); ?>"
                                    title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php if (!empty($items)): ?>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="10" class="text-end">Total Net Payable</td>
                        <td class="text-end text-success">$<?= e(number_format($netTotal, 2)); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<?php if ($isDraft): ?>
<!-- Edit line item modal -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" id="editItemForm">
            <?= csrf_field(); ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editItemModalLabel">Adjust Line Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Gross and net are recalculated automatically from the values you enter.
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Basic Salary</label>
                            <input type="number" name="basic_salary" id="mi_basic" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Housing Allowance</label>
                            <input type="number" name="housing_allowance" id="mi_housing" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Other Allowances</label>
                            <input type="number" name="other_allowances" id="mi_other" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">OT Amount</label>
                            <input type="number" name="ot_amount" id="mi_ot" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Transport (fuel)</label>
                            <input type="number" name="transport_amount" id="mi_trans" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Unpaid Leave Ded.</label>
                            <input type="number" name="deductions" id="mi_deductions" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">Daman (3%)</label>
                            <input type="number" name="daman_deduction" id="mi_daman" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">Income Tax (2%)</label>
                            <input type="number" name="income_tax_deduction" id="mi_tax" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">Advance Ded.</label>
                            <input type="number" name="advance_deduction" id="mi_advance" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Adjustment Note</label>
                            <input type="text" name="notes" id="mi_notes" class="form-control" maxlength="500" placeholder="Reason for adjustment">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Adjustment</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('editItemModal');
    if (!modal) { return; }
    modal.addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        var itemId = btn.dataset.itemId;
        var form = document.getElementById('editItemForm');
        form.action = '<?= e(url('/payroll/runs/items/')); ?>' + itemId;
        document.getElementById('mi_basic').value      = btn.dataset.basic;
        document.getElementById('mi_housing').value    = btn.dataset.housing;
        document.getElementById('mi_other').value      = btn.dataset.other;
        document.getElementById('mi_ot').value         = btn.dataset.ot;
        document.getElementById('mi_trans').value      = btn.dataset.trans;
        document.getElementById('mi_deductions').value = btn.dataset.deductions;
        document.getElementById('mi_daman').value      = btn.dataset.daman;
        document.getElementById('mi_tax').value        = btn.dataset.tax;
        document.getElementById('mi_advance').value    = btn.dataset.advance;
        document.getElementById('mi_notes').value      = btn.dataset.notes;
    });
})();
</script>
<?php endif; ?>
