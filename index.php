<!DOCTYPE html>
<html lang="en">
<head>
  <title>IndicLex</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
   
  <link rel="stylesheet" href="style.css" />
  <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"
    />
</head>

<body>

<header class="sticky-bg">
     <nav>
    <div class="nav-links">

      <i class="fa fa-bars" onclick="toggleMenu()"></i>

      <a href="#" class="hero-link">IndicLex</a>

      <div id="nav-links-sub">
        <ul>
          <li><a href="catalog.php">Catalog</a></li>
          <li><a href="search.php">Search</a></li>
          <li><a href="preferences.php">Preferences</a></li>
        </ul>
      </div>

      <!-- RIGHT SIDE BUTTONS -->
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


  <div class="hero-image">
    <div class="hero-text">
      <h1>IndicLexs</h1>
    </div>
  </div>



  <section id="about">
  <div class="about-us-container">
    <div class="about-us-row reveal">
      <div class="about-us-col">
        <h3>about us</h3>
        <p>
          Lorem Ipsum is simply dummy text of the printing and typesetting
          industry. Lorem Ipsum has been the industry's standard dummy text
          ever since the 1500s, when an unknown printer took a galley of
          type and scrambled it to make a type specimen book.
        </p>
      </div>
      <div class="about-us-col">
        <h3>our goal</h3>
        <p>
          Lorem Ipsum is simply dummy text of the printing and typesetting
          industry. Lorem Ipsum has been the industry's standard dummy text
          ever since the 1500s, when an unknown printer took a galley of
          type and scrambled it to make a type specimen book.
        </p>
      </div>
      <div class="about-us-col">
        <h3>a call away</h3>
        <p>
          Lorem Ipsum is simply dummy text of the printing and typesetting
          industry. Lorem Ipsum has been the industry's standard dummy text
          ever since the 1500s, when an unknown printer took a galley of
          type and scrambled it to make a type specimen book.
        </p>
      </div>
    </div>
  </div>
</section>

<div class="footer">
  <div class="footer-content">
    <p>© 2026 IndicLex. All rights reserved. Developed by Team Dolphins</p>
  </div>
</div>
<script src="script.js"></script>
</body>