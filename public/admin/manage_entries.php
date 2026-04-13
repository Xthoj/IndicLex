<?php
require_once '../../includes/admin_auth.php';
require_once '../../config/database.php';

requireAdmin();

$dict_id = (int)($_GET['dict_id'] ?? 0);

if ($dict_id <= 0) {
    header('Location: manage_dictionaries.php?error=Please select a dictionary first');
    exit;
}

$stmtDict = $conn->prepare("
    SELECT dict_id, name
    FROM dictionaries
    WHERE dict_id = ?
");
$stmtDict->execute([$dict_id]);
$dictionary = $stmtDict->fetch(PDO::FETCH_ASSOC);

if (!$dictionary) {
    header('Location: manage_dictionaries.php?error=Dictionary not found');
    exit;
}

$stmt = $conn->prepare("
    SELECT
        entry_id,
        dict_id,
        lang_1,
        lang_2,
        lang_3,
        pronunciation,
        part_of_speech,
        example,
        notes,
        is_active,
        created_at
    FROM dictionary_entries
    WHERE dict_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$dict_id]);

$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Entries - IndicLex</title>

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

        .top-links {
            margin-bottom: 20px;
        }

        .top-links a {
            text-decoration: none;
            margin-right: 15px;
            color: #007bff;
            font-weight: bold;
        }

        .message-success {
            color: green;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .message-error {
            color: red;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .add-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .add-form input,
        .add-form textarea {
            width: 100%;
            max-width: 500px;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 15px;
        }

        .add-form button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
        }

        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .checkbox-row input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        table {
            background: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Entries</h1>
        <h3>Dictionary: <?php echo htmlspecialchars($dictionary['name']); ?></h3>

        <div class="top-links">
            <a href="manage_dictionaries.php">← Back to Manage Dictionaries</a>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <p class="message-success"><?php echo htmlspecialchars($_GET['success']); ?></p>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <p class="message-error"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <h2>Add New Entry</h2>

        <form action="entry_create.php" method="POST" class="add-form">
            <input type="hidden" name="dict_id" value="<?php echo $dictionary['dict_id']; ?>">

            <label>Language 1:</label><br>
            <input type="text" name="lang_1" required><br>

            <label>Language 2:</label><br>
            <input type="text" name="lang_2"><br>

            <label>Language 3:</label><br>
            <input type="text" name="lang_3"><br>

            <label>Pronunciation:</label><br>
            <input type="text" name="pronunciation"><br>

            <label>Part of Speech:</label><br>
            <input type="text" name="part_of_speech"><br>

            <label>Example:</label><br>
            <textarea name="example" rows="3"></textarea><br>

            <label>Notes:</label><br>
            <textarea name="notes" rows="3"></textarea><br>

            <div class="checkbox-row">
                <input type="checkbox" name="is_active" value="1" checked>
                <label style="margin: 0;">Active</label>
            </div>

            <button type="submit">Add Entry</button>
        </form>

        <table id="entriesTable" class="display">
            <thead>
                <tr>
                    <th>Entry ID</th>
                    <th>Language 1</th>
                    <th>Language 2</th>
                    <th>Language 3</th>
                    <th>Pronunciation</th>
                    <th>Part of Speech</th>
                    <th>Example</th>
                    <th>Notes</th>
                    <th>Active</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['entry_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['lang_1'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['lang_2'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['lang_3'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['pronunciation'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['part_of_speech'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['example'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['notes'] ?? ''); ?></td>
                        <td><?php echo !empty($row['is_active']) ? 'Yes' : 'No'; ?></td>
                        <td><?php echo htmlspecialchars($row['created_at'] ?? ''); ?></td>
                        <td>
                            <a href="entry_update.php?id=<?php echo $row['entry_id']; ?>&dict_id=<?php echo $row['dict_id']; ?>">Edit</a> |
                            <a href="entry_delete.php?id=<?php echo $row['entry_id']; ?>&dict_id=<?php echo $row['dict_id']; ?>"
                               onclick="return confirm('Are you sure you want to delete this entry?');">
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
            $('#entriesTable').DataTable();
        });
    </script>
</body>
</html>
=======
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
