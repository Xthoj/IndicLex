<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';

requireAdmin();

$stmt = $conn->query("
    SELECT dict_id, dict_identifier, name, type, source_lang_1, description, entry_count, is_active, created_at
    FROM dictionaries
    ORDER BY created_at DESC
");
$dictionaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<style>
    .container { width: 90%; max-width: 1200px; margin: 30px auto; }
    h1 { margin-bottom: 20px; }
    .top-links { margin-bottom: 20px; }
    .top-links a { text-decoration: none; margin-right: 15px; color: #007bff; font-weight: bold; }
    .add-form { background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
    .add-form input[type="text"], .add-form textarea { width: 100%; max-width: 500px; padding: 8px; margin-top: 5px; margin-bottom: 15px; box-sizing: border-box; }
    .add-form button { background: #28a745; color: white; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; }
    .checkbox-row { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
    .checkbox-row input[type="checkbox"] { width: auto; margin: 0; }
    table { background: white; }
    body.dark { background: #121212; color: white; }
    body.dark .add-form { background: #1e1e1e; color: white; }
    body.dark .add-form input[type="text"], body.dark .add-form textarea, body.dark .add-form select { background: #2d2d2d; color: white; border: 1px solid #444; }
    body.dark table { background: #1e1e1e; color: white; }
    body.dark th { background: #2d2d2d; color: white; }
    body.dark td { border-color: #444; color: white; }
    body.dark .top-links a { color: #60a5fa; }
</style>

<div class="container">
    <h1>Manage Dictionaries</h1>

    <div class="top-links">
        <a href="dashboard.php">← Back to Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>

    <h2>Create New Dictionary</h2>

    <form action="dictionary_create.php" method="POST" class="add-form">

        <label>Identifier:</label><br>
        <input type="text" name="dict_identifier" placeholder="e.g. eng-tel-001" required><br>

        <label>Name:</label><br>
        <input type="text" name="name" placeholder="e.g. English–Telugu Dictionary" required><br>

        <label>Type:</label><br>
        <select name="type" required style="width:100%; max-width:500px; padding:8px; margin-top:5px; margin-bottom:15px; box-sizing:border-box;">
            <option value="">-- Select type --</option>
            <option value="bilingual">Bilingual</option>
            <option value="trilingual">Trilingual</option>
        </select><br>

        <label>Source Language:</label><br>
        <input type="text" name="source_lang_1" placeholder="e.g. English" required><br>

        <label>Description:</label><br>
        <textarea name="description" rows="3" style="width:100%; max-width:500px; padding:8px; margin-top:5px; margin-bottom:15px; box-sizing:border-box;"></textarea><br>

        <div class="checkbox-row">
            <input type="checkbox" name="is_active" value="1" checked>
            <label style="margin:0;">Active</label>
        </div>

        <div style="display:flex; align-items:center; gap:12px; margin-top:4px;">
            <button type="submit">Create Dictionary</button>
            <a href="../upload_xlsx.php" style="background:#2563eb; color:white; padding:10px 16px; border-radius:6px; text-decoration:none; font-weight:bold;">Import from Excel</a>
        </div>
    </form>

    <h2 style="margin-top:2rem;">All Dictionaries</h2>

    <?php if (isset($_GET['success'])): ?>
        <p style="color:green; font-weight:bold"><?php echo htmlspecialchars($_GET['success']); ?></p>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <p style="color:red; font-weight:bold"><?php echo htmlspecialchars($_GET['error']); ?></p>
    <?php endif; ?>

    <table id="dictionaryTable" class="display">
        <thead>
            <tr>
                <th>#</th>
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
            <?php foreach ($dictionaries as $i => $row): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
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
                           onclick="return confirm('Are you sure you want to delete this dictionary?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function () { $('#dictionaryTable').DataTable(); });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
