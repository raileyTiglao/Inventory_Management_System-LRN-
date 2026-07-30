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
        header('Location: ' . BASE_URL . '/pages/login/index.php');
        exit;
    }
}

/**
 * Deny all accounts access to User and Perms, except admin
 */
const ADMIN_ROLE_NAME = 'Administrator';

function is_admin(): bool
{
    if (!is_logged_in()) {
        return false;
    }

    $user = logged_in_user();
    if (!$user) {
        return false;
    } 

    if (!empty($user['role'])) {
        return strcasecmp($user['role'], ADMIN_ROLE_NAME) === 0;
    }

    // Session predates the 'role' key — fall back to the role table.
    if (!isset($user['role_id'])) {
        return false;
    }

    try {
        $stmt = Connection::get_connecton()->prepare('SELECT role_name FROM dbo.ims_roles WHERE role_id = ?');
        $stmt->execute([$user['role_id']]);
        $role = $stmt->fetch();

        return $role && strcasecmp($role['role_name'], ADMIN_ROLE_NAME) === 0;
    } catch (Exception $e) {
        error_log("Admin check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Require the Administrator role; redirect to 403 otherwise.
 */
function require_admin(): void
{
    require_login();

    if (!is_admin()) {
        http_response_code(403);
        header('Location: ' . BASE_URL . '/pages/403.php');
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
        $db = Connection::get_connecton();

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
