(function () {
  var THEME_KEY = 'ims-theme';
  var toggleBtn = document.getElementById('theme-toggle-btn');
  var lightIcon = document.getElementById('theme-toggle-icon-light');
  var darkIcon = document.getElementById('theme-toggle-icon-dark');

  function reflectTheme(theme) {
    lightIcon.classList.toggle('hidden', theme === 'dark');
    darkIcon.classList.toggle('hidden', theme !== 'dark');
  }

  reflectTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');

  toggleBtn.addEventListener('click', function () {
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var next = isDark ? 'light' : 'dark';
    if (next === 'dark') {
      document.documentElement.setAttribute('data-theme', 'dark');
    } else {
      document.documentElement.removeAttribute('data-theme');
    }
    localStorage.setItem(THEME_KEY, next);
    reflectTheme(next);
  });
})();

(function () {
  var BASE_URL = window.IMS_TOPBAR_CONFIG.baseUrl;
  var CAN_VIEW_INVENTORY = window.IMS_TOPBAR_CONFIG.canViewInventory;

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

(function () {
  var BASE_URL = window.IMS_TOPBAR_CONFIG.baseUrl;
  var modal = document.getElementById('account-modal');
  var form = document.getElementById('account-form');
  var errorEl = document.getElementById('account-form-error');
  var successEl = document.getElementById('account-form-success');

  window.openAccountModal = async function () {
    form.reset();
    errorEl.classList.add('hidden');
    successEl.classList.add('hidden');
    modal.classList.remove('hidden');

    try {
      const res = await fetch(BASE_URL + '/api/account.php');
      const json = await res.json();
      if (json.success) {
        document.getElementById('account-first_name').value = json.data.first_name;
        document.getElementById('account-last_name').value = json.data.last_name;
        document.getElementById('account-modal-title').textContent = json.data.email;
        document.getElementById('account-modal-role').textContent = json.data.role;
      }
    } catch (err) {
      errorEl.textContent = 'Could not load account details.';
      errorEl.classList.remove('hidden');
    }
  };

  window.closeAccountModal = function () {
    modal.classList.add('hidden');
  };

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    errorEl.classList.add('hidden');
    successEl.classList.add('hidden');

    var newPassword = document.getElementById('account-new_password').value;
    var confirmPassword = document.getElementById('account-confirm_password').value;

    if (newPassword && newPassword !== confirmPassword) {
      errorEl.textContent = 'New password and confirmation do not match.';
      errorEl.classList.remove('hidden');
      return;
    }

    var payload = {
      first_name: document.getElementById('account-first_name').value.trim(),
      last_name: document.getElementById('account-last_name').value.trim(),
    };
    if (newPassword) {
      payload.current_password = document.getElementById('account-current_password').value;
      payload.new_password = newPassword;
    }

    var submitBtn = document.getElementById('account-form-submit');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving…';

    try {
      const res = await fetch(BASE_URL + '/api/account.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const json = await res.json();

      if (!json.success) {
        errorEl.textContent = json.message || 'Could not save changes.';
        errorEl.classList.remove('hidden');
        return;
      }

      document.getElementById('account-current_password').value = '';
      document.getElementById('account-new_password').value = '';
      document.getElementById('account-confirm_password').value = '';

      var initialsEl = document.getElementById('topbar-user-initials');
      var nameEl = document.getElementById('topbar-user-name');
      if (initialsEl) initialsEl.textContent = json.data.initials;
      if (nameEl) nameEl.textContent = json.data.name;

      successEl.textContent = 'Account updated successfully.';
      successEl.classList.remove('hidden');
      if (typeof showToast === 'function') showToast('Account updated successfully.', 'success');
    } catch (err) {
      errorEl.textContent = 'Could not reach the server. Please try again.';
      errorEl.classList.remove('hidden');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Save changes';
    }
  });
})();
