<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../auth/rbac.php';

require_login();
require_permission('Roles & Permissions', 'view');

$activePage = 'roles';
$pageEyebrow = 'Access Control';
$pageTitle = 'Roles & Permissions';

$canCreate = has_permission('Roles & Permissions', 'create');
$canEdit = has_permission('Roles & Permissions', 'edit');
$canDelete = has_permission('Roles & Permissions', 'delete');
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
      <p class="text-sm text-ink-muted">Define what each role can see and change across every module.</p>
      <?php if ($canCreate): ?>
        <button onclick="openRoleModal()" class="btn-primary">
          <?= icon('plus', 'w-4 h-4') ?>
          Add role
        </button>
      <?php endif; ?>
    </div>

    <!-- Role cards -->
    <div class="flex items-center gap-3">
      <button type="button" id="role-cards-prev" onclick="scrollRoleCards(-1)" class="pagination-btn role-cards-nav shrink-0" disabled>‹</button>
      <div id="role-cards" class="flex gap-4 overflow-x-hidden flex-1">
        <p class="text-sm text-ink-dim py-8 text-center">Loading roles…</p>
      </div>
      <button type="button" id="role-cards-next" onclick="scrollRoleCards(1)" class="pagination-btn role-cards-nav shrink-0" disabled>›</button>
    </div>

    <!-- Permission matrix -->
    <div class="bin-tag">
      <div class="px-5 py-4 border-b border-border">
        <h2 class="eyebrow-table">Permission Matrix</h2>
      </div>

      <div class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr id="matrix-head">
              <th class="text-left min-w-[160px]">Role</th>
            </tr>
          </thead>
          <tbody id="matrix-body">
            <tr><td class="text-center text-ink-dim py-8">Loading permission matrix…</td></tr>
          </tbody>
        </table>
      </div>
      <div class="flex items-center justify-end gap-3 px-5 py-3.5 border-t border-border">
        <p class="text-[11px] text-ink-dim mr-auto">V · View &nbsp; C · Create &nbsp; E · Edit &nbsp; D · Delete</p>
        <p id="matrix-error" class="hidden text-xs text-stock-out"></p>
        <?php if ($canEdit): ?>
          <button type="button" onclick="loadMatrix()" class="btn-secondary">Reset</button>
          <button type="button" id="matrix-save" onclick="saveMatrix()" class="btn-primary">Save changes</button>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<div id="role-modal" class="hidden flex modal-overlay">
  <div class="bin-tag modal-panel-sm">
    <div class="panel-header">
      <div>
        <p class="eyebrow">Role Record</p>
        <h2 id="role-modal-title" class="font-display font-semibold text-ink">Add Role</h2>
      </div>
      <button type="button" onclick="closeRoleModal()" class="icon-btn-muted">✕</button>
    </div>
    <form id="role-form" class="p-5 space-y-4">
      <input type="hidden" id="role-id">
      <p id="role-form-error" class="hidden text-xs text-stock-out bg-stock-out/10 border border-stock-out/30 rounded-tag px-3 py-2"></p>
      <div>
        <label class="field-label">Role name</label>
        <input type="text" id="role-name" class="field-input" placeholder="e.g. Procurement Officer" required>
      </div>
      <div>
        <label class="field-label">Description</label>
        <textarea id="role-description" class="field-input" rows="3" placeholder="What can this role do?"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeRoleModal()" class="btn-secondary">Cancel</button>
        <button type="submit" id="role-form-submit" class="btn-primary">Save role</button>
      </div>
    </form>
  </div>
</div>

