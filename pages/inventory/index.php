<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../auth/rbac.php';

ensure_routed('inventory');
require_login();
require_permission('Inventory', 'view');

$activePage = 'inventory';
$pageEyebrow = 'Overview';
$pageTitle = 'Inventory';

$canCreate = has_permission('Inventory', 'create');
$canEdit = has_permission('Inventory', 'edit');
$canDelete = has_permission('Inventory', 'delete');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../../components/head.php'; ?>
</head>
<body class="flex min-h-screen">

<?php include __DIR__ . '/../../components/sidebar.php'; ?>

<div class="flex-1 flex flex-col min-w-0">
  <?php include __DIR__ . '/../../components/topbar.php'; ?>

  <main class="flex-1 p-6 space-y-6">

    <div class="flex items-center justify-between">
      <p class="text-sm text-ink-muted">Track SKUs, stock levels, and reorder thresholds across the warehouse.</p>
      <?php if ($canCreate): ?>
        <button onclick="openItemModal()" class="btn-primary">
          <?= icon('plus', 'w-4 h-4') ?>
          Add item
        </button>
      <?php endif; ?>
    </div>

    <!-- Stat cards -->
    <div id="inventory-stats" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <div class="bin-tag stat-card">
        <p class="stat-code">INV-001</p>
        <p class="stat-value" id="stat-total-skus">—</p>
        <p class="stat-label">Total SKUs</p>
      </div>
      <div class="bin-tag stat-card">
        <p class="stat-code">INV-002</p>
        <p class="stat-value" id="stat-stock-value">—</p>
        <p class="stat-label">Stock Value</p>
      </div>
      <div class="bin-tag stat-card">
        <p class="stat-code">INV-003</p>
        <p class="stat-value" id="stat-units">—</p>
        <p class="stat-label">Units on Hand</p>
      </div>
      <div class="bin-tag stat-card">
        <p class="stat-code">INV-004</p>
        <p class="stat-value text-tag-amber" id="stat-low-stock">—</p>
        <p class="stat-label">Low Stock Items</p>
      </div>
    </div>

    <div class="bin-tag">
      <div class="panel-header">
        <div class="flex items-center gap-4">
          <p class="eyebrow-table">INVENTORY</p>
          <div class="flex items-center gap-1.5 p-1 bg-base-deep border border-border rounded-tag">
            <button type="button" id="filter-active" onclick="setStatusFilter('active')" class="px-3 py-1.5 rounded-tag text-xs font-mono uppercase tracking-wide transition-colors">Active</button>
            <button type="button" id="filter-archived" onclick="setStatusFilter('archived')" class="px-3 py-1.5 rounded-tag text-xs font-mono uppercase tracking-wide transition-colors">Archived</button>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <div class="search-wrap w-full lg:w-64">
            <span class="search-icon"><?= icon('search', 'w-4 h-4') ?></span>
            <input type="text" id="inventory-search" placeholder="Search SKU or name..." class="search-input w-full">
          </div>
          <div class="relative">
            <button type="button" id="inventory-sort-btn" class="icon-btn-muted relative" title="Sort / filter">
              <?= icon('filter', 'w-4 h-4') ?>
              <span id="inventory-sort-dot" class="hidden absolute top-1 right-1 w-1.5 h-1.5 rounded-full bg-tag-amber"></span>
            </button>
            <div id="inventory-sort-menu" class="hidden absolute right-0 mt-2 z-10 bin-tag w-44 py-1">
              <button type="button" data-sort="" class="sort-option w-full text-left px-3 py-2 text-xs text-ink-muted hover:bg-overlay/10 hover:text-ink transition-colors">Default</button>
              <button type="button" data-sort="qty_desc" class="sort-option w-full text-left px-3 py-2 text-xs text-ink-muted hover:bg-overlay/10 hover:text-ink transition-colors">Most qty</button>
              <button type="button" data-sort="qty_asc" class="sort-option w-full text-left px-3 py-2 text-xs text-ink-muted hover:bg-overlay/10 hover:text-ink transition-colors">Least qty</button>
              <button type="button" data-sort="low_stock" class="sort-option w-full text-left px-3 py-2 text-xs text-ink-muted hover:bg-overlay/10 hover:text-ink transition-colors">Low stock</button>
            </div>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr>
              <th class="text-left">SKU</th>
              <th class="text-left">NAME</th>
              <th class="text-right">QUANTITY</th>
              <th class="text-right">REORDER LEVEL</th>
              <th class="text-right">UNIT COST</th>
              <th class="text-left">STATUS</th>
              <th class="text-right">ACTIONS</th>
            </tr>
          </thead>
          <tbody id="inventory-tbody">
            <tr><td colspan="7" class="text-center text-ink-dim py-8">Loading inventory…</td></tr>
          </tbody>
        </table>
      </div>

      <div class="panel-footer">
        <div class="flex items-center gap-3">
          <p class="text-xs text-ink-dim" id="inventory-count">Showing 0 of 0 items</p>
          <div class="flex items-center gap-1.5">
            <label for="inventory-page-size" class="text-xs text-ink-dim">Rows:</label>
            <select id="inventory-page-size" onchange="changePageSize(this.value)" class="field-input !w-auto !py-1 !px-2 text-xs">
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
        </div>
        <div class="flex items-center gap-1.5">
          <button id="inventory-prev" onclick="changePage(-1)" class="pagination-btn" disabled>‹</button>
          <span class="pagination-page" id="inventory-page">1</span>
          <button id="inventory-next" onclick="changePage(1)" class="pagination-btn" disabled>›</button>
        </div>
      </div>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../../components/inventory-modal.php'; ?>
