<?php
require_once __DIR__ . '/../bootstrap.php';
$pageTitle = 'Access Denied';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../components/head.php'; ?>
</head>
<body class="min-h-screen flex items-center justify-center bg-base p-4">

  <div class="w-full max-w-md">
    <div class="bin-tag bin-tag-notch text-center">
      <div class="px-7 py-12">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-tag bg-stock-out/10 border border-stock-out/30 mb-6">
          <?= icon('alert', 'w-8 h-8 text-stock-out') ?>
        </div>

        <h1 class="font-display font-semibold text-3xl text-ink mb-2">403</h1>
        <p class="text-lg text-ink-muted mb-1">Access Denied</p>
        <p class="text-sm text-ink-dim mb-6 leading-relaxed">
          You don't have permission to access this resource. Contact your administrator if you believe this is an error.
        </p>

        <a href="<?= BASE_URL ?>/pages/dashboard.php" class="btn-primary inline-flex">
          <?= icon('grid', 'w-4 h-4') ?>
          Back to Dashboard
        </a>
      </div>
    </div>
  </div>

</body>
</html>
