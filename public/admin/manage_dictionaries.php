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
    table { background: white; }
    body.dark { background: #121212; color: white; }
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

    <?php if (isset($_GET['success'])): ?>
        <p style="color:green; font-weight:bold"><?php echo htmlspecialchars($_GET['success']); ?></p>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <p style="color:red; font-weight:bold"><?php echo htmlspecialchars($_GET['error']); ?></p>
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
