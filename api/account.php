<?php
/**
 * /api/account.php
 * Self-service profile endpoint for the logged-in user — update your own
 * name and/or password. Deliberately not gated by any RBAC module
 * permission (unlike /api/users.php): every logged-in user can manage
 * their own account regardless of role, and this endpoint only ever
 * touches the current session's own user_id, never an arbitrary one.
 *
 * GET /api/account.php  → current user's profile (for the account modal)
 * PUT /api/account.php
 *   { first_name, last_name, current_password?, new_password? }
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/../auth/rbac.php';
require_once __DIR__ . '/../utils/api.php';

require_login();

$method = $_SERVER['REQUEST_METHOD'];
$db = Connection::get_connecton();
$currentUser = logged_in_user();
$userId = (int)$currentUser['id'];

if ($method === 'GET') {
    $stmt = $db->prepare('SELECT first_name, last_name FROM dbo.ims_users WHERE user_id = ?');
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();

    json_success([
        'first_name' => $profile['first_name'] ?? '',
        'last_name' => $profile['last_name'] ?? '',
        'email' => $currentUser['email'] ?? '',
        'role' => $currentUser['role'] ?? '',
    ]);
}

if ($method !== 'PUT') {
    json_error('Method not allowed', 405);
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$first_name = trim($data['first_name'] ?? '');
$last_name = trim($data['last_name'] ?? '');
$current_password = $data['current_password'] ?? '';
$new_password = $data['new_password'] ?? '';

if (!$first_name || !$last_name) {
    json_error('First name and last name are required', 400);
}

try {
    $updates = ['first_name = ?', 'last_name = ?'];
    $params = [$first_name, $last_name];

    if ($new_password !== '') {
        if (strlen($new_password) < 8) {
            json_error('New password must be at least 8 characters', 400);
        }

        $stmt = $db->prepare('SELECT password_hash FROM dbo.ims_users WHERE user_id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current_password, $row['password_hash'])) {
            json_error('Current password is incorrect', 400);
        }

        $updates[] = 'password_hash = ?';
        $params[] = password_hash($new_password, PASSWORD_BCRYPT);
    }

    $updates[] = 'updated_at = GETUTCDATE()';
    $params[] = $userId;

    $sql = 'UPDATE dbo.ims_users SET ' . implode(', ', $updates) . ' WHERE user_id = ?';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    // Keep the session in sync so the topbar/initials reflect the change
    // immediately, without requiring the user to log out and back in.
    $_SESSION['user']['name'] = $first_name . ' ' . $last_name;
    $_SESSION['user']['initials'] = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

    json_success([
        'name' => $_SESSION['user']['name'],
        'initials' => $_SESSION['user']['initials'],
    ], 'Account updated successfully');

} catch (Exception $e) {
    error_log('Account API error: ' . $e->getMessage());
    json_error('Server error', 500);
}
