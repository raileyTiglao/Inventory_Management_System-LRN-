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

      <div id="item-readonly-summary" class="hidden flex items-center gap-4 text-xs bg-overlay/5 border border-border-light rounded-tag px-3 py-2">
        <span class="text-ink-dim">Enter New Values <span id="item-sku_code-display" class="font-mono text-ink-muted"></span></span>
      </div>

      <div id="item-grid-top" class="grid grid-cols-2 gap-4">
        <div id="item-sku-field">
          <label class="field-label">SKU code <span class="required-marker">*</span></label>
          <input type="text" id="item-sku_code" class="field-input font-mono" placeholder="SKU-1234" required>
        </div>
        <div>
          <label class="field-label">Name <span class="required-marker">*</span></label>
          <input type="text" id="item-name" class="field-input" placeholder="Steel Bracket M8" required>
        </div>
      </div>

      <div>
        <label class="field-label">Description</label>
        <textarea id="item-description" class="field-input" rows="2" placeholder="Optional details"></textarea>
      </div>

      <div id="item-grid-bottom" class="grid grid-cols-3 gap-4">
        <div id="item-qty-field">
          <label class="field-label">Initial qty <span class="required-marker">*</span></label>
          <input type="number" id="item-quantity_on_hand" class="field-input" min="1" step="1" value="1" required>
        </div>
        <div>
          <label class="field-label">Reorder level <span class="required-marker">*</span></label>
          <input type="number" id="item-reorder_level" class="field-input" min="0" step="1" value="0" required>
        </div>
        <div>
          <label class="field-label">Unit cost <span class="required-marker">*</span></label>
          <input type="number" id="item-unit_cost" class="field-input" min="0" step="0.01" placeholder="0.00" required>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" onclick="closeItemModal()" class="btn-secondary">Cancel</button>
        <button type="submit" id="inventory-form-submit" class="btn-primary">Save item</button>
      </div>
    </form>
  </div>
</div>
