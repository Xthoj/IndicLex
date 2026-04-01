<!DOCTYPE html>
<html lang="en">
<head>
  <title>IndicLex</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"/>
</head>
<body>

<header class="sticky-bg">
  <nav>
    <div class="nav-links">
      <i class="fa fa-bars" onclick="toggleMenu()"></i>
      <a href="index.php" class="hero-link">IndicLex</a>
      <div id="nav-links-sub">
       <ul>
  <li><a href="catalog.php">Catalog</a></li>
  <li><a href="search.php">Search</a></li>
  <li><a href="preferences.php">Preferences</a></li>
  <li><a href="admin/login.php">Admin</a></li>
 </ul>
      </div>
      <div class="nav-right">
        <label class="theme-switch">
          <input type="checkbox" id="theme-toggle">
          <span class="slider"></span>
        </label>
        <a href="login.php" class="sign-in-btn">Sign In</a>
      </div>
    </div>
  </nav>
</header>