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