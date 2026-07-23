<?php
/**
 * RBAC guard.
 * Checks permissions against dbo.ims_role_permissions.
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../connection/db.php';

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/pages/login.php');
        exit;
    }
}

/**
 * Check if the logged-in user has a specific permission.
 * Usage: require_permission('Users', 'edit');
 * Module names must match dbo.ims_permissions.module_name exactly.
 */
function has_permission(string $module, string $action): bool
{
    if (!is_logged_in()) {
        return false;
    }

    $user = logged_in_user();
    if (!$user || !isset($user['role_id'])) {
        return false;
    }

    try {
        $db = get_db();
        
        $stmt = $db->prepare('
            SELECT COUNT(*) as count FROM dbo.ims_role_permissions rp
            JOIN dbo.ims_permissions p ON rp.permission_id = p.permission_id
            WHERE rp.role_id = ? AND p.module_name = ? AND p.action = ?
        ');
        $stmt->execute([$user['role_id'], $module, $action]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    } catch (Exception $e) {
        error_log("Permission check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Require a specific permission; redirect to 403 if not granted.
 * Usage: require_permission('Users', 'edit');
 */
function require_permission(string $module, string $action): void
{
    require_login();

    if (!has_permission($module, $action)) {
        http_response_code(403);
        header('Location: ' . BASE_URL . '/pages/403.php');
        exit;
    }
}
