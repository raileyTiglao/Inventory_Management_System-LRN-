<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../auth/rbac.php';

require_login();
require_permission('Users', 'view');

$activePage = 'users';
$pageEyebrow = 'Access Control';
$pageTitle = 'Users';

$canCreate = has_permission('Users', 'create');
$canEdit = has_permission('Users', 'edit');
$canDelete = has_permission('Users', 'delete');

$currentUser = logged_in_user();

// Queried directly (not via /api/roles.php) since roles the current user
// can assign shouldn't require 'Roles & Permissions' view access.
$roles = get_db()->query('SELECT role_id, role_name FROM dbo.ims_roles ORDER BY role_name')->fetchAll();
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
      <div>
        <p class="text-sm text-ink-muted">Manage accounts and assign roles that control what each person can see and do.</p>
      </div>
      <?php if ($canCreate): ?>
        <button onclick="openUserModal()" class="btn-primary">
          <?= icon('plus', 'w-4 h-4') ?>
          Add user
        </button>
      <?php endif; ?>
    </div>

    <div class="bin-tag">
      <div class="panel-header">
        <div class="flex items-center gap-2">
          <p class="eyebrow-table">users</p>
        </div>
        <div class="flex items-center gap-3">
          <select id="user-role-filter" class="field-input !py-2 !w-40 text-xs">
            <option value="">All roles</option>
            <?php foreach ($roles as $role): ?>
              <option value="<?= (int)$role['role_id'] ?>"><?= e($role['role_name']) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="search-wrap w-64">
            <span class="search-icon"><?= icon('search', 'w-4 h-4') ?></span>
            <input type="text" id="user-search" placeholder="Search users..." class="search-input w-full">
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr>
              <th class="text-left">ID</th>
              <th class="text-left">Name</th>
              <th class="text-left">Email</th>
              <th class="text-left">Role</th>
              <th class="text-left">Status</th>
              <th class="text-left">Last active</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody id="user-tbody">
            <tr><td colspan="7" class="text-center text-ink-dim py-8">Loading users…</td></tr>
          </tbody>
        </table>
      </div>

      <div class="panel-footer">
        <p class="text-xs text-ink-dim" id="user-count">Showing 0 of 0 users</p>
        <div class="flex items-center gap-1.5">
          <button id="user-prev" onclick="changePage(-1)" class="pagination-btn" disabled>‹</button>
          <span class="pagination-page" id="user-page">1</span>
          <button id="user-next" onclick="changePage(1)" class="pagination-btn" disabled>›</button>
        </div>
      </div>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../../components/user-modal.php'; ?>

