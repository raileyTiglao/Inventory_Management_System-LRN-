function showToast(message, type) {
  type = type === 'error' ? 'error' : 'success';
  var container = document.getElementById('toast-container');
  if (!container) return;

  var accent = type === 'success' ? 'border-stock-in' : 'border-stock-out';
  var iconColor = type === 'success' ? 'text-stock-in' : 'text-stock-out';

  var toast = document.createElement('div');
  toast.className = 'relative bg-card border border-border rounded-tag overflow-hidden pointer-events-auto flex items-start gap-3 px-4 py-3 border-l-4 ' + accent + ' cursor-pointer';
  toast.innerHTML =
    '<span class="' + iconColor + ' shrink-0 mt-0.5">' + (type === 'success' ? toastIconCheck() : toastIconAlert()) + '</span>' +
    '<p class="text-sm text-ink flex-1">' + String(message).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    }) + '</p>';

  var timer = setTimeout(function () { toast.remove(); }, 4000);
  toast.addEventListener('click', function () {
    clearTimeout(timer);
    toast.remove();
  });

  container.appendChild(toast);
}

function toastIconCheck() {
  return '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>';
}
function toastIconAlert() {
  return '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9L2.4 18a2 2 0 001.7 3h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>';
}
