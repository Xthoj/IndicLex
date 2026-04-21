<?php
require_once '../config/database.php';
require_once '../includes/header.php';

/*
|--------------------------------------------------------------------------
| Dictionary statistics
|--------------------------------------------------------------------------
*/

// 1. Entry count per dictionary
$stmtEntries = $conn->query("
    SELECT
        d.dict_id,
        d.name,
        COUNT(e.entry_id) AS total_entries
    FROM dictionaries d
    LEFT JOIN dictionary_entries e
        ON d.dict_id = e.dict_id
       AND e.is_active = 1
    WHERE d.is_active = 1
    GROUP BY d.dict_id, d.name
    ORDER BY d.name ASC
");
$entries_per_dictionary = $stmtEntries->fetchAll(PDO::FETCH_ASSOC);

// 2. Dictionary type distribution
$stmtTypes = $conn->query("
    SELECT
        COALESCE(NULLIF(type, ''), 'Unspecified') AS dict_type,
        COUNT(*) AS total_dictionaries
    FROM dictionaries
    WHERE is_active = 1
    GROUP BY COALESCE(NULLIF(type, ''), 'Unspecified')
    ORDER BY dict_type ASC
");
$dictionary_types = $stmtTypes->fetchAll(PDO::FETCH_ASSOC);

// 3. Active vs inactive entries
$stmtStatus = $conn->query("
    SELECT
        CASE
            WHEN is_active = 1 THEN 'Active'
            ELSE 'Inactive'
        END AS entry_status,
        COUNT(*) AS total_entries
    FROM dictionary_entries
    GROUP BY is_active
    ORDER BY entry_status ASC
");
$entry_status = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);

// 4. Top dictionaries by entry count
$stmtTop = $conn->query("
    SELECT
        d.name,
        COUNT(e.entry_id) AS total_entries
    FROM dictionaries d
    LEFT JOIN dictionary_entries e
        ON d.dict_id = e.dict_id
       AND e.is_active = 1
    WHERE d.is_active = 1
    GROUP BY d.dict_id, d.name
    ORDER BY total_entries DESC, d.name ASC
    LIMIT 10
");
$top_dictionaries = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

// Summary counts
$stmtSummary = $conn->query("
    SELECT COUNT(*) AS total_dictionaries
    FROM dictionaries
    WHERE is_active = 1
");
$total_dictionaries = (int)$stmtSummary->fetchColumn();

$stmtSummary2 = $conn->query("
    SELECT COUNT(*) AS total_entries
    FROM dictionary_entries
    WHERE is_active = 1
");
$total_entries = (int)$stmtSummary2->fetchColumn();

$stmtSummary3 = $conn->query("
    SELECT COUNT(*) AS total_inactive_entries
    FROM dictionary_entries
    WHERE is_active = 0
");
$total_inactive_entries = (int)$stmtSummary3->fetchColumn();

/*
|--------------------------------------------------------------------------
| Prepare chart data
|--------------------------------------------------------------------------
*/

$barLabels = [];
$barValues = [];
foreach ($entries_per_dictionary as $row) {
    $barLabels[] = $row['name'];
    $barValues[] = (int)$row['total_entries'];
}

$pieLabels = [];
$pieValues = [];
foreach ($dictionary_types as $row) {
    $pieLabels[] = $row['dict_type'];
    $pieValues[] = (int)$row['total_dictionaries'];
}

$statusLabels = [];
$statusValues = [];
foreach ($entry_status as $row) {
    $statusLabels[] = $row['entry_status'];
    $statusValues[] = (int)$row['total_entries'];
}

$topLabels = [];
$topValues = [];
foreach ($top_dictionaries as $row) {
    $topLabels[] = $row['name'];
    $topValues[] = (int)$row['total_entries'];
}
?>

<style>
.container {
    width: 92%;
    max-width: 1300px;
    margin: 30px auto;
}
.page-intro {
    margin-bottom: 20px;
}
.summary-boxes {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin: 20px 0 30px;
}
.summary-box {
    background: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    padding: 18px;
    min-width: 180px;
}
.summary-box strong {
    display: block;
    margin-bottom: 8px;
}
.chart-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}
.chart-card {
    background: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    padding: 20px;
}
.chart-card h2 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 1.15rem;
}
.chart-card canvas {
    max-width: 100%;
}
.report-table {
    width: 100%;
    border-collapse: collapse;
    background: #ffffff;
    font-size: 0.95rem;
    margin-top: 20px;
}
.report-table th,
.report-table td {
    border: 1px solid #d1d5db;
    padding: 10px;
    text-align: left;
}
.report-table th {
    background: #f3f4f6;
}
.section {
    margin-top: 30px;
}
.note {
    color: #6b7280;
    font-size: 0.92rem;
}

body.dark .summary-box,
body.dark .chart-card,
body.dark .report-table {
    background: #1f2937;
    color: #ffffff;
}
body.dark .report-table th {
    background: #111827;
}
body.dark .summary-box,
body.dark .chart-card,
body.dark .report-table th,
body.dark .report-table td {
    border-color: #374151;
}
body.dark .note {
    color: #9ca3af;
}
</style>

<div class="container">
    <h1>Reports</h1>
    <p class="page-intro">
        This page shows dictionary statistics with visual reports for entry counts,
        dictionary types, and entry status.
    </p>

    <div class="summary-boxes">
        <div class="summary-box">
            <strong>Total Active Dictionaries</strong>
            <?php echo number_format($total_dictionaries); ?>
        </div>

        <div class="summary-box">
            <strong>Total Active Entries</strong>
            <?php echo number_format($total_entries); ?>
        </div>

        <div class="summary-box">
            <strong>Total Inactive Entries</strong>
            <?php echo number_format($total_inactive_entries); ?>
        </div>

        <div class="summary-box">
            <strong>Dictionary Types</strong>
            <?php echo number_format(count($dictionary_types)); ?>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-card">
            <h2>Entries per Dictionary</h2>
            <canvas id="entriesBarChart"></canvas>
        </div>

        <div class="chart-card">
            <h2>Dictionary Type Distribution</h2>
            <canvas id="dictionaryTypePieChart"></canvas>
        </div>

        <div class="chart-card">
            <h2>Entry Status Distribution</h2>
            <canvas id="entryStatusPieChart"></canvas>
        </div>

        <div class="chart-card">
            <h2>Top 10 Dictionaries by Entries</h2>
            <canvas id="topDictionariesBarChart"></canvas>
        </div>
    </div>

    <div class="section">
        <h2>Dictionary Statistics Table</h2>
        <p class="note">This table lists active dictionaries and their active entry totals.</p>

        <table class="report-table">
            <thead>
                <tr>
                    <th>Dictionary ID</th>
                    <th>Dictionary Name</th>
                    <th>Total Active Entries</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($entries_per_dictionary)): ?>
                    <?php foreach ($entries_per_dictionary as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['dict_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo number_format((int)$row['total_entries']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No dictionary statistics available.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const entriesBarLabels = <?php echo json_encode($barLabels); ?>;
const entriesBarValues = <?php echo json_encode($barValues); ?>;

const typePieLabels = <?php echo json_encode($pieLabels); ?>;
const typePieValues = <?php echo json_encode($pieValues); ?>;

const statusPieLabels = <?php echo json_encode($statusLabels); ?>;
const statusPieValues = <?php echo json_encode($statusValues); ?>;

const topBarLabels = <?php echo json_encode($topLabels); ?>;
const topBarValues = <?php echo json_encode($topValues); ?>;
</script>

<script src="/IndicLex-admin-version/assets/js/charts.js"></script>

<?php require_once '../includes/footer.php'; ?>