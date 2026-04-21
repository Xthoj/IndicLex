<?php
require_once '../../includes/admin_auth.php';
require_once '../../config/database.php';

requireAdmin();

$id      = (int)($_GET['id']      ?? 0);
$dict_id = (int)($_GET['dict_id'] ?? 0);

if ($id <= 0 || $dict_id <= 0) {
    header('Location: manage_dictionaries.php?error=Invalid entry ID');
    exit;
}

$stmt = $conn->prepare("DELETE FROM dictionary_entries WHERE entry_id = ?");
$stmt->execute([$id]);

header('Location: manage_entries.php?dict_id=' . $dict_id . '&success=Entry deleted successfully');
exit;
?>
