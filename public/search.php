<?php
/**
 * public/search.php
 * Search dictionary entries — Iteration 5 update:
 *   - Loads preferences (cookie -> DB default -> safety) on page load
 *   - results_per_page driven by preference
 *   - default_dict pre-selects the dictionary dropdown
 */
require_once '../config/database.php';
require_once '../includes/preferences_helper.php';

// Resolve preferences BEFORE any output
$prefs = load_preferences($conn);

// Load dictionaries for dropdown
$dict_stmt    = $conn->query(
    "SELECT dict_id, name, type FROM dictionaries WHERE is_active = 1 ORDER BY name"
);
$dictionaries = $dict_stmt->fetchAll(PDO::FETCH_ASSOC);

// Search parameters
$query   = trim($_GET['query']  ?? '');
$mode    = $_GET['mode']        ?? 'substring';
$page    = max(1, (int)($_GET['page'] ?? 1));

// dict_id: URL param > preference default
$dict_id = $_GET['dict_id'] ?? null;
if ($dict_id === null) {
    // First load — apply the stored default_dict preference
    $dict_id = $prefs['default_dict'];
}

// results_per_page: URL param (allows per-search override) > preference
$per_page_param = $_GET['per_page'] ?? null;
$allowed_rpp    = [5, 10, 20, 50];
if ($per_page_param !== null && in_array((int)$per_page_param, $allowed_rpp, true)) {
    $per_page = (int)$per_page_param;
} else {
    $per_page = $prefs['results_per_page'];
}

$offset = ($page - 1) * $per_page;

$results     = [];
$total_count = 0;
$searched    = false;

$valid_modes = ['exact', 'prefix', 'suffix', 'substring'];
if (!in_array($mode, $valid_modes)) $mode = 'substring';

