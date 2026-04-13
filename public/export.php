<<<<<<< HEAD
<?php
/**
 * public/export.php
 * Export dictionary entries as CSV, JSON, or HTML.
 * Uses professor's schema: dict_id, lang_1, lang_2, lang_3
 */

require_once '../config/database.php';

$dictionaries = [];
try {
    $stmt = $conn->query(
        "SELECT dict_id, name, type, source_lang_1, source_lang_2, source_lang_3
         FROM dictionaries WHERE is_active = 1 ORDER BY name"
    );
    $dictionaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('DB error: ' . htmlspecialchars($e->getMessage()));
}

$format   = $_GET['format']   ?? '';
$dict_id  = filter_input(INPUT_GET, 'dictionary_id', FILTER_VALIDATE_INT);
$doExport = in_array($format, ['csv', 'json', 'html'], true) && $dict_id;

if ($doExport) {
    try {
        $stmt = $conn->prepare(
            "SELECT e.lang_1, e.lang_2, e.lang_3, e.pronunciation,
                    e.part_of_speech, e.example, e.notes,
                    d.name AS dictionary_name, d.type,
                    d.source_lang_1, d.source_lang_2, d.source_lang_3
             FROM   dictionary_entries e
             JOIN   dictionaries d ON d.dict_id = e.dict_id
             WHERE  e.dict_id = ? AND e.is_active = 1
             ORDER  BY e.lang_1"
        );
        $stmt->execute([$dict_id]);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die('DB error: ' . htmlspecialchars($e->getMessage()));
    }

    if (empty($entries)) {
        header('Location: export.php?empty=1'); exit;
    }

    $dictName = $entries[0]['dictionary_name'];
    $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $dictName);
    $date     = date('Ymd');
    $isTri    = $entries[0]['type'] === 'trilingual';
    $l1       = $entries[0]['source_lang_1'];
    $l2       = $entries[0]['source_lang_2'];
    $l3       = $entries[0]['source_lang_3'] ?? 'Lang 3';

    /* ── CSV ── */
    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$safeName}_{$date}.csv\"");
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        $headers = [$l1, $l2];
        if ($isTri) $headers[] = $l3;
        $headers = array_merge($headers, ['pronunciation', 'part_of_speech', 'example']);
        fputcsv($out, $headers);

        foreach ($entries as $r) {
            $row = [$r['lang_1'], $r['lang_2']];
            if ($isTri) $row[] = $r['lang_3'] ?? '';
            $row[] = $r['pronunciation'] ?? '';
            $row[] = $r['part_of_speech'] ?? '';
            $row[] = $r['example'] ?? '';
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    /* ── JSON ── */
    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$safeName}_{$date}.json\"");
        header('Cache-Control: no-store');

        echo json_encode([
            'dictionary'   => $dictName,
            'type'         => $entries[0]['type'],
            'languages'    => array_filter([$l1, $l2, $isTri ? $l3 : null]),
            'exported_at'  => date('c'),
            'total'        => count($entries),
            'entries'      => array_map(fn($r) => array_filter([
                'lang_1'         => $r['lang_1'],
                'lang_2'         => $r['lang_2'],
                'lang_3'         => $r['lang_3'] ?? null,
                'pronunciation'  => $r['pronunciation'] ?? null,
                'part_of_speech' => $r['part_of_speech'] ?? null,
                'example'        => $r['example'] ?? null,
            ], fn($v) => $v !== null && $v !== ''), $entries),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /* ── HTML ── */
    if ($format === 'html') {
        header('Content-Type: text/html; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$safeName}_{$date}.html\"");
        header('Cache-Control: no-store');

        $total   = count($entries);
        $dictEnc = htmlspecialchars($dictName, ENT_QUOTES, 'UTF-8');

        echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'>";
        echo "<title>{$dictEnc} — Export {$date}</title><style>";
        echo "body{font-family:Arial,sans-serif;margin:2rem}h1{font-size:1.4rem}";
        echo ".meta{color:#666;font-size:.8rem;margin-bottom:1rem}";
        echo "table{border-collapse:collapse;width:100%;font-size:.875rem}";
        echo "th,td{border:1px solid #ccc;padding:.4rem .6rem;text-align:left}";
        echo "th{background:#e5e7eb}tr:nth-child(even){background:#f9fafb}";
        echo "</style></head><body>";
        echo "<h1>📖 {$dictEnc}</h1>";
        echo "<p class='meta'>Exported: {$date} | Total: {$total}</p><table><thead><tr>";
        echo "<th>" . htmlspecialchars($l1) . "</th>";
        echo "<th>" . htmlspecialchars($l2) . "</th>";
        if ($isTri) echo "<th>" . htmlspecialchars($l3) . "</th>";
        echo "<th>Pronunciation</th><th>POS</th><th>Example</th></tr></thead><tbody>";

        foreach ($entries as $r) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($r['lang_1']) . "</td>";
            echo "<td>" . htmlspecialchars($r['lang_2']) . "</td>";
            if ($isTri) echo "<td>" . htmlspecialchars($r['lang_3'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($r['pronunciation'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($r['part_of_speech'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($r['example'] ?? '') . "</td>";
            echo "</tr>\n";
        }
        echo "</tbody></table></body></html>";
        exit;
    }
}

include '../includes/header.php';
?>
<style>
.export-wrap { max-width: 600px; margin: 6rem auto 2rem; }
.export-wrap h2   { margin-bottom: 1rem; }
.export-wrap label{ display:block; margin:.8rem 0 .2rem; font-weight:bold; }
.export-wrap select { width:100%; padding:.4rem; box-sizing:border-box; }
.btn-row { display:flex; gap:.6rem; flex-wrap:wrap; margin-top:1rem; }
a.ebtn  { padding:.5rem 1.2rem; border-radius:4px; text-decoration:none;
           font-weight:bold; font-size:.9rem; color:#fff; }
.e-csv  { background:#059669; }
.e-json { background:#d97706; }
.e-html { background:#7c3aed; }
a.ebtn:hover { opacity:.85; }
a.ebtn[disabled] { opacity:.35; pointer-events:none; }
.hint { font-size:.8rem; color:#666; margin-top:.3rem; }
.alert { padding:.75rem 1rem; border-radius:4px; margin:.5rem 0;
         background:#fef9c3; border:1px solid #fde047; color:#713f12; }
</style>

<div class="export-wrap">
  <h2>Export Dictionary</h2>

  <?php if (isset($_GET['empty'])): ?>
    <div class="alert">⚠️ That dictionary has no entries yet. Import some data first.</div>
  <?php endif; ?>

  <label for="dict_sel">Select Dictionary</label>
  <select id="dict_sel" onchange="updateLinks()">
    <option value="">— select a dictionary —</option>
    <?php foreach ($dictionaries as $d): ?>
      <option value="<?= (int)$d['dict_id'] ?>">
        <?= htmlspecialchars($d['name']) ?>
        (<?= htmlspecialchars($d['type']) ?>)
      </option>
    <?php endforeach; ?>
  </select>
  <p class="hint">Choose a dictionary, then click the format you want to download.</p>

  <div class="btn-row">
    <a id="btn_csv"  class="ebtn e-csv"  href="#" disabled>⬇ CSV</a>
    <a id="btn_json" class="ebtn e-json" href="#" disabled>⬇ JSON</a>
    <a id="btn_html" class="ebtn e-html" href="#" disabled>⬇ HTML</a>
  </div>

  <p style="margin-top:1.5rem">
    <a href="upload_xlsx.php">← Back to Import</a> &nbsp;|&nbsp;
    <a href="index.php">← Home</a>
  </p>
</div>

<script>
function updateLinks() {
    const id = document.getElementById('dict_sel').value;
    ['csv','json','html'].forEach(fmt => {
        const a = document.getElementById('btn_' + fmt);
        if (id) {
            a.href = 'export.php?dictionary_id=' + id + '&format=' + fmt;
            a.removeAttribute('disabled');
        } else {
            a.href = '#';
            a.setAttribute('disabled', true);
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
=======
<?php
/**
 * public/export.php
 * Export dictionary entries as CSV, JSON, or HTML.
 * Uses professor's schema: dict_id, lang_1, lang_2, lang_3
 */

require_once '../config/database.php';

$dictionaries = [];
try {
    $stmt = $conn->query(
        "SELECT dict_id, name, type, source_lang_1, source_lang_2, source_lang_3
         FROM dictionaries WHERE is_active = 1 ORDER BY name"
    );
    $dictionaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('DB error: ' . htmlspecialchars($e->getMessage()));
}

$format   = $_GET['format']   ?? '';
$dict_id  = filter_input(INPUT_GET, 'dictionary_id', FILTER_VALIDATE_INT);
$doExport = in_array($format, ['csv', 'json', 'html'], true) && $dict_id;

if ($doExport) {
    try {
        $stmt = $conn->prepare(
            "SELECT e.lang_1, e.lang_2, e.lang_3, e.pronunciation,
                    e.part_of_speech, e.example, e.notes,
                    d.name AS dictionary_name, d.type,
                    d.source_lang_1, d.source_lang_2, d.source_lang_3
             FROM   dictionary_entries e
             JOIN   dictionaries d ON d.dict_id = e.dict_id
             WHERE  e.dict_id = ? AND e.is_active = 1
             ORDER  BY e.lang_1"
        );
        $stmt->execute([$dict_id]);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die('DB error: ' . htmlspecialchars($e->getMessage()));
    }

    if (empty($entries)) {
        header('Location: export.php?empty=1'); exit;
    }

    $dictName = $entries[0]['dictionary_name'];
    $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $dictName);
    $date     = date('Ymd');
    $isTri    = $entries[0]['type'] === 'trilingual';
    $l1       = $entries[0]['source_lang_1'];
    $l2       = $entries[0]['source_lang_2'];
    $l3       = $entries[0]['source_lang_3'] ?? 'Lang 3';

    /* ── CSV ── */
    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$safeName}_{$date}.csv\"");
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        $headers = [$l1, $l2];
        if ($isTri) $headers[] = $l3;
        $headers = array_merge($headers, ['pronunciation', 'part_of_speech', 'example']);
        fputcsv($out, $headers);

        foreach ($entries as $r) {
            $row = [$r['lang_1'], $r['lang_2']];
            if ($isTri) $row[] = $r['lang_3'] ?? '';
            $row[] = $r['pronunciation'] ?? '';
            $row[] = $r['part_of_speech'] ?? '';
            $row[] = $r['example'] ?? '';
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    /* ── JSON ── */
    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$safeName}_{$date}.json\"");
        header('Cache-Control: no-store');

        echo json_encode([
            'dictionary'   => $dictName,
            'type'         => $entries[0]['type'],
            'languages'    => array_filter([$l1, $l2, $isTri ? $l3 : null]),
            'exported_at'  => date('c'),
            'total'        => count($entries),
            'entries'      => array_map(fn($r) => array_filter([
                'lang_1'         => $r['lang_1'],
                'lang_2'         => $r['lang_2'],
                'lang_3'         => $r['lang_3'] ?? null,
                'pronunciation'  => $r['pronunciation'] ?? null,
                'part_of_speech' => $r['part_of_speech'] ?? null,
                'example'        => $r['example'] ?? null,
            ], fn($v) => $v !== null && $v !== ''), $entries),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /* ── HTML ── */
    if ($format === 'html') {
        header('Content-Type: text/html; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$safeName}_{$date}.html\"");
        header('Cache-Control: no-store');

        $total   = count($entries);
        $dictEnc = htmlspecialchars($dictName, ENT_QUOTES, 'UTF-8');

        echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'>";
        echo "<title>{$dictEnc} — Export {$date}</title><style>";
        echo "body{font-family:Arial,sans-serif;margin:2rem}h1{font-size:1.4rem}";
        echo ".meta{color:#666;font-size:.8rem;margin-bottom:1rem}";
        echo "table{border-collapse:collapse;width:100%;font-size:.875rem}";
        echo "th,td{border:1px solid #ccc;padding:.4rem .6rem;text-align:left}";
        echo "th{background:#e5e7eb}tr:nth-child(even){background:#f9fafb}";
        echo "</style></head><body>";
        echo "<h1>📖 {$dictEnc}</h1>";
        echo "<p class='meta'>Exported: {$date} | Total: {$total}</p><table><thead><tr>";
        echo "<th>" . htmlspecialchars($l1) . "</th>";
        echo "<th>" . htmlspecialchars($l2) . "</th>";
        if ($isTri) echo "<th>" . htmlspecialchars($l3) . "</th>";
        echo "<th>Pronunciation</th><th>POS</th><th>Example</th></tr></thead><tbody>";

        foreach ($entries as $r) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($r['lang_1']) . "</td>";
            echo "<td>" . htmlspecialchars($r['lang_2']) . "</td>";
            if ($isTri) echo "<td>" . htmlspecialchars($r['lang_3'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($r['pronunciation'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($r['part_of_speech'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($r['example'] ?? '') . "</td>";
            echo "</tr>\n";
        }
        echo "</tbody></table></body></html>";
        exit;
    }
}

include '../includes/header.php';
?>
<style>
.export-wrap { max-width: 600px; margin: 6rem auto 2rem; }
.export-wrap h2   { margin-bottom: 1rem; }
.export-wrap label{ display:block; margin:.8rem 0 .2rem; font-weight:bold; }
.export-wrap select { width:100%; padding:.4rem; box-sizing:border-box; }
.btn-row { display:flex; gap:.6rem; flex-wrap:wrap; margin-top:1rem; }
a.ebtn  { padding:.5rem 1.2rem; border-radius:4px; text-decoration:none;
           font-weight:bold; font-size:.9rem; color:#fff; }
.e-csv  { background:#059669; }
.e-json { background:#d97706; }
.e-html { background:#7c3aed; }
a.ebtn:hover { opacity:.85; }
a.ebtn[disabled] { opacity:.35; pointer-events:none; }
.hint { font-size:.8rem; color:#666; margin-top:.3rem; }
.alert { padding:.75rem 1rem; border-radius:4px; margin:.5rem 0;
         background:#fef9c3; border:1px solid #fde047; color:#713f12; }
</style>

<div class="export-wrap">
  <h2>Export Dictionary</h2>

  <?php if (isset($_GET['empty'])): ?>
    <div class="alert">⚠️ That dictionary has no entries yet. Import some data first.</div>
  <?php endif; ?>

  <label for="dict_sel">Select Dictionary</label>
  <select id="dict_sel" onchange="updateLinks()">
    <option value="">— select a dictionary —</option>
    <?php foreach ($dictionaries as $d): ?>
      <option value="<?= (int)$d['dict_id'] ?>">
        <?= htmlspecialchars($d['name']) ?>
        (<?= htmlspecialchars($d['type']) ?>)
      </option>
    <?php endforeach; ?>
  </select>
  <p class="hint">Choose a dictionary, then click the format you want to download.</p>

  <div class="btn-row">
    <a id="btn_csv"  class="ebtn e-csv"  href="#" disabled>⬇ CSV</a>
    <a id="btn_json" class="ebtn e-json" href="#" disabled>⬇ JSON</a>
    <a id="btn_html" class="ebtn e-html" href="#" disabled>⬇ HTML</a>
  </div>

  <p style="margin-top:1.5rem">
    <a href="upload_xlsx.php">← Back to Import</a> &nbsp;|&nbsp;
    <a href="index.php">← Home</a>
  </p>
</div>

<script>
function updateLinks() {
    const id = document.getElementById('dict_sel').value;
    ['csv','json','html'].forEach(fmt => {
        const a = document.getElementById('btn_' + fmt);
        if (id) {
            a.href = 'export.php?dictionary_id=' + id + '&format=' + fmt;
            a.removeAttribute('disabled');
        } else {
            a.href = '#';
            a.setAttribute('disabled', true);
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
>>>>>>> 50c55f8a008be9bcda28bc86fc01a2fe49e49c16
