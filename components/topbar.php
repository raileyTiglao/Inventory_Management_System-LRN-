<?php
  require_once __DIR__ . '/../auth/session.php';
  $user = logged_in_user() ?? [
      'name' => 'Unknown',
      'role' => 'User',
      'initials' => '??',
  ];
  $topbarCanViewInventory = has_permission('Inventory', 'view');
?>
<header class="h-16 border-b border-border flex items-center justify-between px-6 sticky top-0 bg-base/95 backdrop-blur z-10">
  <div>
    <p class="eyebrow"><?= e($pageEyebrow ?? 'IMS') ?></p>
    <h1 class="font-display font-semibold text-lg text-ink -mt-0.5"><?= e($pageTitle ?? '') ?></h1>
  </div>

  <div class="flex items-center gap-4">
    <div class="relative">
      <button type="button" id="notif-bell-btn" class="relative w-9 h-9 flex items-center justify-center rounded-tag border border-border hover:border-tag-amber/50 text-ink-muted hover:text-tag-amber transition-colors">
        <?= icon('bell', 'w-[18px] h-[18px]') ?>
        <span id="notif-dot" class="hidden absolute top-1.5 right-1.5 w-1.5 h-1.5 rounded-full bg-tag-amber"></span>
      </button>

      <div id="notif-panel" class="notif-panel hidden bin-tag">
        <div class="panel-header">
          <p class="eyebrow">Notifications</p>
        </div>
        <div id="notif-body" class="notif-body divide-y divide-border/60">
          <p class="px-5 py-6 text-center text-xs text-ink-dim">Loading…</p>
        </div>
      </div>
    </div>

    <div class="relative">
      <button type="button" id="user-menu-btn" class="flex items-center gap-2.5 pl-3 pr-3 py-1.5 -my-1.5 rounded-tag border-l border-border transition-colors hover:bg-overlay/10 hover:border-tag-amber/50" title="Account menu">
        <div id="topbar-user-initials" class="w-8 h-8 rounded-tag bg-tag-amber/15 border border-tag-amber/30 flex items-center justify-center font-mono text-xs text-tag-amber">
          <?= e($user['initials'] ?? '??') ?>
        </div>
        <div class="hidden sm:block leading-tight text-left">
          <p id="topbar-user-name" class="text-sm text-ink"><?= e($user['name'] ?? 'User') ?></p>
          <p class="text-[11px] text-ink-dim"><?= e($user['role'] ?? 'Role') ?></p>
        </div>
      </button>

      <div id="user-menu-panel" class="notif-panel hidden bin-tag">
        <div class="panel-header">
          <p class="eyebrow">Account</p>
        </div>
        <div class="py-1.5">
          <button type="button" id="user-menu-theme-btn" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-ink-muted hover:bg-overlay/10 hover:text-ink transition-colors">
            <span id="user-menu-theme-icon-light"><?= icon('moon', 'w-4 h-4') ?></span>
            <span id="user-menu-theme-icon-dark" class="hidden"><?= icon('sun', 'w-4 h-4') ?></span>
            <span id="user-menu-theme-label">Dark Mode</span>
          </button>
          <button type="button" id="user-menu-account-btn" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-ink-muted hover:bg-overlay/10 hover:text-ink transition-colors">
            <?= icon('users', 'w-4 h-4') ?>
            Edit Account
          </button>
          <div class="border-t border-border my-1.5"></div>
          <button type="button" id="user-menu-signout-btn" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-stock-out/80 hover:text-stock-out hover:bg-stock-out/10 transition-colors">
            <?= icon('logout', 'w-4 h-4') ?>
            Sign out
          </button>
        </div>
      </div>
    </div>
  </div>
</header>

<?php include __DIR__ . '/account-modal.php'; ?>

<script>
  window.IMS_TOPBAR_CONFIG = {
    baseUrl: <?= json_encode(BASE_URL) ?>,
    canViewInventory: <?= json_encode($topbarCanViewInventory) ?>,
  };
</script>
<script src="<?= BASE_URL ?>/scripts/topbar.js"></script>
