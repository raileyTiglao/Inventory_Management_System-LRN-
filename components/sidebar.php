<?php
$sidebarCanViewInventory = has_permission('Inventory', 'view');
$sidebarLowStockCount = 0;
if ($sidebarCanViewInventory) {
    try {
        $sidebarLowStockCount = (int)(Connection::get_connecton()->query("
            SELECT COUNT(*) AS c FROM dbo.ims_inventory WHERE status = 'active' AND quantity_on_hand <= reorder_level
        ")->fetch()['c'] ?? 0);
    } catch (Throwable $e) {
        $sidebarLowStockCount = 0;
    }
}
?>
<aside id="sidebar" class="sidebar-shell w-64 shrink-0 bg-base-deep border-r border-border flex flex-col h-screen sticky top-0 overflow-hidden">

  <div class="sidebar-header h-16 flex items-center justify-between gap-2.5 px-5 border-b border-border">
    <div class="flex items-center gap-2.5 min-w-0 sidebar-label">
      <img src="<?= BASE_URL ?>/assets/icons/logo.svg" alt="" class="w-7 h-7 shrink-0">
      <div class="leading-tight">
        <p class="font-display font-semibold text-ink text-[15px]">IMS</p>
        <p class="eyebrow -mt-0.5">Inventory System</p>
      </div>
    </div>
    <button type="button" id="sidebar-toggle" class="icon-btn-muted shrink-0" title="Collapse sidebar">
      <span id="sidebar-toggle-icon-expanded"><?= icon('panel-left', 'w-4 h-4') ?></span>
      <span id="sidebar-toggle-icon-collapsed" class="hidden"><?= icon('panel-right', 'w-4 h-4') ?></span>
    </button>
  </div>

  <div class="sidebar-search px-3 pt-3">
    <div class="search-wrap w-full">
      <span class="search-icon"><?= icon('search', 'w-4 h-4') ?></span>
      <input type="text" id="sidebar-nav-search" placeholder="Search..." class="search-input w-full">
    </div>
  </div>

  <nav class="flex-1 flex flex-col px-3 py-4 overflow-y-auto overflow-x-hidden">
    <div class="space-y-6">
      <div data-nav-group>
        <p class="eyebrow px-3 mb-2 sidebar-label">Overview</p>
        <div class="space-y-1">
          <a href="<?= url('dashboard') ?>" class="sidebar-link <?= is_active('dashboard') ?>">
            <?= icon('grid', 'w-[18px] h-[18px] shrink-0') ?>
            <span class="sidebar-label">Dashboard</span>
          </a>
          <a href="<?= url('inventory') ?>" class="sidebar-link <?= is_active('inventory') ?>">
            <span class="relative shrink-0">
              <?= icon('package', 'w-[18px] h-[18px]') ?>
              <span id="sidebar-low-stock-dot" class="sidebar-collapsed-dot <?= $sidebarLowStockCount > 0 ? '' : 'hidden' ?>" title="<?= $sidebarLowStockCount ?> item(s) at or below reorder level"></span>
            </span>
            <span class="sidebar-label flex-1">Inventory</span>
            <span id="sidebar-low-stock-badge" class="sidebar-badge sidebar-label <?= $sidebarLowStockCount > 0 ? '' : 'hidden' ?>" title="<?= $sidebarLowStockCount ?> item(s) at or below reorder level"><?= $sidebarLowStockCount ?></span>
          </a>
          <a href="<?= url('activity-log') ?>" class="sidebar-link <?= is_active('activity-log') ?>">
            <?= icon('clock', 'w-[18px] h-[18px] shrink-0') ?>
            <span class="sidebar-label">Activity Log</span>
          </a>
        </div>
      </div>

      <div data-nav-group>
        <p class="eyebrow px-3 mb-2 sidebar-label">Access Control</p>
        <div class="space-y-1">
          <a href="<?= url('users') ?>" class="sidebar-link <?= is_active('users') ?>">
            <?= icon('users', 'w-[18px] h-[18px] shrink-0') ?>
            <span class="sidebar-label">Users</span>
          </a>
          <?php if (is_admin()): ?>
            <a href="<?= url('roles') ?>" class="sidebar-link <?= is_active('roles') ?>">
              <?= icon('shield', 'w-[18px] h-[18px] shrink-0') ?>
              <span class="sidebar-label">Roles &amp; Permissions</span>
            </a>
          <?php endif; ?>
        </div>
      </div>

      <p id="sidebar-no-results" class="hidden px-3 text-xs text-ink-dim sidebar-label">No matches.</p>
    </div>

    <div class="sidebar-label sidebar-brand-footer">
      <img
        src="<?= BASE_URL ?>/assets/icons/logo.png"
        alt="Powered by Information Technology"
        class="sidebar-brand-logo"
      >
    </div>
  </nav>

  <div class="p-3 border-t border-border">
    <button type="button" onclick="openSignOutModal()" class="sidebar-link text-stock-out/80 hover:text-stock-out hover:bg-stock-out/10 w-full text-left">
      <?= icon('logout', 'w-[18px] h-[18px] shrink-0') ?>
      <span class="sidebar-label">Sign out</span>
    </button>
  </div>
</aside>

<div id="signout-modal" class="hidden flex modal-overlay">
  <div class="bin-tag modal-panel-sm">
    <div class="panel-header">
      <div>
        <p class="eyebrow">Session</p>
        <h2 class="font-display font-semibold text-ink">Sign out?</h2>
      </div>
      <button type="button" onclick="closeSignOutModal()" class="icon-btn-muted">✕</button>
    </div>
    <div class="p-5 space-y-4">
      <p class="text-sm text-ink-muted">You'll need to sign in again to access the system.</p>
      <div class="modal-footer">
        <button type="button" onclick="closeSignOutModal()" class="btn-secondary">Cancel</button>
        <a href="<?= url('logout') ?>" class="btn-danger">Sign out</a>
      </div>
    </div>
  </div>
</div>

<script>
  window.IMS_SIDEBAR_CONFIG = {
    baseUrl: <?= json_encode(BASE_URL) ?>,
    canViewInventory: <?= json_encode($sidebarCanViewInventory) ?>,
  };
</script>
<script src="<?= BASE_URL ?>/scripts/sidebar.js"></script>

<?php include __DIR__ . '/toast.php'; ?>
