<?php
require_once '../../includes/admin_auth.php';
require_once '../../config/database.php';

requireAdmin();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: manage_dictionaries.php?error=Invalid dictionary ID');
    exit;
}

try {
    $conn->beginTransaction();

    $stmt1 = $conn->prepare("
        DELETE FROM dictionary_entries
        WHERE dict_id = ?
    ");
    $stmt1->execute([$id]);

    $stmt2 = $conn->prepare("
        DELETE FROM dictionaries
        WHERE dict_id = ?
    ");
    $stmt2->execute([$id]);

    $conn->commit();

    header('Location: manage_dictionaries.php?success=Dictionary deleted successfully');
    exit;

} catch (Exception $e) {
    $conn->rollBack();

    header('Location: manage_dictionaries.php?error=Failed to delete dictionary');
    exit;
}