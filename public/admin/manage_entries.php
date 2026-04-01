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
            width: 95%;
            max-width: 1400px;
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
        <h1>Manage Entries</h1>

        <div class="top-links">
            <a href="dashboard.php">← Back to Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>

        <table id="entriesTable" class="display">
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
                        <td><?php echo htmlspecialchars($row['entry_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['dictionary_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['lang_1'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['lang_2'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['lang_3'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['pronunciation'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['part_of_speech'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['example'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['notes'] ?? ''); ?></td>
                        <td><?php echo !empty($row['is_active']) ? 'Yes' : 'No'; ?></td>
                        <td><?php echo htmlspecialchars($row['created_at'] ?? ''); ?></td>
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