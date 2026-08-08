<?php declare(strict_types=1); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">OT Groups</h4>
        <p class="text-muted mb-0">Configure overtime rules (block size, rate, start time) for each employee group.</p>
    </div>
</div>

<?php if (!empty($companies)): ?>
<form method="get" action="<?= e(url('/payroll/ot-groups')); ?>" class="mb-4">
    <div class="row g-2 align-items-center">
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

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card content-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Group Name</th>
                                <th>OT Starts</th>
                                <th>Block</th>
                                <th>Min Minutes</th>
                                <th class="text-end">Rate/Block</th>
                                <th>Employees</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($groups)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <?= $companyId === 0 ? 'Select a company above.' : 'No OT groups yet. Add one using the form.'; ?>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($groups as $g): ?>
                            <tr>
                                <td><strong><?= e((string) $g['name']); ?></strong></td>
                                <td><?= sprintf('%02d:%02d', $g['ot_start_hour'], $g['ot_start_minute']); ?></td>
                                <td><?= e((string) $g['block_minutes']); ?> min</td>
                                <td><?= e((string) $g['min_ot_minutes']); ?> min</td>
                                <td class="text-end">$<?= number_format((float) $g['amount_per_block'], 2); ?></td>
                                <td><?= (int) $g['employee_count']; ?></td>
                                <td class="text-end">
                                    <a href="<?= e(url('/payroll/ot-groups/' . $g['id'] . '/edit')); ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form method="post" action="<?= e(url('/payroll/ot-groups/' . $g['id'] . '/delete')); ?>" class="d-inline"
                                          onsubmit="return confirm('Delete this OT group? Employees in it will be unaffected but unlinked.')">
                                        <?= csrf_field(); ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
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
    </div>

    <div class="col-lg-5">
        <div class="card content-card">
            <div class="card-body p-4">
                <h6 class="mb-3">Add OT Group</h6>
                <form method="post" action="<?= e(url('/payroll/ot-groups')); ?>">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="company_id" value="<?= e((string) $companyId); ?>">

                    <div class="mb-3">
                        <label class="form-label">Group Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Group 1, G2, Production">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">OT Starts At *
                            <span class="text-muted fw-normal">(shift end time)</span></label>
                        <input type="time" name="ot_start_time" class="form-control" value="17:00" required>
                        <div class="form-text">Overtime counted from this time onward each day.</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label">Block Size (min)</label>
                            <input type="number" name="block_minutes" class="form-control" value="90" min="15" max="480">
                            <div class="form-text">Each paid block = this many minutes of OT.</div>
                        </div>
                        <div class="col">
                            <label class="form-label">Min OT to qualify (min)</label>
                            <input type="number" name="min_ot_minutes" class="form-control" value="30" min="0" max="120">
                            <div class="form-text">Must work at least this many OT min for any pay.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Rate per Block (USD) *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="amount_per_block" class="form-control" value="8.00" min="0" step="0.01" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" <?= $companyId === 0 ? 'disabled' : ''; ?>>
                        <i class="bi bi-plus-lg"></i> Add Group
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card content-card mt-4">
    <div class="card-body p-4">
        <h6 class="mb-1">How OT is calculated</h6>
        <p class="text-muted mb-0 small">
            For each worked day: <strong>OT minutes = clock-out time − OT start time</strong>.
            If OT minutes ≥ <em>Min OT</em>, count complete blocks = floor(OT minutes ÷ Block Size).
            Pay = blocks × Rate/Block. Night OT (crossing midnight) is flagged separately.
        </p>
    </div>
</div>
