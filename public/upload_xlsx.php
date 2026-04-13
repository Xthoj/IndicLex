
<?php
/**
 * public/upload_xlsx.php
 * Bulk dictionary import from Excel (.xlsx) using PhpSpreadsheet.
 * Each sheet becomes a separate dictionary entry.
 * Sheet name is used as the dictionary name.
 *
 * Column order (no header row expected):
 *   A=lang_1, B=lang_2, C=lang_3, D=pronunciation, E=part_of_speech, F=example
 *
 * Requires: IndicLex/vendor/ (composer require phpoffice/phpspreadsheet)
 */

require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

session_start();

define('MAX_FILE_MB',  20);
define('MAX_LANG_LEN', 255);
define('CHUNK_SIZE',   500);

function h(string $s): string     { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function clean(string $s): string { return trim(strip_tags($s)); }

$flash = [];
if (isset($_SESSION['import_result'])) {
    $flash = $_SESSION['import_result'];
    unset($_SESSION['import_result']);
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $_SESSION['import_result'] = ['error' => 'Security token mismatch. Please try again.'];
        header('Location: upload_xlsx.php'); exit;
    }

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

    try {
        $spreadsheet = IOFactory::load($tmp);
    } catch (\Exception $e) {
        $_SESSION['import_result'] = ['error' => 'Could not read Excel file: ' . $e->getMessage()];
        header('Location: upload_xlsx.php'); exit;
    }

    $sheetResults = [];

    $insertStmt = $conn->prepare(
        "INSERT INTO dictionary_entries
             (dict_id, lang_1, lang_2, lang_3, pronunciation, part_of_speech, example)
         VALUES (?,?,?,?,?,?,?)"
    );

    foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {

        $sheetName = trim($worksheet->getTitle());

        // Derive dict_identifier from sheet name: "Telugu -Telugu" → "telugu-telugu"
        $dictIdentifier = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $sheetName));
        $dictIdentifier = trim($dictIdentifier, '-');

        // Determine if trilingual based on sheet name containing 3 languages
        $parts = preg_split('/[\s\-–]+/', $sheetName);
        $isTrilingual = count(array_filter($parts)) >= 3;
        $type = $isTrilingual ? 'trilingual' : 'bilingual';

        // Auto-create dictionary if it doesn't exist
        try {
            $conn->prepare(
                "INSERT INTO dictionaries (dict_identifier, name, type, source_lang_1, source_lang_2, source_lang_3)
                 SELECT ?, ?, ?, ?, ?, ?
                 WHERE NOT EXISTS (SELECT 1 FROM dictionaries WHERE dict_identifier = ?)"
            )->execute([
                $dictIdentifier,
                $sheetName,
                $type,
                $parts[0] ?? $sheetName,
                $parts[1] ?? '',
                $isTrilingual ? ($parts[2] ?? null) : null,
                $dictIdentifier
            ]);

            $s = $conn->prepare("SELECT dict_id FROM dictionaries WHERE dict_identifier = ?");
            $s->execute([$dictIdentifier]);
            $dict_id = $s->fetchColumn();
        } catch (PDOException $e) {
            $sheetResults[] = [
                'sheet' => $sheetName,
                'error' => 'Could not create dictionary record: ' . $e->getMessage(),
            ];
            continue;
        }

        // Load existing lang_1 entries for duplicate detection
        try {
            $s = $conn->prepare("SELECT LOWER(lang_1) FROM dictionary_entries WHERE dict_id = ?");
            $s->execute([$dict_id]);
            $existing = array_flip($s->fetchAll(PDO::FETCH_COLUMN));
        } catch (PDOException $e) {
            $sheetResults[] = [
                'sheet' => $sheetName,
                'error' => 'DB error loading existing entries: ' . $e->getMessage(),
            ];
            continue;
        }

        $toInsert   = [];
        $rowErrors  = [];
        $duplicates = [];
        $skipped    = 0;
        $highestRow = $worksheet->getHighestRow();

        for ($rowNum = 1; $rowNum <= $highestRow; $rowNum++) {
            $lang1 = clean((string)($worksheet->getCell('A' . $rowNum)->getValue() ?? ''));
            $lang2 = clean((string)($worksheet->getCell('B' . $rowNum)->getValue() ?? ''));
            $lang3 = clean((string)($worksheet->getCell('C' . $rowNum)->getValue() ?? ''));
            $pron  = clean((string)($worksheet->getCell('D' . $rowNum)->getValue() ?? ''));
            $pos   = clean((string)($worksheet->getCell('E' . $rowNum)->getValue() ?? ''));
            $ex    = clean((string)($worksheet->getCell('F' . $rowNum)->getValue() ?? ''));

            // Skip blank rows
            if ($lang1 === '' && $lang2 === '') { $skipped++; continue; }

            $errs = [];
            if ($lang1 === '')                      $errs[] = 'lang_1 is empty';
            if ($lang2 === '')                      $errs[] = 'lang_2 is empty';
            if (mb_strlen($lang1) > MAX_LANG_LEN)   $errs[] = 'lang_1 too long';
            if (mb_strlen($lang2) > MAX_LANG_LEN)   $errs[] = 'lang_2 too long';

            if ($errs) {
                $rowErrors[] = "Row $rowNum: " . implode('; ', $errs) . " (lang_1=\"$lang1\")";
                continue;
            }

            $key = mb_strtolower($lang1);
            if (isset($existing[$key])) {
                $duplicates[] = "Row $rowNum: \"$lang1\" already exists — skipped.";
                $skipped++;
                continue;
            }
            $existing[$key] = true;

            $toInsert[] = [
                $dict_id,
                $lang1,
                $lang2,
                $lang3 !== '' ? $lang3 : null,
                $pron  !== '' ? $pron  : null,
                $pos   !== '' ? $pos   : null,
                $ex    !== '' ? $ex    : null,
            ];
        }

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
                    'dict_name'  => $sheetName,
                    'error'      => 'Insert failed: ' . $e->getMessage(),
                    'row_errors' => $rowErrors,
                    'duplicates' => $duplicates,
                ];
                continue;
            }
        }

        $sheetResults[] = [
            'sheet'      => $sheetName,
            'dict_name'  => $sheetName,
            'inserted'   => $inserted,
            'skipped'    => $skipped,
            'row_errors' => $rowErrors,
            'duplicates' => $duplicates,
        ];
    }

    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);

    $_SESSION['import_result'] = ['success' => true, 'sheets' => $sheetResults];
    header('Location: upload_xlsx.php'); exit;
}

