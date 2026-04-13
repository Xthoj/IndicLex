<?php
require_once '../../includes/admin_auth.php';
require_once '../../config/database.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_dictionaries.php');
    exit;
}

$dict_id       = (int)($_POST['dict_id'] ?? 0);
$lang_1        = trim($_POST['lang_1'] ?? '');
$lang_2        = trim($_POST['lang_2'] ?? '');
$lang_3        = trim($_POST['lang_3'] ?? '');
$pronunciation = trim($_POST['pronunciation'] ?? '');
$part_of_speech = trim($_POST['part_of_speech'] ?? '');
$example       = trim($_POST['example'] ?? '');
$notes         = trim($_POST['notes'] ?? '');
$is_active     = isset($_POST['is_active']) ? 1 : 0;

if ($dict_id <= 0 || $lang_1 === '') {
    header('Location: manage_entries.php?dict_id=' . $dict_id . '&error=Language 1 is required');
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO dictionary_entries
        (dict_id, lang_1, lang_2, lang_3, pronunciation, part_of_speech, example, notes, is_active)
    VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $dict_id,
    $lang_1,
    $lang_2,
    $lang_3,
    $pronunciation,
    $part_of_speech,
    $example,
    $notes,
    $is_active,
]);

header('Location: manage_entries.php?dict_id=' . $dict_id . '&success=Entry created successfully');
exit;
?>
