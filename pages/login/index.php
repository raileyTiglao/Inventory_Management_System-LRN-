<?php
require_once __DIR__ . '/../../bootstrap.php';

ensure_routed('login');

$pageTitle = 'Sign In';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../../components/head.php'; ?>
</head>
<body class="min-h-screen flex items-center justify-center bg-base p-4 md:p-8">

  <div class="w-full max-w-6xl">
    <div class="auth-card">

      <!-- Left: reserved entirely for the three.js animation, which mounts
           into #login-3d-bg. The blueprint grid sits behind it so the panel
           isn't blank before the animation is wired in. -->
      <div class="auth-visual" aria-hidden="true">
        <div class="login-backdrop"></div>
        <div id="login-3d-bg" class="auth-visual-canvas"></div>
      </div>

      <!-- Right: sign-in form -->
      <div class="auth-form-side">
        <div class="auth-brand">
          <img src="<?= BASE_URL ?>/assets/icons/logo.svg" alt="" class="w-11 h-11">
          <div>
            <p class="auth-brand-name">IMS</p>
            <p class="eyebrow mt-1">Inventory System</p>
          </div>
        </div>

        <h1 class="auth-title">Log in</h1>
        <p class="auth-subtitle">Sign in with your work account to continue.</p>

        <?php
          $error = $_GET['error'] ?? '';
          $messages = [
              'invalid' => 'Invalid email or password.',
              'inactive' => 'Your account has been deactivated.',
              'server' => 'Server error. Please try again later.',
              'locked' => 'Too many failed attempts. Please wait a moment and try again.',
          ];
          if ($error && isset($messages[$error])): ?>
            <div class="auth-error mt-6">
              <?= icon('alert', 'w-4 h-4 shrink-0 mt-0.5') ?>
              <p><?= e($messages[$error]) ?></p>
            </div>
          <?php endif; ?>

        <form class="mt-7" action="<?= url('login') ?>" method="post">
          <!-- Fields are placeholder-only per the minimal styling, so each
               carries an aria-label to keep an accessible name. -->
          <input type="email" name="email" required class="auth-input"
                 placeholder="Email" aria-label="Email">

          <input type="password" name="password" required class="auth-input mt-4"
                 placeholder="Password" aria-label="Password">

          <label class="auth-check mt-5">
            <input type="checkbox" name="remember" class="w-3.5 h-3.5 rounded accent-tag-amber">
            Keep me signed in
          </label>

          <button type="submit" class="auth-submit mt-6">Sign in</button>

          <div class="text-right mt-4">
            <button type="button" onclick="openForgotPasswordModal()" class="auth-forgot">Forgot your password?</button>
          </div>
        </form>
      </div>
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

  <!-- three.js is self-hosted rather than loaded from a CDN so the login
       page keeps working on an isolated network. -->
  <script type="importmap">
    {
      "imports": {
        "three": "<?= BASE_URL ?>/assets/vendor/three.module.min.js",
        "three/addons/utils/GeometryUtils.js": "<?= BASE_URL ?>/assets/vendor/GeometryUtils.js"
      }
    }
  </script>
  <script type="module" src="<?= BASE_URL ?>/scripts/login-3d.js"></script>

</body>
</html>
