<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../auth/rbac.php';

require_login();
require_permission('Inventory', 'view');

$activePage = 'activity-log';
$pageEyebrow = 'Overview';
$pageTitle = 'Activity Log';
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
      <p class="text-sm text-ink-muted">Full ledger of stock received and issued across the warehouse.</p>
    </div>

    <div class="bin-tag">
      <div class="panel-header">
        <div class="flex items-center gap-4">
          <p class="eyebrow-table">STOCK MOVEMENTS</p>
          <div class="flex items-center gap-1.5 p-1 bg-base-deep border border-border rounded-tag">
            <button type="button" id="filter-all" onclick="setTypeFilter('')" class="px-3 py-1.5 rounded-tag text-xs font-mono uppercase tracking-wide transition-colors">All</button>
            <button type="button" id="filter-in" onclick="setTypeFilter('in')" class="px-3 py-1.5 rounded-tag text-xs font-mono uppercase tracking-wide transition-colors">Received</button>
            <button type="button" id="filter-out" onclick="setTypeFilter('out')" class="px-3 py-1.5 rounded-tag text-xs font-mono uppercase tracking-wide transition-colors">Issued</button>
          </div>
        </div>
        <div class="search-wrap w-full lg:w-64">
          <span class="search-icon"><?= icon('search', 'w-4 h-4') ?></span>
          <input type="text" id="log-search" placeholder="Search SKU, item, or user..." class="search-input w-full">
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr>
              <th class="text-left">DATE</th>
              <th class="text-left">ITEM</th>
              <th class="text-left">TYPE</th>
              <th class="text-right">QUANTITY</th>
              <th class="text-left">USER</th>
              <th class="text-left">REFERENCE</th>
            </tr>
          </thead>
          <tbody id="log-tbody">
            <tr><td colspan="6" class="text-center text-ink-dim py-8">Loading activity…</td></tr>
          </tbody>
        </table>
      </div>

      <div class="panel-footer">
        <div class="flex items-center gap-3">
          <p class="text-xs text-ink-dim" id="log-count">Showing 0 of 0 entries</p>
          <div class="flex items-center gap-1.5">
            <label for="log-page-size" class="text-xs text-ink-dim">Rows:</label>
            <select id="log-page-size" onchange="changePageSize(this.value)" class="field-input !w-auto !py-1 !px-2 text-xs">
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
        </div>
        <div class="flex items-center gap-1.5">
          <button id="log-prev" onclick="changePage(-1)" class="pagination-btn" disabled>‹</button>
          <span class="pagination-page" id="log-page">1</span>
          <button id="log-next" onclick="changePage(1)" class="pagination-btn" disabled>›</button>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
  const BASE_URL = <?= json_encode(BASE_URL) ?>;

  const state = { limit: 10, offset: 0, total: 0, movements: [], search: '', typeFilter: '' };

  function esc(value) {
    return String(value ?? '').replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  function formatDate(value) {
    const d = new Date(value.replace(' ', 'T') + 'Z');
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) +
      ', ' + d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
  }

  async function loadLog() {
    const tbody = document.getElementById('log-tbody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-ink-dim py-8">Loading activity…</td></tr>';

    try {
      const params = new URLSearchParams({
        limit: state.limit,
        offset: state.offset,
        q: state.search,
        type: state.typeFilter,
      });
      const res = await fetch(`${BASE_URL}/api/stock_movements.php?${params.toString()}`);
      const json = await res.json();

      if (!json.success) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-stock-out py-8">${esc(json.message || 'Failed to load activity log')}</td></tr>`;
        return;
      }

      state.movements = json.data.movements || [];
      state.total = json.data.total || 0;

      renderTable();
      renderPagination();
    } catch (err) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-center text-stock-out py-8">Could not reach the server. Please try again.</td></tr>';
    }
  }

  function renderTable() {
    const tbody = document.getElementById('log-tbody');
    const rows = state.movements;

    if (rows.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-center text-ink-dim py-8">No activity found.</td></tr>';
      return;
    }

    tbody.innerHTML = rows.map(m => {
      const isIn = m.movement_type === 'in';
      const typeBadge = isIn
        ? '<span class="badge-green">Received</span>'
        : '<span class="badge-red">Issued</span>';
      const qty = (isIn ? '+' : '-') + m.quantity;
      const who = `${m.first_name} ${m.last_name}`;

      return `
        <tr>
          <td class="text-xs">${esc(formatDate(m.created_at))}</td>
          <td class="text-ink font-medium">
            <div class="text-sm">${esc(m.item_name)}</div>
            <div class="font-mono text-[11px] text-ink-dim">${esc(m.sku_code)}</div>
          </td>
          <td>${typeBadge}</td>
          <td class="text-right font-mono text-xs ${isIn ? 'text-stock-in' : 'text-stock-out'}">${esc(qty)}</td>
          <td class="text-xs">${esc(who)}</td>
          <td class="font-mono text-xs text-ink-dim">${esc(m.reference_code || '—')}</td>
        </tr>`;
    }).join('');
  }

  function renderPagination() {
    const shown = state.movements.length;
    const start = shown === 0 ? 0 : state.offset + 1;
    const end = state.offset + shown;
    document.getElementById('log-count').textContent = `Showing ${start}-${end} of ${state.total} entries`;
    document.getElementById('log-page').textContent = Math.floor(state.offset / state.limit) + 1;
    document.getElementById('log-prev').disabled = state.offset <= 0;
    document.getElementById('log-next').disabled = state.offset + state.limit >= state.total;
  }

  function changePage(direction) {
    const next = state.offset + direction * state.limit;
    if (next < 0 || next >= state.total) return;
    state.offset = next;
    loadLog();
  }

  function changePageSize(value) {
    state.limit = Number(value);
    state.offset = 0;
    loadLog();
  }

  function setTypeFilter(type) {
    state.typeFilter = type;
    state.offset = 0;

    const btns = { '': 'filter-all', in: 'filter-in', out: 'filter-out' };
    Object.entries(btns).forEach(([value, id]) => {
      document.getElementById(id).className = 'px-3 py-1.5 rounded-tag text-xs font-mono uppercase tracking-wide transition-colors ' +
        (value === type ? 'bg-tag-amber/10 text-tag-amber border border-tag-amber/30' : 'text-ink-dim hover:bg-overlay/10 hover:text-ink-muted');
    });

    loadLog();
  }

  let searchDebounce;
  document.getElementById('log-search').addEventListener('input', (e) => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
      state.search = e.target.value.trim();
      state.offset = 0;
      loadLog();
    }, 250);
  });

  setTypeFilter('');
</script>

</body>
</html>
