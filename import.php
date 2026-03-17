<?php
session_start();
require_once 'config/database.php';
require_once 'classes/DictionaryImporter.php';

$importer      = new DictionaryImporter($conn);
$stage         = 'upload';
$import_result = null;
$error_msg     = null;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_FILES['import_file']['tmp_name'])) {
        $error_msg = 'Please select a file.';
    } elseif ($_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        $error_msg = 'Upload error (code ' . $_FILES['import_file']['error'] . ').';
    } elseif (strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
        $error_msg = 'Only .xlsx files are supported.';
    } else {
        $tmp_path = sys_get_temp_dir() . '/indiclex_import_' . session_id() . '.xlsx';
        move_uploaded_file($_FILES['import_file']['tmp_name'], $tmp_path);
        $parse_results = $importer->parse($tmp_path);
        @unlink($tmp_path);
        $import_result = $importer->insert($parse_results);
        $import_result['parse_results'] = $parse_results;
        $stage = 'done';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Import Entries — IndicLex</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<div style="max-width:960px; margin:2rem auto; padding:0 1.5rem;">

    <h2>Import Entries</h2>

    <?php if ($error_msg): ?>
        <p style="color:red;"><?= htmlspecialchars($error_msg) ?></p>
    <?php endif; ?>

    <?php if ($stage === 'upload'): ?>

        <p>Upload an Excel (.xlsx) file.</p>

        <form method="POST" enctype="multipart/form-data" style="margin-top:1.5rem;">
            <input type="file" name="import_file" accept=".xlsx" required>
            <button type="submit">Execute</button>
        </form>

    <?php elseif ($stage === 'done' && $import_result !== null): ?>

        <p><strong><?= $import_result['inserted'] ?> row(s) successfully imported.</strong></p>

        <?php foreach ($import_result['parse_results'] as $sheet): ?>
            <p>
                <strong><?= htmlspecialchars($sheet['sheet_name']) ?>:</strong>
                <?= count($sheet['valid_rows']) ?> imported
                <?php if (!empty($sheet['errors'])): ?>
                    | <?= count($sheet['errors']) ?> error(s)
                <?php endif; ?>
                <?php if ($sheet['dict_created']): ?>
                    <span style="color:green;">(new dictionary created)</span>
                <?php endif; ?>
            </p>
        <?php endforeach; ?>

        <?php if (!empty($import_result['failed'])): ?>
            <p style="color:red;">The following rows failed to insert:</p>
            <table border="1" cellpadding="6" cellspacing="0">
                <thead>
                    <tr><th>Row</th><th>lang_1</th><th>Error</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($import_result['failed'] as $f): ?>
                        <tr>
                            <td><?= $f['row'] ?></td>
                            <td><?= htmlspecialchars($f['lang_1']) ?></td>
                            <td><?= htmlspecialchars($f['message']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <p style="margin-top:1.5rem;">
            <a href="import.php">Import another file</a> |
            <a href="catalog.php">View Catalog</a>
        </p>

    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>