<script>
  const BASE_URL = <?= json_encode(BASE_URL) ?>;
  const CAN_EDIT = <?= json_encode($canEdit) ?>;
  const CAN_DELETE = <?= json_encode($canDelete) ?>;
  const CURRENT_USER_ID = <?= json_encode((int)($currentUser['id'] ?? 0)) ?>;

  const state = { limit: 10, offset: 0, total: 0, users: [], search: '', roleId: '' };

  function esc(value) {
    return String(value ?? '').replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  function initials(first, last) {
    return ((first?.[0] ?? '') + (last?.[0] ?? '')).toUpperCase() || '?';
  }

  function formatLastLogin(value) {
    if (!value) return 'Never';
    const d = new Date(value.replace(' ', 'T') + 'Z');
    const now = new Date();
    const sameDay = d.toDateString() === now.toDateString();
    if (sameDay) return 'Today, ' + d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }

  // A session typically stays valid ~20-30min after the last request; inside
  // that window we treat the user as still on the site rather than just
  // "recently" logged in.
  const ONLINE_WINDOW_MS = 20 * 60 * 1000;

  function isOnlineNow(value) {
    if (!value) return false;
    const d = new Date(value.replace(' ', 'T') + 'Z');
    return (Date.now() - d.getTime()) < ONLINE_WINDOW_MS;
  }

  function roleBadgeClass(roleName) {
    if (roleName === 'Administrator') return 'badge-amber';
    if (/manager/i.test(roleName)) return 'badge-green';
    return 'badge-muted';
  }

  async function loadUsers() {
    const tbody = document.getElementById('user-tbody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-ink-dim py-8">Loading users…</td></tr>';

    try {
      const res = await fetch(`${BASE_URL}/api/users.php?limit=${state.limit}&offset=${state.offset}&q=${encodeURIComponent(state.search)}&role_id=${encodeURIComponent(state.roleId)}`);
      const json = await res.json();

      if (!json.success) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-stock-out py-8">${esc(json.message || 'Failed to load users')}</td></tr>`;
        return;
      }

      // SQL Server (via PDO_SQLSRV) returns integer columns as PHP strings,
      // which json_encode preserves as JSON strings — normalize here so
      // strict-equality lookups (e.g. openUserModal's state.users.find)
      // against numeric ids from onclick="...(${u.user_id})" actually match.
      state.users = (json.data.users || []).map(u => ({
        ...u,
        user_id: Number(u.user_id),
        role_id: Number(u.role_id),
      }));
      state.total = json.data.total || 0;

      renderTable();
      renderPagination();
    } catch (err) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center text-stock-out py-8">Could not reach the server. Please try again.</td></tr>';
    }
  }

  function renderTable() {
    const tbody = document.getElementById('user-tbody');
    const rows = state.users;

    if (rows.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center text-ink-dim py-8">No users found.</td></tr>';
      return;
    }

    tbody.innerHTML = rows.map(u => {
      const name = `${u.first_name} ${u.last_name}`;
      const statusBadge = u.status === 'active'
        ? '<span class="badge-green">Active</span>'
        : '<span class="badge-red">Inactive</span>';

      const lastActive = isOnlineNow(u.last_login)
        ? '<span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-stock-in"></span>Online now</span>'
        : esc(formatLastLogin(u.last_login));

      const actions = [];
      if (CAN_EDIT) {
        actions.push(`<button onclick="openUserModal(${u.user_id})" class="icon-btn-amber" title="Edit">${iconEdit()}</button>`);
      }
      if (CAN_DELETE && u.user_id !== CURRENT_USER_ID) {
        actions.push(`<button onclick="deleteUser(${u.user_id}, '${esc(name)}')" class="icon-btn-danger" title="Delete">${iconTrash()}</button>`);
      }

      return `
        <tr>
          <td class="font-mono text-xs">USR-${String(u.user_id).padStart(4, '0')}</td>
          <td class="text-ink font-medium">
            <div class="flex items-center gap-2.5">
              <div class="w-7 h-7 rounded-tag bg-white/5 border border-border-light flex items-center justify-center font-mono text-[11px] text-ink-muted">
                ${esc(initials(u.first_name, u.last_name))}
              </div>
              ${esc(name)}
            </div>
          </td>
          <td class="font-mono text-xs">${esc(u.email)}</td>
          <td><span class="${roleBadgeClass(u.role_name)}">${esc(u.role_name)}</span></td>
          <td>${statusBadge}</td>
          <td class="text-xs">${lastActive}</td>
          <td>
            <div class="flex items-center justify-end gap-1.5">${actions.join('')}</div>
          </td>
        </tr>`;
    }).join('');
  }

  function renderPagination() {
    const shown = state.users.length;
    const start = shown === 0 ? 0 : state.offset + 1;
    const end = state.offset + shown;
    document.getElementById('user-count').textContent = `Showing ${start}-${end} of ${state.total} users`;
    document.getElementById('user-page').textContent = Math.floor(state.offset / state.limit) + 1;
    document.getElementById('user-prev').disabled = state.offset <= 0;
    document.getElementById('user-next').disabled = state.offset + state.limit >= state.total;
  }

  function changePage(direction) {
    const next = state.offset + direction * state.limit;
    if (next < 0 || next >= state.total) return;
    state.offset = next;
    loadUsers();
  }

  function iconEdit() {
    return '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>';
  }
  function iconTrash() {
    return '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>';
  }

  // ---- Modal handling ----

  function openUserModal(id) {
    const form = document.getElementById('user-form');
    form.reset();
    document.getElementById('user-id').value = '';
    document.getElementById('user-form-error').classList.add('hidden');
    document.getElementById('user-password').required = !id;
    document.getElementById('user-password-hint').textContent = id
      ? 'Leave blank to keep the current password.'
      : 'Required for new accounts.';

    const emailInput = document.getElementById('user-email');
    const emailField = document.getElementById('user-email-field');
    const summary = document.getElementById('user-readonly-summary');

    if (id) {
      const u = state.users.find(x => x.user_id === id);
      document.getElementById('user-modal-title').textContent = 'Edit User · USR-' + String(id).padStart(4, '0');
      document.getElementById('user-id').value = id;
      if (u) {
        document.getElementById('user-first_name').value = u.first_name;
        document.getElementById('user-last_name').value = u.last_name;
        emailInput.value = u.email;
        document.getElementById('user-role_id').value = u.role_id;
        document.getElementById('user-status').value = u.status;
        document.getElementById('user-email-display').textContent = u.email;
      }
      emailField.classList.add('hidden');
      summary.classList.remove('hidden');
    } else {
      document.getElementById('user-modal-title').textContent = 'Add User';
      document.getElementById('user-status').value = 'active';
      emailField.classList.remove('hidden');
      summary.classList.add('hidden');
    }

    document.getElementById('user-modal').classList.remove('hidden');
  }

  function closeUserModal() {
    document.getElementById('user-modal').classList.add('hidden');
  }

  document.getElementById('user-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errorEl = document.getElementById('user-form-error');
    errorEl.classList.add('hidden');

    const id = document.getElementById('user-id').value;
    const password = document.getElementById('user-password').value;

    const payload = {
      first_name: document.getElementById('user-first_name').value.trim(),
      last_name: document.getElementById('user-last_name').value.trim(),
      role_id: Number(document.getElementById('user-role_id').value),
      status: document.getElementById('user-status').value,
    };
    if (!id) {
      payload.email = document.getElementById('user-email').value.trim();
    }
    if (password) {
      payload.password = password;
    }

    const submitBtn = document.getElementById('user-form-submit');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving…';

    try {
      let res;
      if (id) {
        payload.id = Number(id);
        res = await fetch(`${BASE_URL}/api/users.php`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
      } else {
        res = await fetch(`${BASE_URL}/api/users.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
      }

      const json = await res.json();
      if (!json.success) {
        errorEl.textContent = json.message || 'Could not save user.';
        errorEl.classList.remove('hidden');
        return;
      }

      closeUserModal();
      await loadUsers();
    } catch (err) {
      errorEl.textContent = 'Could not reach the server. Please try again.';
      errorEl.classList.remove('hidden');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Save user';
    }
  });

  async function deleteUser(id, name) {
    if (!confirm(`Delete ${name}? This cannot be undone.`)) return;

    try {
      const res = await fetch(`${BASE_URL}/api/users.php?id=${id}`, { method: 'DELETE' });
      const json = await res.json();
      if (!json.success) {
        alert(json.message || 'Could not delete user.');
        return;
      }
      await loadUsers();
    } catch (err) {
      alert('Could not reach the server. Please try again.');
    }
  }

  let searchDebounce;
  document.getElementById('user-search').addEventListener('input', (e) => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
      state.search = e.target.value.trim();
      state.offset = 0;
      loadUsers();
    }, 250);
  });

  document.getElementById('user-role-filter').addEventListener('change', (e) => {
    state.roleId = e.target.value;
    state.offset = 0;
    loadUsers();
  });

  loadUsers();
</script>

</body>
</html>
