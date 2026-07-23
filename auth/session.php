<?php
/**
 * Central session bootstrap.
 * Uses a custom session_name so the IMS session cookie doesn't collide
 * with other PHP apps running on the same XAMPP instance/domain.
 * Included by auth/login.php, auth/logout.php, and auth/guard.php.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_name('ims_session');
    session_start();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function logged_in_user(): ?array
{
    return $_SESSION['user'] ?? null;
}
