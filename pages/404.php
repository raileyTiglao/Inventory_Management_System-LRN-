<?php
require_once __DIR__ . '/../bootstrap.php';

ensure_routed('404');

// Apache's ErrorDocument redispatches here internally, so the response is
// still a 200 unless set explicitly — the browser needs the real status.
http_response_code(404);

$pageTitle = 'Page Not Found';
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
    <?= icon('search', 'login-watermark-icon') ?>
  </div>
  <div class="login-watermark login-watermark-br" aria-hidden="true">
    <?= icon('package', 'login-watermark-icon') ?>
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
        <span class="error-badge"><?= icon('search', '') ?></span>

        <p class="eyebrow mt-4">ERR · 404</p>
        <h1 class="font-display font-semibold text-2xl text-ink mt-1">Oops! Page not found</h1>
        <p class="error-text">
          Looks like this page doesn't exist, or may have moved.
          Let's get you back on track.
        </p>

        <div class="error-actions">
          <a href="<?= url('login') ?>" class="btn-primary">
            <?= icon('lock', 'w-4 h-4') ?>
            Go to Sign In
          </a>
          <button type="button" onclick="history.back()" class="btn-secondary">
            <?= icon('chevron-left', 'w-4 h-4') ?>
            Go back
          </button>
        </div>

        <p class="error-meta">
          REQUESTED · <?= e($_SERVER['REQUEST_URI'] ?? 'unknown') ?>
        </p>
      </div>
    </div>
  </div>

</body>
</html>
