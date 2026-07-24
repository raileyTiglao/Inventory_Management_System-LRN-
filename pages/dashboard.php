<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth/rbac.php';

require_login();
require_permission('Dashboard', 'view');

$activePage = 'dashboard';
$pageEyebrow = 'Overview';
$pageTitle = 'Dashboard';

$db = get_db();

// ---- Stat cards: live counts from dbo.ims_inventory / dbo.ims_users ----
$invSummary = $db->query("
    SELECT COUNT(*) AS total_skus,
           SUM(quantity_on_hand * ISNULL(unit_cost, 0)) AS stock_value,
           SUM(CASE WHEN quantity_on_hand <= reorder_level THEN 1 ELSE 0 END) AS low_stock_count
    FROM dbo.ims_inventory
    WHERE status = 'active'
")->fetch();

$activeUsers = $db->query("SELECT COUNT(*) AS c FROM dbo.ims_users WHERE status = 'active'")->fetch();

$stats = [
    ['label' => 'Total SKUs', 'value' => number_format((int)($invSummary['total_skus'] ?? 0)), 'code' => 'INV-001', 'icon' => 'package'],
    ['label' => 'Stock Value', 'value' => '₱' . number_format((float)($invSummary['stock_value'] ?? 0), 2), 'code' => 'INV-002', 'icon' => 'box'],
    ['label' => 'Low Stock Items', 'value' => number_format((int)($invSummary['low_stock_count'] ?? 0)), 'code' => 'INV-003', 'icon' => 'alert'],
    ['label' => 'Active Users', 'value' => number_format((int)($activeUsers['c'] ?? 0)), 'code' => 'USR-004', 'icon' => 'users'],
];

// ---- Recent activity: live ledger from dbo.ims_stock_movements ----
$activityRows = $db->query('
    SELECT TOP 6 m.movement_type, m.quantity, m.created_at,
           i.sku_code, i.name AS item_name,
           u.first_name, u.last_name
    FROM dbo.ims_stock_movements m
    JOIN dbo.ims_inventory i ON m.sku_id = i.sku_id
    JOIN dbo.ims_users u ON m.user_id = u.user_id
    ORDER BY m.created_at DESC
')->fetchAll();

$activity = array_map(function ($row) {
    $isIn = $row['movement_type'] === 'in';
    return [
        'who' => trim($row['first_name'] . ' ' . $row['last_name']),
        'action' => $isIn ? 'Received stock' : 'Issued stock',
        'item' => $row['sku_code'] . ' · ' . $row['item_name'],
        'qty' => ($isIn ? '+' : '-') . $row['quantity'],
        'time' => date('M j, g:i A', strtotime($row['created_at'])),
        'status' => $isIn ? 'in' : 'out',
    ];
}, $activityRows);

// ---- Low stock alerts: live from dbo.ims_inventory ----
$lowStockRows = $db->query("
    SELECT TOP 5 sku_code, name, quantity_on_hand, reorder_level
    FROM dbo.ims_inventory
    WHERE status = 'active' AND quantity_on_hand <= reorder_level
    ORDER BY (quantity_on_hand - reorder_level) ASC
")->fetchAll();

$lowStock = array_map(fn($row) => [
    'sku' => $row['sku_code'],
    'name' => $row['name'],
    'left' => (int)$row['quantity_on_hand'],
    'reorder' => (int)$row['reorder_level'],
], $lowStockRows);
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

    <!-- Stat cards: signature "bin tag" treatment -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <?php foreach ($stats as $s): ?>
        <div class="bin-tag stat-card">
          <div class="flex items-start justify-between">
            <p class="stat-code"><?= e($s['code']) ?></p>
            <span class="text-ink-dim">
              <?= icon($s['icon'], 'w-3.5 h-3.5') ?>
            </span>
          </div>
          <p class="stat-value"><?= e($s['value']) ?></p>
          <p class="stat-label"><?= e($s['label']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <!-- Recent activity -->
      <div class="lg:col-span-2 bin-tag">
        <div class="panel-header">
          <div>
            <p class="eyebrow">Ledger</p>
            <h2 class="font-display font-semibold text-ink">Recent Activity</h2>
          </div>
          <a href="<?= BASE_URL ?>/pages/activity-log.php" class="text-xs text-tag-amber hover:underline">View full log</a>
        </div>
        <div class="divide-y divide-border/60">
          <?php if (empty($activity)): ?>
            <p class="px-5 py-8 text-center text-sm text-ink-dim">No stock movements recorded yet.</p>
          <?php endif; ?>
          <?php foreach ($activity as $a): ?>
            <div class="flex items-center justify-between px-5 py-3.5">
              <div class="flex items-center gap-3 min-w-0">
                <span class="w-2 h-2 rounded-full shrink-0 <?= $a['status'] === 'in' ? 'bg-stock-in' : 'bg-stock-out' ?>"></span>
                <div class="min-w-0">
                  <p class="text-sm text-ink truncate"><?= e($a['action']) ?> <span class="text-ink-dim">by</span> <?= e($a['who']) ?></p>
                  <p class="text-xs text-ink-muted font-mono truncate"><?= e($a['item']) ?></p>
                </div>
              </div>
              <div class="text-right shrink-0 pl-3">
                <p class="text-sm font-mono <?= $a['status'] === 'in' ? 'text-stock-in' : 'text-stock-out' ?>"><?= e($a['qty']) ?></p>
                <p class="text-[11px] text-ink-dim"><?= e($a['time']) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Low stock alerts -->
      <div class="bin-tag">
        <div class="flex items-center gap-2 px-5 py-4 border-b border-border">
          <?= icon('alert', 'w-4 h-4 text-tag-amber') ?>
          <div>
            <p class="eyebrow">Reorder Watch</p>
            <h2 class="font-display font-semibold text-ink">Low Stock</h2>
          </div>
        </div>
        <div class="divide-y divide-border/60">
          <?php if (empty($lowStock)): ?>
            <p class="px-5 py-8 text-center text-sm text-ink-dim">All stock levels are healthy.</p>
          <?php endif; ?>
          <?php foreach ($lowStock as $item): ?>
            <?php $pct = $item['reorder'] > 0 ? max(4, min(100, round(($item['left'] / $item['reorder']) * 100))) : 100; ?>
            <div class="px-5 py-3.5">
              <div class="flex items-center justify-between mb-1.5">
                <p class="text-sm text-ink"><?= e($item['name']) ?></p>
                <p class="font-mono text-xs text-tag-amber"><?= e($item['left']) ?> left</p>
              </div>
              <p class="font-mono text-[11px] text-ink-dim mb-2"><?= e($item['sku']) ?></p>
              <div class="h-1.5 bg-white/5 rounded-full overflow-hidden">
                <div class="h-full bg-tag-amber rounded-full" style="width: <?= $pct ?>%"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </main>
</div>

</body>
</html>
