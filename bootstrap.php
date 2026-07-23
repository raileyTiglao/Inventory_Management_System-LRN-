<?php
/**
 * Shared bootstrap included by every page.
 * Session/RBAC guards will be added here in the auth implementation phase.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils/helpers.php';
require_once __DIR__ . '/utils/icons.php';

// session_name will be set explicitly once auth/session.php is implemented,
// e.g. session_name('ims_session'); session_start();
