<?php
require_once '../config/database.php';
 
// Fetch all dictionaries
$dict_stmt = $conn->query("SELECT dict_id, name FROM dictionaries ORDER BY name");
$dictionaries = $dict_stmt->fetchAll(PDO::FETCH_ASSOC);
 
//Search parameters
$query       = trim($_GET['query']   ?? '');
$dict_id     = $_GET['dict_id']      ?? 'all';
$mode        = $_GET['mode']         ?? 'substring';
$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 10;
$offset      = ($page - 1) * $per_page;
 
$results     = [];
$total_count = 0;
$searched    = false;
 
// Search modes
$valid_modes = ['exact', 'prefix', 'suffix', 'substring'];
if (!in_array($mode, $valid_modes)) $mode = 'substring';
 
if ($query !== '') {
    $searched = true;
 
    // Escaped replaces % and _ with \& and \_ so it does not get treated as a wildcard in SQL.
    $escaped = str_replace(['%', '_'], ['\%', '\_'], $query);

    switch ($mode) {
        case 'exact':     $pattern = $escaped;           break;
        case 'prefix':    $pattern = $escaped . '%';     break;
        case 'suffix':    $pattern = '%' . $escaped;     break;
        case 'substring': $pattern = '%' . $escaped . '%'; break;
    }
 
    // Build WHERE clause
    $where_parts = ["(e.lang_1 LIKE :pattern OR e.lang_2 LIKE :pattern OR e.lang_3 LIKE :pattern)"];
    $params      = [':pattern' => $pattern];
 
    if ($dict_id !== 'all' && is_numeric($dict_id)) {
        $where_parts[] = "e.dict_id = :dict_id";
        $params[':dict_id'] = (int)$dict_id;
    }
 
    $where = "WHERE e.is_active = 1 AND " . implode(" AND ", $where_parts);
 
    // Count total results for pagination
    $count_sql = "SELECT COUNT(*) FROM dictionary_entries e $where";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute($params);
    $total_count = (int)$count_stmt->fetchColumn();
 
    // Fetch paginated results
    $results_sql = "
        SELECT e.entry_id, e.lang_1, e.lang_2, e.lang_3,
               e.pronunciation, e.part_of_speech, e.example,
               d.name AS dict_name, d.type AS dict_type,
               d.source_lang_1, d.source_lang_2, d.source_lang_3
        FROM dictionary_entries e
        JOIN dictionaries d ON e.dict_id = d.dict_id
        $where
        ORDER BY e.lang_1
        LIMIT :limit OFFSET :offset
    ";
 
    $results_stmt = $conn->prepare($results_sql);
    foreach ($params as $key => $val) {
        $results_stmt->bindValue($key, $val);
    }
    $results_stmt->bindValue(':limit',  $per_page, PDO::PARAM_INT);
    $results_stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);
    $results_stmt->execute();
    $results = $results_stmt->fetchAll(PDO::FETCH_ASSOC);
}
 
$total_pages = $total_count > 0 ? ceil($total_count / $per_page) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search — IndicLex</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
 
<?php include '../includes/header.php'; ?>
 
