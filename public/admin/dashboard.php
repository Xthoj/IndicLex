<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_stats.php';

requireAdmin();

$stats = getDashboardStats($conn);

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
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

    .dashboard-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        margin-top: 15px;
    }

    .dashboard-table th,
    .dashboard-table td {
        padding: 12px;
        border: 1px solid #ddd;
        text-align: left;
    }

    .dashboard-table th {
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

    .btn-blue  { background: #007bff; }
    .btn-green { background: #28a745; }

    body.dark .card            { background: #1e1e1e; color: white; }
    body.dark .dashboard-table { background: #1e1e1e; color: white; }
    body.dark .dashboard-table th { background: #2d2d2d; color: white; }
    body.dark .dashboard-table td { border-color: #444; }
</style>

<div class="container">
    <div class="topbar">
        <div>
            <h1>Admin Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
        </div>
        <div>
            <a class="logout" href="<?= BASE_URL ?>/public/index.php" style="margin-right:15px;">← Back to home</a>
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

    <table class="dashboard-table">
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
        <a href="manage_dictionaries.php" class="btn-blue">Manage Dictionaries / Entries</a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
