<div id="movement-modal" class="hidden flex modal-overlay">
  <div class="bin-tag modal-panel-sm">
    <div class="panel-header">
      <div>
        <p class="eyebrow" id="movement-modal-eyebrow">Stock Movement</p>
        <h2 id="movement-modal-title" class="font-display font-semibold text-ink">Receive Stock</h2>
      </div>
      <button type="button" onclick="closeMoveModal()" class="icon-btn-muted">✕</button>
    </div>

    <form id="movement-form" class="p-5 space-y-4">
      <input type="hidden" id="move-sku_id">
      <input type="hidden" id="move-type" value="in">

      <p id="movement-form-error" class="hidden text-xs text-stock-out bg-stock-out/10 border border-stock-out/30 rounded-tag px-3 py-2"></p>

      <div class="flex items-center gap-1.5 p-1 bg-base-deep border border-border rounded-tag">
        <button type="button" id="move-type-in" onclick="setMoveType('in')" class="flex-1 py-2 rounded-tag text-xs font-mono uppercase tracking-wide transition-colors">Receive (in)</button>
        <button type="button" id="move-type-out" onclick="setMoveType('out')" class="flex-1 py-2 rounded-tag text-xs font-mono uppercase tracking-wide transition-colors">Issue (out)</button>
      </div>

      <div>
        <label class="field-label">Quantity</label>
        <input type="number" id="move-quantity" class="field-input" min="1" step="1" placeholder="0" required>
      </div>

      <div>
        <label class="field-label">Reference code</label>
        <input type="text" id="move-reference_code" class="field-input font-mono" placeholder="PO-1029 / issue ticket # (optional)">
      </div>

      <div>
        <label class="field-label">Notes</label>
        <textarea id="move-notes" class="field-input" rows="2" placeholder="Optional details"></textarea>
      </div>

      <div class="modal-footer">
        <button type="button" onclick="closeMoveModal()" class="btn-secondary">Cancel</button>
        <button type="submit" id="movement-form-submit" class="btn-primary">Record movement</button>
      </div>
    </form>
  </div>
</div>
