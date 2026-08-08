<?php declare(strict_types=1); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">BioTime Attendance Import</h4>
        <p class="text-muted mb-0">Upload a ZKTeco BioTime Excel export to record attendance and calculate overtime.</p>
    </div>
</div>

<?php if (!empty($result)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <strong>Import complete!</strong>
    <?= (int) $result['matched']; ?> rows matched,
    <?= (int) $result['unmatched']; ?> unmatched,
    <?= (int) $result['written']; ?> attendance records written.
    OT calculated: <strong><?= (int) $result['ot_rows']; ?> day entries</strong>,
    total <strong>$<?= number_format((float) $result['ot_total'], 2); ?></strong>.
    <?php if (!empty($result['unmatched_names'])): ?>
    <hr>
    <strong>Unmatched names (no employee found):</strong>
    <?= e(implode(', ', array_slice($result['unmatched_names'], 0, 10))); ?>
    <?= count($result['unmatched_names']) > 10 ? ' …and ' . (count($result['unmatched_names']) - 10) . ' more' : ''; ?>
    <div class="form-text text-muted mt-1">Ensure employee names or codes in the Excel match the Employees list exactly.</div>
    <?php endif; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card content-card">
            <div class="card-body p-4">
                <h6 class="mb-3">Upload BioTime Excel</h6>
                <form method="post" action="<?= e(url('/attendance/import/upload')); ?>" enctype="multipart/form-data">
                    <?= csrf_field(); ?>

                    <?php if (!empty($companies)): ?>
                    <div class="mb-3">
                        <label class="form-label">Company *</label>
                        <select name="company_id" class="form-select" required onchange="syncCompany(this.value)">
                            <?php foreach ($companies as $c): ?>
                            <option value="<?= e((string) $c['id']); ?>" <?= (int) $c['id'] === $companyId ? 'selected' : ''; ?>>
                                <?= e((string) $c['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="company_id" value="<?= e((string) $companyId); ?>">
                    <?php endif; ?>

                    <div class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label">Month *</label>
                            <select name="month" class="form-select" required>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m; ?>" <?= $m === (int) date('n') ? 'selected' : ''; ?>>
                                    <?= date('F', mktime(0,0,0,$m,1)); ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col">
                            <label class="form-label">Year *</label>
                            <select name="year" class="form-select" required>
                                <?php for ($y = (int) date('Y'); $y >= 2023; $y--): ?>
                                <option value="<?= $y; ?>" <?= $y === (int) date('Y') ? 'selected' : ''; ?>><?= $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Excel File (.xlsx / .xls) *</label>
                        <input type="file" name="attendance_file" class="form-control" accept=".xlsx,.xls" required>
                        <div class="form-text">Export from BioTime: Reports → Attendance Report → Export to Excel.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-upload"></i> Upload &amp; Import
                    </button>
                </form>
            </div>
        </div>

        <div class="card content-card mt-3">
            <div class="card-body p-4">
                <h6 class="mb-2">Re-calculate OT Only</h6>
                <p class="text-muted small mb-3">Already imported attendance records? Re-run the OT engine without re-uploading.</p>
                <form method="post" action="<?= e(url('/attendance/import/recalc-ot')); ?>">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="company_id" value="<?= e((string) $companyId); ?>">
                    <div class="row g-2 mb-3">
                        <div class="col">
                            <select name="month" class="form-select">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m; ?>" <?= $m === (int) date('n') ? 'selected' : ''; ?>>
                                    <?= date('F', mktime(0,0,0,$m,1)); ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col">
                            <select name="year" class="form-select">
                                <?php for ($y = (int) date('Y'); $y >= 2023; $y--): ?>
                                <option value="<?= $y; ?>"><?= $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-clockwise"></i> Recalculate OT
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card content-card">
            <div class="card-body p-0">
                <div class="px-4 py-3 border-bottom">
                    <h6 class="mb-0">Import History</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Period</th>
                                <th>File</th>
                                <th>Rows</th>
                                <th>Matched</th>
                                <th>Imported By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($imports)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No imports yet.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($imports as $imp): ?>
                            <tr>
                                <td><?= date('M Y', mktime(0, 0, 0, (int) $imp['import_month'], 1, (int) $imp['import_year'])); ?></td>
                                <td class="text-truncate" style="max-width:160px" title="<?= e((string) $imp['filename']); ?>">
                                    <?= e((string) $imp['filename']); ?>
                                </td>
                                <td><?= (int) $imp['row_count']; ?></td>
                                <td>
                                    <span class="text-success"><?= (int) $imp['matched']; ?> ✓</span>
                                    <?php if ((int) $imp['unmatched'] > 0): ?>
                                    <span class="text-danger ms-1"><?= (int) $imp['unmatched']; ?> ✗</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e((string) ($imp['imported_by_email'] ?? '—')); ?></td>
                                <td><?= date('d M Y H:i', strtotime((string) $imp['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card content-card mt-3">
            <div class="card-body p-4">
                <h6 class="mb-2">Expected BioTime Format</h6>
                <p class="text-muted small mb-2">The importer auto-detects the header row and maps these column names:</p>
                <div class="row g-2 small">
                    <div class="col-sm-6">
                        <strong class="text-dark">Employee:</strong>
                        <span class="text-muted">Name, Employee Name, Employee No, Emp ID, Code</span>
                    </div>
                    <div class="col-sm-6">
                        <strong class="text-dark">Date:</strong>
                        <span class="text-muted">Date, Work Date, Day</span>
                    </div>
                    <div class="col-sm-6">
                        <strong class="text-dark">Clock In:</strong>
                        <span class="text-muted">Check In, First In, Clock In, On Duty</span>
                    </div>
                    <div class="col-sm-6">
                        <strong class="text-dark">Clock Out:</strong>
                        <span class="text-muted">Check Out, Last Out, Clock Out, Off Duty</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function syncCompany(val) {
    document.querySelectorAll('input[name="company_id"]').forEach(el => el.value = val);
}
</script>
