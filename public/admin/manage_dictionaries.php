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
<<<<<<< HEAD

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Dictionaries - IndicLex</title>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 30px auto;
        }

        h1 {
            margin-bottom: 20px;
        }

        .top-links {
            margin-bottom: 20px;
        }

        .top-links a {
            text-decoration: none;
            margin-right: 15px;
            color: #007bff;
            font-weight: bold;
        }

        table {
            background: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Dictionaries</h1>

        <div class="top-links">
            <a href="dashboard.php">← Back to Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
        <?php if (isset($_GET['success'])): ?>
            <p class="message-success"><?php echo htmlspecialchars($_GET['success']); ?></p>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <p class="message-error"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <table id="dictionaryTable" class="display">
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    <?php foreach ($dictionaries as $row): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['dict_id']); ?></td>
            <td><?php echo htmlspecialchars($row['dict_identifier'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($row['type'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($row['source_lang_1'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($row['description'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($row['entry_count'] ?? '0'); ?></td>
            <td><?php echo !empty($row['is_active']) ? 'Yes' : 'No'; ?></td>
            <td><?php echo htmlspecialchars($row['created_at'] ?? ''); ?></td>
           <td>
    <a href="dictionary_update.php?id=<?php echo $row['dict_id']; ?>">Edit</a> |

    <a href="manage_entries.php?dict_id=<?php echo $row['dict_id']; ?>">Manage Entries</a> |

    <a href="dictionary_delete.php?id=<?php echo $row['dict_id']; ?>"
       onclick="return confirm('Are you sure you want to delete this dictionary?');">
        Delete
    </a>
</td>
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
</body>
</html>
=======
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
>>>>>>> 50c55f8a008be9bcda28bc86fc01a2fe49e49c16
