<?php
/**
 * Login processor — verifies credentials against dbo.ims_users.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../connection/db.php';
require_once __DIR__ . '/session.php';

// Login rate limiting — lightweight/session-based for now (testing only;
// resets if the browser session is cleared, and isn't shared across
// browsers). Revisit with a DB- or IP-based store before relying on this.
const LOGIN_ATTEMPT_LIMIT = 3;
const LOGIN_LOCKOUT_SECONDS = 10;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lockedUntil = $_SESSION['login_locked_until'] ?? 0;
    if ($lockedUntil > time()) {
        header('Location: ' . url('login', ['error' => 'locked']));
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        header('Location: ' . url('login', ['error' => 'invalid']));
        exit;
    }

    try {
        $db = Connection::get_connecton();

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
            register_failed_login_attempt();
            header('Location: ' . url('login', ['error' => 'invalid']));
            exit;
        }

        if ($user['status'] !== 'active') {
            header('Location: ' . url('login', ['error' => 'inactive']));
            exit;
        }

        if (!password_verify($password, $user['password_hash'])) {
            register_failed_login_attempt();
            header('Location: ' . url('login', ['error' => 'invalid']));
            exit;
        }

        // Password verified — load user into session
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_locked_until'] = 0;
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

        header('Location: ' . url('dashboard'));
        exit;

    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        header('Location: ' . url('login', ['error' => 'server']));
        exit;
    }
}

function register_failed_login_attempt(): void
{
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] >= LOGIN_ATTEMPT_LIMIT) {
        $_SESSION['login_locked_until'] = time() + LOGIN_LOCKOUT_SECONDS;
        $_SESSION['login_attempts'] = 0;
    }
}

header('Location: ' . url('login'));
exit;