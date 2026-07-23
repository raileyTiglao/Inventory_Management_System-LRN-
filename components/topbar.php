<?php
  require_once __DIR__ . '/../auth/session.php';
  $user = logged_in_user() ?? [
      'name' => 'Unknown',
      'role' => 'User',
      'initials' => '??',
  ];
?>
<header class="h-16 border-b border-border flex items-center justify-between px-6 sticky top-0 bg-base/95 backdrop-blur z-10">
  <div>
    <p class="eyebrow"><?= e($pageEyebrow ?? 'IMS') ?></p>
    <h1 class="font-display font-semibold text-lg text-ink -mt-0.5"><?= e($pageTitle ?? '') ?></h1>
  </div>

  <div class="flex items-center gap-4">
    <img
      src="<?= BASE_URL ?>/assets/icons/logo.png"
      alt="Powered by Information Technology"
      class="hidden lg:block h-7 w-auto opacity-60 hover:opacity-100 transition-opacity"
      style="filter: invert(1);"
    >

    <div class="search-wrap hidden md:block w-56">
      <span class="search-icon">
        <?= icon('search', 'w-4 h-4') ?>
      </span>
      <input type="text" placeholder="Search..." class="search-input w-full">
    </div>

    <button class="relative w-9 h-9 flex items-center justify-center rounded-tag border border-border hover:border-tag-amber/50 text-ink-muted hover:text-tag-amber transition-colors">
      <?= icon('bell', 'w-4.5 h-4.5') ?>
      <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 rounded-full bg-tag-amber"></span>
    </button>

    <div class="flex items-center gap-2.5 pl-3 border-l border-border">
      <div id="topbar-user-initials" class="w-8 h-8 rounded-tag bg-tag-amber/15 border border-tag-amber/30 flex items-center justify-center font-mono text-xs text-tag-amber">
        <?= e($user['initials'] ?? '??') ?>
      </div>
      <div class="hidden sm:block leading-tight">
        <p id="topbar-user-name" class="text-sm text-ink"><?= e($user['name'] ?? 'User') ?></p>
        <p class="text-[11px] text-ink-dim"><?= e($user['role'] ?? 'Role') ?></p>
      </div>
    </div>
  </div>
</header>
