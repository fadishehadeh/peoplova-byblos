<?php declare(strict_types=1); ?>
<?php if (!empty($recaptchaSiteKey)): ?>
<script src="https://www.google.com/recaptcha/api.js?render=<?= e($recaptchaSiteKey); ?>"></script>
<?php endif; ?>
<div class="auth-form-card">
    <div class="p-4 p-md-5">
        <h2 class="fw-bold mb-1" style="font-size:1.35rem"><?= e($pageTitle ?? 'Sign in'); ?></h2>
        <p class="text-muted mb-4" style="font-size:.875rem">Use your work email or username to continue.</p>
        <form method="post" action="<?= e(url('/login')); ?>" novalidate id="loginForm">
            <?= csrf_field(); ?>
            <?php if (!empty($recaptchaSiteKey)): ?>
                <input type="hidden" name="g-recaptcha-response" id="gRecaptchaResponse">
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:.8125rem">Email or Username</label>
                <input type="text" name="login" class="form-control" value="<?= e((string) old('login')); ?>" autofocus required>
            </div>
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-baseline mb-1">
                    <label class="form-label fw-semibold mb-0" style="font-size:.8125rem">Password</label>
                    <a href="<?= e(url('/forgot-password')); ?>" class="small text-muted" style="font-size:.78rem">Forgot password?</a>
                </div>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2" id="loginBtn">Sign In &amp; Verify</button>
        </form>
        <hr class="mt-4 mb-3" style="border-color:#f1f5f9">
        <p class="text-center text-muted mb-0" style="font-size:.775rem">
            <i class="bi bi-shield-lock me-1 text-success"></i>Secured with two-factor email verification.
        </p>

        <?php if (!empty($devUsers)): ?>
        <div class="mt-4 p-3 rounded" style="background:#fffbea;border:1px dashed #f0c040;">
            <p class="fw-semibold mb-2 text-center" style="font-size:.78rem;color:#92690a;">
                <i class="bi bi-bug me-1"></i>DEV — Quick Login
            </p>
            <div class="d-flex flex-wrap gap-2 justify-content-center">
                <?php foreach ($devUsers as $du): ?>
                <form method="post" action="<?= e(url('/dev-login')); ?>">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="user_id" value="<?= e((string)$du['id']); ?>">
                    <button type="submit" class="btn btn-sm btn-warning" title="<?= e($du['email']); ?>">
                        <?= e($du['username']); ?>
                        <span class="badge bg-dark ms-1" style="font-size:.65rem;"><?= e($du['role_name'] ?? '—'); ?></span>
                    </button>
                </form>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php if (!empty($recaptchaSiteKey)): ?>
<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    grecaptcha.ready(function() {
        grecaptcha.execute('<?= e($recaptchaSiteKey); ?>', {action: 'login'}).then(function(token) {
            document.getElementById('gRecaptchaResponse').value = token;
            form.submit();
        });
    });
});
</script>
<?php endif; ?>
