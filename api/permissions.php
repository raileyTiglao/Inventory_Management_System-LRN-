<?php
/**
 * /api/permissions.php
 * Read-only endpoint to list all available permissions.
 * 
 * GET  /api/permissions.php                   → list all permissions grouped by module
 * GET  /api/permissions.php?role_id=2         → list permissions for a specific role
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/../auth/rbac.php';
require_once __DIR__ . '/../utils/api.php';

require_login();

$db = get_db();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_error('Method not allowed', 405);
    }

    require_permission('Roles & Permissions', 'view');

    if (isset($_GET['role_id'])) {
        // Get permissions for a specific role
        $role_id = (int)$_GET['role_id'];

        $stmt = $db->prepare('
            SELECT p.permission_id, p.module_name, p.action, p.description,
                   CASE WHEN rp.role_permission_id IS NOT NULL THEN 1 ELSE 0 END as assigned
            FROM dbo.ims_permissions p
            LEFT JOIN dbo.ims_role_permissions rp ON p.permission_id = rp.permission_id AND rp.role_id = ?
            ORDER BY p.module_name, p.action
        ');
        $stmt->execute([$role_id]);

        $permissions = $stmt->fetchAll();

        // Group by module
        $grouped = [];
        foreach ($permissions as $perm) {
            $module = $perm['module_name'];
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $perm;
        }

        json_success(['permissions' => $grouped, 'role_id' => $role_id]);
    } else {
        // List all permissions grouped by module
        $stmt = $db->prepare('
            SELECT permission_id, module_name, action, description
            FROM dbo.ims_permissions
            ORDER BY module_name, action
        ');
        $stmt->execute();

        $permissions = $stmt->fetchAll();

        // Group by module
        $grouped = [];
        foreach ($permissions as $perm) {
            $module = $perm['module_name'];
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $perm;
        }

        json_success(['permissions' => $grouped, 'total' => count($permissions)]);
    }

} catch (Exception $e) {
    error_log('Permissions API error: ' . $e->getMessage());
    json_error('Server error', 500);
}
