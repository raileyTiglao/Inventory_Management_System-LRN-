<div id="delete-user-modal" class="hidden flex modal-overlay">
  <div class="bin-tag modal-panel-sm">
    <div class="panel-header">
      <div>
        <p class="eyebrow">Users</p>
        <h2 class="font-display font-semibold text-ink">Delete user?</h2>
      </div>
      <button type="button" onclick="closeDeleteUserModal()" class="icon-btn-muted">✕</button>
    </div>
    <div class="p-5 space-y-4">
      <p class="text-sm text-ink-muted">
        Delete <span id="delete-user-modal-name" class="font-mono text-ink"></span>?
        This cannot be undone.
      </p>
      <div class="modal-footer">
        <button type="button" onclick="closeDeleteUserModal()" class="btn-secondary">Cancel</button>
        <button type="button" onclick="confirmDeleteUser()" class="btn-danger">Delete</button>
      </div>
    </div>
  </div>
</div>
