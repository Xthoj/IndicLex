<?php
/**
 * public/upload_xlsx.php
 * Bulk dictionary import from Excel (.xlsx) using PhpSpreadsheet.
 * Each sheet in the workbook becomes a separate dictionary.
 * Sheet name is used as the dictionary name.
 *
 * Column order (no header row expected):
 *   A=word, B=part_of_speech, C=meaning, D=translation1, E=translation2
 *
 * Place this file in:  IndicLex/public/upload_xlsx.php
 * Requires:            IndicLex/vendor/ (from: composer require phpoffice/phpspreadsheet)
 */

require_once '../config/database.php';          // provides $conn (PDO)
require_once '../vendor/autoload.php';          // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlDate;

session_start();

/* ─── Config ──────────────────────────────────────────────────────── */
define('MAX_FILE_MB',  20);
define('MAX_WORD_LEN', 255);
define('MAX_POS_LEN',  20);
define('CHUNK_SIZE',   500);

/* ─── Helpers ─────────────────────────────────────────────────────── */
function h(string $s): string     { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function clean(string $s): string { return trim(strip_tags($s)); }

/* ─── Flash ───────────────────────────────────────────────────────── */
$flash = [];
if (isset($_SESSION['import_result'])) {
    $flash = $_SESSION['import_result'];
    unset($_SESSION['import_result']);
}

/* ─── CSRF ────────────────────────────────────────────────────────── */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/* ─── Handle POST ─────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $_SESSION['import_result'] = ['error' => 'Security token mismatch. Please try again.'];
        header('Location: upload_xlsx.php'); exit;
    }

    // ── File upload checks ─────────────────────────────────────────
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds the server upload_max_filesize limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds the form MAX_FILE_SIZE limit.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded. Please try again.',
        UPLOAD_ERR_NO_FILE    => 'No file was selected.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temp folder — contact your host.',
        UPLOAD_ERR_CANT_WRITE => 'Server could not write the file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension blocked the upload.',
    ];

    $fileError = $_FILES['xlsx_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($fileError !== UPLOAD_ERR_OK) {
        $_SESSION['import_result'] = ['error' => $uploadErrors[$fileError] ?? "Upload error (code $fileError)."];
        header('Location: upload_xlsx.php'); exit;
    }

    $tmp    = $_FILES['xlsx_file']['tmp_name'];
    $ext    = strtolower(pathinfo($_FILES['xlsx_file']['name'], PATHINFO_EXTENSION));
    $sizeMB = $_FILES['xlsx_file']['size'] / 1_048_576;

    if (!in_array($ext, ['xlsx', 'xls'], true)) {
        $_SESSION['import_result'] = ['error' => 'Only .xlsx or .xls files are accepted.'];
        header('Location: upload_xlsx.php'); exit;
    }
    if ($sizeMB > MAX_FILE_MB) {
        $_SESSION['import_result'] = ['error' => sprintf('File is %.1f MB — max allowed is %d MB.', $sizeMB, MAX_FILE_MB)];
        header('Location: upload_xlsx.php'); exit;
    }

    // ── Load workbook with PhpSpreadsheet ──────────────────────────
    try {
        $spreadsheet = IOFactory::load($tmp);
    } catch (\Exception $e) {
        $_SESSION['import_result'] = ['error' => 'Could not read Excel file: ' . $e->getMessage()];
        header('Location: upload_xlsx.php'); exit;
    }

    // ── Process each sheet as a separate dictionary ────────────────
    $sheetResults = [];
    $insertStmt   = $conn->prepare(
        "INSERT INTO dictionary_entries
             (dictionary_id, word, part_of_speech, meaning, translation1, translation2)
         VALUES (?,?,?,?,?,?)"
    );

    foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {

        // Dictionary name from sheet name
        $sheetName = trim($worksheet->getTitle());
        $dictName  = ucwords(str_replace(['-', '_'], ' ', $sheetName));

        // Auto-create dictionary if it doesn't exist
        try {
            $conn->prepare(
                "INSERT INTO dictionaries (name) SELECT ? WHERE NOT EXISTS
                 (SELECT 1 FROM dictionaries WHERE name = ?)"
            )->execute([$dictName, $dictName]);

            $s = $conn->prepare("SELECT dictionary_id FROM dictionaries WHERE name = ?");
            $s->execute([$dictName]);
            $dict_id = $s->fetchColumn();
        } catch (PDOException $e) {
            $sheetResults[] = [
                'sheet'  => $sheetName,
                'error'  => 'Could not create dictionary record: ' . $e->getMessage(),
            ];
            continue;
        }

        // Load existing words for duplicate detection
        try {
            $s = $conn->prepare("SELECT LOWER(word) FROM dictionary_entries WHERE dictionary_id = ?");
            $s->execute([$dict_id]);
            $existing = array_flip($s->fetchAll(PDO::FETCH_COLUMN));
        } catch (PDOException $e) {
            $sheetResults[] = [
                'sheet' => $sheetName,
                'error' => 'DB error loading existing entries: ' . $e->getMessage(),
            ];
            continue;
        }

        // ── Row loop ───────────────────────────────────────────────
        $toInsert   = [];
        $rowErrors  = [];
        $duplicates = [];
        $skipped    = 0;
        $highestRow = $worksheet->getHighestRow();

        for ($rowNum = 1; $rowNum <= $highestRow; $rowNum++) {

            // Read cells A–E by position
            $word = clean((string)($worksheet->getCell('A' . $rowNum)->getValue() ?? ''));
            $pos  = clean((string)($worksheet->getCell('B' . $rowNum)->getValue() ?? ''));
            $mean = clean((string)($worksheet->getCell('C' . $rowNum)->getValue() ?? ''));
            $tr1  = clean((string)($worksheet->getCell('D' . $rowNum)->getValue() ?? ''));
            $tr2  = clean((string)($worksheet->getCell('E' . $rowNum)->getValue() ?? ''));

            // Skip fully blank rows
            if ($word === '' && $pos === '' && $mean === '') { $skipped++; continue; }

            // Validate
            $errs = [];
            if ($word === '')                     $errs[] = 'word is empty';
            if (mb_strlen($word) > MAX_WORD_LEN)  $errs[] = 'word exceeds ' . MAX_WORD_LEN . ' chars';
            if (mb_strlen($pos)  > MAX_POS_LEN)   $errs[] = 'part_of_speech exceeds ' . MAX_POS_LEN . ' chars';

            if ($errs) {
                $rowErrors[] = "Row $rowNum: " . implode('; ', $errs) . " (word=\"$word\")";
                continue;
            }

            // Duplicate check
            $key = mb_strtolower($word);
            if (isset($existing[$key])) {
                $duplicates[] = "Row $rowNum: \"$word\" already exists — skipped.";
                $skipped++;
                continue;
            }
            $existing[$key] = true;

            $toInsert[] = [$dict_id, $word, $pos, $mean, $tr1, $tr2];
        }

        // ── Bulk insert ────────────────────────────────────────────
        $inserted = 0;
        if ($toInsert) {
            try {
                foreach (array_chunk($toInsert, CHUNK_SIZE) as $chunk) {
                    $conn->beginTransaction();
                    foreach ($chunk as $vals) $insertStmt->execute($vals);
                    $conn->commit();
                    $inserted += count($chunk);
                }
            } catch (PDOException $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                $sheetResults[] = [
                    'sheet'      => $sheetName,
                    'dict_name'  => $dictName,
                    'error'      => 'Insert failed: ' . $e->getMessage(),
                    'row_errors' => $rowErrors,
                    'duplicates' => $duplicates,
                ];
                continue;
            }
        }

        $sheetResults[] = [
            'sheet'      => $sheetName,
            'dict_name'  => $dictName,
            'inserted'   => $inserted,
            'skipped'    => $skipped,
            'row_errors' => $rowErrors,
            'duplicates' => $duplicates,
        ];
    }

    // Free memory
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);

    $_SESSION['import_result'] = ['success' => true, 'sheets' => $sheetResults];
    header('Location: upload_xlsx.php'); exit;
}

/* ─── HTML ────────────────────────────────────────────────────────── */
include '../includes/header.php';
?>
<style>
.import-wrap { max-width: 700px; margin: 2rem auto; }
.import-wrap h2 { margin-bottom: 1rem; }
.import-wrap label { display: block; margin: .8rem 0 .2rem; font-weight: bold; }
.import-wrap input[type=file] { width: 100%; padding: .4rem; box-sizing: border-box; }
.import-wrap button {
    margin-top: 1rem; padding: .5rem 1.4rem; background: #2563eb;
    color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem;
}
.import-wrap button:hover { background: #1d4ed8; }
.alert  { padding: .75rem 1rem; border-radius: 4px; margin: .5rem 0; }
.a-ok   { background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; }
.a-err  { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
.a-warn { background: #fef9c3; border: 1px solid #fde047; color: #713f12; }
.sheet-result { border: 1px solid #e5e7eb; border-radius: 6px; padding: .75rem 1rem; margin: .5rem 0; }
.sheet-result h3 { margin: 0 0 .4rem; font-size: 1rem; }
details { margin-top: .4rem; }
summary { cursor: pointer; font-weight: bold; font-size: .875rem; }
details ul { font-size: .85rem; margin: .3rem 0; padding-left: 1.2rem; }
.hint { font-size: .8rem; color: #666; margin-top: .3rem; }
</style>

<div class="import-wrap">
  <h2>📥 Bulk Dictionary Import (Excel)</h2>

  <?php if (!empty($flash['error'])): ?>
    <div class="alert a-err">❌ <?= h($flash['error']) ?></div>
  <?php endif; ?>

  <?php if (!empty($flash['success'])): ?>
    <div class="alert a-ok">
      ✅ Import complete — <?= count($flash['sheets']) ?> sheet(s) processed.
    </div>

    <?php foreach ($flash['sheets'] as $r): ?>
      <div class="sheet-result">
        <h3>📄 <?= h($r['sheet']) ?> → <em><?= h($r['dict_name'] ?? $r['sheet']) ?></em></h3>

        <?php if (!empty($r['error'])): ?>
          <div class="alert a-err" style="margin:.3rem 0">❌ <?= h($r['error']) ?></div>
        <?php else: ?>
          <div class="alert a-ok" style="margin:.3rem 0">
            ✅ <strong><?= (int)$r['inserted'] ?></strong> rows inserted,
            <strong><?= (int)$r['skipped'] ?></strong> skipped.
          </div>
        <?php endif; ?>

        <?php if (!empty($r['row_errors'])): ?>
          <div class="alert a-err" style="margin:.3rem 0">
            ⚠️ <?= count($r['row_errors']) ?> row(s) failed validation.
            <details><summary>Show errors</summary>
              <ul><?php foreach ($r['row_errors'] as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
            </details>
          </div>
        <?php endif; ?>

        <?php if (!empty($r['duplicates'])): ?>
          <div class="alert a-warn" style="margin:.3rem 0">
            ℹ️ <?= count($r['duplicates']) ?> duplicate(s) skipped.
            <details><summary>Show duplicates</summary>
              <ul><?php foreach ($r['duplicates'] as $d): ?><li><?= h($d) ?></li><?php endforeach; ?></ul>
            </details>
          </div>
        <?php endif; ?>

      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token"    value="<?= h($csrfToken) ?>">
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= MAX_FILE_MB * 1048576 ?>">

    <label for="xlsx_file">Excel File (.xlsx)</label>
    <input type="file" name="xlsx_file" id="xlsx_file" accept=".xlsx,.xls" required>
    <p class="hint">
      Each sheet in the workbook is imported as a separate dictionary.<br>
      The sheet name becomes the dictionary name automatically.<br>
      Column order: <code>A=word &nbsp; B=part of speech &nbsp; C=meaning &nbsp; D=translation1 &nbsp; E=translation2</code><br>
      Max <?= MAX_FILE_MB ?> MB &middot; No header row needed.
    </p>

    <button type="submit">Upload &amp; Import</button>
  </form>

  <p style="margin-top: 1.5rem">
    <a href="export.php">→ Export dictionary data</a> &nbsp;|&nbsp;
    <a href="index.php">← Home</a>
  </p>
</div>

<?php include '../includes/footer.php'; ?>