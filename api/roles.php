<?php
/**
 * /api/roles.php
 * CRUD endpoint for role management.
 * 
 * GET  /api/roles.php                  → list all roles
 * GET  /api/roles.php?id=1             → get single role with permissions
 * POST /api/roles.php                  → create role
 * PUT  /api/roles.php                  → update role
 * DELETE /api/roles.php?id=1           → delete role
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/../auth/rbac.php';
require_once __DIR__ . '/../utils/api.php';

require_login_json();

// Role management is admin-only, whatever permissions a role has been granted.
if (!is_admin()) {
    json_error('Forbidden', 403);
}

$method = get_http_method();
$db = Connection::get_connecton();

try {
    if ($method === 'GET') {
        require_permission_json('Roles & Permissions', 'view');

        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];

            $stmt = $db->prepare('
                SELECT role_id, role_name, description, created_at, updated_at
                FROM dbo.ims_roles
                WHERE role_id = ?
            ');
            $stmt->execute([$id]);
            $role = $stmt->fetch();

            if (!$role) {
                json_error('Role not found', 404);
            }

            // Fetch permissions for this role
            $perms = $db->prepare('
                SELECT p.permission_id, p.module_name, p.action, p.description
                FROM dbo.ims_role_permissions rp
                JOIN dbo.ims_permissions p ON rp.permission_id = p.permission_id
                WHERE rp.role_id = ?
                ORDER BY p.module_name, p.action
            ');
            $perms->execute([$id]);

            $role['permissions'] = $perms->fetchAll();

            json_success($role);
        } else {
            // List all roles with user counts
            $stmt = $db->prepare('
                SELECT r.role_id, r.role_name, r.description, r.created_at, r.updated_at,
                       COUNT(u.user_id) as user_count
                FROM dbo.ims_roles r
                LEFT JOIN dbo.ims_users u ON r.role_id = u.role_id
                GROUP BY r.role_id, r.role_name, r.description, r.created_at, r.updated_at
                ORDER BY r.role_name
            ');
            $stmt->execute();

            json_success($stmt->fetchAll());
        }
    }

    elseif ($method === 'POST') {
        require_permission_json('Roles & Permissions', 'create');

        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $role_name = trim($data['role_name'] ?? '');
        $description = trim($data['description'] ?? '');
        $permission_ids = $data['permission_ids'] ?? [];

        if (!$role_name) {
            json_error('Missing required field: role_name', 400);
        }

        // Check if role name already exists
        $check = $db->prepare('SELECT 1 FROM dbo.ims_roles WHERE role_name = ?');
        $check->execute([$role_name]);
        if ($check->fetch()) {
            json_error('Role name already exists', 409);
        }

        $stmt = $db->prepare('
            INSERT INTO dbo.ims_roles (role_name, description)
            VALUES (?, ?)
        ');
        $stmt->execute([$role_name, $description ?: null]);

        $roleId = $db->lastInsertId();

        // Assign permissions if provided
        if (!empty($permission_ids)) {
            $permStmt = $db->prepare('INSERT INTO dbo.ims_role_permissions (role_id, permission_id) VALUES (?, ?)');
            foreach ($permission_ids as $permId) {
                $permStmt->execute([$roleId, (int)$permId]);
            }
        }

        $role = $db->prepare('SELECT * FROM dbo.ims_roles WHERE role_id = ?');
        $role->execute([$roleId]);

        json_success($role->fetch(), 'Role created successfully', 201);
    }

    elseif ($method === 'PUT') {
        require_permission_json('Roles & Permissions', 'edit');

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id'])) {
            json_error('Missing required field: id', 400);
        }

        $id = (int)$data['id'];

        $exists = $db->prepare('SELECT 1 FROM dbo.ims_roles WHERE role_id = ?');
        $exists->execute([$id]);
        if (!$exists->fetch()) {
            json_error('Role not found', 404);
        }

        $updates = [];
        $params = [];

        if (isset($data['role_name'])) {
            $updates[] = 'role_name = ?';
            $params[] = trim($data['role_name']);
        }
        if (isset($data['description'])) {
            $updates[] = 'description = ?';
            $params[] = trim($data['description']) ?: null;
        }

        // permission_ids-only updates (the matrix "Save changes" flow) are
        // valid too — don't require role_name/description to also be set.
        if (empty($updates) && !isset($data['permission_ids'])) {
            json_error('No fields to update', 400);
        }

        if (!empty($updates)) {
            $updates[] = 'updated_at = GETUTCDATE()';
            $params[] = $id;

            $sql = 'UPDATE dbo.ims_roles SET ' . implode(', ', $updates) . ' WHERE role_id = ?';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }

        // Update permissions if provided
        if (isset($data['permission_ids'])) {
            // Delete existing permissions
            $db->prepare('DELETE FROM dbo.ims_role_permissions WHERE role_id = ?')->execute([$id]);

            // Insert new permissions
            if (!empty($data['permission_ids'])) {
                $permStmt = $db->prepare('INSERT INTO dbo.ims_role_permissions (role_id, permission_id) VALUES (?, ?)');
                foreach ($data['permission_ids'] as $permId) {
                    $permStmt->execute([$id, (int)$permId]);
                }
            }
        }

        $role = $db->prepare('SELECT * FROM dbo.ims_roles WHERE role_id = ?');
        $role->execute([$id]);

        json_success($role->fetch(), 'Role updated successfully');
    }

    elseif ($method === 'DELETE') {
        require_permission_json('Roles & Permissions', 'delete');

        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            json_error('Missing required parameter: id', 400);
        }

        // Check if role is assigned to users
        $check = $db->prepare('SELECT COUNT(*) as count FROM dbo.ims_users WHERE role_id = ?');
        $check->execute([$id]);
        $result = $check->fetch();

        if ($result['count'] > 0) {
            json_error('Cannot delete role that is assigned to users', 409);
        }

        $stmt = $db->prepare('DELETE FROM dbo.ims_roles WHERE role_id = ?');
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            json_error('Role not found', 404);
        }

        json_success(null, 'Role deleted successfully');
    }

    else {
        json_error('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log('Roles API error: ' . $e->getMessage());
    json_error('Server error', 500);
}
