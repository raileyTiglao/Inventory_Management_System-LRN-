<?php
/** @var array $roles Provided by the including page (pages/users/index.php). */
?>
<div id="user-modal" class="hidden flex modal-overlay">
  <div class="bin-tag modal-panel">
    <div class="panel-header">
      <div>
        <p class="eyebrow">User Record</p>
        <h2 id="user-modal-title" class="font-display font-semibold text-ink">Add User</h2>
      </div>
      <button type="button" onclick="closeUserModal()" class="icon-btn-muted">✕</button>
    </div>

    <form id="user-form" class="p-5 space-y-4">
      <input type="hidden" id="user-id">

      <p id="user-form-error" class="hidden text-xs text-stock-out bg-stock-out/10 border border-stock-out/30 rounded-tag px-3 py-2"></p>

      <div id="user-readonly-summary" class="hidden flex items-center gap-4 text-xs bg-white/5 border border-border-light rounded-tag px-3 py-2">
        <span class="text-ink-dim">Email <span id="user-email-display" class="font-mono text-ink-muted"></span></span>
        <span class="text-[11px] text-ink-dim ml-auto">Enter New User details</span>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="field-label">First name</label>
          <input type="text" id="user-first_name" class="field-input" placeholder="Jamie" required>
        </div>
        <div>
          <label class="field-label">Last name</label>
          <input type="text" id="user-last_name" class="field-input" placeholder="Montes" required>
        </div>
      </div>

      <div id="user-email-field">
        <label class="field-label">Email</label>
        <input type="email" id="user-email" class="field-input" placeholder="jamie.montes@ims.local" required>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="field-label">Role</label>
          <select id="user-role_id" class="field-input">
            <?php foreach ($roles as $r): ?>
              <option value="<?= (int)$r['role_id'] ?>"><?= e($r['role_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="field-label">Status</label>
          <select id="user-status" class="field-input">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>

      <div>
        <label class="field-label">Password</label>
        <input type="password" id="user-password" class="field-input" placeholder="••••••••">
        <p id="user-password-hint" class="text-[11px] text-ink-dim mt-1.5">Required for new accounts.</p>
      </div>

      <div class="modal-footer">
        <button type="button" onclick="closeUserModal()" class="btn-secondary">Cancel</button>
        <button type="submit" id="user-form-submit" class="btn-primary">Save user</button>
      </div>
    </form>
  </div>
</div>
