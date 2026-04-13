<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/app.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>IndicLex</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"/>
</head>
<body>

<header class="sticky-bg">
  <nav>
    <div class="nav-links">
      <i class="fa fa-bars" onclick="toggleMenu()"></i>
      <a href="<?= BASE_URL ?>/public/index.php" class="hero-link">IndicLex</a>
      <div id="nav-links-sub">
       <ul>
  <li><a href="<?= BASE_URL ?>/public/catalog.php">Catalog</a></li>
  <li><a href="<?= BASE_URL ?>/public/search.php">Search</a></li>
  <li><a href="<?= BASE_URL ?>/public/preferences.php">Preferences</a></li>
   <?php if (isset($_SESSION['admin_id'])): ?>
    <li><a href="<?= BASE_URL ?>/public/admin/dashboard.php">Admin</a></li>
  <?php else: ?>
    <li><a href="<?= BASE_URL ?>/public/admin/login.php">Admin</a></li>
  <?php endif; ?>
 </ul>
      </div>
      <div class="nav-right">
        <label class="theme-switch">
          <input type="checkbox" id="theme-toggle">
          <span class="slider"></span>
        </label>
        

      </div>
    </div>
  </nav>
</header>
<main class="main-content">