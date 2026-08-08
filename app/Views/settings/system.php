<?php declare(strict_types=1); ?>
<?php require base_path('app/Views/partials/settings-nav.php'); ?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <h5 class="mb-1">System Settings</h5>
        <p class="text-muted mb-0">Super admins can override operational runtime settings here without editing the server <code>.env</code> file.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <form method="post" action="<?= e(url('/settings/system/test-email')); ?>" class="d-inline">
            <?= csrf_field(); ?>
            <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-envelope-check"></i> Send Test Email</button>
        </form>
        <form method="post" action="<?= e(url('/settings/system/test-backups')); ?>" class="d-inline">
            <?= csrf_field(); ?>
            <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-hdd-stack"></i> Check Backup Config</button>
        </form>
        <form method="post" action="<?= e(url('/settings/system/test-b2')); ?>" class="d-inline">
            <?= csrf_field(); ?>
            <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-cloud-check"></i> Validate B2</button>
        </form>
    </div>
</div>

<form method="post" action="<?= e(url('/settings/system')); ?>" enctype="multipart/form-data">
    <?= csrf_field(); ?>

    <?php foreach (($operationalSettings ?? []) as $section): ?>
    <div class="card content-card mb-4">
        <div class="card-body p-4">
            <div class="mb-4">
                <h5 class="mb-1"><?= e((string) ($section['title'] ?? 'Settings')); ?></h5>
                <p class="text-muted mb-0"><?= e((string) ($section['description'] ?? '')); ?></p>
            </div>

            <div class="row g-3">
                <?php foreach (($section['fields'] ?? []) as $field): ?>
                    <?php
                        $definition = $field['definition'];
                        $alias = (string) $field['alias'];
                        $inputType = (string) ($definition['input'] ?? 'text');
                        $isSecret = !empty($definition['secret']);
                        $effectiveValue = $field['effective_value'];
                        $displayValue = (string) ($field['display_value'] ?? '');
                        $colClass = $inputType === 'textarea' ? 'col-12' : 'col-12 col-md-6';
                    ?>
                    <div class="<?= e($colClass); ?>">
                        <label class="form-label fw-semibold"><?= e((string) $definition['label']); ?></label>

                        <?php if ($inputType === 'boolean'): ?>
                            <input type="hidden" name="settings[<?= e($alias); ?>]" value="0">
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                       type="checkbox"
                                       role="switch"
                                       name="settings[<?= e($alias); ?>]"
                                       id="setting_<?= e($alias); ?>"
                                       value="1"
                                       <?= !empty($effectiveValue) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="setting_<?= e($alias); ?>">
                                    <?= !empty($effectiveValue) ? 'Enabled' : 'Disabled'; ?>
                                </label>
                            </div>
                        <?php elseif ($inputType === 'select'): ?>
                            <select name="settings[<?= e($alias); ?>]" class="form-select">
                                <?php foreach (($definition['options'] ?? []) as $optionValue => $optionLabel): ?>
                                    <?php
                                        $selected = false;
                                        if ($optionValue === '__EMPTY__') {
                                            $selected = (string) $effectiveValue === '';
                                        } else {
                                            $selected = (string) $effectiveValue === (string) $optionValue;
                                        }
                                    ?>
                                    <option value="<?= e((string) $optionValue); ?>" <?= $selected ? 'selected' : ''; ?>>
                                        <?= e((string) $optionLabel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($inputType === 'textarea'): ?>
                            <textarea name="settings[<?= e($alias); ?>]" class="form-control" rows="4" placeholder="<?= e((string) ($definition['placeholder'] ?? '')); ?>"><?= e($displayValue); ?></textarea>
                        <?php else: ?>
                            <input type="<?= e($isSecret ? 'password' : $inputType); ?>"
                                   name="settings[<?= e($alias); ?>]"
                                   class="form-control"
                                   value="<?= e($isSecret ? '' : $displayValue); ?>"
                                   placeholder="<?= e($isSecret && !empty($field['has_secret_value']) ? 'Saved value hidden. Enter a new value to replace it.' : (string) ($definition['placeholder'] ?? '')); ?>">
                        <?php endif; ?>

                        <div class="form-text"><?= e((string) ($definition['help'] ?? '')); ?></div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span class="badge text-bg-<?= !empty($field['uses_override']) ? 'primary' : 'light'; ?>">
                                <?= !empty($field['uses_override']) ? 'DB override active' : '.env / config fallback'; ?>
                            </span>
                            <?php if ($isSecret && !empty($field['has_secret_value'])): ?>
                                <span class="badge text-bg-secondary">Secret stored / available</span>
                            <?php endif; ?>
                            <?php if (!empty($field['updated_at'])): ?>
                                <span class="small text-muted">Updated <?= e((string) $field['updated_at']); ?><?= !empty($field['updated_by_name']) ? ' by ' . e((string) $field['updated_by_name']) : ''; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="card content-card mb-4">
        <div class="card-body p-4">
            <div class="mb-4">
                <h5 class="mb-1">Module Toggles</h5>
                <p class="text-muted mb-0">Disabling a module hides its navigation items and blocks access to its routes. Existing data is preserved.</p>
            </div>

            <div class="form-check form-switch">
                <input class="form-check-input"
                       type="checkbox"
                       role="switch"
                       name="payroll_enabled"
                       id="payrollEnabledToggle"
                       value="1"
                       <?= ($payrollEnabled ?? false) ? 'checked' : ''; ?>>
                <label class="form-check-label fw-semibold" for="payrollEnabledToggle">
                    Payroll Module
                </label>
                <div class="form-text mt-1">When enabled, HR admins and super admins can access salary structures, payroll runs, and payslip downloads.</div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mb-4">
        <button type="submit" class="btn btn-primary">Save System Settings</button>
        <a href="<?= e(url('/settings')); ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<div class="card content-card">
    <div class="card-body p-4">
        <div class="mb-3">
            <h5 class="mb-1">Recent Operational Changes</h5>
            <p class="text-muted mb-0">Recent runtime override edits saved by super admins.</p>
        </div>

        <?php if (empty($recentOperationalChanges)): ?>
            <div class="empty-state">No operational setting changes have been recorded yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Setting</th>
                            <th>Updated By</th>
                            <th>Updated At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOperationalChanges as $change): ?>
                            <tr>
                                <td><?= e(ucwords(str_replace('_', ' ', (string) $change['category_name']))); ?></td>
                                <td><?= e((string) ($change['label'] ?? ucwords(str_replace('_', ' ', (string) $change['setting_key'])))); ?></td>
                                <td><?= e((string) ($change['updated_by_name'] ?? 'System')); ?></td>
                                <td><?= e((string) $change['updated_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card content-card mb-4 border-warning-subtle">
    <div class="card-body p-4">
        <div class="mb-4">
            <h5 class="mb-1"><i class="bi bi-database-fill-gear me-2 text-warning"></i>Data Management</h5>
            <p class="text-muted mb-0">Seed sample data for demos, or wipe all business data to start fresh. System users, roles, and settings are never touched.</p>
        </div>

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div class="border rounded-3 p-4 h-100">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="rounded-2 p-2" style="background:rgba(var(--bs-success-rgb),.1)">
                            <i class="bi bi-stars fs-4 text-success"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-semibold">Seed Demo Data</h6>
                            <p class="text-muted mb-0" style="font-size:.85rem">Adds 10 sample employees across 5 departments, leave types, balances, and 3 announcements. Safe to run multiple times.</p>
                        </div>
                    </div>
                    <form method="post" action="<?= e(url('/settings/system/seed-demo')); ?>">
                        <?= csrf_field(); ?>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-play-fill me-1"></i> Seed Demo Data
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="border border-danger-subtle rounded-3 p-4 h-100">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="rounded-2 p-2" style="background:rgba(var(--bs-danger-rgb),.1)">
                            <i class="bi bi-trash3-fill fs-4 text-danger"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-semibold text-danger">Delete All Data</h6>
                            <p class="text-muted mb-0" style="font-size:.85rem">Permanently removes all employees, departments, leave records, documents, and announcements. <strong>Cannot be undone.</strong></p>
                        </div>
                    </div>
                    <form method="post" action="<?= e(url('/settings/system/delete-all-data')); ?>"
                          onsubmit="return (document.getElementById('confirmDeleteInput').value === 'DELETE') || (alert('Type DELETE to confirm') || false)">
                        <?= csrf_field(); ?>
                        <div class="mb-2">
                            <input type="text" id="confirmDeleteInput" name="confirm_delete"
                                   class="form-control form-control-sm"
                                   placeholder="Type DELETE to confirm" autocomplete="off">
                        </div>
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Delete All Data
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
