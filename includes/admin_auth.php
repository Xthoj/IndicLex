<?php
/**
 * includes/admin_auth.php
 * Starts a persistent session and provides requireAdmin() guard.
 * The session cookie lasts 7 days so the admin stays logged in
 * across browser restarts until they explicitly log out.
 */

if (session_status() === PHP_SESSION_NONE) {
    $lifetime = 7 * 24 * 60 * 60; // 7 days

    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    if (isset($_SESSION['admin_id'])) {
        setcookie(session_name(), session_id(), [
            'expires' => time() + $lifetime,
            'path' => '/',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

function requireAdmin(): void
{
    if (
        !isset($_SESSION['admin_id']) ||
        !isset($_SESSION['admin_role']) ||
        $_SESSION['admin_role'] !== 'admin'
    ) {
        header('Location: /IndicLex-main/public/admin/login.php');
        exit;
    }
}