include '../includes/header.php';
?>
<style>
.import-wrap { max-width: 700px; margin: 6rem auto 2rem; }
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
  <h2>Bulk Dictionary Import (Excel)</h2>

  <?php if (!empty($flash['error'])): ?>
    <div class="alert a-err">❌ <?= h($flash['error']) ?></div>
  <?php endif; ?>

  <?php if (!empty($flash['success'])): ?>
    <div class="alert a-ok">
      ✅ Import complete — <?= count($flash['sheets']) ?> sheet(s) processed.
    </div>

    <?php foreach ($flash['sheets'] as $r): ?>
      <div class="sheet-result">
        <h3>📄 <?= h($r['sheet']) ?></h3>

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
      Each sheet is imported as a separate dictionary using the sheet name.<br>
      Column order: <code>A=lang_1 &nbsp; B=lang_2 &nbsp; C=lang_3 &nbsp; D=pronunciation &nbsp; E=part_of_speech &nbsp; F=example</code><br>
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
=======
<?php
/**
 * public/upload_xlsx.php
 * Bulk dictionary import from Excel (.xlsx) using PhpSpreadsheet.
 * Each sheet becomes a separate dictionary entry.
 * Sheet name is used as the dictionary name.
 *
 * Column order (no header row expected):
 *   A=lang_1, B=lang_2, C=lang_3, D=pronunciation, E=part_of_speech, F=example
 *
 * Requires: IndicLex/vendor/ (composer require phpoffice/phpspreadsheet)
 */

require_once '../config/database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

session_start();

define('MAX_FILE_MB',  20);
define('MAX_LANG_LEN', 255);
define('CHUNK_SIZE',   500);

