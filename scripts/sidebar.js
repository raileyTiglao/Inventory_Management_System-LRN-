(function () {
  var STORAGE_KEY = 'ims-sidebar-collapsed';
  var sidebar = document.getElementById('sidebar');
  var toggle = document.getElementById('sidebar-toggle');
  var expandedIcon = document.getElementById('sidebar-toggle-icon-expanded');
  var collapsedIcon = document.getElementById('sidebar-toggle-icon-collapsed');

  function reflectToggleIcon(collapsed) {
    expandedIcon.classList.toggle('hidden', collapsed);
    collapsedIcon.classList.toggle('hidden', !collapsed);
  }

  var startCollapsed = localStorage.getItem(STORAGE_KEY) === '1';
  if (startCollapsed) {
    sidebar.classList.add('sidebar-collapsed');
  }
  reflectToggleIcon(startCollapsed);

  toggle.addEventListener('click', function () {
    var collapsed = sidebar.classList.toggle('sidebar-collapsed');
    localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
    reflectToggleIcon(collapsed);
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

// Called by pages that change inventory quantities/status (item save,
// archive/restore, stock movements) so the nav badge updates immediately
// instead of only reflecting reality after the next full page load.
window.refreshSidebarLowStock = async function () {
  var config = window.IMS_SIDEBAR_CONFIG;
  var badge = document.getElementById('sidebar-low-stock-badge');
  var dot = document.getElementById('sidebar-low-stock-dot');
  if (!config || !config.canViewInventory || !badge || !dot) return;

  try {
    const res = await fetch(config.baseUrl + '/api/inventory.php?status=active&low_stock=1&limit=1');
    const json = await res.json();
    const count = (json.success && json.data.total) || 0;
    const title = count + ' item(s) at or below reorder level';

    badge.textContent = count;
    badge.title = title;
    dot.title = title;
    badge.classList.toggle('hidden', count === 0);
    dot.classList.toggle('hidden', count === 0);
  } catch (err) {
    // Leave the last-known badge state in place on failure.
  }
};
