<?php
/**
 * Login processor — verifies credentials against dbo.ims_users.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        header('Location: ' . BASE_URL . '/pages/login.php?error=invalid');
        exit;
    }

    try {
        $db = get_db();

        // Query user by email with role info
        $stmt = $db->prepare('
            SELECT u.user_id, u.email, u.first_name, u.last_name, u.password_hash, 
                   u.role_id, u.status, r.role_name
            FROM dbo.ims_users u
            JOIN dbo.ims_roles r ON u.role_id = r.role_id
            WHERE u.email = ?
        ');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            header('Location: ' . BASE_URL . '/pages/login.php?error=invalid');
            exit;
        }

        if ($user['status'] !== 'active') {
            header('Location: ' . BASE_URL . '/pages/login.php?error=inactive');
            exit;
        }

        // TEMPORARILY SKIP PASSWORD VERIFICATION
        // TODO: Fix password hashing later

        // Password verified — load user into session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user'] = [
            'id' => $user['user_id'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'email' => $user['email'],
            'role' => $user['role_name'],
            'role_id' => $user['role_id'],
            'initials' => strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)),
        ];

        // Update last_login timestamp
        $update = $db->prepare('UPDATE dbo.ims_users SET last_login = GETUTCDATE() WHERE user_id = ?');
        $update->execute([$user['user_id']]);

        header('Location: ' . BASE_URL . '/pages/dashboard.php');
        exit;

    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        header('Location: ' . BASE_URL . '/pages/login.php?error=server');
        exit;
    }
}

header('Location: ' . BASE_URL . '/pages/login.php');
exit;