function h(string $s): string     { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function clean(string $s): string { return trim(strip_tags($s)); }

$flash = [];
if (isset($_SESSION['import_result'])) {
    $flash = $_SESSION['import_result'];
    unset($_SESSION['import_result']);
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $_SESSION['import_result'] = ['error' => 'Security token mismatch. Please try again.'];
        header('Location: upload_xlsx.php'); exit;
    }

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

    try {
        $spreadsheet = IOFactory::load($tmp);
    } catch (\Exception $e) {
        $_SESSION['import_result'] = ['error' => 'Could not read Excel file: ' . $e->getMessage()];
        header('Location: upload_xlsx.php'); exit;
    }

    $sheetResults = [];

    $insertStmt = $conn->prepare(
        "INSERT INTO dictionary_entries
             (dict_id, lang_1, lang_2, lang_3, pronunciation, part_of_speech, example)
         VALUES (?,?,?,?,?,?,?)"
    );

    foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {

        $sheetName = trim($worksheet->getTitle());

        // Derive dict_identifier from sheet name: "Telugu -Telugu" → "telugu-telugu"
        $dictIdentifier = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $sheetName));
        $dictIdentifier = trim($dictIdentifier, '-');

        // Determine if trilingual based on sheet name containing 3 languages
        $parts = preg_split('/[\s\-–]+/', $sheetName);
        $isTrilingual = count(array_filter($parts)) >= 3;
        $type = $isTrilingual ? 'trilingual' : 'bilingual';

        // Auto-create dictionary if it doesn't exist
        try {
            $conn->prepare(
                "INSERT INTO dictionaries (dict_identifier, name, type, source_lang_1, source_lang_2, source_lang_3)
                 SELECT ?, ?, ?, ?, ?, ?
                 WHERE NOT EXISTS (SELECT 1 FROM dictionaries WHERE dict_identifier = ?)"
            )->execute([
                $dictIdentifier,
                $sheetName,
                $type,
                $parts[0] ?? $sheetName,
                $parts[1] ?? '',
                $isTrilingual ? ($parts[2] ?? null) : null,
                $dictIdentifier
            ]);

            $s = $conn->prepare("SELECT dict_id FROM dictionaries WHERE dict_identifier = ?");
            $s->execute([$dictIdentifier]);
            $dict_id = $s->fetchColumn();
        } catch (PDOException $e) {
            $sheetResults[] = [
                'sheet' => $sheetName,
                'error' => 'Could not create dictionary record: ' . $e->getMessage(),
            ];
            continue;
        }

        // Load existing lang_1 entries for duplicate detection
        try {
            $s = $conn->prepare("SELECT LOWER(lang_1) FROM dictionary_entries WHERE dict_id = ?");
            $s->execute([$dict_id]);
            $existing = array_flip($s->fetchAll(PDO::FETCH_COLUMN));
        } catch (PDOException $e) {
            $sheetResults[] = [
                'sheet' => $sheetName,
                'error' => 'DB error loading existing entries: ' . $e->getMessage(),
            ];
            continue;
        }

        $toInsert   = [];
        $rowErrors  = [];
        $duplicates = [];
        $skipped    = 0;
        $highestRow = $worksheet->getHighestRow();

        for ($rowNum = 1; $rowNum <= $highestRow; $rowNum++) {
            $lang1 = clean((string)($worksheet->getCell('A' . $rowNum)->getValue() ?? ''));
            $lang2 = clean((string)($worksheet->getCell('B' . $rowNum)->getValue() ?? ''));
            $lang3 = clean((string)($worksheet->getCell('C' . $rowNum)->getValue() ?? ''));
            $pron  = clean((string)($worksheet->getCell('D' . $rowNum)->getValue() ?? ''));
            $pos   = clean((string)($worksheet->getCell('E' . $rowNum)->getValue() ?? ''));
            $ex    = clean((string)($worksheet->getCell('F' . $rowNum)->getValue() ?? ''));

            // Skip blank rows
            if ($lang1 === '' && $lang2 === '') { $skipped++; continue; }

            $errs = [];
            if ($lang1 === '')                      $errs[] = 'lang_1 is empty';
            if ($lang2 === '')                      $errs[] = 'lang_2 is empty';
            if (mb_strlen($lang1) > MAX_LANG_LEN)   $errs[] = 'lang_1 too long';
            if (mb_strlen($lang2) > MAX_LANG_LEN)   $errs[] = 'lang_2 too long';

            if ($errs) {
                $rowErrors[] = "Row $rowNum: " . implode('; ', $errs) . " (lang_1=\"$lang1\")";
                continue;
            }

            $key = mb_strtolower($lang1);
            if (isset($existing[$key])) {
                $duplicates[] = "Row $rowNum: \"$lang1\" already exists — skipped.";
                $skipped++;
                continue;
            }
            $existing[$key] = true;

            $toInsert[] = [
                $dict_id,
                $lang1,
                $lang2,
                $lang3 !== '' ? $lang3 : null,
                $pron  !== '' ? $pron  : null,
                $pos   !== '' ? $pos   : null,
                $ex    !== '' ? $ex    : null,
            ];
        }

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
                    'dict_name'  => $sheetName,
                    'error'      => 'Insert failed: ' . $e->getMessage(),
                    'row_errors' => $rowErrors,
                    'duplicates' => $duplicates,
                ];
                continue;
            }
        }

        $sheetResults[] = [
            'sheet'      => $sheetName,
            'dict_name'  => $sheetName,
            'inserted'   => $inserted,
            'skipped'    => $skipped,
            'row_errors' => $rowErrors,
            'duplicates' => $duplicates,
        ];
    }

    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);

    $_SESSION['import_result'] = ['success' => true, 'sheets' => $sheetResults];
    header('Location: upload_xlsx.php'); exit;
}

include '../includes/header.php';
?>
<style>
.import-wrap { max-width: 700px; margin: 6rem auto 2rem; }
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
  <h2>Bulk Dictionary Import (Excel)</h2>

  <?php if (!empty($flash['error'])): ?>
    <div class="alert a-err">❌ <?= h($flash['error']) ?></div>
  <?php endif; ?>

  <?php if (!empty($flash['success'])): ?>
    <div class="alert a-ok">
      ✅ Import complete — <?= count($flash['sheets']) ?> sheet(s) processed.
    </div>

    <?php foreach ($flash['sheets'] as $r): ?>
      <div class="sheet-result">
        <h3>📄 <?= h($r['sheet']) ?></h3>

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
      Each sheet is imported as a separate dictionary using the sheet name.<br>
      Column order: <code>A=lang_1 &nbsp; B=lang_2 &nbsp; C=lang_3 &nbsp; D=pronunciation &nbsp; E=part_of_speech &nbsp; F=example</code><br>
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
