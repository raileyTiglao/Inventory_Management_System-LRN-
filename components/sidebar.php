<aside class="w-64 shrink-0 bg-base-deep border-r border-border flex flex-col h-screen sticky top-0">

  <div class="h-16 flex items-center gap-2.5 px-5 border-b border-border">
    <img src="<?= BASE_URL ?>/assets/icons/logo.svg" alt="" class="w-7 h-7">
    <div class="leading-tight">
      <p class="font-display font-semibold text-ink text-[15px]">IMS</p>
      <p class="eyebrow -mt-0.5">Inventory System</p>
    </div>
  </div>

  <nav class="flex-1 px-3 py-4 space-y-6 overflow-y-auto">
    <div>
      <p class="eyebrow px-3 mb-2">Overview</p>
      <div class="space-y-1">
        <a href="<?= BASE_URL ?>/pages/dashboard.php" class="sidebar-link <?= is_active('dashboard') ?>">
          <?= icon('grid', 'w-4.5 h-4.5') ?>
          <span>Dashboard</span>
        </a>
        <a href="<?= BASE_URL ?>/pages/inventory/index.php" class="sidebar-link <?= is_active('inventory') ?>">
          <?= icon('package', 'w-4.5 h-4.5') ?>
          <span>Inventory</span>
        </a>
      </div>
    </div>

    <div>
      <p class="eyebrow px-3 mb-2">Access Control</p>
      <div class="space-y-1">
        <a href="<?= BASE_URL ?>/pages/users/index.php" class="sidebar-link <?= is_active('users') ?>">
          <?= icon('users', 'w-4.5 h-4.5') ?>
          <span>Users</span>
        </a>
        <a href="<?= BASE_URL ?>/pages/roles/index.php" class="sidebar-link <?= is_active('roles') ?>">
          <?= icon('shield', 'w-4.5 h-4.5') ?>
          <span>Roles &amp; Permissions</span>
        </a>
      </div>
    </div>

    <div>
      <p class="eyebrow px-3 mb-2">System</p>
      <div class="space-y-1">
        <a href="<?= BASE_URL ?>/pages/settings.php" class="sidebar-link <?= is_active('settings') ?>">
          <?= icon('settings', 'w-4.5 h-4.5') ?>
          <span>Settings</span>
        </a>
      </div>
    </div>
  </nav>

  <div class="p-3 border-t border-border">
    <a href="<?= BASE_URL ?>/auth/logout.php" class="sidebar-link text-stock-out/80 hover:text-stock-out hover:bg-stock-out/10">
      <?= icon('logout', 'w-4.5 h-4.5') ?>
      <span>Sign out</span>
    </a>
  </div>
</aside>