if ($query !== '') {
    $searched = true;

    $escaped = str_replace(['%', '_'], ['\%', '\_'], $query);

    switch ($mode) {
        case 'exact':     $pattern = $escaped;             break;
        case 'prefix':    $pattern = $escaped . '%';       break;
        case 'suffix':    $pattern = '%' . $escaped;       break;
        case 'substring': $pattern = '%' . $escaped . '%'; break;
    }

    $where_parts = ["(e.lang_1 LIKE :pattern OR e.lang_2 LIKE :pattern OR e.lang_3 LIKE :pattern)"];
    $params      = [':pattern' => $pattern];

    $where_parts[] = "e.is_active = 1";

    if ($dict_id !== 'all' && is_numeric($dict_id)) {
        $where_parts[] = "e.dict_id = :dict_id";
        $params[':dict_id'] = (int)$dict_id;
    }

    $where = "WHERE " . implode(" AND ", $where_parts);

    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM dictionary_entries e $where");
    $count_stmt->execute($params);
    $total_count = (int)$count_stmt->fetchColumn();

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

include '../includes/header.php';
?>
<style>
.search-wrap { max-width: 900px; margin: 6rem auto 2rem; padding: 0 1.5rem; }
.search-wrap h2 { margin-bottom: 1rem; }
.search-wrap input[type=text] { width:100%; padding:.5rem; font-size:1rem; margin-top:.3rem; box-sizing:border-box; }
.search-wrap select { padding:.45rem; font-size:.95rem; margin-top:.3rem; }
body.dark .search-wrap select { background: var(--bg); color: var(--text); border-color: #4b5563; }
body.dark .search-wrap input[type=text] { background: var(--bg); color: var(--text); border-color: #4b5563; border: 1px solid; }
.result-table { width:100%; border-collapse:collapse; font-size:.875rem; margin-top:1rem; }
.result-table th, .result-table td { border:1px solid #e5e7eb; padding:.4rem .6rem; text-align:left; }
.result-table th { background:#f3f4f6; font-weight:bold; }
.result-table tr:hover { background:#f9fafb; }
body.dark .result-table th { background:#1f2937; }
body.dark .result-table th,
body.dark .result-table td { border-color:#374151; }
body.dark .result-table tr:hover { background:#111827; }
.pagination { display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; margin-top:1rem; }
.pagination a { padding:.3rem .6rem; border:1px solid #d1d5db; border-radius:4px; text-decoration:none; font-size:.9rem; color:var(--text); }
.pagination a:hover { background:#f3f4f6; }
body.dark .pagination a:hover { background:#1f2937; }
.pagination strong { padding:.3rem .6rem; background:#2563eb; color:#fff; border-radius:4px; font-size:.9rem; }
.mode-group { display:flex; gap:1rem; flex-wrap:wrap; margin-top:.5rem; }

/* Results-per-page inline control */
.rpp-row { display:flex; align-items:center; gap:.5rem; margin-top:1rem; font-size:.85rem; color:#6b7280; }
body.dark .rpp-row { color:#9ca3af; }
.rpp-row select { padding:.3rem .5rem; font-size:.85rem; }

/* Pref hint link */
.pref-hint { font-size:.78rem; color:#6b7280; margin-top:.4rem; }
.pref-hint a { color:#2563eb; }
body.dark .pref-hint a { color:#60a5fa; }

/* Autocomplete */
.autocomplete-wrapper { position:relative; }
.autocomplete-list { position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #d1d5db; border-top:none; border-radius:0 0 6px 6px; z-index:100; max-height:220px; overflow-y:auto; }
.autocomplete-list li { list-style:none; padding:.45rem .6rem; cursor:pointer; font-size:.9rem; border-bottom:1px solid #f3f4f6; }
.autocomplete-list li:hover, .autocomplete-list li.active { background:#eff6ff; }
body.dark .autocomplete-list { background:#1e1e1e; border-color:#374151; }
body.dark .autocomplete-list li { border-color:#374151; color:white; }
body.dark .autocomplete-list li:hover, body.dark .autocomplete-list li.active { background:#1f2937; }

/* Word length */
.word-length-section { margin-top:2rem; }
.word-length-section h3 { margin-bottom:.75rem; font-size:1rem; }
.length-groups { display:flex; flex-wrap:wrap; gap:.5rem; }
.length-badge { display:inline-flex; align-items:center; gap:.4rem; background:#f3f4f6; border:1px solid #d1d5db; border-radius:6px; padding:.3rem .7rem; font-size:.85rem; }
.length-badge a { color:#2563eb; text-decoration:none; font-size:.75rem; }
.length-badge a:hover { text-decoration:underline; }
body.dark .length-badge { background:#1f2937; border-color:#374151; color:white; }
body.dark .length-badge a { color:#60a5fa; }
</style>

<div class="search-wrap">
  <h2>Search</h2>

  <form method="GET" action="search.php" id="search-form">

    <div style="margin-bottom:1rem;">
      <label for="query"><strong>Search term</strong></label>
      <div class="autocomplete-wrapper">
        <input type="text" name="query" id="query"
               value="<?= htmlspecialchars($query) ?>"
               placeholder="Enter a word in any language..."
               autocomplete="off" required>
        <ul class="autocomplete-list" id="autocomplete-list" style="display:none;"></ul>
      </div>
    </div>

    <!-- Hidden: carry per_page through pagination links -->
    <input type="hidden" name="per_page" value="<?= $per_page ?>">

    <div style="display:flex; gap:2rem; flex-wrap:wrap; margin-bottom:1rem;">

      <div>
        <label for="dict_id"><strong>Dictionary</strong></label><br>
        <select name="dict_id" id="dict_id">
          <option value="all" <?= $dict_id === 'all' ? 'selected' : '' ?>>All Dictionaries</option>
          <?php foreach ($dictionaries as $d): ?>
            <option value="<?= $d['dict_id'] ?>"
              <?= (string)$dict_id === (string)$d['dict_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($d['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($prefs['default_dict'] !== 'all'): ?>
          <div class="pref-hint">Default from <a href="preferences.php">preferences</a></div>
        <?php endif; ?>
      </div>

      <div>
        <label><strong>Search mode</strong></label>
        <div class="mode-group">
          <?php foreach (['exact' => 'Exact', 'prefix' => 'Prefix', 'suffix' => 'Suffix', 'substring' => 'Substring'] as $val => $label): ?>
            <label>
              <input type="radio" name="mode" value="<?= $val ?>" <?= $mode === $val ? 'checked' : '' ?>>
              <?= $label ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

    </div>

    <button type="submit" class="btn btn-primary">Search</button>
  </form>

  <?php if ($searched): ?>
    <hr style="margin:1.5rem 0;">

    <?php if ($total_count === 0): ?>
      <p>No results found for <strong>"<?= htmlspecialchars($query) ?>"</strong>.</p>

    <?php else: ?>
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem;">
        <p style="margin:0;">
          Found <strong><?= number_format($total_count) ?></strong>
          result<?= $total_count !== 1 ? 's' : '' ?>
          for <strong>"<?= htmlspecialchars($query) ?>"</strong>
          &mdash; showing <?= $offset + 1 ?>&ndash;<?= min($offset + $per_page, $total_count) ?>
        </p>

        <!-- Inline results-per-page switcher -->
        <div class="rpp-row">
          <label for="rpp-select">Show:</label>
          <select id="rpp-select" onchange="changePerPage(this.value)">
            <?php foreach ([5, 10, 20, 50] as $n): ?>
              <option value="<?= $n ?>" <?= $per_page === $n ? 'selected' : '' ?>>
                <?= $n ?> / page
              </option>
            <?php endforeach; ?>
          </select>
          <span>(preference: <?= $prefs['results_per_page'] ?> &mdash; <a href="preferences.php">change</a>)</span>
        </div>
      </div>

      <table class="result-table">
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

      <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <?php
          $base = http_build_query([
              'query'    => $query,
              'dict_id'  => $dict_id,
              'mode'     => $mode,
              'per_page' => $per_page,
          ]);
          ?>

          <?php if ($page > 1): ?>
            <a href="search.php?<?= $base ?>&page=<?= $page - 1 ?>">&larr; Prev</a>
          <?php endif; ?>

          <?php
          $start = max(1, $page - 3);
          $end   = min($total_pages, $page + 3);
          for ($p = $start; $p <= $end; $p++):
          ?>
            <?php if ($p === $page): ?>
              <strong><?= $p ?></strong>
            <?php else: ?>
              <a href="search.php?<?= $base ?>&page=<?= $p ?>"><?= $p ?></a>
            <?php endif; ?>
          <?php endfor; ?>

          <?php if ($page < $total_pages): ?>
            <a href="search.php?<?= $base ?>&page=<?= $page + 1 ?>">Next &rarr;</a>
          <?php endif; ?>

          <span style="color:#666; font-size:.85rem;">Page <?= $page ?> of <?= $total_pages ?></span>
        </div>
      <?php endif; ?>

    <?php endif; ?>

    <?php
    // Word Length Matching — group all results by lang_1 length
    if ($searched && $total_count > 0):
        $length_groups = [];
        foreach ($results as $row) {
            $len = mb_strlen($row['lang_1']);
            $length_groups[$len][] = $row['lang_1'];
        }
        ksort($length_groups);
    ?>
    <div class="word-length-section">
      <h3>Word Length Matching</h3>
      <div class="length-groups">
        <?php foreach ($length_groups as $len => $words): ?>
          <span class="length-badge">
            <?= $len ?> letters (<?= count($words) ?>)
            <a href="https://www.telugupuzzles.com/puzzles.php" target="_blank" rel="noopener">
              Try in puzzle &rarr;
            </a>
          </span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  <?php endif; ?>

</div>

<script>
function changePerPage(n) {
  var url = new URL(window.location.href);
  url.searchParams.set('per_page', n);
  url.searchParams.set('page', '1');
  window.location.href = url.toString();
}

// Autocomplete
(function () {
  const input    = document.getElementById('query');
  const list     = document.getElementById('autocomplete-list');
  const dictSel  = document.getElementById('dict_id');
  let debounce   = null;
  let activeIdx  = -1;

  function getApiBase() {
    // Use the same origin; works with or without .htaccess clean URLs
    const origin = window.location.origin;
    const path   = window.location.pathname.replace(/\/search\.php$/, '');
    return origin + path + '/api/search.php';
  }

  function showSuggestions(items) {
    list.innerHTML = '';
    activeIdx = -1;
    if (!items.length) { list.style.display = 'none'; return; }

    items.slice(0, 5).forEach(function (item, i) {
      const li = document.createElement('li');
      li.textContent = item.lang_1 + (item.lang_2 ? '  —  ' + item.lang_2 : '');
      li.addEventListener('mousedown', function (e) {
        e.preventDefault();
        input.value = item.lang_1;
        list.style.display = 'none';
        document.getElementById('search-form').submit();
      });
      list.appendChild(li);
    });
    list.style.display = 'block';
  }

  input.addEventListener('input', function () {
    clearTimeout(debounce);
    const q = input.value.trim();
    if (q.length < 2) { list.style.display = 'none'; return; }

    debounce = setTimeout(function () {
      const dict = dictSel ? dictSel.value : 'all';
      const url  = getApiBase() + '?q=' + encodeURIComponent(q) + '&dict=' + encodeURIComponent(dict) + '&mode=substring';

      fetch(url)
        .then(function (r) { return r.json(); })
        .then(function (data) { showSuggestions(data.results || []); })
        .catch(function () { list.style.display = 'none'; });
    }, 250);
  });

  // Keyboard navigation
  input.addEventListener('keydown', function (e) {
    const items = list.querySelectorAll('li');
    if (!items.length) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      activeIdx = Math.min(activeIdx + 1, items.length - 1);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      activeIdx = Math.max(activeIdx - 1, -1);
    } else if (e.key === 'Escape') {
      list.style.display = 'none'; return;
    } else { return; }
    items.forEach(function (li, i) { li.classList.toggle('active', i === activeIdx); });
    if (activeIdx >= 0) input.value = items[activeIdx].textContent.split('  —  ')[0];
  });

  document.addEventListener('click', function (e) {
    if (!e.target.closest('.autocomplete-wrapper')) list.style.display = 'none';
  });
})();
</script>

<?php include '../includes/footer.php'; ?>
