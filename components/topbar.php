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
    <div class="search-wrap hidden md:block w-56">
      <span class="search-icon">
        <?= icon('search', 'w-4 h-4') ?>
      </span>
      <input type="text" placeholder="Search..." class="search-input w-full">
    </div>

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

    <div class="flex items-center gap-2.5 pl-3 border-l border-border">
      <div id="topbar-user-initials" class="w-8 h-8 rounded-tag bg-tag-amber/15 border border-tag-amber/30 flex items-center justify-center font-mono text-xs text-tag-amber">
        <?= e($user['initials'] ?? '??') ?>
      </div>
      <div class="hidden sm:block leading-tight">
        <p id="topbar-user-name" class="text-sm text-ink"><?= e($user['name'] ?? 'User') ?></p>
        <p class="text-[11px] text-ink-dim"><?= e($user['role'] ?? 'Role') ?></p>
      </div>
    </div>
  </div>
</header>

<script>
(function () {
  var BASE_URL = <?= json_encode(BASE_URL) ?>;
  var CAN_VIEW_INVENTORY = <?= json_encode($topbarCanViewInventory) ?>;

  var btn = document.getElementById('notif-bell-btn');
  var panel = document.getElementById('notif-panel');
  var body = document.getElementById('notif-body');
  var dot = document.getElementById('notif-dot');
  var loaded = false;

  function esc(value) {
    return String(value ?? '').replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function timeAgo(value) {
    var d = new Date(value.replace(' ', 'T') + 'Z');
    var mins = Math.max(1, Math.round((Date.now() - d.getTime()) / 60000));
    if (mins < 60) return mins + 'm ago';
    var hours = Math.round(mins / 60);
    if (hours < 24) return hours + 'h ago';
    return Math.round(hours / 24) + 'd ago';
  }

  function section(title, rows) {
    return '<p class="eyebrow px-5 pt-3 pb-2">' + title + '</p>' + rows;
  }

  function lowStockRow(item) {
    return '' +
      '<div class="px-5 py-3 flex items-center justify-between gap-3">' +
        '<div class="min-w-0">' +
          '<p class="text-sm text-ink truncate">' + esc(item.name) + '</p>' +
          '<p class="font-mono text-[11px] text-ink-dim">' + esc(item.sku_code) + '</p>' +
        '</div>' +
        '<span class="font-mono text-xs text-tag-amber shrink-0">' + esc(item.quantity_on_hand) + ' left</span>' +
      '</div>';
  }

  function activityRow(m) {
    var isIn = m.movement_type === 'in';
    return '' +
      '<div class="px-5 py-3 flex items-center justify-between gap-3">' +
        '<div class="flex items-center gap-2.5 min-w-0">' +
          '<span class="w-1.5 h-1.5 rounded-full shrink-0 ' + (isIn ? 'bg-stock-in' : 'bg-stock-out') + '"></span>' +
          '<div class="min-w-0">' +
            '<p class="text-xs text-ink truncate">' + (isIn ? 'Received' : 'Issued') + ' <span class="text-ink-dim">&middot;</span> ' + esc(m.item_name) + '</p>' +
            '<p class="text-[11px] text-ink-dim">' + timeAgo(m.created_at) + '</p>' +
          '</div>' +
        '</div>' +
        '<span class="font-mono text-xs shrink-0 ' + (isIn ? 'text-stock-in' : 'text-stock-out') + '">' + (isIn ? '+' : '-') + esc(m.quantity) + '</span>' +
      '</div>';
  }

  async function loadNotifications() {
    if (!CAN_VIEW_INVENTORY) {
      body.innerHTML = '<p class="px-5 py-6 text-center text-xs text-ink-dim">No notifications.</p>';
      return;
    }

    try {
      var [lowStockRes, activityRes] = await Promise.all([
        fetch(BASE_URL + '/api/inventory.php?status=active&low_stock=1&limit=5'),
        fetch(BASE_URL + '/api/stock_movements.php?limit=5'),
      ]);
      var lowStockJson = await lowStockRes.json();
      var activityJson = await activityRes.json();

      var lowStockItems = (lowStockJson.success && lowStockJson.data.items) || [];
      var movements = (activityJson.success && activityJson.data.movements) || [];

      dot.classList.toggle('hidden', lowStockItems.length === 0);

      var html = '';
      if (lowStockItems.length > 0) {
        html += section('Low Stock', lowStockItems.map(lowStockRow).join(''));
      }
      if (movements.length > 0) {
        html += section('Recent Activity', movements.map(activityRow).join(''));
      }
      body.innerHTML = html || '<p class="px-5 py-6 text-center text-xs text-ink-dim">No notifications.</p>';
    } catch (err) {
      body.innerHTML = '<p class="px-5 py-6 text-center text-xs text-stock-out">Could not load notifications.</p>';
    }
  }

  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    var opening = panel.classList.contains('hidden');
    panel.classList.toggle('hidden', !opening);
    if (opening && !loaded) {
      loaded = true;
      loadNotifications();
    }
  });

  document.addEventListener('click', function (e) {
    if (!panel.classList.contains('hidden') && !panel.contains(e.target) && e.target !== btn) {
      panel.classList.add('hidden');
    }
  });
})();
</script>
