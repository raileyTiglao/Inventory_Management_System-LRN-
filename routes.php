<?php
/**
 * Route table for the front controller (index.php).
 *
 * Keys are clean paths relative to BASE_URL; values are the file that
 * handles them, relative to the project root. A value can be either:
 *
 *   'path' => 'file.php'                        — any HTTP method
 *   'path' => ['GET' => 'a.php', 'POST' => 'b'] — dispatched per method
 *
 * The api/* entries are aliases: the frontend still calls /api/<name>.php
 * directly (those files exist on disk, so the rewrite serves them without
 * involving this table), but the extensionless form resolves here too.
 */

return [
    // Pages
    'login'        => [
        'GET'  => 'pages/login/index.php',
        'POST' => 'auth/login.php',
    ],
    'logout'       => 'auth/logout.php',
    'dashboard'    => 'pages/dashboard/index.php',
    'inventory'    => 'pages/inventory/index.php',
    'users'        => 'pages/users/index.php',
    'roles'        => 'pages/roles/index.php',
    'activity-log' => 'pages/activity-log/index.php',

    // Error pages — reachable directly so the RBAC guards can redirect here.
    '403'          => 'pages/403.php',
    '404'          => 'pages/404.php',

    // API aliases
    'api/account'         => 'api/account.php',
    'api/inventory'       => 'api/inventory.php',
    'api/permissions'     => 'api/permissions.php',
    'api/roles'           => 'api/roles.php',
    'api/stock_movements' => 'api/stock_movements.php',
    'api/users'           => 'api/users.php',
];
