<div id="archive-modal" class="hidden flex modal-overlay">
  <div class="bin-tag modal-panel-sm">
    <div class="panel-header">
      <div>
        <p class="eyebrow">Inventory</p>
        <h2 class="font-display font-semibold text-ink">Archive item?</h2>
      </div>
      <button type="button" onclick="closeArchiveModal()" class="icon-btn-muted">✕</button>
    </div>
    <div class="p-5 space-y-4">
      <p class="text-sm text-ink-muted">
        Archive <span id="archive-modal-sku" class="font-mono text-ink"></span>?
        It will be hidden from the active list, but its history is kept and it can be restored anytime.
      </p>
      <div class="modal-footer">
        <button type="button" onclick="closeArchiveModal()" class="btn-secondary">Cancel</button>
        <button type="button" onclick="confirmArchive()" class="btn-danger">Archive</button>
      </div>
    </div>
  </div>
</div>
