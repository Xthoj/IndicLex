<?php
require_once '../../includes/admin_auth.php';
require_once '../../config/database.php';

requireAdmin();

$stmt = $conn->query("
    SELECT
        dictionary_entries.entry_id,
        dictionary_entries.dict_id,
        dictionaries.name AS dictionary_name,
        dictionary_entries.lang_1,
        dictionary_entries.lang_2,
        dictionary_entries.lang_3,
        dictionary_entries.pronunciation,
        dictionary_entries.part_of_speech,
        dictionary_entries.example,
        dictionary_entries.notes,
        dictionary_entries.is_active,
        dictionary_entries.created_at
    FROM dictionary_entries
    LEFT JOIN dictionaries
        ON dictionary_entries.dict_id = dictionaries.dict_id
    ORDER BY dictionary_entries.created_at DESC
");

$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Entries – IndicLex</title>

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
      width: 95%;
      max-width: 1400px;
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
  <h1>Manage Entries</h1>

  <table id="entriesTable" class="display" style="width:100%">
    <thead>
      <tr>
        <th>Entry ID</th>
        <th>Dictionary</th>
        <th>Language 1</th>
        <th>Language 2</th>
        <th>Language 3</th>
        <th>Pronunciation</th>
        <th>Part of Speech</th>
        <th>Example</th>
        <th>Notes</th>
        <th>Active</th>
        <th>Created At</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($entries as $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['entry_id']) ?></td>
          <td><?= htmlspecialchars($row['dictionary_name'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['lang_1'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['lang_2'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['lang_3'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['pronunciation'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['part_of_speech'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['example'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['notes'] ?? '') ?></td>
          <td><?= !empty($row['is_active']) ? 'Yes' : 'No' ?></td>
          <td><?= htmlspecialchars($row['created_at'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
  $(document).ready(function () {
    $('#entriesTable').DataTable();
  });
</script>
<script src="../../assets/js/script.js"></script>
</body>
</html>
