<?php
$sidebarLowStockCount = 0;
if (has_permission('Inventory', 'view')) {
    try {
        $sidebarLowStockCount = (int)(get_db()->query("
            SELECT COUNT(*) AS c FROM dbo.ims_inventory WHERE status = 'active' AND quantity_on_hand <= reorder_level
        ")->fetch()['c'] ?? 0);
    } catch (Throwable $e) {
        $sidebarLowStockCount = 0;
    }
}
?>
<aside id="sidebar" class="sidebar-shell w-64 shrink-0 bg-base-deep border-r border-border flex flex-col h-screen sticky top-0 overflow-hidden">

  <div class="h-16 flex items-center justify-between gap-2.5 px-5 border-b border-border">
    <div class="flex items-center gap-2.5 min-w-0">
      <img src="<?= BASE_URL ?>/assets/icons/logo.svg" alt="" class="w-7 h-7 shrink-0">
      <div class="leading-tight sidebar-label">
        <p class="font-display font-semibold text-ink text-[15px]">IMS</p>
        <p class="eyebrow -mt-0.5">Inventory System</p>
      </div>
    </div>
    <button type="button" id="sidebar-toggle" class="icon-btn-muted shrink-0" title="Collapse sidebar">
      <span id="sidebar-toggle-icon"><?= icon('chevron-left', 'w-4 h-4') ?></span>
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
          <a href="<?= BASE_URL ?>/pages/dashboard.php" class="sidebar-link <?= is_active('dashboard') ?>">
            <?= icon('grid', 'w-[18px] h-[18px] shrink-0') ?>
            <span class="sidebar-label">Dashboard</span>
          </a>
          <a href="<?= BASE_URL ?>/pages/inventory/index.php" class="sidebar-link <?= is_active('inventory') ?>">
            <?= icon('package', 'w-[18px] h-[18px] shrink-0') ?>
            <span class="sidebar-label flex-1">Inventory</span>
            <?php if ($sidebarLowStockCount > 0): ?>
              <span class="sidebar-badge sidebar-label" title="<?= $sidebarLowStockCount ?> item(s) at or below reorder level"><?= $sidebarLowStockCount ?></span>
            <?php endif; ?>
          </a>
          <a href="<?= BASE_URL ?>/pages/activity-log.php" class="sidebar-link <?= is_active('activity-log') ?>">
            <?= icon('clock', 'w-[18px] h-[18px] shrink-0') ?>
            <span class="sidebar-label">Activity Log</span>
          </a>
        </div>
      </div>

      <div data-nav-group>
        <p class="eyebrow px-3 mb-2 sidebar-label">Access Control</p>
        <div class="space-y-1">
          <a href="<?= BASE_URL ?>/pages/users/index.php" class="sidebar-link <?= is_active('users') ?>">
            <?= icon('users', 'w-[18px] h-[18px] shrink-0') ?>
            <span class="sidebar-label">Users</span>
          </a>
          <a href="<?= BASE_URL ?>/pages/roles/index.php" class="sidebar-link <?= is_active('roles') ?>">
            <?= icon('shield', 'w-[18px] h-[18px] shrink-0') ?>
            <span class="sidebar-label">Roles &amp; Permissions</span>
          </a>
        </div>
      </div>

      <div data-nav-group>
        <p class="eyebrow px-3 mb-2 sidebar-label">System</p>
        <div class="space-y-1">
          <a href="<?= BASE_URL ?>/pages/settings.php" class="sidebar-link <?= is_active('settings') ?>">
            <?= icon('settings', 'w-[18px] h-[18px] shrink-0') ?>
            <span class="sidebar-label">Settings</span>
          </a>
        </div>
      </div>

      <p id="sidebar-no-results" class="hidden px-3 text-xs text-ink-dim sidebar-label">No matches.</p>
    </div>

    <div class="sidebar-label sidebar-brand-footer">
      <img
        src="<?= BASE_URL ?>/assets/icons/logo.png"
        alt="Powered by Information Technology"
        class="sidebar-brand-logo"
        style="filter: invert(1);"
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
        <a href="<?= BASE_URL ?>/auth/logout.php" class="btn-danger">Sign out</a>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var STORAGE_KEY = 'ims-sidebar-collapsed';
  var sidebar = document.getElementById('sidebar');
  var toggle = document.getElementById('sidebar-toggle');

  if (localStorage.getItem(STORAGE_KEY) === '1') {
    sidebar.classList.add('sidebar-collapsed');
  }

  toggle.addEventListener('click', function () {
    var collapsed = sidebar.classList.toggle('sidebar-collapsed');
    localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
  });

  var searchInput = document.getElementById('sidebar-nav-search');
  var noResults = document.getElementById('sidebar-no-results');

  searchInput.addEventListener('input', function () {
    var q = searchInput.value.trim().toLowerCase();
    var anyGroupVisible = false;

    document.querySelectorAll('[data-nav-group]').forEach(function (group) {
      var groupHasMatch = false;
      group.querySelectorAll('.sidebar-link').forEach(function (link) {
        var match = !q || link.textContent.toLowerCase().indexOf(q) !== -1;
        link.classList.toggle('hidden', !match);
        if (match) groupHasMatch = true;
      });
      group.classList.toggle('hidden', !groupHasMatch);
      if (groupHasMatch) anyGroupVisible = true;
    });

    noResults.classList.toggle('hidden', anyGroupVisible || !q);
  });
})();

function openSignOutModal() {
  document.getElementById('signout-modal').classList.remove('hidden');
}
function closeSignOutModal() {
  document.getElementById('signout-modal').classList.add('hidden');
}
</script>
