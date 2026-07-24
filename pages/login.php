<?php
require_once __DIR__ . '/../bootstrap.php';
$pageTitle = 'Sign In';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../components/head.php'; ?>
</head>
<body class="min-h-screen flex items-center justify-center bg-base p-4 relative overflow-hidden">

  <!-- ambient perforation stripes, echoing the bin-tag signature -->
  <div class="absolute inset-x-0 top-0 h-1 bg-perf-h"></div>
  <div class="absolute inset-x-0 bottom-0 h-1 bg-perf-h"></div>

  <div class="w-full max-w-md">
    <div class="flex items-center justify-center gap-2.5 mb-8">
      <img src="<?= BASE_URL ?>/assets/icons/logo.svg" alt="" class="w-9 h-9">
      <div class="leading-tight">
        <p class="font-display font-semibold text-xl text-ink">IMS</p>
        <p class="eyebrow -mt-1">Inventory System</p>
      </div>
    </div>

    <div class="bin-tag bin-tag-notch">
      <div class="px-7 pt-7 pb-2">
        <p class="eyebrow">USR · Sign In</p>
        <h1 class="font-display font-semibold text-2xl text-ink mt-1">Welcome back</h1>
        <p class="text-sm text-ink-muted mt-1.5">Sign in with your work account to continue.</p>
      </div>

      <?php
        $error = $_GET['error'] ?? '';
        $messages = [
            'invalid' => 'Invalid email or password.',
            'inactive' => 'Your account has been deactivated.',
            'server' => 'Server error. Please try again later.',
            'locked' => 'Too many failed attempts. Please wait a moment and try again.',
        ];
        if ($error && isset($messages[$error])): ?>
          <div class="px-7 pt-4 pb-0">
            <div class="p-3 rounded-tag bg-stock-out/10 border border-stock-out/30 flex items-start gap-2">
              <?= icon('alert', 'w-4 h-4 text-stock-out shrink-0 mt-0.5') ?>
              <p class="text-sm text-stock-out"><?= e($messages[$error]) ?></p>
            </div>
          </div>
        <?php endif; ?>

      <form class="px-7 pb-7 pt-5 space-y-4" action="<?= BASE_URL ?>/auth/login.php" method="post">
        <div>
          <label class="field-label">Email</label>
          <input type="email" name="email" required class="field-input" placeholder="you@ims.local">
        </div>

        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="field-label !mb-0">Password</label>
            <button type="button" onclick="openForgotPasswordModal()" class="text-[11px] text-tag-amber hover:underline">Forgot?</button>
          </div>
          <input type="password" name="password" required class="field-input" placeholder="••••••••">
        </div>

        <label class="flex items-center gap-2 text-xs text-ink-muted">
          <input type="checkbox" name="remember" class="w-3.5 h-3.5 rounded accent-tag-amber bg-base-deep border-border-light">
          Keep me signed in
        </label>

        <button type="submit" class="btn-primary w-full !py-3 mt-2">
          <?= icon('lock', 'w-4 h-4') ?>
          Sign in
        </button>
      </form>
    </div>

    <p class="text-center text-[11px] text-ink-dim mt-6">Access is limited to authorized personnel.</p>
  </div>

  <div id="forgot-password-modal" class="hidden flex modal-overlay">
    <div class="bin-tag modal-panel-sm">
      <div class="panel-header">
        <div>
          <p class="eyebrow">Account Recovery</p>
          <h2 class="font-display font-semibold text-ink">Forgot your password?</h2>
        </div>
        <button type="button" onclick="closeForgotPasswordModal()" class="icon-btn-muted">✕</button>
      </div>
      <div class="p-5 space-y-4">
        <p class="text-sm text-ink-muted">Password resets are handled by your system administrator. Reach out to them directly with your account email to get back in.</p>
        <div class="modal-footer">
          <button type="button" onclick="closeForgotPasswordModal()" class="btn-primary">Got it</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    function openForgotPasswordModal() {
      document.getElementById('forgot-password-modal').classList.remove('hidden');
    }
    function closeForgotPasswordModal() {
      document.getElementById('forgot-password-modal').classList.add('hidden');
    }
  </script>

</body>
</html>
