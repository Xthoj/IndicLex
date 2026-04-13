<?php
<<<<<<< HEAD
if (session_status() === PHP_SESSION_NONE) {
    session_start();
=======
/**
 * includes/admin_auth.php
 * Starts a persistent session and provides requireAdmin() guard.
 * The session cookie lasts 7 days so the admin stays logged in
 * across browser restarts until they explicitly log out.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Keep the session alive for 7 days
    $lifetime = 7 * 24 * 60 * 60; // 604800 seconds

    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path'     => '/',
        'secure'   => false,   // set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    // Refresh the cookie expiry on every request so activity resets the timer
    if (isset($_SESSION['admin_id'])) {
        setcookie(session_name(), session_id(), [
            'expires'  => time() + $lifetime,
            'path'     => '/',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
>>>>>>> 50c55f8a008be9bcda28bc86fc01a2fe49e49c16
}

function requireAdmin()
{
    if (
        !isset($_SESSION['admin_id']) ||
        !isset($_SESSION['admin_role']) ||
        $_SESSION['admin_role'] !== 'admin'
    ) {
        header('Location: login.php');
        exit;
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> 50c55f8a008be9bcda28bc86fc01a2fe49e49c16
