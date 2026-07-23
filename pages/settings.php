<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth/rbac.php';

require_login();

$activePage = 'settings';
$pageEyebrow = 'System';
$pageTitle = 'Settings';

$user = logged_in_user();

$stmt = get_db()->prepare('SELECT first_name, last_name FROM dbo.ims_users WHERE user_id = ?');
$stmt->execute([(int)$user['id']]);
$profile = $stmt->fetch();
$firstName = $profile['first_name'] ?? '';
$lastName = $profile['last_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../components/head.php'; ?>
</head>
<body class="flex min-h-screen">

<?php include __DIR__ . '/../components/sidebar.php'; ?>

<div class="flex-1 flex flex-col min-w-0">
  <?php include __DIR__ . '/../components/topbar.php'; ?>

  <main class="flex-1 p-6 space-y-6">

    <p class="text-sm text-ink-muted">Manage your own account details. These changes apply only to you.</p>

    <div class="bin-tag max-w-xl">
      <div class="panel-header">
        <div>
          <p class="eyebrow">Account</p>
          <h2 class="font-display font-semibold text-ink"><?= e($user['email'] ?? '') ?></h2>
        </div>
        <span class="badge-muted"><?= e($user['role'] ?? '') ?></span>
      </div>

      <form id="account-form" class="p-5 space-y-5">
        <p id="account-form-error" class="hidden text-xs text-stock-out bg-stock-out/10 border border-stock-out/30 rounded-tag px-3 py-2"></p>
        <p id="account-form-success" class="hidden text-xs text-stock-in bg-stock-in/10 border border-stock-in/30 rounded-tag px-3 py-2"></p>

        <div>
          <p class="eyebrow mb-3">Profile</p>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="field-label">First name</label>
              <input type="text" id="account-first_name" class="field-input" value="<?= e($firstName) ?>" required>
            </div>
            <div>
              <label class="field-label">Last name</label>
              <input type="text" id="account-last_name" class="field-input" value="<?= e($lastName) ?>" required>
            </div>
          </div>
        </div>

        <div class="pt-2 border-t border-border">
          <p class="eyebrow mb-3 mt-4">Change password</p>
          <p class="text-[11px] text-ink-dim mb-3">Leave blank to keep your current password.</p>
          <div class="space-y-4">
            <div>
              <label class="field-label">Current password</label>
              <input type="password" id="account-current_password" class="field-input" placeholder="••••••••">
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="field-label">New password</label>
                <input type="password" id="account-new_password" class="field-input" placeholder="At least 8 characters">
              </div>
              <div>
                <label class="field-label">Confirm new password</label>
                <input type="password" id="account-confirm_password" class="field-input" placeholder="Repeat new password">
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end pt-2">
          <button type="submit" id="account-form-submit" class="btn-primary">Save changes</button>
        </div>
      </form>
    </div>
  </main>
</div>

<script>
  const BASE_URL = <?= json_encode(BASE_URL) ?>;

  document.getElementById('account-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errorEl = document.getElementById('account-form-error');
    const successEl = document.getElementById('account-form-success');
    errorEl.classList.add('hidden');
    successEl.classList.add('hidden');

    const newPassword = document.getElementById('account-new_password').value;
    const confirmPassword = document.getElementById('account-confirm_password').value;

    if (newPassword && newPassword !== confirmPassword) {
      errorEl.textContent = 'New password and confirmation do not match.';
      errorEl.classList.remove('hidden');
      return;
    }

    const payload = {
      first_name: document.getElementById('account-first_name').value.trim(),
      last_name: document.getElementById('account-last_name').value.trim(),
    };
    if (newPassword) {
      payload.current_password = document.getElementById('account-current_password').value;
      payload.new_password = newPassword;
    }

    const submitBtn = document.getElementById('account-form-submit');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving…';

    try {
      const res = await fetch(`${BASE_URL}/api/account.php`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const json = await res.json();

      if (!json.success) {
        errorEl.textContent = json.message || 'Could not save changes.';
        errorEl.classList.remove('hidden');
        return;
      }

      document.getElementById('account-current_password').value = '';
      document.getElementById('account-new_password').value = '';
      document.getElementById('account-confirm_password').value = '';

      const initialsEl = document.getElementById('topbar-user-initials');
      const nameEl = document.getElementById('topbar-user-name');
      if (initialsEl) initialsEl.textContent = json.data.initials;
      if (nameEl) nameEl.textContent = json.data.name;

      successEl.textContent = 'Account updated successfully.';
      successEl.classList.remove('hidden');
    } catch (err) {
      errorEl.textContent = 'Could not reach the server. Please try again.';
      errorEl.classList.remove('hidden');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Save changes';
    }
  });
</script>

</body>
</html>
