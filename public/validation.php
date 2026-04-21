<?php
require_once '../config/database.php';
require_once '../includes/header.php';

// Load active dictionaries
$dictStmt = $conn->query("
    SELECT dict_id, name, type
    FROM dictionaries
    WHERE is_active = 1
    ORDER BY name ASC
");
$dictionaries = $dictStmt->fetchAll(PDO::FETCH_ASSOC);

$dict_id = isset($_GET['dict_id']) ? (int)$_GET['dict_id'] : 0;

$duplicates = [];
$missing_entries = [];
$dictionary = null;
$error = '';

if ($dict_id > 0) {
    // Get selected dictionary
    $stmtDict = $conn->prepare("
        SELECT dict_id, name, type
        FROM dictionaries
        WHERE dict_id = :dict_id
          AND is_active = 1
    ");
    $stmtDict->execute([':dict_id' => $dict_id]);
    $dictionary = $stmtDict->fetch(PDO::FETCH_ASSOC);

    if (!$dictionary) {
        $error = 'Selected dictionary was not found.';
    } else {
        // Duplicate detection inside the selected dictionary
        $sqlDuplicates = "
            SELECT
                lang_1,
                lang_2,
                lang_3,
                COUNT(*) AS duplicate_count
            FROM dictionary_entries
            WHERE dict_id = :dict_id
              AND is_active = 1
            GROUP BY lang_1, lang_2, lang_3
            HAVING COUNT(*) > 1
            ORDER BY duplicate_count DESC, lang_1 ASC
        ";
        $stmtDuplicates = $conn->prepare($sqlDuplicates);
        $stmtDuplicates->execute([':dict_id' => $dict_id]);
        $duplicates = $stmtDuplicates->fetchAll(PDO::FETCH_ASSOC);

        // Missing-entry analysis:
        // Find lang_1 words that exist in other dictionaries but not in selected dictionary
        $sqlMissing = "
            SELECT
                d.dict_id,
                d.name AS source_dictionary,
                e.lang_1,
                e.lang_2,
                e.lang_3
            FROM dictionary_entries e
            INNER JOIN dictionaries d
                ON e.dict_id = d.dict_id
            WHERE e.is_active = 1
              AND d.is_active = 1
              AND e.dict_id <> :dict_id
              AND e.lang_1 NOT IN (
                  SELECT lang_1
                  FROM dictionary_entries
                  WHERE dict_id = :dict_id
                    AND is_active = 1
              )
            ORDER BY d.name ASC, e.lang_1 ASC
            LIMIT 300
        ";
        $stmtMissing = $conn->prepare($sqlMissing);
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
.note {
    color: #6b7280;
    font-size: 0.9rem;
    margin-top: 6px;
}
body.dark .note {
    color: #9ca3af;
}
</style>

<div class="container">
    <h1>Data Integrity & Validation</h1>
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
        <div class="note">Duplicate detection runs inside the selected dictionary. Missing-entry analysis compares the selected dictionary against other active dictionaries.</div>
    </form>

    <?php if ($dict_id > 0 && $dictionary && $error === ''): ?>
        <div class="summary-boxes">
            <div class="summary-box">
                <strong>Dictionary</strong><br>
                <?php echo htmlspecialchars($dictionary['name']); ?>
            </div>
            <div class="summary-box">
                <strong>Duplicate Groups</strong><br>
                <?php echo count($duplicates); ?>
            </div>
            <div class="summary-box">
                <strong>Missing Entry Matches</strong><br>
                <?php echo count($missing_entries); ?>
            </div>
        </div>

        <div class="section">
            <h2>Duplicate Entries</h2>
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
                                <td><?php echo htmlspecialchars($row['duplicate_count'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No duplicate entries found in this dictionary.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Missing Entries Compared to Other Dictionaries</h2>
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
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>