<?php declare(strict_types=1); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Edit OT Group</h4>
        <p class="text-muted mb-0"><?= e((string) $group['name']); ?></p>
    </div>
    <a href="<?= e(url('/payroll/ot-groups?company_id=' . $group['company_id'])); ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card content-card">
            <div class="card-body p-4">
                <form method="post" action="<?= e(url('/payroll/ot-groups/' . $group['id'] . '/edit')); ?>">
                    <?= csrf_field(); ?>

                    <div class="mb-3">
                        <label class="form-label">Group Name *</label>
                        <input type="text" name="name" class="form-control" required value="<?= e((string) $group['name']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">OT Starts At *</label>
                        <input type="time" name="ot_start_time" class="form-control" required
                               value="<?= sprintf('%02d:%02d', $group['ot_start_hour'], $group['ot_start_minute']); ?>">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label">Block Size (min)</label>
                            <input type="number" name="block_minutes" class="form-control" value="<?= e((string) $group['block_minutes']); ?>" min="15" max="480">
                        </div>
                        <div class="col">
                            <label class="form-label">Min OT to qualify (min)</label>
                            <input type="number" name="min_ot_minutes" class="form-control" value="<?= e((string) $group['min_ot_minutes']); ?>" min="0" max="120">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Rate per Block (USD) *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="amount_per_block" class="form-control" value="<?= e((string) $group['amount_per_block']); ?>" min="0" step="0.01" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="<?= e(url('/payroll/ot-groups?company_id=' . $group['company_id'])); ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
