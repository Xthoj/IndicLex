    <?php
require_once '../../includes/admin_auth.php';
require_once '../../includes/admin_stats.php';
require_once '../../config/database.php';

requireAdmin();

$stats = getDashboardStats($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IndicLex</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
        }

        .container {
            width: 90%;
            max-width: 1100px;
            margin: 30px auto;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .card h2 {
            margin: 0 0 10px;
            font-size: 18px;
        }

        .card p {
            font-size: 30px;
            margin: 0;
            font-weight: bold;
            color: #007bff;
        }

        .logout {
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f2f2f2;
        }

        .action-links {
            margin-top: 30px;
        }

        .action-links a {
            display: inline-block;
            margin-right: 15px;
            padding: 10px 16px;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        .btn-blue {
            background: #007bff;
        }

        .btn-green {
            background: #28a745;
        }
    </style>
</head>
<body>

<div class="container">

    <div class="topbar">
        <div>
            <h1>IndicLex Admin Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
        </div>

        <div>
            <a class="logout" href="../index.php" style="margin-right:15px;">← Back to home</a>
            <a class="logout" href="logout.php">Logout</a>
        </div>
    </div>

    <div class="cards">
        <div class="card">
            <h2>Total Dictionaries</h2>
            <p><?php echo htmlspecialchars($stats['total_dictionaries']); ?></p>
        </div>

        <div class="card">
            <h2>Total Words</h2>
            <p><?php echo htmlspecialchars($stats['total_words']); ?></p>
        </div>
    </div>

    <h2>Words Per Dictionary</h2>

    <table>
        <thead>
            <tr>
                <th>Dictionary</th>
                <th>Word Count</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($stats['words_per_dictionary'] as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['word_count']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="action-links">
        <a href="manage_dictionaries.php" class="btn-blue">Manage Dictionaries</a>
        <a href="manage_entries.php" class="btn-green">Manage Entries</a>
    </div>

</div>

</body>
</html>