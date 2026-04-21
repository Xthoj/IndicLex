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

$dict_a = isset($_GET['dict_a']) ? (int)$_GET['dict_a'] : 0;
$dict_b = isset($_GET['dict_b']) ? (int)$_GET['dict_b'] : 0;

$shared_entries = [];
$unique_a = [];
$unique_b = [];
$overlapping_translations = [];
$error = '';

if ($dict_a > 0 && $dict_b > 0) {
    if ($dict_a === $dict_b) {
        $error = 'Please select two different dictionaries.';
    } else {
        // Shared entries based on lang_1 match
        $sqlShared = "
            SELECT
                a.lang_1,
                a.lang_2 AS dict_a_lang_2,
                a.lang_3 AS dict_a_lang_3,
                b.lang_2 AS dict_b_lang_2,
                b.lang_3 AS dict_b_lang_3
            FROM dictionary_entries a
            INNER JOIN dictionary_entries b
                ON a.lang_1 = b.lang_1
            WHERE a.dict_id = :dict_a
              AND b.dict_id = :dict_b
              AND a.is_active = 1
              AND b.is_active = 1
            ORDER BY a.lang_1 ASC
        ";
        $stmtShared = $conn->prepare($sqlShared);
        $stmtShared->execute([
            ':dict_a' => $dict_a,
            ':dict_b' => $dict_b
        ]);
        $shared_entries = $stmtShared->fetchAll(PDO::FETCH_ASSOC);

        // Unique entries in Dictionary A
        $sqlUniqueA = "
            SELECT
                a.lang_1,
                a.lang_2,
                a.lang_3
            FROM dictionary_entries a
            LEFT JOIN dictionary_entries b
                ON a.lang_1 = b.lang_1
               AND b.dict_id = :dict_b
               AND b.is_active = 1
            WHERE a.dict_id = :dict_a
              AND a.is_active = 1
              AND b.entry_id IS NULL
            ORDER BY a.lang_1 ASC
        ";
        $stmtUniqueA = $conn->prepare($sqlUniqueA);
        $stmtUniqueA->execute([
            ':dict_a' => $dict_a,
            ':dict_b' => $dict_b
        ]);
        $unique_a = $stmtUniqueA->fetchAll(PDO::FETCH_ASSOC);

        // Unique entries in Dictionary B
        $sqlUniqueB = "
            SELECT
                b.lang_1,
                b.lang_2,
                b.lang_3
            FROM dictionary_entries b
            LEFT JOIN dictionary_entries a
                ON b.lang_1 = a.lang_1
               AND a.dict_id = :dict_a
               AND a.is_active = 1
            WHERE b.dict_id = :dict_b
              AND b.is_active = 1
              AND a.entry_id IS NULL
            ORDER BY b.lang_1 ASC
        ";
        $stmtUniqueB = $conn->prepare($sqlUniqueB);
        $stmtUniqueB->execute([
            ':dict_a' => $dict_a,
            ':dict_b' => $dict_b
        ]);
        $unique_b = $stmtUniqueB->fetchAll(PDO::FETCH_ASSOC);

        // Overlapping translations based on lang_2 or lang_3
        $sqlOverlap = "
            SELECT
                a.lang_1 AS dict_a_word,
                a.lang_2 AS dict_a_lang_2,
                a.lang_3 AS dict_a_lang_3,
                b.lang_1 AS dict_b_word,
                b.lang_2 AS dict_b_lang_2,
                b.lang_3 AS dict_b_lang_3
            FROM dictionary_entries a
            INNER JOIN dictionary_entries b
                ON (
                    (a.lang_2 IS NOT NULL AND a.lang_2 <> '' AND a.lang_2 = b.lang_2)
                    OR
                    (a.lang_3 IS NOT NULL AND a.lang_3 <> '' AND a.lang_3 = b.lang_3)
                )
            WHERE a.dict_id = :dict_a
              AND b.dict_id = :dict_b
              AND a.is_active = 1
              AND b.is_active = 1
            ORDER BY a.lang_1 ASC
        ";
        $stmtOverlap = $conn->prepare($sqlOverlap);
        $stmtOverlap->execute([
            ':dict_a' => $dict_a,
            ':dict_b' => $dict_b
        ]);
        $overlapping_translations = $stmtOverlap->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<style>
.container {
    width: 92%;
    max-width: 1300px;
    margin: 30px auto;
}
.compare-form {
    background: #ffffff;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
}
.compare-row {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    align-items: end;
}
.compare-row div {
    flex: 1;
    min-width: 220px;
}
.compare-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}
.compare-form select {
    width: 100%;
    padding: 10px;
}
.compare-form button {
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
body.dark .compare-form,
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
body.dark .compare-form select {
    background: #111827;
    color: #fff;
    border: 1px solid #374151;
}
</style>

<div class="container">
    <h1>Dictionary Comparison</h1>
    <p>Select two dictionaries to compare shared entries, unique entries, and overlapping translations.</p>

    <?php if ($error !== ''): ?>
        <p class="message-error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="GET" action="compare.php" class="compare-form">
        <div class="compare-row">
            <div>
                <label for="dict_a">Dictionary A</label>
                <select name="dict_a" id="dict_a" required>
                    <option value="">Select Dictionary A</option>
                    <?php foreach ($dictionaries as $dict): ?>
                        <option value="<?php echo (int)$dict['dict_id']; ?>"
                            <?php echo $dict_a === (int)$dict['dict_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dict['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="dict_b">Dictionary B</label>
                <select name="dict_b" id="dict_b" required>
                    <option value="">Select Dictionary B</option>
                    <?php foreach ($dictionaries as $dict): ?>
                        <option value="<?php echo (int)$dict['dict_id']; ?>"
                            <?php echo $dict_b === (int)$dict['dict_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dict['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex:0 0 auto;">
                <button type="submit">Compare</button>
            </div>
        </div>
    </form>

    <?php if ($dict_a > 0 && $dict_b > 0 && $error === ''): ?>
        <div class="summary-boxes">
            <div class="summary-box">
                <strong>Shared Entries</strong><br>
                <?php echo count($shared_entries); ?>
            </div>
            <div class="summary-box">
                <strong>Unique in Dictionary A</strong><br>
                <?php echo count($unique_a); ?>
            </div>
            <div class="summary-box">
                <strong>Unique in Dictionary B</strong><br>
                <?php echo count($unique_b); ?>
            </div>
            <div class="summary-box">
                <strong>Overlapping Translations</strong><br>
                <?php echo count($overlapping_translations); ?>
            </div>
        </div>

        <div class="section">
            <h2>Shared Entries</h2>
            <table class="result-table">
                <thead>
                    <tr>
                        <th>Common Word</th>
                        <th>Dictionary A Translation</th>
                        <th>Dictionary A Lang 3</th>
                        <th>Dictionary B Translation</th>
                        <th>Dictionary B Lang 3</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($shared_entries)): ?>
                        <?php foreach ($shared_entries as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['lang_1'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['dict_a_lang_2'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['dict_a_lang_3'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['dict_b_lang_2'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['dict_b_lang_3'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No shared entries found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Unique Entries in Dictionary A</h2>
            <table class="result-table">
                <thead>
                    <tr>
                        <th>Lang 1</th>
                        <th>Lang 2</th>
                        <th>Lang 3</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($unique_a)): ?>
                        <?php foreach ($unique_a as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['lang_1'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['lang_2'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['lang_3'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3">No unique entries found in Dictionary A.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Unique Entries in Dictionary B</h2>
            <table class="result-table">
                <thead>
                    <tr>
                        <th>Lang 1</th>
                        <th>Lang 2</th>
                        <th>Lang 3</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($unique_b)): ?>
                        <?php foreach ($unique_b as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['lang_1'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['lang_2'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['lang_3'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3">No unique entries found in Dictionary B.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Overlapping Translations</h2>
            <table class="result-table">
                <thead>
                    <tr>
                        <th>Dictionary A Word</th>
                        <th>Dictionary A Translation</th>
                        <th>Dictionary B Word</th>
                        <th>Dictionary B Translation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($overlapping_translations)): ?>
                        <?php foreach ($overlapping_translations as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['dict_a_word'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['dict_a_lang_2'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['dict_b_word'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['dict_b_lang_2'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No overlapping translations found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>