<div style="max-width:900px; margin:2rem auto; padding:0 1.5rem;">
 
    <h2>Search</h2>
 
    <!-- Search Form -->
    <form method="GET" action="search.php">
 
        <div style="margin-bottom:1rem;">
            <label for="query"><strong>Search term</strong></label><br>
            <input type="text"
                   name="query"
                   id="query"
                   value="<?= htmlspecialchars($query) ?>"
                   placeholder="Enter a word..."
                   style="width:100%; padding:.5rem; font-size:1rem; margin-top:.3rem;"
                   required>
        </div>
 
        <div style="display:flex; gap:2rem; flex-wrap:wrap; margin-bottom:1rem;">
 
            <div>
                <label for="dict_id"><strong>Dictionary</strong></label><br>
                <select name="dict_id" id="dict_id" style="padding:.45rem; font-size:.95rem; margin-top:.3rem;">
                    <option value="all" <?= $dict_id === 'all' ? 'selected' : '' ?>>All Dictionaries</option>
                    <?php foreach ($dictionaries as $d): ?>
                        <option value="<?= $d['dict_id'] ?>"
                            <?= (string)$dict_id === (string)$d['dict_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
 
            <div>
                <label><strong>Search mode</strong></label><br>
                <div style="display:flex; gap:1rem; margin-top:.5rem;">
                    <?php foreach (['exact' => 'Exact', 'prefix' => 'Prefix', 'suffix' => 'Suffix', 'substring' => 'Substring'] as $val => $label): ?>
                        <label>
                            <input type="radio" name="mode" value="<?= $val ?>"
                                <?= $mode === $val ? 'checked' : '' ?>>
                            <?= $label ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
 
        </div>
 
        <button type="submit">Search</button>
 
    </form>
 
    <!-- Results -->
    <?php if ($searched): ?>
 
        <hr style="margin:1.5rem 0;">
 
        <?php if ($total_count === 0): ?>
            <p>No results found for <strong>"<?= htmlspecialchars($query) ?>"</strong>.</p>
 
        <?php else: ?>
 
            <p>
                Found <strong><?= number_format($total_count) ?></strong> result<?= $total_count !== 1 ? 's' : '' ?>
                for <strong>"<?= htmlspecialchars($query) ?>"</strong>
                <?php if ($dict_id !== 'all'): ?>
                    in <strong><?= htmlspecialchars($dictionaries[array_search($dict_id, array_column($dictionaries, 'dict_id'))]['name'] ?? '') ?></strong>
                <?php endif; ?>
                — showing <?= $offset + 1 ?>–<?= min($offset + $per_page, $total_count) ?>
            </p>
 
            <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th>Lang 1</th>
                        <th>Lang 2</th>
                        <th>Lang 3</th>
                        <th>Pronunciation</th>
                        <th>POS</th>
                        <th>Example</th>
                        <th>Dictionary</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['lang_1']) ?></td>
                            <td><?= htmlspecialchars($row['lang_2']) ?></td>
                            <td><?= htmlspecialchars($row['lang_3'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['pronunciation'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['part_of_speech'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['example'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['dict_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
 
            <!-- Pagination  -->
            <?php if ($total_pages > 1): ?>
                <div style="margin-top:1rem; display:flex; gap:.5rem; flex-wrap:wrap; align-items:center;">
 
                    <?php
                    // Build base URL preserving all params except page
                    $base_params = http_build_query([
                        'query'   => $query,
                        'dict_id' => $dict_id,
                        'mode'    => $mode,
                    ]);
                    ?>
 
                    <?php if ($page > 1): ?>
                        <a href="search.php?<?= $base_params ?>&page=<?= $page - 1 ?>">← Prev</a>
                    <?php endif; ?>
 
                    <?php
                    // Show up to 7 page links centered around current page
                    $start = max(1, $page - 3);
                    $end   = min($total_pages, $page + 3);
                    for ($p = $start; $p <= $end; $p++):
                    ?>
                        <?php if ($p === $page): ?>
                            <strong>[<?= $p ?>]</strong>
                        <?php else: ?>
                            <a href="search.php?<?= $base_params ?>&page=<?= $p ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
 
                    <?php if ($page < $total_pages): ?>
                        <a href="search.php?<?= $base_params ?>&page=<?= $page + 1 ?>">Next →</a>
                    <?php endif; ?>
 
                    <span style="color:#666; font-size:.9rem;">
                        Page <?= $page ?> of <?= $total_pages ?>
                    </span>
 
                </div>
            <?php endif; ?>
 
        <?php endif; ?>
 
    <?php endif; ?>
 
</div>
 
<?php include '../includes/footer.php'; ?>
 
</body>
</html>