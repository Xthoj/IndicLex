<?php
require_once '../config/database.php';
require_once '../includes/admin_auth.php';
requireAdmin();
require_once '../includes/header.php';

// Load active dictionaries
$dictStmt = $conn->query("
    SELECT dict_id, name, type
    FROM dictionaries
    WHERE is_active = 1
    ORDER BY name ASC
");
$dictionaries = $dictStmt->fetchAll(PDO::FETCH_ASSOC);

$dict_id   = isset($_GET['dict_id']) ? (int)$_GET['dict_id'] : 0;
$page_size = 50;
$page_dupe = isset($_GET['page_dupe'])    ? max(1, (int)$_GET['page_dupe'])    : 1;
$page_miss = isset($_GET['page_miss'])    ? max(1, (int)$_GET['page_miss'])    : 1;

$duplicates     = [];
$missing_entries = [];
$total_dupes    = 0;
$total_missing  = 0;
$dictionary     = null;
$error          = '';

function paginate_url_v(string $key, int $page): string {
    $params = array_merge($_GET, [$key => $page]);
    return 'validation.php?' . http_build_query($params);
}

if ($dict_id > 0) {
    $stmtDict = $conn->prepare("
        SELECT dict_id, name, type
        FROM dictionaries
        WHERE dict_id = :dict_id AND is_active = 1
    ");
    $stmtDict->execute([':dict_id' => $dict_id]);
    $dictionary = $stmtDict->fetch(PDO::FETCH_ASSOC);

    if (!$dictionary) {
        $error = 'Selected dictionary was not found.';
    } else {

        // ── Duplicate detection ───────────────────────────────────────────
        $stmtCount = $conn->prepare("
            SELECT COUNT(*) FROM (
                SELECT lang_1, lang_2, lang_3
                FROM dictionary_entries
                WHERE dict_id = :dict_id AND is_active = 1
                GROUP BY lang_1, lang_2, lang_3
                HAVING COUNT(*) > 1
            ) AS dupes
        ");
        $stmtCount->execute([':dict_id' => $dict_id]);
        $total_dupes = (int)$stmtCount->fetchColumn();

        $page_dupe = min($page_dupe, max(1, (int)ceil($total_dupes / $page_size)));
        $offset = ($page_dupe - 1) * $page_size;

        $stmtDuplicates = $conn->prepare("
            SELECT lang_1, lang_2, lang_3, COUNT(*) AS duplicate_count
            FROM dictionary_entries
            WHERE dict_id = :dict_id AND is_active = 1
            GROUP BY lang_1, lang_2, lang_3
            HAVING COUNT(*) > 1
            ORDER BY duplicate_count DESC, lang_1 ASC
            LIMIT {$page_size} OFFSET {$offset}
        ");
        $stmtDuplicates->execute([':dict_id' => $dict_id]);
        $duplicates = $stmtDuplicates->fetchAll(PDO::FETCH_ASSOC);

        // ── Missing-entry analysis ────────────────────────────────────────
        $stmtCount = $conn->prepare("
            SELECT COUNT(DISTINCT e.dict_id, e.lang_1)
            FROM dictionary_entries e
            INNER JOIN dictionaries d ON e.dict_id = d.dict_id
            WHERE e.is_active = 1
              AND d.is_active = 1
              AND e.dict_id <> :dict_id
              AND e.lang_1 NOT IN (
                  SELECT lang_1
                  FROM dictionary_entries
                  WHERE dict_id = :dict_id AND is_active = 1
              )
        ");
        $stmtCount->execute([':dict_id' => $dict_id]);
        $total_missing = (int)$stmtCount->fetchColumn();

        $page_miss = min($page_miss, max(1, (int)ceil($total_missing / $page_size)));
        $offset = ($page_miss - 1) * $page_size;

        $stmtMissing = $conn->prepare("
            SELECT d.name AS source_dictionary, e.lang_1, e.lang_2, e.lang_3
            FROM dictionary_entries e
            INNER JOIN dictionaries d ON e.dict_id = d.dict_id
            WHERE e.is_active = 1
              AND d.is_active = 1
              AND e.dict_id <> :dict_id
              AND e.lang_1 NOT IN (
                  SELECT lang_1
                  FROM dictionary_entries
                  WHERE dict_id = :dict_id AND is_active = 1
              )
            ORDER BY d.name ASC, e.lang_1 ASC
            LIMIT {$page_size} OFFSET {$offset}
        ");
        $stmtMissing->execute([':dict_id' => $dict_id]);
        $missing_entries = $stmtMissing->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<style>
.container {
    width: 92%;
    max-width: 1300px;
    margin: 30px auto;
}
.validation-form {
    background: #ffffff;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
}
.validation-row {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    align-items: end;
}
.validation-row div {
    flex: 1;
    min-width: 220px;
}
.validation-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}
.validation-form select {
    width: 100%;
    padding: 10px;
}
.validation-form button {
    padding: 10px 18px;
    border: none;
    background: #2563eb;
    color: #fff;
    border-radius: 6px;
    cursor: pointer;
}
.section {
    margin-top: 30px;
}
.section h2 {
    margin-bottom: 12px;
}
.result-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    font-size: 0.95rem;
}
.result-table th,
.result-table td {
    border: 1px solid #d1d5db;
    padding: 10px;
    text-align: left;
    vertical-align: top;
}
.result-table th {
    background: #f3f4f6;
}
.message-error {
    color: #b91c1c;
    font-weight: bold;
    margin-bottom: 15px;
}
.summary-boxes {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin: 20px 0;
}
.summary-box {
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 15px;
    min-width: 180px;
}
.pagination {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
    flex-wrap: wrap;
}
.pagination a,
.pagination span {
    padding: 6px 12px;
    border: 1px solid #d1d5db;
    border-radius: 5px;
    text-decoration: none;
    font-size: 0.9rem;
    color: #1f2937;
    background: #fff;
}
.pagination a:hover {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
}
.pagination .current-page {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
    font-weight: bold;
}
.pagination .disabled {
    color: #9ca3af;
    cursor: default;
    pointer-events: none;
}
.page-info {
    color: #6b7280;
    font-size: 0.88rem;
    margin-top: 6px;
}
.note {
    color: #6b7280;
    font-size: 0.9rem;
    margin-top: 6px;
}
body.dark .validation-form,
body.dark .result-table,
body.dark .summary-box {
    background: #1f2937;
    color: #fff;
}
body.dark .result-table th {
    background: #111827;
}
body.dark .result-table th,
body.dark .result-table td,
body.dark .summary-box {
    border-color: #374151;
}
body.dark .validation-form select {
    background: #111827;
    color: #fff;
    border: 1px solid #374151;
}
body.dark .pagination a,
body.dark .pagination span {
    background: #1f2937;
    color: #d1d5db;
    border-color: #374151;
}
body.dark .pagination a:hover,
body.dark .pagination .current-page {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
}
body.dark .page-info,
body.dark .note {
    color: #9ca3af;
}
</style>

<?php
function render_pagination_v(int $current, int $total_pages, string $url_key): void {
    if ($total_pages <= 1) return;

    $window = 2;
    echo '<div class="pagination">';

    if ($current > 1) {
        echo '<a href="' . htmlspecialchars(paginate_url_v($url_key, $current - 1)) . '">&laquo; Prev</a>';
    } else {
        echo '<span class="disabled">&laquo; Prev</span>';
    }

    for ($p = max(1, $current - $window); $p <= min($total_pages, $current + $window); $p++) {
        if ($p === $current) {
            echo '<span class="current-page">' . $p . '</span>';
        } else {
            echo '<a href="' . htmlspecialchars(paginate_url_v($url_key, $p)) . '">' . $p . '</a>';
        }
    }

    if ($current < $total_pages) {
        echo '<a href="' . htmlspecialchars(paginate_url_v($url_key, $current + 1)) . '">Next &raquo;</a>';
    } else {
        echo '<span class="disabled">Next &raquo;</span>';
    }

    echo '</div>';
}
?>

<div class="container">
    <h1>Data Integrity &amp; Validation</h1>
    <p>Select a dictionary to check duplicate entries and missing entries compared to other dictionaries.</p>

    <?php if ($error !== ''): ?>
        <p class="message-error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="GET" action="validation.php" class="validation-form">
        <div class="validation-row">
            <div>
                <label for="dict_id">Dictionary</label>
                <select name="dict_id" id="dict_id" required>
                    <option value="">Select Dictionary</option>
                    <?php foreach ($dictionaries as $dict): ?>
                        <option value="<?php echo (int)$dict['dict_id']; ?>"
                            <?php echo $dict_id === (int)$dict['dict_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dict['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex:0 0 auto;">
                <button type="submit">Run Validation</button>
            </div>
        </div>
        <p class="note">Duplicate detection runs inside the selected dictionary. Missing-entry analysis compares the selected dictionary against all other active dictionaries.</p>
    </form>

    <?php if ($dict_id > 0 && $dictionary && $error === ''): ?>
        <?php
        $pages_dupe = max(1, (int)ceil($total_dupes   / $page_size));
        $pages_miss = max(1, (int)ceil($total_missing / $page_size));
        ?>

        <div class="summary-boxes">
            <div class="summary-box">
                <strong>Dictionary</strong>
                <?php echo htmlspecialchars($dictionary['name']); ?>
            </div>
            <div class="summary-box">
                <strong>Duplicate Groups</strong>
                <?php echo number_format($total_dupes); ?>
            </div>
            <div class="summary-box">
                <strong>Missing Entry Matches</strong>
                <?php echo number_format($total_missing); ?>
            </div>
        </div>

        <!-- Duplicate Entries -->
        <div class="section">
            <h2>Duplicate Entries</h2>
            <p class="page-info">
                Showing page <?php echo $page_dupe; ?> of <?php echo $pages_dupe; ?>
                (<?php echo number_format($total_dupes); ?> duplicate group<?php echo $total_dupes !== 1 ? 's' : ''; ?>)
            </p>
            <table class="result-table">
                <thead>
                    <tr>
                        <th>Lang 1</th>
                        <th>Lang 2</th>
                        <th>Lang 3</th>
                        <th>Duplicate Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($duplicates)): ?>
                        <?php foreach ($duplicates as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['lang_1'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['lang_2'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['lang_3'] ?? ''); ?></td>
                                <td><?php echo (int)$row['duplicate_count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No duplicate entries found in this dictionary.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php render_pagination_v($page_dupe, $pages_dupe, 'page_dupe'); ?>
        </div>

        <!-- Missing Entries -->
        <div class="section">
            <h2>Missing Entries Compared to Other Dictionaries</h2>
            <p class="page-info">
                Showing page <?php echo $page_miss; ?> of <?php echo $pages_miss; ?>
                (<?php echo number_format($total_missing); ?> missing entr<?php echo $total_missing !== 1 ? 'ies' : 'y'; ?>)
            </p>
            <table class="result-table">
                <thead>
                    <tr>
                        <th>Source Dictionary</th>
                        <th>Lang 1</th>
                        <th>Lang 2</th>
                        <th>Lang 3</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($missing_entries)): ?>
                        <?php foreach ($missing_entries as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['source_dictionary'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['lang_1'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['lang_2'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['lang_3'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No missing-entry differences found against other dictionaries.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php render_pagination_v($page_miss, $pages_miss, 'page_miss'); ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
