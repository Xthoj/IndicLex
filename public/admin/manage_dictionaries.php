<?php
require_once '../../includes/admin_auth.php';
require_once '../../config/database.php';

requireAdmin();

$stmt = $conn->query("
    SELECT dict_id, dict_identifier, name, type, source_lang_1, description, entry_count, is_active, created_at
    FROM dictionaries
    ORDER BY created_at DESC
");

$dictionaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Dictionaries – IndicLex</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="../../assets/css/style.css"/>
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"/>

  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

  <style>
    .page-wrap {
      width: 90%;
      max-width: 1200px;
      margin: 90px auto 60px;
    }
    .page-wrap h1 { margin-bottom: 24px; font-size: 1.6rem; }
    table.dataTable { background: var(--bg, #fff); }
    body.dark table.dataTable { color: var(--text); }
    body.dark .dataTables_wrapper { color: var(--text); }
    body.dark input[type="search"] { background: #2a2a2a; color: #eee; border-color: #444; }
  </style>
</head>
<body>

<!-- Shared Navbar -->
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

<!-- Page Content -->
<div class="page-wrap">
  <h1>Manage Dictionaries</h1>

  <table id="dictionaryTable" class="display" style="width:100%">
    <thead>
      <tr>
        <th>ID</th>
        <th>Identifier</th>
        <th>Name</th>
        <th>Type</th>
        <th>Source Language</th>
        <th>Description</th>
        <th>Entry Count</th>
        <th>Active</th>
        <th>Created At</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($dictionaries as $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['dict_id']) ?></td>
          <td><?= htmlspecialchars($row['dict_identifier'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['name'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['type'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['source_lang_1'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['description'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['entry_count'] ?? '0') ?></td>
          <td><?= !empty($row['is_active']) ? 'Yes' : 'No' ?></td>
          <td><?= htmlspecialchars($row['created_at'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
  $(document).ready(function () {
    $('#dictionaryTable').DataTable();
  });
</script>
<script src="../../assets/js/script.js"></script>
</body>
</html>
