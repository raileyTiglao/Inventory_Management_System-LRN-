<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth/session.php';

// The guards redirect here, so the redirect itself is a 302 — set the real
// status on the page the browser actually lands on.
http_response_code(403);

$pageTitle = 'Access Denied';
$user = logged_in_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../components/head.php'; ?>
</head>
<body class="min-h-screen flex items-center justify-center bg-base p-4 relative overflow-hidden">

  <!-- same ambient backdrop as the sign-in screen: blueprint grid, amber
       glow, and oversized watermark icons -->
  <div class="login-backdrop" aria-hidden="true"></div>
  <div class="login-watermark login-watermark-tl" aria-hidden="true">
    <?= icon('lock', 'login-watermark-icon') ?>
  </div>
  <div class="login-watermark login-watermark-br" aria-hidden="true">
    <?= icon('shield', 'login-watermark-icon') ?>
  </div>
  <div class="absolute inset-x-0 bottom-0 h-1 bg-perf-h" aria-hidden="true"></div>

  <div class="w-full max-w-md relative z-10">
    <div class="flex items-center justify-center gap-2.5 mb-8">
      <img src="<?= BASE_URL ?>/assets/icons/logo.svg" alt="" class="w-9 h-9">
      <div class="leading-tight">
        <p class="font-display font-semibold text-xl text-ink">IMS</p>
        <p class="eyebrow -mt-1">Inventory System</p>
      </div>
    </div>

    <div class="bin-tag bin-tag-notch">
      <div class="error-body">
        <span class="error-badge"><?= icon('lock', '') ?></span>

        <p class="eyebrow mt-4">ERR · 403</p>
        <h1 class="font-display font-semibold text-2xl text-ink mt-1">Access Denied</h1>
        <p class="error-text">
          Your role doesn't have permission to open this page. If you think you
          should, ask an administrator to update your access.
        </p>

        <div class="error-actions">
          <?php if ($user): ?>
            <a href="<?= BASE_URL ?>/pages/dashboard/index.php" class="btn-primary">
              <?= icon('grid', 'w-4 h-4') ?>
              Back to Dashboard
            </a>
            <button type="button" onclick="history.back()" class="btn-secondary">
              <?= icon('chevron-left', 'w-4 h-4') ?>
              Go back
            </button>
          <?php else: ?>
            <a href="<?= BASE_URL ?>/pages/login/index.php" class="btn-primary">
              <?= icon('lock', 'w-4 h-4') ?>
              Sign in
            </a>
          <?php endif; ?>
        </div>

        <p class="error-meta">
          <?php if ($user): ?>
            SIGNED IN AS <?= e(strtoupper($user['name'] ?? 'UNKNOWN')) ?> ·
            ROLE <?= e(strtoupper($user['role'] ?? 'UNASSIGNED')) ?>
          <?php else: ?>
            NO ACTIVE SESSION
          <?php endif; ?>
        </p>
      </div>
    </div>
  </div>

</body>
</html>
