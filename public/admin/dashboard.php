<?php
session_start();

require_once '../../config/database.php';
require_once '../../includes/admin_auth.php';
require_once '../../includes/admin_stats.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

requireAdmin();

$stats = getDashboardStats($conn);

if (!is_array($stats)) {
    $stats = [
        'total_dictionaries' => 0,
        'total_words' => 0,
        'words_per_dictionary' => []
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - IndicLex</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <style>
    .dashboard-wrap {
      width: 90%;
      max-width: 1100px;
      margin: 90px auto 60px;
    }
    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }
    .topbar h1 {
      margin: 0 0 4px;
      font-size: 1.6rem;
    }
    .topbar p {
      margin: 0;
      color: #6b7280;
      font-size: .95rem;
    }
    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-bottom: 32px;
    }
    .card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      padding: 22px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.07);
    }
    .card h2 {
      margin: 0 0 10px;
      font-size: 1rem;
      color: #6b7280;
    }
    .card p {
      font-size: 2rem;
      margin: 0;
      font-weight: bold;
      color: #007bff;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      box-shadow: 0 3px 10px rgba(0,0,0,0.07);
      border-radius: 8px;
      overflow: hidden;
      margin-top: 12px;
    }
    th, td {
      padding: 12px 16px;
      border-bottom: 1px solid #e5e7eb;
      text-align: left;
    }
    th {
      background: #f9fafb;
      font-size: .9rem;
      color: #374151;
    }
    .action-links {
      margin-top: 28px;
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
    }
    .btn-manage {
      display: inline-block;
      padding: 10px 18px;
      color: #fff;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 600;
    }
    .btn-blue { background: #007bff; }
    .btn-green { background: #28a745; }
  </style>
</head>
<body>

<header class="sticky-bg">
  <nav>
    <div class="nav-links">
      <i class="fa fa-bars" onclick="toggleMenu()"></i>
      <a href="../index.php" class="hero-link">IndicLex</a>
      <div id="nav-links-sub">
        <ul>
          <li><a href="../catalog.php">Catalog</a></li>
          <li><a href="../search.php">Search</a></li>
          <li><a href="../preferences.php">Preferences</a></li>
        </ul>
      </div>
      <div class="nav-right">
        <label class="theme-switch">
          <input type="checkbox" id="theme-toggle">
          <span class="slider"></span>
        </label>
        <a href="logout.php" class="sign-in-btn">Sign Out</a>
      </div>
    </div>
  </nav>
</header>

<div class="dashboard-wrap">
  <div class="topbar">
    <div>
      <h1>Admin Dashboard</h1>
      <p>Welcome, <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></p>
    </div>
  </div>

  <div class="cards">
    <div class="card">
      <h2>Total Dictionaries</h2>
      <p><?= htmlspecialchars($stats['total_dictionaries'] ?? 0) ?></p>
    </div>
    <div class="card">
      <h2>Total Words</h2>
      <p><?= htmlspecialchars($stats['total_words'] ?? 0) ?></p>
    </div>
  </div>

  <h2 style="font-size:1.15rem; margin-bottom:8px;">Words Per Dictionary</h2>
  <table>
    <thead>
      <tr>
        <th>Dictionary</th>
        <th>Word Count</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($stats['words_per_dictionary'])): ?>
        <?php foreach ($stats['words_per_dictionary'] as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['word_count'] ?? 0) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="2">No data available.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="action-links">
    <a href="manage_dictionaries.php" class="btn-manage btn-blue">Manage Dictionaries</a>
    <a href="manage_entries.php" class="btn-manage btn-green">Manage Entries</a>
  </div>
</div>

<script src="../../assets/js/script.js"></script>
</body>
</html>