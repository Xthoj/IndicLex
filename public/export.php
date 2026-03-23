<?php
/**
 * public/export.php
 * Export dictionary entries as CSV, JSON, or HTML.
 *
 * Place this file in:  IndicLex/public/export.php
 */

require_once '../config/database.php';  // provides $conn (PDO)

/* ─── Load dictionaries ──────────────────────────────────────────── */
$dictionaries = [];
try {
    $stmt = $conn->query("SELECT dictionary_id, name FROM dictionaries ORDER BY name");
    $dictionaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('DB error: ' . htmlspecialchars($e->getMessage()));
}

/* ─── Handle export download request ────────────────────────────── */
$format  = $_GET['format']        ?? '';
$dict_id = filter_input(INPUT_GET, 'dictionary_id', FILTER_VALIDATE_INT);
$doExport = in_array($format, ['csv', 'json', 'html'], true) && $dict_id;

if ($doExport) {

    try {
        $stmt = $conn->prepare(
            "SELECT e.word, e.part_of_speech, e.meaning, e.translation1, e.translation2,
                    d.name AS dictionary_name
             FROM   dictionary_entries e
             JOIN   dictionaries d ON d.dictionary_id = e.dictionary_id
             WHERE  e.dictionary_id = ?
             ORDER  BY e.word"
        );
        $stmt->execute([$dict_id]);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die('DB error: ' . htmlspecialchars($e->getMessage()));
    }

    if (empty($entries)) {
        // No entries — redirect back with a message
        header('Location: export.php?empty=1'); exit;
    }

    $dictName = $entries[0]['dictionary_name'];
    $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $dictName);
    $date     = date('Ymd');

    /* ── CSV ──────────────────────────────────────────────────── */
    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$safeName}_{$date}.csv\"");
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");  // UTF-8 BOM for Excel
        fputcsv($out, ['word', 'part_of_speech', 'meaning', 'translation1', 'translation2']);
        foreach ($entries as $r) {
            fputcsv($out, [$r['word'], $r['part_of_speech'], $r['meaning'], $r['translation1'], $r['translation2']]);
        }
        fclose($out);
        exit;
    }

    /* ── JSON ─────────────────────────────────────────────────── */
    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$safeName}_{$date}.json\"");
        header('Cache-Control: no-store');

        echo json_encode([
            'dictionary'  => $dictName,
            'exported_at' => date('c'),
            'total'       => count($entries),
            'entries'     => array_map(fn($r) => [
                'word'           => $r['word'],
                'part_of_speech' => $r['part_of_speech'],
                'meaning'        => $r['meaning'],
                'translation1'   => $r['translation1'],
                'translation2'   => $r['translation2'],
            ], $entries),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /* ── HTML ─────────────────────────────────────────────────── */
    if ($format === 'html') {
        header('Content-Type: text/html; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$safeName}_{$date}.html\"");
        header('Cache-Control: no-store');

        $total   = count($entries);
        $dictEnc = htmlspecialchars($dictName, ENT_QUOTES, 'UTF-8');
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8">
          <title>{$dictEnc} — Export {$date}</title>
          <style>
            body  { font-family: Arial, sans-serif; margin: 2rem; }
            h1    { font-size: 1.4rem; }
            .meta { color: #666; font-size: .8rem; margin-bottom: 1rem; }
            table { border-collapse: collapse; width: 100%; font-size: .875rem; }
            th,td { border: 1px solid #ccc; padding: .4rem .6rem; text-align: left; }
            th    { background: #e5e7eb; font-weight: bold; }
            tr:nth-child(even) { background: #f9fafb; }
          </style>
        </head>
        <body>
        <h1>📖 {$dictEnc}</h1>
        <p class="meta">Exported: {$date} &nbsp;|&nbsp; Total entries: {$total}</p>
        <table>
          <thead>
            <tr><th>Word</th><th>POS</th><th>Meaning</th><th>Translation 1</th><th>Translation 2</th></tr>
          </thead>
          <tbody>
        HTML;

        foreach ($entries as $r) {
            $w  = htmlspecialchars($r['word'],           ENT_QUOTES, 'UTF-8');
            $p  = htmlspecialchars($r['part_of_speech'], ENT_QUOTES, 'UTF-8');
            $m  = htmlspecialchars($r['meaning'],        ENT_QUOTES, 'UTF-8');
            $t1 = htmlspecialchars($r['translation1'],   ENT_QUOTES, 'UTF-8');
            $t2 = htmlspecialchars($r['translation2'],   ENT_QUOTES, 'UTF-8');
            echo "    <tr><td>$w</td><td>$p</td><td>$m</td><td>$t1</td><td>$t2</td></tr>\n";
        }

        echo "  </tbody>\n</table>\n</body>\n</html>";
        exit;
    }
}

/* ─── Regular page (no download triggered) ───────────────────────── */
include '../includes/header.php';
?>
<style>
.export-wrap { max-width: 600px; margin: 2rem auto; }
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
  <h2>📤 Export Dictionary</h2>

  <?php if (isset($_GET['empty'])): ?>
    <div class="alert">⚠️ That dictionary has no entries yet. Import some data first.</div>
  <?php endif; ?>

  <label for="dict_sel">Select Dictionary</label>
  <select id="dict_sel" onchange="updateLinks()">
    <option value="">— select a dictionary —</option>
    <?php foreach ($dictionaries as $d): ?>
      <option value="<?= (int)$d['dictionary_id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <p class="hint">Choose a dictionary, then click the format you want to download.</p>

  <div class="btn-row">
    <a id="btn_csv"  class="ebtn e-csv"  href="#" disabled>⬇ CSV</a>
    <a id="btn_json" class="ebtn e-json" href="#" disabled>⬇ JSON</a>
    <a id="btn_html" class="ebtn e-html" href="#" disabled>⬇ HTML</a>
  </div>

  <p style="margin-top:1.5rem">
    <a href="upload_csv.php">← Back to Import</a> &nbsp;|&nbsp;
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