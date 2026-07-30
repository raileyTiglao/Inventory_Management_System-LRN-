<?php
/**
 * /api/users.php
 * CRUD endpoint for user management.
 * 
 * GET  /api/users.php                  → list all users
 * GET  /api/users.php?id=1             → get single user
 * POST /api/users.php                  → create user
 * PUT  /api/users.php                  → update user (requires JSON body with 'id')
 * DELETE /api/users.php?id=1           → delete user
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/../auth/rbac.php';
require_once __DIR__ . '/../utils/api.php';

require_login();

$method = $_SERVER['REQUEST_METHOD'];
$db = Connection::get_connecton();

try {
    if ($method === 'GET') {
        // List all users or get single user
        require_permission('Users', 'view');

        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $db->prepare('
                SELECT u.user_id, u.email, u.first_name, u.last_name, u.status, 
                       u.last_login, u.created_at, r.role_name, r.role_id
                FROM dbo.ims_users u
                JOIN dbo.ims_roles r ON u.role_id = r.role_id
                WHERE u.user_id = ?
            ');
            $stmt->execute([$id]);
            $user = $stmt->fetch();

            if (!$user) {
                json_error('User not found', 404);
            }

            json_success($user);
        } else {
            // List all users with pagination
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            $search = trim($_GET['q'] ?? '');

            $where = '';
            $likeParams = [];
            if ($search !== '') {
                $where = 'WHERE u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR (u.first_name + \' \' + u.last_name) LIKE ?';
                $like = '%' . str_replace(['%', '_'], ['[%]', '[_]'], $search) . '%';
                $likeParams = [$like, $like, $like, $like];
            }

            $stmt = $db->prepare("
                SELECT u.user_id, u.email, u.first_name, u.last_name, u.status,
                       u.last_login, u.created_at, r.role_name, r.role_id,
                       COUNT(*) OVER() as total_count
                FROM dbo.ims_users u
                JOIN dbo.ims_roles r ON u.role_id = r.role_id
                $where
                ORDER BY u.created_at DESC
                OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
            ");
            $paramIndex = 1;
            foreach ($likeParams as $p) {
                $stmt->bindValue($paramIndex++, $p, PDO::PARAM_STR);
            }
            $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
            $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $users = $stmt->fetchAll();

            $total = $users[0]['total_count'] ?? 0;
            json_success([
                'users' => $users,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ]);
        }
    }

    elseif ($method === 'POST') {
        require_permission('Users', 'create');

        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $email = trim($data['email'] ?? '');
        $first_name = trim($data['first_name'] ?? '');
        $last_name = trim($data['last_name'] ?? '');
        $password = $data['password'] ?? '';
        $role_id = (int)($data['role_id'] ?? 0);
        $status = $data['status'] ?? 'active';

        if (!$email || !$first_name || !$last_name || !$password || !$role_id) {
            json_error('Missing required fields: email, first_name, last_name, password, role_id', 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_error('Invalid email format', 400);
        }

        // Check if email already exists
        $check = $db->prepare('SELECT 1 FROM dbo.ims_users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            json_error('Email already in use', 409);
        }

        // Check role exists
        $roleCheck = $db->prepare('SELECT 1 FROM dbo.ims_roles WHERE role_id = ?');
        $roleCheck->execute([$role_id]);
        if (!$roleCheck->fetch()) {
            json_error('Role not found', 404);
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $db->prepare('
            INSERT INTO dbo.ims_users (email, first_name, last_name, password_hash, role_id, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$email, $first_name, $last_name, $password_hash, $role_id, $status]);

        $lastId = $db->lastInsertId();
        $user = $db->prepare('
            SELECT u.user_id, u.email, u.first_name, u.last_name, u.status,
                   u.last_login, u.created_at, r.role_name, r.role_id
            FROM dbo.ims_users u
            JOIN dbo.ims_roles r ON u.role_id = r.role_id
            WHERE u.user_id = ?
        ');
        $user->execute([$lastId]);

        json_success($user->fetch(), 'User created successfully', 201);
    }

    elseif ($method === 'PUT') {
        require_permission('Users', 'edit');

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id'])) {
            json_error('Missing required field: id', 400);
        }

        $id = (int)$data['id'];
        $updates = [];
        $params = [];

        if (isset($data['first_name'])) {
            $updates[] = 'first_name = ?';
            $params[] = trim($data['first_name']);
        }
        if (isset($data['last_name'])) {
            $updates[] = 'last_name = ?';
            $params[] = trim($data['last_name']);
        }
        if (isset($data['role_id'])) {
            $updates[] = 'role_id = ?';
            $params[] = (int)$data['role_id'];
        }
        if (isset($data['status'])) {
            $updates[] = 'status = ?';
            $params[] = $data['status'];
        }
        if (isset($data['password'])) {
            $updates[] = 'password_hash = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($updates)) {
            json_error('No fields to update', 400);
        }

        $updates[] = 'updated_at = GETUTCDATE()';
        $params[] = $id;

        $sql = 'UPDATE dbo.ims_users SET ' . implode(', ', $updates) . ' WHERE user_id = ?';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            json_error('User not found or no changes made', 404);
        }

        $user = $db->prepare('
            SELECT u.user_id, u.email, u.first_name, u.last_name, u.status,
                   u.last_login, u.created_at, r.role_name, r.role_id
            FROM dbo.ims_users u
            JOIN dbo.ims_roles r ON u.role_id = r.role_id
            WHERE u.user_id = ?
        ');
        $user->execute([$id]);

        json_success($user->fetch(), 'User updated successfully');
    }

    elseif ($method === 'DELETE') {
        require_permission('Users', 'delete');

        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            json_error('Missing required parameter: id', 400);
        }

        $currentUser = logged_in_user();
        if ($currentUser && (int)$currentUser['id'] === $id) {
            json_error('You cannot delete your own account', 409);
        }

        try {
            $stmt = $db->prepare('DELETE FROM dbo.ims_users WHERE user_id = ?');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                json_error('Cannot delete this user: they have recorded stock movements.', 409);
            }
            throw $e;
        }

        if ($stmt->rowCount() === 0) {
            json_error('User not found', 404);
        }

        json_success(null, 'User deleted successfully');
    }

    else {
        json_error('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log('Users API error: ' . $e->getMessage());
    json_error('Server error', 500);
}
