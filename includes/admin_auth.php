<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';

function requireAdmin()
{
    if (
        !isset($_SESSION['admin_id']) ||
        !isset($_SESSION['admin_role']) ||
        $_SESSION['admin_role'] !== 'admin'
    ) {
        header('Location: ' . BASE_URL . '/public/admin/login.php');
        exit;
    }
}