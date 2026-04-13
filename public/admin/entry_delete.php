<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: manage_dictionaries.php?error=Invalid dictionary ID');
    exit;
}

try {
    // Optional: delete related entries first if foreign key cascade is not enabled
    $deleteEntries = $pdo->prepare("DELETE FROM dictionary_entries WHERE dictionary_id = :dictionary_id");
    $deleteEntries->execute([':dictionary_id' => $id]);

    $deleteDictionary = $pdo->prepare("DELETE FROM dictionaries WHERE id = :id");
    $deleteDictionary->execute([':id' => $id]);

    header('Location: manage_dictionaries.php?success=Dictionary deleted successfully');
    exit;
} catch (PDOException $e) {
    header('Location: manage_dictionaries.php?error=Failed to delete dictionary');
    exit;
}
?>