<script>
  const BASE_URL = <?= json_encode(BASE_URL) ?>;
  const CAN_EDIT = <?= json_encode($canEdit) ?>;
  const CAN_DELETE = <?= json_encode($canDelete) ?>;

  // Fixed display order — the DB returns actions alphabetically, but the
  // matrix should always read View / Create / Edit / Delete, skipping
  // whichever of those a given module doesn't actually have (e.g. Settings
  // only has view/edit).
  const ACTION_ORDER = ['view', 'create', 'edit', 'delete'];
  const ACTION_LABEL = { view: 'V', create: 'C', edit: 'E', delete: 'D' };

  let roles = [];
  let modules = []; // [{ name, permissions: [{permission_id, action}, ...ordered] }]
  let assignedByRole = {}; // { [role_id]: Set<permission_id> }

  function esc(value) {
    return String(value ?? '').replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  function roleCode(id) {
    return 'ROLE-' + String(id).padStart(3, '0');
  }

  async function loadRoles() {
    const container = document.getElementById('role-cards');
    try {
      const res = await fetch(`${BASE_URL}/api/roles.php`);
      const json = await res.json();

      if (!json.success) {
        container.innerHTML = `<p class="text-sm text-stock-out col-span-full py-8 text-center">${esc(json.message || 'Failed to load roles')}</p>`;
        return;
      }

      // SQL Server (via PDO_SQLSRV) returns integer columns as PHP strings,
      // which json_encode preserves as JSON strings — normalize here so
      // strict-equality lookups (e.g. openRoleModal's roles.find) against
      // numeric ids from onclick="...(${r.role_id})" actually match.
      roles = (json.data || []).map(r => ({ ...r, role_id: Number(r.role_id) }));
      renderRoleCards();
    } catch (err) {
      container.innerHTML = '<p class="text-sm text-stock-out col-span-full py-8 text-center">Could not reach the server. Please try again.</p>';
    }
  }

  function renderRoleCards() {
    const container = document.getElementById('role-cards');

    if (roles.length === 0) {
      container.innerHTML = '<p class="text-sm text-ink-dim py-8 text-center">No roles yet.</p>';
      updateRoleCardsNav();
      return;
    }

    container.innerHTML = roles.map(r => {
      const userCount = Number(r.user_count || 0);
      const canDeleteThis = CAN_DELETE && userCount === 0;

      return `
        <div class="bin-tag stat-card w-64 shrink-0">
          <div class="flex items-start justify-between">
            <p class="stat-code">${roleCode(r.role_id)}</p>
            <div class="flex items-center gap-1">
              ${canDeleteThis ? `<button onclick="deleteRole(${r.role_id}, '${esc(r.role_name)}')" class="icon-btn-danger !w-6 !h-6" title="Delete role">${iconTrash()}</button>` : ''}
              <span class="text-tag-amber">${iconShield()}</span>
            </div>
          </div>
          <p class="font-display font-semibold text-ink mt-3">${esc(r.role_name)}</p>
          <p class="text-xs text-ink-muted mt-1 leading-relaxed">${esc(r.description || 'No description')}</p>
          <div class="flex items-center justify-between mt-4 pt-3 border-t border-border/60">
            <span class="font-mono text-[11px] text-ink-dim">${userCount} user${userCount === 1 ? '' : 's'}</span>
            ${CAN_EDIT ? `<button onclick="openRoleModal(${r.role_id})" class="text-xs text-tag-amber hover:underline">Edit</button>` : ''}
          </div>
        </div>`;
    }).join('');

    updateRoleCardsNav();
  }

  function updateRoleCardsNav() {
    const el = document.getElementById('role-cards');
    const prev = document.getElementById('role-cards-prev');
    const next = document.getElementById('role-cards-next');
    prev.disabled = el.scrollLeft <= 0;
    next.disabled = el.scrollLeft + el.clientWidth >= el.scrollWidth - 1;
  }

  function scrollRoleCards(direction) {
    const el = document.getElementById('role-cards');
    el.scrollBy({ left: direction * 288, behavior: 'smooth' });
    setTimeout(updateRoleCardsNav, 300);
  }

  function iconShield() {
    return '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.5l8 3v6c0 5-3.4 8.6-8 10-4.6-1.4-8-5-8-10v-6l8-3z"/></svg>';
  }
  function iconTrash() {
    return '<svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>';
  }

  // ---- Permission matrix ----

  async function loadMatrix() {
    document.getElementById('matrix-error').classList.add('hidden');
    const head = document.getElementById('matrix-head');
    const body = document.getElementById('matrix-body');
    body.innerHTML = '<tr><td class="text-center text-ink-dim py-8">Loading permission matrix…</td></tr>';

    try {
      const [permsRes, ...roleRes] = await Promise.all([
        fetch(`${BASE_URL}/api/permissions.php`),
        ...(roles.length ? roles.map(r => fetch(`${BASE_URL}/api/permissions.php?role_id=${r.role_id}`)) : []),
      ]);

      const permsJson = await permsRes.json();
      if (!permsJson.success) {
        body.innerHTML = `<tr><td class="text-center text-stock-out py-8">${esc(permsJson.message || 'Failed to load permissions')}</td></tr>`;
        return;
      }

      modules = Object.keys(permsJson.data.permissions).map(name => {
        const byAction = {};
        permsJson.data.permissions[name].forEach(p => { byAction[p.action] = p.permission_id; });
        const ordered = ACTION_ORDER
          .filter(a => byAction[a] !== undefined)
          .map(a => ({ action: a, permission_id: byAction[a] }));
        return { name, permissions: ordered };
      });

      assignedByRole = {};
      for (let i = 0; i < roles.length; i++) {
        const roleJson = await roleRes[i].json();
        const set = new Set();
        if (roleJson.success) {
          Object.values(roleJson.data.permissions).flat().forEach(p => {
            if (Number(p.assigned)) set.add(p.permission_id);
          });
        }
        assignedByRole[roles[i].role_id] = set;
      }

      renderMatrixHead();
      renderMatrixBody();
    } catch (err) {
      body.innerHTML = '<tr><td class="text-center text-stock-out py-8">Could not reach the server. Please try again.</td></tr>';
    }
  }

  function renderMatrixHead() {
    const head = document.getElementById('matrix-head');
    head.innerHTML = '<th class="text-left min-w-[160px]">Role</th>' +
      modules.map(m => `<th class="text-center min-w-[160px]">${esc(m.name)}</th>`).join('');
  }

  function renderMatrixBody() {
    const body = document.getElementById('matrix-body');

    if (roles.length === 0) {
      body.innerHTML = '<tr><td class="text-center text-ink-dim py-8">No roles yet.</td></tr>';
      return;
    }

    body.innerHTML = roles.map(r => {
      const assigned = assignedByRole[r.role_id] || new Set();
      const cells = modules.map(m => {
        const boxes = m.permissions.map(p => `
          <label class="flex flex-col items-center gap-1 cursor-pointer group">
            <input type="checkbox"
              data-role-id="${r.role_id}"
              data-permission-id="${p.permission_id}"
              ${assigned.has(p.permission_id) ? 'checked' : ''}
              ${CAN_EDIT ? '' : 'disabled'}
              class="w-3.5 h-3.5 rounded accent-tag-amber bg-base-deep border-border-light">
            <span class="text-[9px] font-mono text-ink-dim group-hover:text-ink-muted">${ACTION_LABEL[p.action]}</span>
          </label>`).join('');
        return `<td><div class="flex items-center justify-center gap-2">${boxes}</div></td>`;
      }).join('');

      return `<tr><td class="text-ink font-medium">${esc(r.role_name)}</td>${cells}</tr>`;
    }).join('');
  }

  async function saveMatrix() {
    const errorEl = document.getElementById('matrix-error');
    errorEl.classList.add('hidden');

    const saveBtn = document.getElementById('matrix-save');
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving…';

    try {
      const results = await Promise.all(roles.map(r => {
        const checked = Array.from(
          document.querySelectorAll(`input[data-role-id="${r.role_id}"]:checked`)
        ).map(cb => Number(cb.dataset.permissionId));

        return fetch(`${BASE_URL}/api/roles.php`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: r.role_id, permission_ids: checked }),
        }).then(res => res.json());
      }));

      const failed = results.find(r => !r.success);
      if (failed) {
        errorEl.textContent = failed.message || 'Could not save some changes.';
        errorEl.classList.remove('hidden');
        return;
      }

      await loadMatrix();
    } catch (err) {
      errorEl.textContent = 'Could not reach the server. Please try again.';
      errorEl.classList.remove('hidden');
    } finally {
      saveBtn.disabled = false;
      saveBtn.textContent = 'Save changes';
    }
  }

  // ---- Role modal ----

  function openRoleModal(id) {
    const form = document.getElementById('role-form');
    form.reset();
    document.getElementById('role-id').value = '';
    document.getElementById('role-form-error').classList.add('hidden');

    if (id) {
      const r = roles.find(x => x.role_id === id);
      document.getElementById('role-modal-title').textContent = 'Edit Role · ' + roleCode(id);
      document.getElementById('role-id').value = id;
      if (r) {
        document.getElementById('role-name').value = r.role_name;
        document.getElementById('role-description').value = r.description || '';
      }
    } else {
      document.getElementById('role-modal-title').textContent = 'Add Role';
    }

    document.getElementById('role-modal').classList.remove('hidden');
  }

  function closeRoleModal() {
    document.getElementById('role-modal').classList.add('hidden');
  }

  document.getElementById('role-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errorEl = document.getElementById('role-form-error');
    errorEl.classList.add('hidden');

    const id = document.getElementById('role-id').value;
    const payload = {
      role_name: document.getElementById('role-name').value.trim(),
      description: document.getElementById('role-description').value.trim(),
    };

    const submitBtn = document.getElementById('role-form-submit');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving…';

    try {
      let res;
      if (id) {
        payload.id = Number(id);
        res = await fetch(`${BASE_URL}/api/roles.php`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
      } else {
        res = await fetch(`${BASE_URL}/api/roles.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
      }

      const json = await res.json();
      if (!json.success) {
        errorEl.textContent = json.message || 'Could not save role.';
        errorEl.classList.remove('hidden');
        return;
      }

      closeRoleModal();
      await loadRoles();
      await loadMatrix();
    } catch (err) {
      errorEl.textContent = 'Could not reach the server. Please try again.';
      errorEl.classList.remove('hidden');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Save role';
    }
  });

  async function deleteRole(id, name) {
    if (!confirm(`Delete ${name}? This cannot be undone.`)) return;

    try {
      const res = await fetch(`${BASE_URL}/api/roles.php?id=${id}`, { method: 'DELETE' });
      const json = await res.json();
      if (!json.success) {
        alert(json.message || 'Could not delete role.');
        return;
      }
      await loadRoles();
      await loadMatrix();
    } catch (err) {
      alert('Could not reach the server. Please try again.');
    }
  }

  (async function init() {
    await loadRoles();
    await loadMatrix();
  })();
</script>

</body>
</html>
