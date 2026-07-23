<div id="inventory-modal" class="hidden flex modal-overlay">
  <div class="bin-tag modal-panel">
    <div class="panel-header">
      <div>
        <p class="eyebrow">Inventory Record</p>
        <h2 id="inventory-modal-title" class="font-display font-semibold text-ink">Add Item</h2>
      </div>
      <button type="button" onclick="closeItemModal()" class="icon-btn-muted">✕</button>
    </div>

    <form id="inventory-form" class="p-5 space-y-4">
      <input type="hidden" id="item-id">

      <p id="inventory-form-error" class="hidden text-xs text-stock-out bg-stock-out/10 border border-stock-out/30 rounded-tag px-3 py-2"></p>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="field-label">SKU code</label>
          <input type="text" id="item-sku_code" class="field-input font-mono" placeholder="SKU-1234" required>
        </div>
        <div>
          <label class="field-label">Name</label>
          <input type="text" id="item-name" class="field-input" placeholder="Steel Bracket M8" required>
        </div>
      </div>

      <div>
        <label class="field-label">Description</label>
        <textarea id="item-description" class="field-input" rows="2" placeholder="Optional details"></textarea>
      </div>

      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="field-label" id="item-quantity_on_hand-label">Initial qty</label>
          <input type="number" id="item-quantity_on_hand" class="field-input" min="0" step="1" value="0">
          <p id="item-quantity_on_hand-hint" class="hidden text-[11px] text-ink-dim mt-1.5">Use Receive/Issue to change stock.</p>
        </div>
        <div>
          <label class="field-label">Reorder level</label>
          <input type="number" id="item-reorder_level" class="field-input" min="0" step="1" value="0">
        </div>
        <div>
          <label class="field-label">Unit cost</label>
          <input type="number" id="item-unit_cost" class="field-input" min="0" step="0.01" placeholder="0.00">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" onclick="closeItemModal()" class="btn-secondary">Cancel</button>
        <button type="submit" id="inventory-form-submit" class="btn-primary">Save item</button>
      </div>
    </form>
  </div>
</div>
