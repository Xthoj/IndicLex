<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
}