<?php
define('BASE_URL', '/ims');

/**
 * Which URL style url() emits for internal links:
 *   false → /ims/index.php?page=dashboard   (no server modules required)
 *   true  → /ims/dashboard                  (needs mod_rewrite / IIS URL Rewrite)
 *
 * The router ACCEPTS both styles either way — this only controls which one
 * the app generates. Leave it false until the IIS URL Rewrite module is
 * confirmed installed on the production host, then flip it to true; no other
 * code has to change.
 */
define('CLEAN_URLS', false);

define('APP_NAME', 'IMS');
define('SYSTEM_CODE', 'ims');
