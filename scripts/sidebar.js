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
