<div id="account-modal" class="hidden flex modal-overlay">
  <div class="bin-tag w-full max-w-xl">
    <div class="panel-header">
      <div>
        <p class="eyebrow">Account</p>
        <h2 id="account-modal-title" class="font-display font-semibold text-ink"></h2>
      </div>
      <div class="flex items-center gap-3">
        <span id="account-modal-role" class="badge-muted"></span>
        <button type="button" onclick="closeAccountModal()" class="icon-btn-muted">✕</button>
      </div>
    </div>

    <form id="account-form" class="p-5 space-y-5">
      <p id="account-form-error" class="hidden text-xs text-stock-out bg-stock-out/10 border border-stock-out/30 rounded-tag px-3 py-2"></p>
      <p id="account-form-success" class="hidden text-xs text-stock-in bg-stock-in/10 border border-stock-in/30 rounded-tag px-3 py-2"></p>

      <div>
        <p class="eyebrow mb-3">Profile</p>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="field-label">First name <span class="required-marker">*</span></label>
            <input type="text" id="account-first_name" class="field-input" required>
          </div>
          <div>
            <label class="field-label">Last name <span class="required-marker">*</span></label>
            <input type="text" id="account-last_name" class="field-input" required>
          </div>
        </div>
      </div>

      <div class="pt-2 border-t border-border">
        <p class="eyebrow mb-3 mt-4">Change password</p>
        <p class="text-[11px] text-ink-dim mb-3">Leave blank to keep your current password.</p>
        <div class="space-y-4">
          <div>
            <label class="field-label">Current password</label>
            <input type="password" id="account-current_password" class="field-input" placeholder="••••••••">
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="field-label">New password</label>
              <input type="password" id="account-new_password" class="field-input" placeholder="At least 8 characters">
            </div>
            <div>
              <label class="field-label">Confirm new password</label>
              <input type="password" id="account-confirm_password" class="field-input" placeholder="Repeat new password">
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" onclick="closeAccountModal()" class="btn-secondary">Cancel</button>
        <button type="submit" id="account-form-submit" class="btn-primary">Save changes</button>
      </div>
    </form>
  </div>
</div>
