<div id="item-history-modal" class="hidden flex modal-overlay">
  <div class="bin-tag modal-panel">
    <div class="panel-header">
      <div class="min-w-0">
        <p class="eyebrow">Ledger · <span id="item-history-sku">—</span></p>
        <h2 id="item-history-name" class="font-display font-semibold text-ink truncate">Item History</h2>
      </div>
      <button type="button" onclick="closeItemHistoryModal()" class="icon-btn-muted shrink-0">✕</button>
    </div>

    <div class="p-5 space-y-4">
      <!-- min-w-0 cancels .data-table's 720px floor so the table fits the
           panel instead of forcing a horizontal scrollbar inside it. -->
      <div class="overflow-x-auto overflow-y-auto max-h-[55vh]">
        <table class="data-table min-w-0">
          <thead>
            <tr>
              <th class="text-left">Date</th>
              <th class="text-left">Type</th>
              <th class="text-right">Qty</th>
              <th class="text-right">Balance</th>
              <th class="text-left">Reference</th>
              <th class="text-left">By</th>
            </tr>
          </thead>
          <tbody id="item-history-tbody">
            <tr><td colspan="6" class="text-center text-ink-dim py-8">Loading…</td></tr>
          </tbody>
        </table>
      </div>

      <div class="modal-footer">
        <button type="button" onclick="closeItemHistoryModal()" class="btn-primary">Close</button>
      </div>
    </div>
  </div>
</div>