<?php include __DIR__ . '/../../components/stock-movement-modal.php'; ?>
<?php include __DIR__ . '/../../components/archive-modal.php'; ?>

<script>
  const BASE_URL = <?= json_encode(BASE_URL) ?>;
  const CAN_EDIT = <?= json_encode($canEdit) ?>;
  const CAN_DELETE = <?= json_encode($canDelete) ?>;

  const state = { limit: 10, offset: 0, total: 0, items: [], statusFilter: 'active', sort: '', stats: { total_units: 0, total_value: 0, low_stock_count: 0 } };

  function esc(value) {
    return String(value ?? '').replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  function formatMoney(value) {
    const n = Number(value ?? 0);
    return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  async function loadInventory() {
    const tbody = document.getElementById('inventory-tbody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-ink-dim py-8">Loading inventory…</td></tr>';

    try {
      const res = await fetch(`${BASE_URL}/api/inventory.php?limit=${state.limit}&offset=${state.offset}&status=${state.statusFilter}&sort=${encodeURIComponent(state.sort)}`);
      const json = await res.json();

      if (!json.success) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-stock-out py-8">${esc(json.message || 'Failed to load inventory')}</td></tr>`;
        return;
      }

      // SQL Server (via PDO_SQLSRV) returns integer columns as PHP strings,
      // which json_encode preserves as JSON strings — normalize here so
      // strict-equality lookups (e.g. openItemModal's state.items.find)
      // against numeric ids from onclick="...(${item.sku_id})" actually match.
      state.items = (json.data.items || []).map(item => ({
        ...item,
        sku_id: Number(item.sku_id),
      }));
      state.total = json.data.total || 0;
      state.stats = json.data.stats || { total_units: 0, total_value: 0, low_stock_count: 0 };

      renderTable();
      renderStats();
      renderPagination();
    } catch (err) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center text-stock-out py-8">Could not reach the server. Please try again.</td></tr>';
    }
  }

  function renderTable() {
    const tbody = document.getElementById('inventory-tbody');
    const q = (document.getElementById('inventory-search').value || '').toLowerCase();

    const rows = state.items.filter(item =>
      !q || item.sku_code.toLowerCase().includes(q) || item.name.toLowerCase().includes(q)
    );

    if (rows.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center text-ink-dim py-8">No inventory items found.</td></tr>';
      return;
    }

    tbody.innerHTML = rows.map(item => {
      const archived = item.status === 'archived';
      const low = Number(item.quantity_on_hand) <= Number(item.reorder_level);
      let statusBadge;
      if (archived) {
        statusBadge = '<span class="badge-muted">Archived</span>';
      } else {
        statusBadge = low
          ? '<span class="badge-red">Low stock</span>'
          : '<span class="badge-green">In stock</span>';
      }

      const actions = [];
      if (!archived) {
        if (CAN_EDIT) {
          actions.push(`<button onclick="openMoveModal(${item.sku_id}, 'in')" class="icon-btn-success" title="Receive stock">${iconStockIn()}</button>`);
          actions.push(`<button onclick="openMoveModal(${item.sku_id}, 'out')" class="icon-btn-danger" title="Issue stock">${iconStockOut()}</button>`);
          actions.push(`<button onclick="openItemModal(${item.sku_id})" class="icon-btn-amber" title="Edit details">${iconEdit()}</button>`);
        }
        if (CAN_DELETE) {
          actions.push(`<button onclick="archiveItem(${item.sku_id}, '${esc(item.sku_code)}')" class="icon-btn-danger" title="Archive">${iconArchive()}</button>`);
        }
      } else {
        if (CAN_EDIT) {
          actions.push(`<button onclick="openItemModal(${item.sku_id})" class="icon-btn-amber" title="Edit details">${iconEdit()}</button>`);
        }
        if (CAN_DELETE) {
          actions.push(`<button onclick="restoreItem(${item.sku_id}, '${esc(item.sku_code)}')" class="icon-btn-success" title="Restore">${iconRestore()}</button>`);
        }
      }

      return `
        <tr>
          <td class="font-mono text-xs">${esc(item.sku_code)}</td>
          <td class="text-ink font-medium">${esc(item.name)}</td>
          <td class="text-right font-mono text-xs">${esc(item.quantity_on_hand)}</td>
          <td class="text-right font-mono text-xs text-ink-dim">${esc(item.reorder_level)}</td>
          <td class="text-right font-mono text-xs">${formatMoney(item.unit_cost)}</td>
          <td>${statusBadge}</td>
          <td>
            <div class="flex items-center justify-end gap-1.5">${actions.join('')}</div>
          </td>
        </tr>`;
    }).join('');
  }

  function renderStats() {
    document.getElementById('stat-total-skus').textContent = state.total.toLocaleString();
    document.getElementById('stat-stock-value').textContent = formatMoney(state.stats.total_value);
    document.getElementById('stat-units').textContent = state.stats.total_units.toLocaleString();
    document.getElementById('stat-low-stock').textContent = state.stats.low_stock_count.toLocaleString();
  }

  function renderPagination() {
    const shown = state.items.length;
    const start = shown === 0 ? 0 : state.offset + 1;
    const end = state.offset + shown;
    document.getElementById('inventory-count').textContent = `Showing ${start}-${end} of ${state.total} items`;
    document.getElementById('inventory-page').textContent = Math.floor(state.offset / state.limit) + 1;
    document.getElementById('inventory-prev').disabled = state.offset <= 0;
    document.getElementById('inventory-next').disabled = state.offset + state.limit >= state.total;
  }

  function changePage(direction) {
    const next = state.offset + direction * state.limit;
    if (next < 0 || next >= state.total) return;
    state.offset = next;
    loadInventory();
  }

  function changePageSize(value) {
    state.limit = Number(value);
    state.offset = 0;
    loadInventory();
  }

  function setStatusFilter(status) {
    state.statusFilter = status;
    state.offset = 0;

    const activeBtn = document.getElementById('filter-active');
    const archivedBtn = document.getElementById('filter-archived');
    activeBtn.className = 'px-3 py-1.5 rounded-tag text-xs font-mono uppercase tracking-wide transition-colors ' +
      (status === 'active' ? 'bg-tag-amber/10 text-tag-amber border border-tag-amber/30' : 'text-ink-dim hover:bg-overlay/10 hover:text-ink-muted');
    archivedBtn.className = 'px-3 py-1.5 rounded-tag text-xs font-mono uppercase tracking-wide transition-colors ' +
      (status === 'archived' ? 'bg-tag-amber/10 text-tag-amber border border-tag-amber/30' : 'text-ink-dim hover:bg-overlay/10 hover:text-ink-muted');

    loadInventory();
  }

  const SORT_LABELS = { '': 'Default', qty_desc: 'Most qty', qty_asc: 'Least qty', low_stock: 'Low stock' };

  function setSort(sort) {
    state.sort = sort;
    state.offset = 0;

    document.querySelectorAll('.sort-option').forEach(function (btn) {
      const active = btn.dataset.sort === sort;
      btn.classList.toggle('text-tag-amber', active);
      btn.classList.toggle('text-ink-muted', !active);
    });

    const dot = document.getElementById('inventory-sort-dot');
    dot.classList.toggle('hidden', sort === '');
    document.getElementById('inventory-sort-btn').title = 'Sort / filter: ' + SORT_LABELS[sort];

    document.getElementById('inventory-sort-menu').classList.add('hidden');
    loadInventory();
  }

  (function () {
    const btn = document.getElementById('inventory-sort-btn');
    const menu = document.getElementById('inventory-sort-menu');

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      menu.classList.toggle('hidden');
    });

    menu.querySelectorAll('.sort-option').forEach(function (option) {
      option.addEventListener('click', function () {
        setSort(option.dataset.sort);
      });
    });

    document.addEventListener('click', function (e) {
      if (!menu.classList.contains('hidden') && !menu.contains(e.target) && e.target !== btn) {
        menu.classList.add('hidden');
      }
    });
  })();

  function iconEdit() {
    return '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>';
  }
  function iconTrash() {
    return '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>';
  }
  function iconArchive() {
    return '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="4" rx="1"/><path d="M4 8v11a1 1 0 001 1h14a1 1 0 001-1V8"/><path d="M10 13h4"/></svg>';
  }
  function iconRestore() {
    return '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 109-9 9.75 9.75 0 00-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>';
  }
  function iconStockIn() {
    return '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>';
  }
  function iconStockOut() {
    return '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg>';
  }

  // ---- Modal handling ----

  function openItemModal(id) {
    const form = document.getElementById('inventory-form');
    form.reset();
    document.getElementById('item-id').value = '';
    document.getElementById('inventory-form-error').classList.add('hidden');

    const qtyInput = document.getElementById('item-quantity_on_hand');
    const skuField = document.getElementById('item-sku-field');
    const qtyField = document.getElementById('item-qty-field');
    const gridTop = document.getElementById('item-grid-top');
    const gridBottom = document.getElementById('item-grid-bottom');
    const summary = document.getElementById('item-readonly-summary');

    if (id) {
      const item = state.items.find(i => i.sku_id === id);
      document.getElementById('inventory-modal-title').textContent = 'Edit Item · ' + (item ? item.sku_code : id);
      document.getElementById('item-id').value = id;
      if (item) {
        document.getElementById('item-sku_code').value = item.sku_code;
        document.getElementById('item-name').value = item.name;
        document.getElementById('item-description').value = item.description || '';
        qtyInput.value = item.quantity_on_hand;
        document.getElementById('item-reorder_level').value = item.reorder_level;
        document.getElementById('item-unit_cost').value = item.unit_cost ?? '';
        document.getElementById('item-sku_code-display').textContent = item.sku_code;
      }

      skuField.classList.add('hidden');
      qtyField.classList.add('hidden');
      gridTop.classList.replace('grid-cols-2', 'grid-cols-1');
      gridBottom.classList.replace('grid-cols-3', 'grid-cols-2');
      summary.classList.remove('hidden');
    } else {
      document.getElementById('inventory-modal-title').textContent = 'Add Item';
      skuField.classList.remove('hidden');
      qtyField.classList.remove('hidden');
      gridTop.classList.replace('grid-cols-1', 'grid-cols-2');
      gridBottom.classList.replace('grid-cols-2', 'grid-cols-3');
      summary.classList.add('hidden');
    }

    document.getElementById('inventory-modal').classList.remove('hidden');
  }

  function closeItemModal() {
    document.getElementById('inventory-modal').classList.add('hidden');
  }

  document.getElementById('inventory-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errorEl = document.getElementById('inventory-form-error');
    errorEl.classList.add('hidden');

    const id = document.getElementById('item-id').value;
    const payload = {
      sku_code: document.getElementById('item-sku_code').value.trim(),
      name: document.getElementById('item-name').value.trim(),
      description: document.getElementById('item-description').value.trim(),
      reorder_level: document.getElementById('item-reorder_level').value,
      unit_cost: document.getElementById('item-unit_cost').value,
    };
    if (!id) {
      // Initial stock is only settable when creating a new item — after
      // that, quantity can only change through Receive/Issue.
      payload.quantity_on_hand = document.getElementById('item-quantity_on_hand').value;
    }

    const submitBtn = document.getElementById('inventory-form-submit');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving…';

    try {
      let res;
      if (id) {
        payload.id = Number(id);
        res = await fetch(`${BASE_URL}/api/inventory.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-HTTP-Method-Override': 'PUT' },
          body: JSON.stringify(payload),
        });
      } else {
        res = await fetch(`${BASE_URL}/api/inventory.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
      }

      const json = await res.json();
      if (!json.success) {
        errorEl.textContent = json.message || 'Could not save item.';
        errorEl.classList.remove('hidden');
        return;
      }

      closeItemModal();
      showToast(id ? 'Item updated successfully.' : 'Item created successfully.', 'success');
      await loadInventory();
      if (typeof refreshSidebarLowStock === 'function') refreshSidebarLowStock();
    } catch (err) {
      errorEl.textContent = 'Could not reach the server. Please try again.';
      errorEl.classList.remove('hidden');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Save item';
    }
  });

  async function setItemStatus(id, status) {
    try {
      const res = await fetch(`${BASE_URL}/api/inventory.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-HTTP-Method-Override': 'PUT' },
        body: JSON.stringify({ id, status }),
      });
      const json = await res.json();
      if (!json.success) {
        showToast(json.message || 'Could not update item.', 'error');
        return;
      }
      showToast(status === 'archived' ? 'Item archived.' : 'Item restored.', 'success');
      await loadInventory();
      if (typeof refreshSidebarLowStock === 'function') refreshSidebarLowStock();
    } catch (err) {
      showToast('Could not reach the server. Please try again.', 'error');
    }
  }

  let pendingArchiveId = null;

  function archiveItem(id, skuCode) {
    pendingArchiveId = id;
    document.getElementById('archive-modal-sku').textContent = skuCode;
    document.getElementById('archive-modal').classList.remove('hidden');
  }

  function closeArchiveModal() {
    document.getElementById('archive-modal').classList.add('hidden');
    pendingArchiveId = null;
  }

  function confirmArchive() {
    const id = pendingArchiveId;
    closeArchiveModal();
    if (id != null) setItemStatus(id, 'archived');
  }

  function restoreItem(id, skuCode) {
    return setItemStatus(id, 'active');
  }

  document.getElementById('inventory-search').addEventListener('input', renderTable);

  // ---- Stock movement (Receive / Issue) ----

  function setMoveType(type) {
    document.getElementById('move-type').value = type;

    const badge = document.getElementById('move-type-badge');
    badge.className = 'flex items-center gap-2 px-3 py-2.5 rounded-tag border text-xs font-mono uppercase tracking-wide ' +
      (type === 'in' ? 'bg-stock-in/15 text-stock-in border-stock-in/30' : 'bg-stock-out/15 text-stock-out border-stock-out/30');
    badge.innerHTML = (type === 'in' ? iconStockIn() : iconStockOut()) + '<span>' + (type === 'in' ? 'Receiving stock' : 'Issuing stock') + '</span>';

    const submitBtn = document.getElementById('movement-form-submit');
    submitBtn.textContent = type === 'in' ? 'Record receipt' : 'Record issue';
  }

  function openMoveModal(id, type) {
    const item = state.items.find(i => i.sku_id === id);
    document.getElementById('movement-form').reset();
    document.getElementById('movement-form-error').classList.add('hidden');
    document.getElementById('move-sku_id').value = id;
    document.getElementById('movement-modal-title').textContent =
      (type === 'in' ? 'Receive Stock · ' : 'Issue Stock · ') + (item ? item.sku_code : id);
    document.getElementById('movement-modal-eyebrow').textContent = item ? item.name : 'Stock Movement';
    setMoveType(type);
    document.getElementById('movement-modal').classList.remove('hidden');
  }

  function closeMoveModal() {
    document.getElementById('movement-modal').classList.add('hidden');
  }

  document.getElementById('movement-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errorEl = document.getElementById('movement-form-error');
    errorEl.classList.add('hidden');

    const payload = {
      sku_id: Number(document.getElementById('move-sku_id').value),
      movement_type: document.getElementById('move-type').value,
      quantity: document.getElementById('move-quantity').value,
      reference_code: document.getElementById('move-reference_code').value.trim(),
      notes: document.getElementById('move-notes').value.trim(),
    };

    const submitBtn = document.getElementById('movement-form-submit');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving…';

    try {
      const res = await fetch(`${BASE_URL}/api/stock_movements.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const json = await res.json();

      if (!json.success) {
        errorEl.textContent = json.message || 'Could not record movement.';
        errorEl.classList.remove('hidden');
        return;
      }

      closeMoveModal();
      showToast(payload.movement_type === 'in' ? 'Stock received successfully.' : 'Stock issued successfully.', 'success');
      await loadInventory();
      if (typeof refreshSidebarLowStock === 'function') refreshSidebarLowStock();
    } catch (err) {
      errorEl.textContent = 'Could not reach the server. Please try again.';
      errorEl.classList.remove('hidden');
    } finally {
      submitBtn.disabled = false;
      setMoveType(document.getElementById('move-type').value);
    }
  });

  setStatusFilter('active');
</script>

</body>
</html>
